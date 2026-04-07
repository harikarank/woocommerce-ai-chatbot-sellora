<?php
/**
 * Google Gemini provider — Generative Language API.
 *
 * @package AIWooAssistant
 * @see https://ai.google.dev/api/generate-content
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class Gemini_Provider implements Provider_Interface {

	private $settings;

	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	public function generate_response( array $payload ) {
		$api_key = trim( (string) $this->settings->get( 'gemini_api_key' ) );

		if ( '' === $api_key ) {
			throw new \Exception(
				__( 'Google Gemini API key is missing. Add it under Sellora AI → Settings → General.', 'ai-woocommerce-assistant' )
			);
		}

		$model       = $this->validated_model( (string) $this->settings->get( 'gemini_model' ) );
		$temperature = max( 0.0, min( 1.0, (float) $this->settings->get( 'temperature' ) ) );

		// API key is passed as a query param — never in Authorization header for Gemini.
		$url = 'https://generativelanguage.googleapis.com/v1beta/models/'
			. rawurlencode( $model )
			. ':generateContent?key='
			. rawurlencode( $api_key );

		$request_body = array(
			'system_instruction' => array(
				'parts' => array(
					array( 'text' => (string) $payload['instructions'] ),
				),
			),
			'contents'           => array(
				array(
					'role'  => 'user',
					'parts' => array(
						array( 'text' => (string) $payload['input'] ),
					),
				),
			),
			'generationConfig'   => array(
				'temperature' => $temperature,
			),
		);

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json',
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
				: __( 'Unexpected Gemini API error.', 'ai-woocommerce-assistant' );
			throw new \Exception( sanitize_text_field( $msg ) );
		}

		$text = isset( $body['candidates'][0]['content']['parts'][0]['text'] )
				&& is_string( $body['candidates'][0]['content']['parts'][0]['text'] )
			? trim( $body['candidates'][0]['content']['parts'][0]['text'] )
			: '';

		if ( '' !== $text ) {
			return $text;
		}

		// Surface finish reason if text is empty (e.g. safety block).
		$finish_reason = $body['candidates'][0]['finishReason'] ?? '';
		if ( '' !== $finish_reason && 'STOP' !== $finish_reason ) {
			throw new \Exception(
				sprintf(
					/* translators: %s: Gemini finish reason */
					__( 'Gemini could not complete the response (reason: %s).', 'ai-woocommerce-assistant' ),
					sanitize_text_field( $finish_reason )
				)
			);
		}

		throw new \Exception( __( 'Gemini returned an empty response.', 'ai-woocommerce-assistant' ) );
	}

	private function validated_model( $model ) {
		$supported = array(
			'gemini-2.5-flash',
			'gemini-2.5-pro',
			'gemini-2.5-flash-lite',
			'gemini-2.0-flash-lite',
			'gemini-1.5-pro',
			'gemini-1.5-flash',
		);

		return in_array( $model, $supported, true ) ? $model : 'gemini-2.5-flash';
	}
}
