<?php
/**
 * Admin settings page — tab layout.
 *
 * @package AIWooAssistant
 * @var \AIWooAssistant\Settings $settings Passed from Settings::render_settings_page().
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Sellora AI — WooCommerce Chatbot & Shopping Assistant', 'ai-woocommerce-assistant' ); ?></h1>

	<nav class="nav-tab-wrapper" id="aiwoo-tab-nav" aria-label="<?php esc_attr_e( 'Settings sections', 'ai-woocommerce-assistant' ); ?>">
		<a href="#" class="nav-tab nav-tab-active" data-aiwoo-tab="general"><?php esc_html_e( 'General', 'ai-woocommerce-assistant' ); ?></a>
		<a href="#" class="nav-tab" data-aiwoo-tab="widget"><?php esc_html_e( 'Widget', 'ai-woocommerce-assistant' ); ?></a>
		<a href="#" class="nav-tab" data-aiwoo-tab="appearance"><?php esc_html_e( 'Appearance', 'ai-woocommerce-assistant' ); ?></a>
		<a href="#" class="nav-tab" data-aiwoo-tab="prompt"><?php esc_html_e( 'AI &amp; Prompt', 'ai-woocommerce-assistant' ); ?></a>
	</nav>

	<form action="options.php" method="post" id="aiwoo-settings-form">
		<?php settings_fields( 'ai_woo_assistant' ); ?>

		<?php /* ── GENERAL ────────────────────────────────────────────────── */ ?>
		<div id="aiwoo-tab-general" class="aiwoo-tab-pane">
			<p class="description" style="margin-top:16px;"><?php esc_html_e( 'Core plugin behaviour, AI provider, and chat limits.', 'ai-woocommerce-assistant' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ai-woo-assistant-enabled"><?php esc_html_e( 'Enable widget', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'enabled' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-max_message_length"><?php esc_html_e( 'Max message length', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'max_message_length' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-provider"><?php esc_html_e( 'AI provider', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'provider' ) ); ?></td>
				</tr>
				<?php /* ── OpenAI fields ── */ ?>
				<tr data-aiwoo-provider="openai">
					<th scope="row"><label for="ai-woo-assistant-openai_api_key"><?php esc_html_e( 'OpenAI API key', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'openai_api_key' ) ); ?></td>
				</tr>
				<tr data-aiwoo-provider="openai">
					<th scope="row"><label for="ai-woo-assistant-openai_model"><?php esc_html_e( 'OpenAI model', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'openai_model' ) ); ?></td>
				</tr>
				<?php /* ── Claude fields ── */ ?>
				<tr data-aiwoo-provider="claude">
					<th scope="row"><label for="ai-woo-assistant-claude_api_key"><?php esc_html_e( 'Anthropic API key', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'claude_api_key' ) ); ?></td>
				</tr>
				<tr data-aiwoo-provider="claude">
					<th scope="row"><label for="ai-woo-assistant-claude_model"><?php esc_html_e( 'Claude model', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'claude_model' ) ); ?></td>
				</tr>
				<?php /* ── Gemini fields ── */ ?>
				<tr data-aiwoo-provider="gemini">
					<th scope="row"><label for="ai-woo-assistant-gemini_api_key"><?php esc_html_e( 'Google AI Studio API key', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'gemini_api_key' ) ); ?></td>
				</tr>
				<tr data-aiwoo-provider="gemini">
					<th scope="row"><label for="ai-woo-assistant-gemini_model"><?php esc_html_e( 'Gemini model', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'gemini_model' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-temperature"><?php esc_html_e( 'Response temperature', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'temperature' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-max_context_products"><?php esc_html_e( 'Catalog products in context', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'max_context_products' ) ); ?></td>
				</tr>
			</table>
		</div>

		<?php /* ── WIDGET ─────────────────────────────────────────────────── */ ?>
		<div id="aiwoo-tab-widget" class="aiwoo-tab-pane" hidden>
			<p class="description" style="margin-top:16px;"><?php esc_html_e( 'Panel header copy, branding images, and the opening message.', 'ai-woocommerce-assistant' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ai-woo-assistant-panel_title"><?php esc_html_e( 'Panel header title', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'panel_title' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-panel_subtitle"><?php esc_html_e( 'Panel header subtitle', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'panel_subtitle' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-company_logo"><?php esc_html_e( 'Panel header logo', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'company_logo' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-employee_photo"><?php esc_html_e( 'Assistant avatar photo', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'employee_photo' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-chat_icon"><?php esc_html_e( 'Chat launcher icon', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'chat_icon' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-welcome_message"><?php esc_html_e( 'Welcome message', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'welcome_message' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-enquiry_title"><?php esc_html_e( 'Enquiry form title', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'enquiry_title' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-enquiry_content"><?php esc_html_e( 'Enquiry form intro text', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'enquiry_content' ) ); ?></td>
				</tr>
			</table>
		</div>

		<?php /* ── APPEARANCE ─────────────────────────────────────────────── */ ?>
		<div id="aiwoo-tab-appearance" class="aiwoo-tab-pane" hidden>
			<p class="description" style="margin-top:16px;"><?php esc_html_e( 'Override individual colours. Leave any field blank to use the built-in default.', 'ai-woocommerce-assistant' ); ?></p>

			<h3><?php esc_html_e( 'Accent', 'ai-woocommerce-assistant' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Controls the launcher button, scrollbar thumb, product card accents, and enquiry form border.', 'ai-woocommerce-assistant' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ai-woo-assistant-primary_color"><?php esc_html_e( 'Accent color', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'primary_color' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_primary_hover"><?php esc_html_e( 'Accent hover color', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_primary_hover' ) ); ?></td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Panel &amp; Layout', 'ai-woocommerce-assistant' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_surface"><?php esc_html_e( 'Widget background', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_surface' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_bg"><?php esc_html_e( 'Messages area background', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_bg' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_border"><?php esc_html_e( 'Border color', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_border' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_text"><?php esc_html_e( 'Body text', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_text' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_text_soft"><?php esc_html_e( 'Secondary text', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_text_soft' ) ); ?></td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Shape', 'ai-woocommerce-assistant' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ai-woo-assistant-border_radius"><?php esc_html_e( 'Corner radius (px)', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'border_radius' ) ); ?></td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Panel Borders', 'ai-woocommerce-assistant' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_panel_border"><?php esc_html_e( 'Panel border color', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_panel_border' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_header_border_bottom"><?php esc_html_e( 'Header bottom border color', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_header_border_bottom' ) ); ?></td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Header', 'ai-woocommerce-assistant' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_header_bg"><?php esc_html_e( 'Header background', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_header_bg' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_header_text"><?php esc_html_e( 'Header text &amp; icons', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_header_text' ) ); ?></td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Messages', 'ai-woocommerce-assistant' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_user_bubble_bg"><?php esc_html_e( 'User message background', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_user_bubble_bg' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_user_bubble_text"><?php esc_html_e( 'User message text', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_user_bubble_text' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_agent_bubble_bg"><?php esc_html_e( 'Agent message background', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_agent_bubble_bg' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_agent_bubble_text"><?php esc_html_e( 'Agent message text', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_agent_bubble_text' ) ); ?></td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Input &amp; Send Button', 'ai-woocommerce-assistant' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_form_bg"><?php esc_html_e( 'Form area background', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_form_bg' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_input_bg"><?php esc_html_e( 'Input background', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_input_bg' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_input_text"><?php esc_html_e( 'Input text', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_input_text' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_send_bg"><?php esc_html_e( 'Send button background', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_send_bg' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_send_text"><?php esc_html_e( 'Send button text', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_send_text' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_send_hover_bg"><?php esc_html_e( 'Send button hover background', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_send_hover_bg' ) ); ?></td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'Typing Indicator &amp; Character Counter', 'ai-woocommerce-assistant' ); ?></h3>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_loading_bg"><?php esc_html_e( 'Typing indicator background', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_loading_bg' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_loading_text"><?php esc_html_e( 'Typing indicator text color', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_loading_text' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_counter_bg"><?php esc_html_e( 'Character counter background', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_counter_bg' ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="ai-woo-assistant-color_counter_text"><?php esc_html_e( 'Character counter text color', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'color_counter_text' ) ); ?></td>
				</tr>
			</table>
		</div>

		<?php /* ── AI & PROMPT ────────────────────────────────────────────── */ ?>
		<div id="aiwoo-tab-prompt" class="aiwoo-tab-pane" hidden>
			<p class="description" style="margin-top:16px;"><?php esc_html_e( 'Additional instructions appended to the system prompt. The base prompt is built-in and handles store context, currency, and anti-hallucination rules.', 'ai-woocommerce-assistant' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="ai-woo-assistant-system_prompt"><?php esc_html_e( 'Additional system prompt', 'ai-woocommerce-assistant' ); ?></label></th>
					<td><?php $settings->render_field( array( 'key' => 'system_prompt' ) ); ?></td>
				</tr>
			</table>
		</div>

		<?php submit_button(); ?>
	</form>
</div>
