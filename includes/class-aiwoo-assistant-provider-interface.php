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
	 * Generate an assistant response (legacy prompt-based path).
	 *
	 * @param array<string, mixed> $payload Keys: instructions (string), input (string).
	 * @return string
	 */
	public function generate_response( array $payload );

	/**
	 * Generate a response using MCP-style tool/function calling.
	 *
	 * The provider sends the messages + tool definitions to the AI. If the AI
	 * responds with tool calls, $tool_executor is invoked, the results are fed
	 * back, and the loop continues until the AI produces a final text response
	 * or MAX_ROUNDS is reached.
	 *
	 * @param array<string, mixed> $payload       Keys: instructions (string), messages (array).
	 * @param array[]              $tools          Tool definitions from MCP_Tools::get_tool_definitions().
	 * @param callable             $tool_executor  fn(string $name, array $args): array
	 * @return string
	 * @throws \Exception On API or protocol error.
	 */
	public function generate_with_tools( array $payload, array $tools, callable $tool_executor );
}
