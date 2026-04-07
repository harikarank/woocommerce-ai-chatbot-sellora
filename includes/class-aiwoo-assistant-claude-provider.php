<?php
/**
 * Anthropic Claude provider — Messages API.
 *
 * @package AIWooAssistant
 * @see https://docs.anthropic.com/en/api/messages
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class Claude_Provider implements Provider_Interface {

	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function generate_response( array $payload ) {
		$api_key = trim( (string) $this->settings->get( 'claude_api_key' ) );

		if ( '' === $api_key ) {
			throw new \Exception(
				__( 'Anthropic API key is missing. Add it under Sellora AI → Settings → General.', 'ai-woocommerce-assistant' )
			);
		}

		$model       = $this->validated_model( (string) $this->settings->get( 'claude_model' ) );
		$temperature = max( 0.0, min( 1.0, (float) $this->settings->get( 'temperature' ) ) );

		$request_body = array(
			'model'       => $model,
			'max_tokens'  => 1024,
			'temperature' => $temperature,
			'system'      => (string) $payload['instructions'],
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => (string) $payload['input'],
				),
			),
		);

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 30,
				'headers' => array(
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
					'Content-Type'      => 'application/json',
				),
				'body'    => wp_json_encode( $request_body ),
			)
		);

		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {
			$msg = isset( $body['error']['message'] ) && is_string( $body['error']['message'] )
				? $body['error']['message']
				: __( 'Unexpected Anthropic API error.', 'ai-woocommerce-assistant' );
			throw new \Exception( sanitize_text_field( $msg ) );
		}

		$text = isset( $body['content'][0]['text'] ) && is_string( $body['content'][0]['text'] )
			? trim( $body['content'][0]['text'] )
			: '';

		if ( '' !== $text ) {
			return $text;
		}

		throw new \Exception( __( 'Claude returned an empty response.', 'ai-woocommerce-assistant' ) );
	}

	private function validated_model( $model ) {
		$supported = array(
			'claude-opus-4-6',
			'claude-sonnet-4-6',
			'claude-haiku-4-5-20251001',
		);

		return in_array( $model, $supported, true ) ? $model : 'claude-sonnet-4-6';
	}
}
