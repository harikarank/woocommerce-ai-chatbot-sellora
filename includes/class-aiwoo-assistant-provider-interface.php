<?php
/**
 * Provider contract.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

interface Provider_Interface {
	/**
	 * Generate an assistant response.
	 *
	 * @param array<string, mixed> $payload Provider payload.
	 * @return string
	 */
	public function generate_response( array $payload );
}
