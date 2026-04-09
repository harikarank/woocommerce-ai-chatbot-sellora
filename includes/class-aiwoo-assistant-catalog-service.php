<?php
/**
 * WooCommerce catalog context helpers.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class Catalog_Service {
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function get_current_product_context() {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return null;
		}

		$product = wc_get_product( get_the_ID() );

		if ( ! $product ) {
			return null;
		}

		return $this->format_product( $product );
	}

	/**
	 * @param string   $message            User message to derive keywords from.
	 * @param int      $current_product_id Product currently being viewed (0 = none).
	 * @param int|null $limit              Override the settings-based limit (used by MCP_Tools).
	 * @return array[]
	 */
	public function find_relevant_products( $message, $current_product_id = 0, $limit = null ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		$limit = ( null !== $limit ) ? max( 1, min( 10, (int) $limit ) ) : (int) $this->settings->get( 'max_context_products' );
		$product_ids = array();
		$keywords    = $this->extract_keywords( $message );

		if ( $current_product_id > 0 ) {
			$product_ids[] = $current_product_id;
		}

		foreach ( $keywords as $keyword ) {
			$query = new \WP_Query(
				array(
					'post_type'              => 'product',
					'post_status'            => 'publish',
					'posts_per_page'         => $limit,
					's'                      => $keyword,
					'fields'                 => 'ids',
					'ignore_sticky_posts'    => true,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			if ( ! empty( $query->posts ) && is_array( $query->posts ) ) {
				$product_ids = array_merge( $product_ids, $query->posts );
			}

			if ( count( array_unique( $product_ids ) ) >= $limit ) {
				break;
			}
		}

		$product_ids = array_values( array_unique( array_filter( array_map( 'absint', $product_ids ) ) ) );

		$product_ids = array_slice( $product_ids, 0, $limit );

		return array_values(
			array_filter(
				array_map(
					function( $product_id ) {
						$product = wc_get_product( $product_id );
						if ( ! $product ) {
							return null;
						}

						return $this->format_product( $product );
					},
					$product_ids
				)
			)
		);
	}

	private function extract_keywords( $message ) {
		$message  = strtolower( wp_strip_all_tags( (string) $message ) );
		$tokens   = preg_split( '/\s+/', $message );
		$keywords = array();

		if ( ! is_array( $tokens ) ) {
			return array();
		}

		foreach ( $tokens as $token ) {
			$token = preg_replace( '/[^\p{L}\p{N}\-]/u', '', (string) $token ) ?? '';

			$length = mb_strlen( $token );

			if ( '' === $token || $length < 3 ) {
				continue;
			}

			$keywords[] = $token;
		}

		if ( empty( $keywords ) && '' !== trim( $message ) ) {
			$keywords[] = trim( $message );
		}

		return array_slice( array_values( array_unique( $keywords ) ), 0, 6 );
	}

	private function format_product( $product ) {
		$image_id  = $product->get_image_id();
		$image_url = $image_id ? (string) wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
		$sale_price = $product->get_sale_price();

		$data = array(
			'id'                => $product->get_id(),
			'name'              => $product->get_name(),
			'image_url'         => $image_url,
			'price'             => wp_strip_all_tags( wp_kses_post( wc_price( (float) $product->get_price() ) ) ),
			'permalink'         => $product->get_permalink(),
			'stock_status'      => $product->get_stock_status(),
			'short_description' => $this->clean_text( wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 30 ) ),
			'description'       => $this->clean_text( wp_trim_words( wp_strip_all_tags( $product->get_description() ), 50 ) ),
			'attributes'        => $this->format_attributes( $product ),
		);

		// Only include sale/regular when the product is actually on sale.
		if ( '' !== (string) $sale_price ) {
			$data['sale_price']    = $sale_price;
			$data['regular_price'] = $product->get_regular_price();
		}

		return $data;
	}

	/**
	 * Collapse consecutive whitespace and decode HTML entities.
	 * Saves tokens when JSON-encoded into the AI prompt.
	 */
	private function clean_text( $text ) {
		$text = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( (string) preg_replace( '/\s+/', ' ', $text ) );
	}

	private function format_attributes( $product ) {
		$attributes = array();

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute->get_visible() ) {
				continue;
			}

			$name = wc_attribute_label( $attribute->get_name() );

			if ( $attribute->is_taxonomy() ) {
				$values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), array( 'fields' => 'names' ) );
			} else {
				$values = $attribute->get_options();
			}

			$attributes[ $name ] = implode( ', ', array_map( 'sanitize_text_field', (array) $values ) );
		}

		return $attributes;
	}
}
