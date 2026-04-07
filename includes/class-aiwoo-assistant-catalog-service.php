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

	public function find_relevant_products( $message, $current_product_id = 0 ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return array();
		}

		$limit       = (int) $this->settings->get( 'max_context_products' );
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
		return array(
			'id'                => $product->get_id(),
			'name'              => $product->get_name(),
			'price'             => wp_strip_all_tags( wp_kses_post( wc_price( (float) $product->get_price() ) ) ),
			'regular_price'     => $product->get_regular_price(),
			'sale_price'        => $product->get_sale_price(),
			'sku'               => $product->get_sku(),
			'permalink'         => $product->get_permalink(),
			'stock_status'      => $product->get_stock_status(),
			'short_description' => wp_trim_words( wp_strip_all_tags( $product->get_short_description() ), 30 ),
			'description'       => wp_trim_words( wp_strip_all_tags( $product->get_description() ), 50 ),
			'categories'        => wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
			'tags'              => wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'names' ) ),
			'attributes'        => $this->format_attributes( $product ),
		);
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
