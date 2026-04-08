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

	/**
	 * MCP-style tool calling via the Gemini Generative Language API.
	 *
	 * @param array    $payload       ['instructions' => string, 'messages' => array]
	 * @param array[]  $tools         Tool definitions (canonical format).
	 * @param callable $tool_executor fn(string $name, array $args): array
	 * @return string
	 * @throws \Exception
	 */
	public function generate_with_tools( array $payload, array $tools, callable $tool_executor ) {
		$api_key     = trim( (string) $this->settings->get( 'gemini_api_key' ) );
		$model       = $this->validated_model( (string) $this->settings->get( 'gemini_model' ) );
		$temperature = max( 0.0, min( 1.0, (float) $this->settings->get( 'temperature' ) ) );

		if ( '' === $api_key ) {
			throw new \Exception(
				__( 'Google Gemini API key is missing. Add it under Sellora AI → Settings → General.', 'ai-woocommerce-assistant' )
			);
		}

		$url = 'https://generativelanguage.googleapis.com/v1beta/models/'
			. rawurlencode( $model )
			. ':generateContent?key='
			. rawurlencode( $api_key );

		// Convert canonical definitions to Gemini's functionDeclarations format.
		$gemini_tools = array(
			array(
				'function_declarations' => array_map(
					static function ( array $def ): array {
						return array(
							'name'        => $def['name'],
							'description' => $def['description'],
							'parameters'  => $def['parameters'],
						);
					},
					$tools
				),
			),
		);

		// Build initial contents.
		$instructions = (string) ( $payload['instructions'] ?? '' );
		$contents     = array();
		foreach ( (array) ( $payload['messages'] ?? array() ) as $msg ) {
			$role       = 'assistant' === ( $msg['role'] ?? '' ) ? 'model' : 'user';
			$contents[] = array(
				'role'  => $role,
				'parts' => array( array( 'text' => (string) ( $msg['content'] ?? '' ) ) ),
			);
		}

		$max_rounds = 5;

		for ( $round = 0; $round < $max_rounds; $round++ ) {
			$request_body = array(
				'contents'         => $contents,
				'tools'            => $gemini_tools,
				'generationConfig' => array( 'temperature' => $temperature ),
			);
			if ( '' !== $instructions ) {
				$request_body['system_instruction'] = array(
					'parts' => array( array( 'text' => $instructions ) ),
				);
			}

			$response = wp_remote_post(
				$url,
				array(
					'timeout' => 30,
					'headers' => array( 'Content-Type' => 'application/json' ),
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

			$candidate    = $body['candidates'][0] ?? array();
			$content      = $candidate['content'] ?? array();
			$parts        = $content['parts'] ?? array();
			$finish       = $candidate['finishReason'] ?? 'STOP';

			// Append model turn.
			if ( ! empty( $content ) ) {
				$contents[] = $content;
			}

			// Check for function calls.
			$function_calls = array_filter( $parts, static function ( array $p ): bool {
				return isset( $p['functionCall'] );
			} );

			if ( ! empty( $function_calls ) ) {
				$function_responses = array();
				foreach ( $function_calls as $part ) {
					$fc        = $part['functionCall'];
					$tool_name = (string) ( $fc['name'] ?? '' );
					$tool_args = is_array( $fc['args'] ?? null ) ? $fc['args'] : array();

					$tool_result = call_user_func( $tool_executor, $tool_name, $tool_args );

					$function_responses[] = array(
						'functionResponse' => array(
							'name'     => $tool_name,
							'response' => $tool_result,
						),
					);
				}

				$contents[] = array( 'role' => 'user', 'parts' => $function_responses );
				continue;
			}

			// Extract text response.
			foreach ( $parts as $part ) {
				if ( isset( $part['text'] ) && is_string( $part['text'] ) && '' !== trim( $part['text'] ) ) {
					return trim( $part['text'] );
				}
			}

			if ( '' !== $finish && 'STOP' !== $finish ) {
				throw new \Exception(
					sprintf(
						/* translators: %s: Gemini finish reason */
						__( 'Gemini could not complete the response (reason: %s).', 'ai-woocommerce-assistant' ),
						sanitize_text_field( $finish )
					)
				);
			}

			break;
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
