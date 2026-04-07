<?php
/**
 * OpenAI provider.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

use WP_Error;

defined( 'ABSPATH' ) || exit;

final class OpenAI_Provider implements Provider_Interface {
	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function generate_response( array $payload ) {
		$api_key = trim( (string) $this->settings->get( 'openai_api_key' ) );

		if ( '' === $api_key ) {
			throw new \Exception( __( 'OpenAI API key is missing. Configure it in the plugin settings.', 'ai-woocommerce-assistant' ) );
		}

		$request_body = array(
			'model'        => (string) $this->settings->get( 'openai_model' ),
			'temperature'  => (float) $this->settings->get( 'temperature' ),
			'instructions' => (string) $payload['instructions'],
			'input'        => (string) $payload['input'],
		);

		$response = wp_remote_post(
			'https://api.openai.com/v1/responses',
			array(
				'timeout' => 25,
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $request_body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			/** @var WP_Error $response */
			throw new \Exception( $response->get_error_message() );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {
			$message = $body['error']['message'] ?? __( 'Unexpected OpenAI API error.', 'ai-woocommerce-assistant' );
			throw new \Exception( sanitize_text_field( (string) $message ) );
		}

		if ( ! empty( $body['output_text'] ) && is_string( $body['output_text'] ) ) {
			return trim( $body['output_text'] );
		}

		if ( ! empty( $body['output'] ) && is_array( $body['output'] ) ) {
			$text = $this->extract_output_text( $body['output'] );

			if ( '' !== $text ) {
				return $text;
			}
		}

		throw new \Exception( __( 'OpenAI returned an empty response.', 'ai-woocommerce-assistant' ) );
	}

	private function extract_output_text( array $output ) {
		$parts = array();

		foreach ( $output as $item ) {
			if ( empty( $item['content'] ) || ! is_array( $item['content'] ) ) {
				continue;
			}

			foreach ( $item['content'] as $content_item ) {
				if ( ! empty( $content_item['text'] ) && is_string( $content_item['text'] ) ) {
					$parts[] = trim( $content_item['text'] );
				}
			}
		}

		return trim( implode( "\n\n", array_filter( $parts ) ) );
	}
}
