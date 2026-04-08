<?php
/**
 * Chat orchestration service.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class Chat_Service {
	private $settings;

	private $catalog_service;

	private $quick_reply_service;

	public function __construct( Settings $settings, Catalog_Service $catalog_service, Quick_Reply_Service $quick_reply_service ) {
		$this->settings            = $settings;
		$this->catalog_service     = $catalog_service;
		$this->quick_reply_service = $quick_reply_service;
	}

	public function generate_reply( $message, array $history = array(), array $page_context = array() ) {
		$message      = sanitize_textarea_field( $message );
		$history      = $this->sanitize_history( $history );
		$page_context = $this->sanitize_page_context( $page_context );

		// ── Quick reply check — runs before catalog search and AI call ────────
		$quick_response = $this->quick_reply_service->find_match( $message );

		if ( null !== $quick_response ) {
			return array(
				'message'         => $quick_response,
				'html'            => false,
				'enquiry_form'    => false,
				'recommendations' => array(),
			);
		}
		// ── End quick reply check ─────────────────────────────────────────────

		$current_product_id = ! empty( $page_context['product']['id'] ) ? absint( $page_context['product']['id'] ) : 0;
		$products           = $this->catalog_service->find_relevant_products( $message, $current_product_id );

		if ( empty( $products ) ) {
			return array(
				'message'           => __( "We couldn't find an exact match. Please share more details.", 'ai-woocommerce-assistant' ),
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

		$assistant_message = call_ai_model( $message, $payload );

		return array(
			'message'         => $this->build_product_response_html( $assistant_message, $products ),
			'html'            => true,
			'enquiry_form'    => false,
			'recommendations' => $products,
		);
	}

	private function build_instructions() {
		$currency      = get_option( 'woocommerce_currency', 'USD' );
		$base_prompt   = trim( (string) $this->settings->get( 'system_prompt' ) );
		$prompt_parts  = array();

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

		$product_lines = array_map(
			static function( $product ) {
				return wp_json_encode( $product );
			},
			$products
		);

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

		return $sanitized;
	}

	private function build_product_response_html( $assistant_message, array $products ) {
		$html = wpautop( esc_html( $assistant_message ) );

		$html .= '<div class="aiwoo-product-list">';

		foreach ( array_slice( $products, 0, 3 ) as $product ) {
			$html .= '<a class="aiwoo-product-card" href="' . esc_url( $product['permalink'] ) . '">';
			$html .= '<strong class="aiwoo-product-card__title">' . esc_html( $product['name'] ) . '</strong>';
			$html .= '<span class="aiwoo-product-card__price">' . esc_html( $product['price'] ) . '</span>';
			$html .= '<span class="aiwoo-product-card__stock">' . esc_html( $this->format_stock_status( $product['stock_status'] ) ) . '</span>';
			$html .= '<span class="aiwoo-product-card__desc">' . esc_html( wp_trim_words( $product['short_description'] ?: $product['description'], 18 ) ) . '</span>';
			$html .= '</a>';
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

		$html  = '<div class="aiwoo-enquiry">';

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
