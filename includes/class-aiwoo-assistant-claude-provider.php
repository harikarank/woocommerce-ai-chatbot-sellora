<?php
/**
 * Claude provider stub.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class Claude_Provider implements Provider_Interface {
	public function generate_response( array $payload ) {
		unset( $payload );

		throw new \Exception( __( 'Claude support is not enabled yet. Switch the provider to OpenAI for live responses.', 'ai-woocommerce-assistant' ) );
	}
}
