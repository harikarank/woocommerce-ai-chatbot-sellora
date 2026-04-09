<?php
/**
 * MCP-style tool registry and executor.
 *
 * Defines tool schemas (passed to AI providers as function/tool definitions)
 * and executes tool calls server-side with sanitisation, validation, and
 * transient caching. No product data is injected into prompts directly —
 * the AI fetches only what it needs via tool calls.
 *
 * Tools:
 *   - get_products          Search catalog by keyword.
 *   - get_product_details   Full detail for one product by ID.
 *   - get_related_products  Upsell / cross-sell IDs for a product.
 *   - get_user_context      Personalisation: viewed products, searches, cart.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class MCP_Tools {

	/** Transient TTL for cached tool results (seconds). */
	const CACHE_TTL = 120;

	/** Max tool invocations per request (guards against runaway loops). */
	const MAX_CALLS_PER_TOOL = 3;

	/** @var Settings */
	private $settings;

	/** @var Catalog_Service */
	private $catalog_service;

	/**
	 * Per-request context passed from Chat_Service
	 * (sanitised pageContext from the frontend).
	 *
	 * @var array
	 */
	private $request_context = array();

	/**
	 * In-memory call counter to cap repeated tool invocations within one
	 * HTTP request without touching any persistent state.
	 *
	 * @var array<string, int>
	 */
	private $call_counts = array();

	/**
	 * Products fetched by get_products during this request.
	 * Chat_Service can read these after the tool loop to build product cards.
	 *
	 * @var array[]
	 */
	private $fetched_products = array();

	public function __construct( Settings $settings, Catalog_Service $catalog_service ) {
		$this->settings        = $settings;
		$this->catalog_service = $catalog_service;
	}

	// -------------------------------------------------------------------------
	// Context
	// -------------------------------------------------------------------------

	/**
	 * Inject the sanitised page/user context for this request.
	 * Must be called before any tool execution.
	 *
	 * @param array $context Sanitised output of Chat_Service::sanitize_page_context().
	 */
	public function set_request_context( array $context ): void {
		$this->request_context = $context;
		// Reset per-request state.
		$this->call_counts      = array();
		$this->fetched_products = array();
	}

	/**
	 * Products fetched by get_products during this request (for product cards).
	 *
	 * @return array[]
	 */
	public function get_fetched_products(): array {
		return $this->fetched_products;
	}

	// -------------------------------------------------------------------------
	// Tool definitions — the JSON Schema sent to AI providers
	// -------------------------------------------------------------------------

	/**
	 * Return tool definitions understood by all three providers.
	 * Each provider converts this canonical format to its native schema.
	 *
	 * @return array[]
	 */
	public function get_tool_definitions(): array {
		$max = max( 1, min( 10, (int) $this->settings->get( 'mcp_max_products' ) ) );

		$tools = array(
			array(
				'name'        => 'get_products',
				'description' => 'Search the WooCommerce product catalog and return matching products. '
					. 'Use this when the user is looking for a type of product, asking for '
					. 'recommendations, or comparing options. Always call this before recommending products.',
				'parameters'  => array(
					'type'       => 'object',
					'properties' => array(
						'query' => array(
							'type'        => 'string',
							'description' => 'Search query derived from what the user is looking for.',
						),
						'limit' => array(
							'type'        => 'integer',
							/* translators: %d = maximum products per tool call */
							'description' => sprintf( 'Maximum results to return (1–%d).', $max ),
							'minimum'     => 1,
							'maximum'     => $max,
						),
					),
					'required' => array( 'query' ),
				),
			),
			array(
				'name'        => 'get_product_details',
				'description' => 'Get full details for a specific product by its WooCommerce ID. '
					. 'Use this when the user asks about a product that has already been identified '
					. '(e.g., they want size, material, or description details).',
				'parameters'  => array(
					'type'       => 'object',
					'properties' => array(
						'product_id' => array(
							'type'        => 'integer',
							'description' => 'The WooCommerce product ID.',
							'minimum'     => 1,
						),
					),
					'required' => array( 'product_id' ),
				),
			),
		);

		if ( 'yes' === $this->settings->get( 'enable_upsell' ) ) {
			$tools[] = array(
				'name'        => 'get_related_products',
				'description' => 'Get upsell and cross-sell suggestions for a specific product. '
					. 'Use this when the user is viewing or asking about a product and might benefit '
					. 'from "you may also like" or "customers also bought" suggestions.',
				'parameters'  => array(
					'type'       => 'object',
					'properties' => array(
						'product_id' => array(
							'type'        => 'integer',
							'description' => 'The WooCommerce product ID to find related products for.',
							'minimum'     => 1,
						),
					),
					'required' => array( 'product_id' ),
				),
			);
		}

		if ( 'yes' === $this->settings->get( 'enable_personalization' ) ) {
			$tools[] = array(
				'name'        => 'get_user_context',
				'description' => 'Get personalisation context for the current visitor: recently viewed '
					. 'products, recent searches, and current cart contents. '
					. 'Use this at the start of a session to tailor recommendations.',
				'parameters'  => array(
					'type'       => 'object',
					'properties' => array(
						'session_id' => array(
							'type'        => 'string',
							'description' => 'The visitor session ID (optional).',
						),
					),
					'required' => array(),
				),
			);
		}

		return $tools;
	}

	// -------------------------------------------------------------------------
	// Tool executor
	// -------------------------------------------------------------------------

	/**
	 * Dispatch a tool call by name and return the result array.
	 * Handles per-tool call-count capping and unknown-tool errors.
	 *
	 * @param string $tool_name Tool name as declared in get_tool_definitions().
	 * @param array  $args      Decoded JSON arguments from the AI.
	 * @return array Result (will be JSON-encoded and sent back to the AI).
	 */
	public function execute( string $tool_name, array $args ): array {
		// Enforce per-tool call cap within this HTTP request.
		$this->call_counts[ $tool_name ] = ( $this->call_counts[ $tool_name ] ?? 0 ) + 1;
		if ( $this->call_counts[ $tool_name ] > self::MAX_CALLS_PER_TOOL ) {
			return array( 'error' => 'Tool call limit reached for this request.' );
		}

		switch ( $tool_name ) {
			case 'get_products':
				return $this->tool_get_products( $args );
			case 'get_product_details':
				return $this->tool_get_product_details( $args );
			case 'get_related_products':
				return $this->tool_get_related_products( $args );
			case 'get_user_context':
				return $this->tool_get_user_context( $args );
			default:
				return array( 'error' => 'Unknown tool: ' . sanitize_key( $tool_name ) );
		}
	}

	// -------------------------------------------------------------------------
	// Tool implementations
	// -------------------------------------------------------------------------

	/**
	 * Search the catalog.
	 *
	 * @param array $args ['query' => string, 'limit' => int]
	 * @return array
	 */
	private function tool_get_products( array $args ): array {
		$query = sanitize_text_field( (string) ( $args['query'] ?? '' ) );
		$max   = max( 1, min( 10, (int) $this->settings->get( 'mcp_max_products' ) ) );
		$limit = isset( $args['limit'] ) ? max( 1, min( $max, (int) $args['limit'] ) ) : $max;

		if ( '' === $query ) {
			return array( 'error' => 'query is required.' );
		}

		$cache_key = 'aiwoo_tool_products_' . md5( $query . '|' . $limit );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$current_product_id = ! empty( $this->request_context['product']['id'] )
			? absint( $this->request_context['product']['id'] )
			: 0;

		$raw_products = $this->catalog_service->find_relevant_products( $query, $current_product_id, $limit );

		// Accumulate for optional product-card rendering by Chat_Service.
		$this->fetched_products = array_values(
			array_unique( array_merge( $this->fetched_products, $raw_products ), SORT_REGULAR )
		);

		// Return a slim subset so we don't bloat the AI context.
		// Omit stock_status for in-stock items (default) to save tokens.
		$products = array_map(
			static function ( array $p ): array {
				$slim = array(
					'id'    => (int) $p['id'],
					'name'  => (string) $p['name'],
					'price' => (string) $p['price'],
					'url'   => (string) $p['permalink'],
				);
				if ( ! empty( $p['stock_status'] ) && 'instock' !== $p['stock_status'] ) {
					$slim['stock'] = (string) $p['stock_status'];
				}
				return $slim;
			},
			$raw_products
		);

		$result = array( 'products' => $products );
		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Return detailed info for one product.
	 *
	 * @param array $args ['product_id' => int]
	 * @return array
	 */
	private function tool_get_product_details( array $args ): array {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array( 'error' => 'WooCommerce is not active.' );
		}

		$product_id = absint( $args['product_id'] ?? 0 );
		if ( $product_id < 1 ) {
			return array( 'error' => 'Invalid product_id.' );
		}

		$cache_key = 'aiwoo_tool_detail_' . $product_id;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array( 'error' => "Product {$product_id} not found." );
		}

		// Format visible attributes.
		$attributes = array();
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_visible() ) {
				continue;
			}
			$label  = wc_attribute_label( $attribute->get_name() );
			$values = $attribute->is_taxonomy()
				? wc_get_product_terms( $product_id, $attribute->get_name(), array( 'fields' => 'names' ) )
				: $attribute->get_options();

			$attributes[ $label ] = implode( ', ', array_map( 'sanitize_text_field', (array) $values ) );
		}

		$stock_status = $product->get_stock_status();
		$sale_price   = $product->get_sale_price();

		$result = array(
			'id'                => $product_id,
			'name'              => $product->get_name(),
			'price'             => wp_strip_all_tags( wc_price( (float) $product->get_price() ) ),
			'short_description' => wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 30 ),
			'attributes'        => $attributes,
			'url'               => $product->get_permalink(),
		);

		if ( 'instock' !== $stock_status ) {
			$result['stock'] = $stock_status;
		}
		if ( '' !== (string) $sale_price ) {
			$result['sale_price']    = $sale_price;
			$result['regular_price'] = $product->get_regular_price();
		}

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Return upsell and cross-sell products for a given product.
	 *
	 * @param array $args ['product_id' => int]
	 * @return array
	 */
	private function tool_get_related_products( array $args ): array {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array( 'error' => 'WooCommerce is not active.' );
		}

		$product_id = absint( $args['product_id'] ?? 0 );
		if ( $product_id < 1 ) {
			return array( 'error' => 'Invalid product_id.' );
		}

		$cache_key = 'aiwoo_tool_related_' . $product_id;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return array( 'error' => "Product {$product_id} not found." );
		}

		$format = static function ( int $id ): ?array {
			$p = wc_get_product( $id );
			if ( ! $p ) {
				return null;
			}
			return array(
				'id'           => $id,
				'name'         => $p->get_name(),
				'price'        => wp_strip_all_tags( wc_price( (float) $p->get_price() ) ),
				'url'          => $p->get_permalink(),
				'stock_status' => $p->get_stock_status(),
			);
		};

		$upsell_ids    = array_slice( array_map( 'absint', $product->get_upsell_ids() ),    0, 4 );
		$crosssell_ids = array_slice( array_map( 'absint', $product->get_cross_sell_ids() ), 0, 4 );

		$result = array(
			'upsells'     => array_values( array_filter( array_map( $format, $upsell_ids ) ) ),
			'cross_sells' => array_values( array_filter( array_map( $format, $crosssell_ids ) ) ),
		);

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Return personalisation context for the current visitor.
	 *
	 * @param array $args ['session_id' => string] (optional)
	 * @return array
	 */
	private function tool_get_user_context( array $args ): array {
		$ctx = $this->request_context;

		// ── Recently viewed products (tracked by JS, sent in pageContext) ──────
		$viewed = array();
		if ( ! empty( $ctx['viewedProducts'] ) && is_array( $ctx['viewedProducts'] ) ) {
			foreach ( array_slice( $ctx['viewedProducts'], 0, 5 ) as $item ) {
				if ( ! is_array( $item ) || empty( $item['id'] ) ) {
					continue;
				}
				$viewed[] = array(
					'id'   => absint( $item['id'] ),
					'name' => sanitize_text_field( (string) ( $item['name'] ?? '' ) ),
				);
			}
		}

		// ── Search history (tracked by JS, sent in pageContext) ───────────────
		$searches = array();
		if ( ! empty( $ctx['searchHistory'] ) && is_array( $ctx['searchHistory'] ) ) {
			foreach ( array_slice( $ctx['searchHistory'], 0, 5 ) as $kw ) {
				$kw = sanitize_text_field( (string) $kw );
				if ( '' !== $kw ) {
					$searches[] = $kw;
				}
			}
		}

		// ── Cart items (WooCommerce server-side) ──────────────────────────────
		$cart_items = array();
		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' )
			&& ! is_null( WC()->cart ) ) {
			foreach ( array_slice( WC()->cart->get_cart(), 0, 5, true ) as $item ) {
				$p = $item['data'] ?? null;
				if ( ! $p instanceof \WC_Product ) {
					continue;
				}
				$cart_items[] = array(
					'id'       => $p->get_id(),
					'name'     => $p->get_name(),
					'quantity' => max( 1, (int) ( $item['quantity'] ?? 1 ) ),
				);
			}
		}

		return array(
			'viewed_products' => $viewed,
			'search_history'  => $searches,
			'cart_items'      => $cart_items,
		);
	}
}
