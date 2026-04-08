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

	/**
	 * MCP-style tool calling via the Chat Completions API.
	 *
	 * @param array    $payload       ['instructions' => string, 'messages' => array]
	 * @param array[]  $tools         Tool definitions (canonical format).
	 * @param callable $tool_executor fn(string $name, array $args): array
	 * @return string
	 * @throws \Exception
	 */
	public function generate_with_tools( array $payload, array $tools, callable $tool_executor ) {
		$api_key     = trim( (string) $this->settings->get( 'openai_api_key' ) );
		$model       = (string) $this->settings->get( 'openai_model' );
		$temperature = (float) $this->settings->get( 'temperature' );

		if ( '' === $api_key ) {
			throw new \Exception( __( 'OpenAI API key is missing. Configure it in the plugin settings.', 'ai-woocommerce-assistant' ) );
		}

		// Convert canonical tool definitions to the Chat Completions format.
		$oa_tools = array_map(
			static function ( array $def ): array {
				return array(
					'type'     => 'function',
					'function' => array(
						'name'        => $def['name'],
						'description' => $def['description'],
						'parameters'  => $def['parameters'],
					),
				);
			},
			$tools
		);

		// Build initial messages.
		$instructions = (string) ( $payload['instructions'] ?? '' );
		$messages     = array();
		if ( '' !== $instructions ) {
			$messages[] = array( 'role' => 'system', 'content' => $instructions );
		}
		foreach ( (array) ( $payload['messages'] ?? array() ) as $msg ) {
			$role = in_array( $msg['role'] ?? '', array( 'user', 'assistant' ), true ) ? $msg['role'] : 'user';
			$messages[] = array( 'role' => $role, 'content' => (string) ( $msg['content'] ?? '' ) );
		}

		$max_rounds = 5;

		for ( $round = 0; $round < $max_rounds; $round++ ) {
			$request_body = array(
				'model'       => $model,
				'temperature' => $temperature,
				'messages'    => $messages,
				'tools'       => $oa_tools,
				'tool_choice' => 'auto',
			);

			$response = wp_remote_post(
				'https://api.openai.com/v1/chat/completions',
				array(
					'timeout' => 30,
					'headers' => array(
						'Authorization' => 'Bearer ' . $api_key,
						'Content-Type'  => 'application/json',
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
					: __( 'Unexpected OpenAI API error.', 'ai-woocommerce-assistant' );
				throw new \Exception( sanitize_text_field( $msg ) );
			}

			$choice         = $body['choices'][0] ?? array();
			$assistant_msg  = $choice['message'] ?? array();
			$finish_reason  = $choice['finish_reason'] ?? 'stop';

			// Append assistant turn to history.
			$messages[] = $assistant_msg;

			if ( 'tool_calls' === $finish_reason && ! empty( $assistant_msg['tool_calls'] ) ) {
				// Execute each tool call and append results.
				foreach ( $assistant_msg['tool_calls'] as $tc ) {
					$tool_name = $tc['function']['name'] ?? '';
					$raw_args  = $tc['function']['arguments'] ?? '{}';
					$tool_args = json_decode( $raw_args, true );
					$tool_id   = $tc['id'] ?? '';

					if ( ! is_array( $tool_args ) ) {
						$tool_args = array();
					}

					$tool_result = call_user_func( $tool_executor, $tool_name, $tool_args );

					$messages[] = array(
						'role'         => 'tool',
						'tool_call_id' => $tool_id,
						'content'      => wp_json_encode( $tool_result ),
					);
				}
				// Continue loop — AI will respond with the final text.
				continue;
			}

			// Final text response.
			$text = $assistant_msg['content'] ?? '';
			if ( is_string( $text ) && '' !== trim( $text ) ) {
				return trim( $text );
			}

			break;
		}

		throw new \Exception( __( 'OpenAI returned an empty response.', 'ai-woocommerce-assistant' ) );
	}
}
