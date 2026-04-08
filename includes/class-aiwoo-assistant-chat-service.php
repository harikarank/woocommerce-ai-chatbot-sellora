<?php
/**
 * Chat orchestration service.
 *
 * Routes each chat turn through either the legacy prompt-based path or the
 * new MCP tool-calling path, depending on the `enable_mcp` setting.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class Chat_Service {

	/** @var Settings */
	private $settings;

	/** @var Catalog_Service */
	private $catalog_service;

	/** @var Quick_Reply_Service */
	private $quick_reply_service;

	/** @var MCP_Tools */
	private $mcp_tools;

	/** @var AI_Error_Logger */
	private $ai_error_logger;

	public function __construct(
		Settings $settings,
		Catalog_Service $catalog_service,
		Quick_Reply_Service $quick_reply_service,
		MCP_Tools $mcp_tools,
		AI_Error_Logger $ai_error_logger
	) {
		$this->settings          = $settings;
		$this->catalog_service   = $catalog_service;
		$this->quick_reply_service = $quick_reply_service;
		$this->mcp_tools         = $mcp_tools;
		$this->ai_error_logger   = $ai_error_logger;
	}

	// -------------------------------------------------------------------------
	// Public entry point
	// -------------------------------------------------------------------------

	/**
	 * Generate a reply for the given user message.
	 *
	 * @param string $message      Sanitised user input.
	 * @param array  $history      Conversation history [{role, content}, ...].
	 * @param array  $page_context Frontend page/product context.
	 * @param string $session_id   Session identifier (for error logging).
	 * @param string $ip_address   Visitor IP (for error logging).
	 * @return array{message: string, html: bool, enquiry_form: bool, recommendations: array}
	 * @throws \Exception On unrecoverable AI provider error.
	 */
	public function generate_reply( $message, array $history = array(), array $page_context = array(), $session_id = '', $ip_address = '' ) {
		$message      = sanitize_textarea_field( $message );
		$history      = $this->sanitize_history( $history );
		$page_context = $this->sanitize_page_context( $page_context );

		// ── 1. Quick reply check — intercepts before any AI or catalog call ───
		$quick_response = $this->quick_reply_service->find_match( $message );
		if ( null !== $quick_response ) {
			return array(
				'message'         => $quick_response,
				'html'            => false,
				'enquiry_form'    => false,
				'recommendations' => array(),
			);
		}

		// ── 2. Route to MCP or legacy path ────────────────────────────────────
		if ( 'yes' === $this->settings->get( 'enable_mcp' ) ) {
			return $this->generate_reply_mcp( $message, $history, $page_context, $session_id, $ip_address );
		}

		return $this->generate_reply_legacy( $message, $history, $page_context, $session_id, $ip_address );
	}

	// -------------------------------------------------------------------------
	// MCP tool-calling path
	// -------------------------------------------------------------------------

	/**
	 * MCP path: the AI fetches data via tools; no product catalog is injected
	 * into the prompt. Adds ≈ 1 extra API round trip per tool call; transient
	 * caching in MCP_Tools keeps latency acceptable.
	 *
	 * @param string $message
	 * @param array  $history
	 * @param array  $page_context
	 * @return array
	 */
	private function generate_reply_mcp( string $message, array $history, array $page_context, string $session_id = '', string $ip_address = '' ): array {
		// Give the tool executor access to the current request's context
		// (viewed products, search history, cart) before calling any tool.
		$this->mcp_tools->set_request_context( $page_context );

		$tools         = $this->mcp_tools->get_tool_definitions();
		$mcp_tools_ref = $this->mcp_tools;

		$tool_executor = static function ( string $name, array $args ) use ( $mcp_tools_ref ): array {
			return $mcp_tools_ref->execute( $name, $args );
		};

		try {
			$provider = make_ai_provider( $this->settings );

			$assistant_message = $provider->generate_with_tools(
				array(
					'settings'     => $this->settings,
					'instructions' => $this->build_instructions_mcp(),
					'messages'     => $this->build_messages_mcp( $message, $history, $page_context ),
				),
				$tools,
				$tool_executor
			);
		} catch ( \Exception $e ) {
			// AI unavailable — fall back to catalog search with product cards.
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( 'Sellora AI fallback (MCP): ' . $e->getMessage() );
			}
			$this->ai_error_logger->log( $session_id, $ip_address, $message, 'mcp', $e->getMessage() );
			$current_product_id = ! empty( $page_context['product']['id'] ) ? absint( $page_context['product']['id'] ) : 0;
			$products           = $this->catalog_service->find_relevant_products( $message, $current_product_id );
			return $this->build_product_fallback_response( $products );
		}

		// If get_products was called, render product cards under the AI text.
		$fetched_products = $this->mcp_tools->get_fetched_products();

		$response_html = wpautop( esc_html( $assistant_message ) );
		if ( ! empty( $fetched_products ) ) {
			$response_html .= $this->build_product_cards_html( $fetched_products );
		}

		return array(
			'message'         => $response_html,
			'html'            => true,
			'enquiry_form'    => false,
			'recommendations' => $fetched_products,
		);
	}

	/**
	 * Build the system instructions for MCP mode.
	 * Intentionally concise — no product data injected here.
	 *
	 * @return string
	 */
	private function build_instructions_mcp(): string {
		$currency    = get_option( 'woocommerce_currency', 'USD' );
		$base_prompt = trim( (string) $this->settings->get( 'system_prompt' ) );
		$parts       = array();

		if ( '' !== $base_prompt ) {
			$parts[] = $base_prompt;
		}

		$parts[] = 'You are a helpful shopping assistant for ' . get_bloginfo( 'name' ) . '.';
		$parts[] = 'Store currency: ' . $currency . '. Store description: ' . get_bloginfo( 'description' ) . '.';
		$parts[] = 'Always use the available tools to fetch product data before recommending items.';
		$parts[] = 'Never invent or guess product details — rely only on tool results.';
		$parts[] = 'When recommending products, include the name, price, availability, and a product link.';

		return implode( "\n", $parts );
	}

	/**
	 * Convert sanitised history + current message into a messages array
	 * suitable for the provider's generate_with_tools() call.
	 *
	 * @param string $message
	 * @param array  $history
	 * @param array  $page_context
	 * @return array  [{role, content}, ...]
	 */
	private function build_messages_mcp( string $message, array $history, array $page_context ): array {
		$messages = array();

		// Include up to the last 6 history entries.
		foreach ( array_slice( $history, -6 ) as $entry ) {
			if ( empty( $entry['role'] ) || empty( $entry['content'] ) ) {
				continue;
			}
			$messages[] = array(
				'role'    => $entry['role'],
				'content' => mb_substr( (string) $entry['content'], 0, 1000 ),
			);
		}

		// Prepend lightweight page context to the user message (no product data).
		$context_prefix = '';
		if ( ! empty( $page_context['pageUrl'] ) ) {
			$context_prefix .= 'Current page: ' . esc_url_raw( $page_context['pageUrl'] ) . "\n";
		}
		if ( ! empty( $page_context['product']['id'] ) && ! empty( $page_context['product']['name'] ) ) {
			$context_prefix .= 'Viewing product #' . absint( $page_context['product']['id'] )
				. ' — ' . sanitize_text_field( $page_context['product']['name'] ) . "\n";
		}

		$messages[] = array(
			'role'    => 'user',
			'content' => ( '' !== $context_prefix ? $context_prefix . "\n" : '' )
				. sanitize_text_field( $message ),
		);

		return $messages;
	}

	// -------------------------------------------------------------------------
	// Legacy prompt-based path (unchanged behaviour)
	// -------------------------------------------------------------------------

	/**
	 * Legacy path: pre-fetches products from the catalog and injects them
	 * into the prompt. Used when enable_mcp = no (the default).
	 *
	 * @param string $message
	 * @param array  $history
	 * @param array  $page_context
	 * @return array
	 */
	private function generate_reply_legacy( string $message, array $history, array $page_context, string $session_id = '', string $ip_address = '' ): array {
		$current_product_id = ! empty( $page_context['product']['id'] ) ? absint( $page_context['product']['id'] ) : 0;
		$products           = $this->catalog_service->find_relevant_products( $message, $current_product_id );

		if ( empty( $products ) ) {
			$no_match = trim( (string) $this->settings->get( 'no_match_text' ) );
			if ( '' === $no_match ) {
				$no_match = __( "We couldn't find an exact match. Please share more details.", 'ai-woocommerce-assistant' );
			}
			return array(
				'message'           => $no_match,
				'html'              => false,
				'enquiry_form'      => true,
				'enquiry_form_html' => $this->get_enquiry_form_html(),
				'recommendations'   => array(),
			);
		}

		$payload = array(
			'settings'     => $this->settings,
			'instructions' => $this->build_instructions(),
			'input'        => $this->build_input( $message, $history, $page_context, $products ),
		);

		try {
			$assistant_message = call_ai_model( $message, $payload );
		} catch ( \Exception $e ) {
			// AI unavailable — fall back to product cards without AI text.
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				error_log( 'Sellora AI fallback (legacy): ' . $e->getMessage() );
			}
			$this->ai_error_logger->log( $session_id, $ip_address, $message, 'legacy', $e->getMessage() );
			return $this->build_product_fallback_response( $products );
		}

		return array(
			'message'         => $this->build_product_response_html( $assistant_message, $products ),
			'html'            => true,
			'enquiry_form'    => false,
			'recommendations' => $products,
		);
	}

	// -------------------------------------------------------------------------
	// Legacy helpers (unchanged)
	// -------------------------------------------------------------------------

	private function build_instructions() {
		$currency     = get_option( 'woocommerce_currency', 'USD' );
		$base_prompt  = trim( (string) $this->settings->get( 'system_prompt' ) );
		$prompt_parts = array();

		if ( '' !== $base_prompt ) {
			$prompt_parts[] = $base_prompt;
		}

		$prompt_parts[] = 'Use only the supplied store and product context for factual product claims.';
		$prompt_parts[] = 'If information is missing, say so plainly instead of inventing details.';
		$prompt_parts[] = 'Never claim live shipping, fulfillment, or policy details unless they appear in the provided context.';
		$prompt_parts[] = 'Store name: ' . get_bloginfo( 'name' ) . '.';
		$prompt_parts[] = 'Store description: ' . get_bloginfo( 'description' ) . '.';
		$prompt_parts[] = 'Store currency: ' . $currency . '.';

		return implode( "\n", $prompt_parts );
	}

	private function build_input( $message, array $history, array $page_context, array $products ) {
		$history_lines = array();
		foreach ( array_slice( $history, -6 ) as $entry ) {
			if ( empty( $entry['role'] ) || empty( $entry['content'] ) ) {
				continue;
			}
			$role            = 'assistant' === $entry['role'] ? 'Assistant' : 'Customer';
			$history_lines[] = $role . ': ' . sanitize_text_field( (string) $entry['content'] );
		}

		$page_lines = array(
			'Current page URL: ' . ( ! empty( $page_context['pageUrl'] ) ? esc_url_raw( $page_context['pageUrl'] ) : home_url( '/' ) ),
		);

		if ( ! empty( $page_context['product'] ) && is_array( $page_context['product'] ) ) {
			$page_lines[] = 'Current product context: ' . wp_json_encode( $page_context['product'] );
		}

		$product_lines = array_map( static fn( $p ) => wp_json_encode( $p ), $products );

		return implode(
			"\n\n",
			array_filter(
				array(
					'Recent conversation:' . ( $history_lines ? "\n" . implode( "\n", $history_lines ) : "\nNo prior messages." ),
					'Page context:' . "\n" . implode( "\n", $page_lines ),
					'Relevant products:' . ( $product_lines ? "\n" . implode( "\n", $product_lines ) : "\nNo products found." ),
					'Latest customer message: ' . sanitize_text_field( $message ),
				)
			)
		);
	}

	// -------------------------------------------------------------------------
	// Sanitisation helpers
	// -------------------------------------------------------------------------

	private function sanitize_history( array $history ) {
		$sanitized = array();

		foreach ( array_slice( $history, -8 ) as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$role    = ! empty( $entry['role'] ) && 'assistant' === $entry['role'] ? 'assistant' : 'user';
			$content = ! empty( $entry['content'] ) ? sanitize_textarea_field( wp_strip_all_tags( (string) $entry['content'] ) ) : '';
			$content = mb_substr( $content, 0, 1000 );

			if ( '' === trim( $content ) ) {
				continue;
			}

			$sanitized[] = array(
				'role'    => $role,
				'content' => $content,
			);
		}

		return $sanitized;
	}

	private function sanitize_page_context( array $page_context ) {
		$sanitized = array(
			'pageUrl' => ! empty( $page_context['pageUrl'] ) ? esc_url_raw( (string) $page_context['pageUrl'] ) : '',
		);

		if ( ! empty( $page_context['product'] ) && is_array( $page_context['product'] ) ) {
			$sanitized['product'] = array(
				'id'                => ! empty( $page_context['product']['id'] ) ? absint( $page_context['product']['id'] ) : 0,
				'name'              => ! empty( $page_context['product']['name'] ) ? sanitize_text_field( (string) $page_context['product']['name'] ) : '',
				'price'             => ! empty( $page_context['product']['price'] ) ? sanitize_text_field( (string) $page_context['product']['price'] ) : '',
				'permalink'         => ! empty( $page_context['product']['permalink'] ) ? esc_url_raw( (string) $page_context['product']['permalink'] ) : '',
				'stock_status'      => ! empty( $page_context['product']['stock_status'] ) ? sanitize_text_field( (string) $page_context['product']['stock_status'] ) : '',
				'short_description' => ! empty( $page_context['product']['short_description'] ) ? sanitize_textarea_field( (string) $page_context['product']['short_description'] ) : '',
			);
		}

		// ── Personalisation fields (MCP mode) ─────────────────────────────────
		$sanitized['viewedProducts'] = array();
		if ( ! empty( $page_context['viewedProducts'] ) && is_array( $page_context['viewedProducts'] ) ) {
			foreach ( array_slice( $page_context['viewedProducts'], 0, 10 ) as $item ) {
				if ( ! is_array( $item ) || empty( $item['id'] ) ) {
					continue;
				}
				$sanitized['viewedProducts'][] = array(
					'id'   => absint( $item['id'] ),
					'name' => sanitize_text_field( (string) ( $item['name'] ?? '' ) ),
				);
			}
		}

		$sanitized['searchHistory'] = array();
		if ( ! empty( $page_context['searchHistory'] ) && is_array( $page_context['searchHistory'] ) ) {
			foreach ( array_slice( $page_context['searchHistory'], 0, 10 ) as $kw ) {
				$kw = sanitize_text_field( (string) $kw );
				if ( '' !== $kw ) {
					$sanitized['searchHistory'][] = $kw;
				}
			}
		}

		return $sanitized;
	}

	// -------------------------------------------------------------------------
	// HTML builders
	// -------------------------------------------------------------------------

	/**
	 * Wrap AI text + product cards (legacy path).
	 */
	private function build_product_response_html( $assistant_message, array $products ) {
		$html  = wpautop( esc_html( $assistant_message ) );
		$html .= $this->build_product_cards_html( $products );
		return $html;
	}

	/**
	 * Build a fallback response when the AI provider is unavailable.
	 * Shows product cards from catalog search with a friendly message,
	 * or the enquiry form if no products were found.
	 */
	private function build_product_fallback_response( array $products ): array {
		if ( empty( $products ) ) {
			$no_match = trim( (string) $this->settings->get( 'no_match_text' ) );
			if ( '' === $no_match ) {
				$no_match = __( "We couldn't find an exact match. Please share more details.", 'ai-woocommerce-assistant' );
			}
			return array(
				'message'           => $no_match,
				'html'              => false,
				'enquiry_form'      => true,
				'enquiry_form_html' => $this->get_enquiry_form_html(),
				'recommendations'   => array(),
			);
		}

		$fallback_text = __( 'Here are some products that might match what you\'re looking for:', 'ai-woocommerce-assistant' );
		$html          = wpautop( esc_html( $fallback_text ) );
		$html         .= $this->build_product_cards_html( $products );

		return array(
			'message'         => $html,
			'html'            => true,
			'enquiry_form'    => false,
			'recommendations' => $products,
		);
	}

	/**
	 * Render product cards for a slice of products (shared between MCP and legacy paths).
	 * Which fields are shown is controlled by settings (all off by default).
	 */
	private function build_product_cards_html( array $products ): string {
		if ( empty( $products ) ) {
			return '';
		}

		$show_image     = 'yes' === $this->settings->get( 'card_show_image' );
		$show_price     = 'yes' === $this->settings->get( 'card_show_price' );
		$show_stock     = 'yes' === $this->settings->get( 'card_show_stock' );
		$show_desc      = 'yes' === $this->settings->get( 'card_show_desc' );
		$show_view_link = 'yes' === $this->settings->get( 'card_show_view_link' );

		$html = '<div class="aiwoo-product-list">';
		foreach ( array_slice( $products, 0, 3 ) as $product ) {
			$permalink = esc_url( $product['permalink'] );
			$html     .= '<div class="aiwoo-product-card">';

			if ( $show_image && ! empty( $product['image_url'] ) ) {
				$html .= '<img class="aiwoo-product-card__image" src="' . esc_url( $product['image_url'] ) . '" alt="' . esc_attr( $product['name'] ) . '" loading="lazy" />';
			}

			$html .= '<a class="aiwoo-product-card__title" href="' . $permalink . '">' . esc_html( $product['name'] ) . '</a>';

			if ( $show_price ) {
				$html .= '<span class="aiwoo-product-card__price">' . esc_html( $product['price'] ) . '</span>';
			}

			if ( $show_stock ) {
				$html .= '<span class="aiwoo-product-card__stock">' . esc_html( $this->format_stock_status( $product['stock_status'] ) ) . '</span>';
			}

			if ( $show_desc ) {
				$desc = wp_trim_words( $product['short_description'] ?: ( $product['description'] ?? '' ), 18 );
				if ( '' !== $desc ) {
					$html .= '<span class="aiwoo-product-card__desc">' . esc_html( $desc ) . '</span>';
				}
			}

			if ( $show_view_link ) {
				$html .= '<a class="aiwoo-product-card__view" href="' . $permalink . '">' . esc_html__( 'View details', 'ai-woocommerce-assistant' ) . '</a>';
			}

			$html .= '</div>';
		}
		$html .= '</div>';

		return $html;
	}

	private function format_stock_status( $status ) {
		switch ( $status ) {
			case 'instock':
				return __( 'In stock', 'ai-woocommerce-assistant' );
			case 'outofstock':
				return __( 'Out of stock', 'ai-woocommerce-assistant' );
			case 'onbackorder':
				return __( 'Available on backorder', 'ai-woocommerce-assistant' );
			default:
				return sanitize_text_field( (string) $status );
		}
	}

	private function get_enquiry_form_html() {
		$title   = trim( (string) $this->settings->get( 'enquiry_title' ) );
		$content = trim( (string) $this->settings->get( 'enquiry_content' ) );

		$html = '<div class="aiwoo-enquiry">';

		if ( '' !== $title ) {
			$html .= '<p class="aiwoo-enquiry__title">' . esc_html( $title ) . '</p>';
		}

		if ( '' !== $content ) {
			$html .= '<p class="aiwoo-enquiry__intro">' . esc_html( $content ) . '</p>';
		}

		$html .= '<form class="aiwoo-enquiry-form">';
		$html .= '<input type="text" name="name" placeholder="' . esc_attr__( 'Name', 'ai-woocommerce-assistant' ) . '" required />';
		$html .= '<input type="text" name="phone" placeholder="' . esc_attr__( 'Phone (optional)', 'ai-woocommerce-assistant' ) . '" />';
		$html .= '<input type="email" name="email" placeholder="' . esc_attr__( 'Email', 'ai-woocommerce-assistant' ) . '" required />';
		$html .= '<textarea name="message" rows="3" placeholder="' . esc_attr__( 'Message', 'ai-woocommerce-assistant' ) . '" required></textarea>';
		// Honeypot field — invisible to real users; bots populate it and get silently rejected.
		$html .= '<input type="text" name="aiwoo_hp" value="" autocomplete="off" tabindex="-1" aria-hidden="true" style="position:absolute;left:-9999px;top:-9999px;width:1px;height:1px;opacity:0;" />';
		$html .= '<button type="submit">' . esc_html__( 'Send enquiry', 'ai-woocommerce-assistant' ) . '</button>';
		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}
}
