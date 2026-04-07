<?php
/**
 * API bootstrap — loads provider classes and exposes factory helpers.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

require_once AI_WOO_ASSISTANT_PATH . 'includes/class-aiwoo-assistant-provider-interface.php';
require_once AI_WOO_ASSISTANT_PATH . 'includes/class-aiwoo-assistant-openai-provider.php';
require_once AI_WOO_ASSISTANT_PATH . 'includes/class-aiwoo-assistant-claude-provider.php';
require_once AI_WOO_ASSISTANT_PATH . 'includes/class-aiwoo-assistant-gemini-provider.php';
require_once AI_WOO_ASSISTANT_PATH . 'includes/class-aiwoo-assistant-chat-service.php';
require_once AI_WOO_ASSISTANT_PATH . 'includes/class-aiwoo-assistant-ajax-controller.php';

/**
 * Build the configured AI provider instance.
 *
 * @param Settings|null $settings Settings object.
 * @return Provider_Interface
 */
function make_ai_provider( $settings = null ) {
	$settings = $settings instanceof Settings ? $settings : new Settings();
	$provider = (string) $settings->get( 'provider' );

	switch ( $provider ) {
		case 'claude':
			return new Claude_Provider( $settings );

		case 'gemini':
			return new Gemini_Provider( $settings );

		case 'openai':
		default:
			return new OpenAI_Provider( $settings );
	}
}

/**
 * Call the configured AI model.
 *
 * @param string               $message User or prompt message.
 * @param array<string, mixed> $context Additional context including instructions and settings.
 * @return string
 * @throws \Exception When provider request fails.
 */
function call_ai_model( $message, $context = array() ) {
	$settings     = isset( $context['settings'] ) && $context['settings'] instanceof Settings ? $context['settings'] : new Settings();
	$provider     = make_ai_provider( $settings );
	$instructions = isset( $context['instructions'] ) ? (string) $context['instructions'] : '';
	$input        = isset( $context['input'] ) ? (string) $context['input'] : (string) $message;

	return $provider->generate_response(
		array(
			'instructions' => $instructions,
			'input'        => $input,
		)
	);
}
