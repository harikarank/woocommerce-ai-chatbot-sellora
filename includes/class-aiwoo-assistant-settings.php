<?php
/**
 * Settings management.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class Settings {
	private $option_name = 'ai_woo_assistant_settings';

	private $defaults = array(
		'enabled'              => 'yes',
		'provider'             => 'openai',
		'openai_api_key'       => '',
		'openai_model'         => 'gpt-5.4-mini',
//		'system_prompt'        => "You are a professional customer support assistant for an eCommerce company.\nYour role is to help users find products and answer queries naturally.\n\nRules:\n- Always respond like a human sales assistant.\n- Be polite, concise, and helpful.\n- Recommend products when relevant.\n- If no product available, ask for more details.\n- Do not hallucinate products.",
		'system_prompt' 	   => "You are a friendly and professional customer support assistant for an eCommerce store.\n\nYour goal is to help customers naturally - like a real human sales assistant.\n\nBehavior rules:\n- Start with a simple, warm greeting.\n- Do NOT recommend products immediately unless the user clearly asks or shows buying intent.\n- First understand what the user needs by asking 1–2 relevant questions.\n- Keep responses short, natural, and conversational (avoid scripted or robotic tone).\n- Only recommend products after understanding the requirement.\n- When recommending, show 2–4 relevant items max with short explanations.\n- If unsure, ask clarifying questions instead of guessing.\n- Never overwhelm the user with too much information.\n- Avoid mentioning internal details like pricing currency unless asked.\n\nConversation style:\n- Sound human, not like a bot.\n- Avoid long paragraphs.\n- Use simple, friendly language.\n\nExamples:\n\nUser: Hi\nAssistant: Hi! 😊 How can I help you today?",
		'welcome_message'      => 'Hello! How can I assist you today?',
		'panel_title'          => '',
		'panel_subtitle'       => 'What are you looking for today…',
		'chat_placeholder'     => 'Ask about products…',
		'company_logo'         => '',
		'employee_photo'       => '',
		'primary_color'        => '#9a162d',
		'chat_icon'            => '',
		'max_context_products' => 4,
		'temperature'          => 0.4,
		// Claude (Anthropic)
		'claude_api_key'          => '',
		'claude_model'            => 'claude-sonnet-4-6',
		// Gemini (Google)
		'gemini_api_key'          => '',
		'gemini_model'            => 'gemini-2.5-flash',
		// Input limits
		'max_message_length'      => 200,
		// Colour overrides — empty string = use CSS variable default
		'color_primary_hover'     => '',
		'color_surface'           => '',
		'color_bg'                => '',
		'color_border'            => '',
		'color_text'              => '',
		'color_text_soft'         => '',
		'color_header_bg'         => '',
		'color_header_text'       => '',
		'color_user_bubble_bg'    => '',
		'color_user_bubble_text'  => '',
		'color_agent_bubble_bg'   => '',
		'color_agent_bubble_text' => '',
		'color_send_bg'           => '',
		'color_send_text'         => '',
		'color_send_hover_bg'     => '',
		'color_input_bg'          => '',
		'color_input_text'        => '',
		'color_loading_bg'        => '',
		'color_loading_text'      => '',
		'color_counter_bg'        => '',
		'color_counter_text'      => '',
		'color_panel_border'      => '',
		'color_header_border_bottom' => '',
		'color_form_bg'           => '',
		'color_avatar_assistant_bg' => '',
		'color_avatar_user_bg'    => '',
		// Widget shape
		'border_radius'           => 0,
		// Enquiry form
		'enquiry_title'           => '',
		'enquiry_content'         => 'Let us know a bit more, and we\'ll suggest what fits best.',
		// Product card display toggles (all off by default)
		'card_show_price'         => 'no',
		'card_show_stock'         => 'no',
		'card_show_image'         => 'no',
		'card_show_desc'          => 'no',
		'card_show_view_link'     => 'no',
		// No-match fallback text
		'no_match_text'           => "We couldn't find an exact match. Please share more details.",
		// Form border colour
		'color_form_border'       => '',
		// MCP / AI Intelligence
		'enable_mcp'              => 'no',
		'mcp_max_products'        => 5,
		'enable_personalization'  => 'no',
		'enable_upsell'           => 'no',
	);

	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		// Admin menu registration is handled by Admin_Menu class.
	}

	public function all() {
		$stored = get_option( $this->option_name, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		$settings                 = wp_parse_args( $stored, $this->defaults );
		$settings['openai_model'] = $this->normalize_openai_model( $settings['openai_model'] ?? '' );

		return $settings;
	}

	public function get( $key ) {
		$settings = $this->all();

		return $settings[ $key ] ?? null;
	}

	public function is_enabled() {
		return 'yes' === $this->get( 'enabled' );
	}

	public function register_settings() {
		register_setting(
			'ai_woo_assistant',
			$this->option_name,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->defaults,
			)
		);

		add_settings_section(
			'ai_woo_assistant_general',
			__( 'Sellora AI Settings', 'ai-woocommerce-assistant' ),
			static function() {
				echo '<p>' . esc_html__( 'Configure your Sellora AI chatbot, provider, widget appearance, and response behaviour.', 'ai-woocommerce-assistant' ) . '</p>';
			},
			'ai-woo-assistant'
		);

		$fields = array(
			'enabled'              => __( 'Enable widget', 'ai-woocommerce-assistant' ),
			'provider'             => __( 'AI provider', 'ai-woocommerce-assistant' ),
			'openai_api_key'       => __( 'OpenAI API key', 'ai-woocommerce-assistant' ),
			'openai_model'         => __( 'OpenAI model', 'ai-woocommerce-assistant' ),
			'temperature'          => __( 'Response temperature', 'ai-woocommerce-assistant' ),
			'max_context_products' => __( 'Catalog products in context', 'ai-woocommerce-assistant' ),
			'panel_title'          => __( 'Panel header title', 'ai-woocommerce-assistant' ),
			'panel_subtitle'       => __( 'Panel header subtitle', 'ai-woocommerce-assistant' ),
			'chat_placeholder'     => __( 'Chat input placeholder', 'ai-woocommerce-assistant' ),
			'company_logo'         => __( 'Panel header logo', 'ai-woocommerce-assistant' ),
			'employee_photo'       => __( 'Assistant avatar photo', 'ai-woocommerce-assistant' ),
			'primary_color'        => __( 'Widget accent color', 'ai-woocommerce-assistant' ),
			'chat_icon'            => __( 'Chat launcher icon', 'ai-woocommerce-assistant' ),
			'welcome_message'      => __( 'Welcome message', 'ai-woocommerce-assistant' ),
			'system_prompt'        => __( 'Additional system prompt', 'ai-woocommerce-assistant' ),
			'color_panel_border'         => __( 'Panel border color', 'ai-woocommerce-assistant' ),
			'color_header_border_bottom' => __( 'Header bottom border color', 'ai-woocommerce-assistant' ),
			'color_loading_bg'           => __( 'Typing indicator background', 'ai-woocommerce-assistant' ),
			'color_loading_text'         => __( 'Typing indicator text color', 'ai-woocommerce-assistant' ),
			'color_counter_bg'           => __( 'Character counter background', 'ai-woocommerce-assistant' ),
			'color_counter_text'         => __( 'Character counter text color', 'ai-woocommerce-assistant' ),
			'color_avatar_assistant_bg'  => __( 'Assistant avatar background', 'ai-woocommerce-assistant' ),
			'color_avatar_user_bg'       => __( 'User avatar background', 'ai-woocommerce-assistant' ),
		);

		foreach ( $fields as $field_key => $label ) {
			add_settings_field(
				$field_key,
				$label,
				array( $this, 'render_field' ),
				'ai-woo-assistant',
				'ai_woo_assistant_general',
				array(
					'key' => $field_key,
				)
			);
		}
	}

	public function sanitize_settings( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$settings = $this->all();

		$settings['enabled']  = ! empty( $input['enabled'] ) ? 'yes' : 'no';

		$allowed_providers    = array( 'openai', 'claude', 'gemini' );
		$settings['provider'] = isset( $input['provider'] ) && in_array( $input['provider'], $allowed_providers, true )
			? $input['provider']
			: 'openai';

		// OpenAI — blank input preserves existing key.
		$openai_key_input = isset( $input['openai_api_key'] ) ? sanitize_text_field( wp_unslash( $input['openai_api_key'] ) ) : '';
		if ( '' !== $openai_key_input ) {
			$settings['openai_api_key'] = $openai_key_input;
		}
		$settings['openai_model'] = isset( $input['openai_model'] ) ? $this->normalize_openai_model( wp_unslash( $input['openai_model'] ) ) : $this->defaults['openai_model'];

		// Claude — blank input preserves existing key.
		$claude_key_input = isset( $input['claude_api_key'] ) ? sanitize_text_field( wp_unslash( $input['claude_api_key'] ) ) : '';
		if ( '' !== $claude_key_input ) {
			$settings['claude_api_key'] = $claude_key_input;
		}
		$settings['claude_model'] = isset( $input['claude_model'] ) ? $this->normalize_claude_model( wp_unslash( $input['claude_model'] ) ) : $this->defaults['claude_model'];

		// Gemini — blank input preserves existing key.
		$gemini_key_input = isset( $input['gemini_api_key'] ) ? sanitize_text_field( wp_unslash( $input['gemini_api_key'] ) ) : '';
		if ( '' !== $gemini_key_input ) {
			$settings['gemini_api_key'] = $gemini_key_input;
		}
		$settings['gemini_model'] = isset( $input['gemini_model'] ) ? $this->normalize_gemini_model( wp_unslash( $input['gemini_model'] ) ) : $this->defaults['gemini_model'];
		$settings['system_prompt']        = isset( $input['system_prompt'] ) ? sanitize_textarea_field( wp_unslash( $input['system_prompt'] ) ) : '';
		$settings['welcome_message']      = isset( $input['welcome_message'] ) ? sanitize_textarea_field( wp_unslash( $input['welcome_message'] ) ) : $this->defaults['welcome_message'];
		$settings['panel_title']          = isset( $input['panel_title'] ) ? sanitize_text_field( wp_unslash( $input['panel_title'] ) ) : '';
		$settings['panel_subtitle']       = isset( $input['panel_subtitle'] ) ? sanitize_text_field( wp_unslash( $input['panel_subtitle'] ) ) : $this->defaults['panel_subtitle'];
		$settings['chat_placeholder']     = isset( $input['chat_placeholder'] ) ? sanitize_text_field( wp_unslash( $input['chat_placeholder'] ) ) : $this->defaults['chat_placeholder'];
		$settings['company_logo']         = isset( $input['company_logo'] ) ? esc_url_raw( wp_unslash( $input['company_logo'] ) ) : '';
		$settings['employee_photo']       = isset( $input['employee_photo'] ) ? esc_url_raw( wp_unslash( $input['employee_photo'] ) ) : '';
		$settings['primary_color']        = isset( $input['primary_color'] ) ? sanitize_hex_color( $input['primary_color'] ) : $this->defaults['primary_color'];
		$settings['chat_icon']            = isset( $input['chat_icon'] ) ? esc_url_raw( wp_unslash( $input['chat_icon'] ) ) : '';
		$settings['max_context_products'] = isset( $input['max_context_products'] ) ? max( 1, min( 10, absint( $input['max_context_products'] ) ) ) : $this->defaults['max_context_products'];
		$settings['temperature']          = isset( $input['temperature'] ) ? max( 0, min( 1, (float) $input['temperature'] ) ) : $this->defaults['temperature'];

		if ( empty( $settings['primary_color'] ) ) {
			$settings['primary_color'] = $this->defaults['primary_color'];
		}

		$settings['max_message_length'] = isset( $input['max_message_length'] )
			? max( 10, min( 1000, absint( $input['max_message_length'] ) ) )
			: $this->defaults['max_message_length'];

		// Product card toggles.
		$settings['card_show_price']     = ! empty( $input['card_show_price'] )     ? 'yes' : 'no';
		$settings['card_show_stock']     = ! empty( $input['card_show_stock'] )     ? 'yes' : 'no';
		$settings['card_show_image']     = ! empty( $input['card_show_image'] )     ? 'yes' : 'no';
		$settings['card_show_desc']      = ! empty( $input['card_show_desc'] )      ? 'yes' : 'no';
		$settings['card_show_view_link'] = ! empty( $input['card_show_view_link'] ) ? 'yes' : 'no';

		$settings['no_match_text'] = isset( $input['no_match_text'] ) && '' !== trim( $input['no_match_text'] )
			? sanitize_textarea_field( wp_unslash( $input['no_match_text'] ) )
			: $this->defaults['no_match_text'];

		$color_keys = array(
			'color_primary_hover', 'color_surface', 'color_bg', 'color_border',
			'color_text', 'color_text_soft', 'color_header_bg', 'color_header_text',
			'color_user_bubble_bg', 'color_user_bubble_text', 'color_agent_bubble_bg',
			'color_agent_bubble_text', 'color_send_bg', 'color_send_text',
			'color_send_hover_bg', 'color_input_bg', 'color_input_text',
			'color_loading_bg', 'color_loading_text',
			'color_counter_bg', 'color_counter_text',
			'color_panel_border', 'color_header_border_bottom',
			'color_form_bg', 'color_form_border',
			'color_avatar_assistant_bg', 'color_avatar_user_bg',
		);

		$settings['border_radius'] = isset( $input['border_radius'] )
			? max( 0, min( 24, absint( $input['border_radius'] ) ) )
			: $this->defaults['border_radius'];

		$settings['enquiry_title']   = isset( $input['enquiry_title'] ) ? sanitize_text_field( wp_unslash( $input['enquiry_title'] ) ) : '';
		$settings['enquiry_content'] = isset( $input['enquiry_content'] ) ? sanitize_textarea_field( wp_unslash( $input['enquiry_content'] ) ) : $this->defaults['enquiry_content'];

		foreach ( $color_keys as $key ) {
			$settings[ $key ] = isset( $input[ $key ] )
				? ( sanitize_hex_color( $input[ $key ] ) ?? '' )
				: '';
		}

		// MCP / AI Intelligence settings.
		$settings['enable_mcp']             = ! empty( $input['enable_mcp'] ) ? 'yes' : 'no';
		$settings['mcp_max_products']        = isset( $input['mcp_max_products'] )
			? max( 1, min( 10, absint( $input['mcp_max_products'] ) ) )
			: $this->defaults['mcp_max_products'];
		$settings['enable_personalization']  = ! empty( $input['enable_personalization'] ) ? 'yes' : 'no';
		$settings['enable_upsell']           = ! empty( $input['enable_upsell'] ) ? 'yes' : 'no';

		return $settings;
	}

	public function register_settings_page() {
		add_options_page(
			__( 'Sellora AI', 'ai-woocommerce-assistant' ),
			__( 'Sellora AI', 'ai-woocommerce-assistant' ),
			'manage_options',
			'ai-woo-assistant',
			array( $this, 'render_settings_page' )
		);
	}

	public function render_field( $args ) {
		$key      = $args['key'];
		$value    = $this->get( $key );
		$name     = $this->option_name . '[' . $key . ']';
		$field_id = 'ai-woo-assistant-' . $key;

		switch ( $key ) {
			case 'enabled':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> %4$s</label>',
					esc_attr( $field_id ),
					esc_attr( $name ),
					checked( 'yes', $value, false ),
					esc_html__( 'Show the assistant widget on the frontend.', 'ai-woocommerce-assistant' )
				);
				break;

			case 'provider':
				?>
				<select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>">
					<option value="openai"  <?php selected( 'openai',  $value ); ?>><?php esc_html_e( 'OpenAI',           'ai-woocommerce-assistant' ); ?></option>
					<option value="claude"  <?php selected( 'claude',  $value ); ?>><?php esc_html_e( 'Claude (Anthropic)', 'ai-woocommerce-assistant' ); ?></option>
					<option value="gemini"  <?php selected( 'gemini',  $value ); ?>><?php esc_html_e( 'Gemini (Google)',    'ai-woocommerce-assistant' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'Select the AI provider. Enter the corresponding API key in the fields below.', 'ai-woocommerce-assistant' ); ?></p>
				<?php
				break;

			case 'claude_api_key':
				$masked = $this->mask_api_key( (string) $value );
				printf(
					'<input type="password" class="regular-text" id="%1$s" name="%2$s" value="" autocomplete="off" placeholder="%3$s" />',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_attr( $masked )
				);
				if ( '' !== (string) $value ) {
					echo '<p class="description">' . esc_html__( 'Key is saved. Leave blank to keep the current key, or enter a new one to replace it.', 'ai-woocommerce-assistant' ) . '</p>';
				}
				echo '<p class="description"><a href="https://console.anthropic.com/account/keys" target="_blank" rel="noopener">'
					. esc_html__( 'Get your Anthropic API key →', 'ai-woocommerce-assistant' )
					. '</a></p>';
				break;

			case 'claude_model':
				?>
				<select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>">
					<option value="claude-sonnet-4-6"          <?php selected( 'claude-sonnet-4-6',          $value ); ?>><?php esc_html_e( 'Claude Sonnet 4.6 (recommended)', 'ai-woocommerce-assistant' ); ?></option>
					<option value="claude-opus-4-6"            <?php selected( 'claude-opus-4-6',            $value ); ?>><?php esc_html_e( 'Claude Opus 4.6 (most capable)',   'ai-woocommerce-assistant' ); ?></option>
					<option value="claude-haiku-4-5-20251001"  <?php selected( 'claude-haiku-4-5-20251001',  $value ); ?>><?php esc_html_e( 'Claude Haiku 4.5 (fastest)',      'ai-woocommerce-assistant' ); ?></option>
				</select>
				<?php
				break;

			case 'gemini_api_key':
				$masked = $this->mask_api_key( (string) $value );
				printf(
					'<input type="password" class="regular-text" id="%1$s" name="%2$s" value="" autocomplete="off" placeholder="%3$s" />',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_attr( $masked )
				);
				if ( '' !== (string) $value ) {
					echo '<p class="description">' . esc_html__( 'Key is saved. Leave blank to keep the current key, or enter a new one to replace it.', 'ai-woocommerce-assistant' ) . '</p>';
				}
				echo '<p class="description"><a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">'
					. esc_html__( 'Get your Google AI Studio API key →', 'ai-woocommerce-assistant' )
					. '</a></p>';
				break;

			case 'gemini_model':
				?>
				<select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>">
					<option value="gemini-2.5-flash"      <?php selected( 'gemini-2.5-flash',      $value ); ?>><?php esc_html_e( 'Gemini 2.5 Flash (recommended)',  'ai-woocommerce-assistant' ); ?></option>
					<option value="gemini-2.5-pro"        <?php selected( 'gemini-2.5-pro',        $value ); ?>><?php esc_html_e( 'Gemini 2.5 Pro (most capable)',   'ai-woocommerce-assistant' ); ?></option>
					<option value="gemini-2.5-flash-lite" <?php selected( 'gemini-2.5-flash-lite', $value ); ?>><?php esc_html_e( 'Gemini 2.5 Flash-Lite (fastest)', 'ai-woocommerce-assistant' ); ?></option>
					<option value="gemini-2.0-flash-lite" <?php selected( 'gemini-2.0-flash-lite', $value ); ?>><?php esc_html_e( 'Gemini 2.0 Flash-Lite',           'ai-woocommerce-assistant' ); ?></option>
					<option value="gemini-1.5-pro"        <?php selected( 'gemini-1.5-pro',        $value ); ?>><?php esc_html_e( 'Gemini 1.5 Pro',                  'ai-woocommerce-assistant' ); ?></option>
					<option value="gemini-1.5-flash"      <?php selected( 'gemini-1.5-flash',      $value ); ?>><?php esc_html_e( 'Gemini 1.5 Flash',                'ai-woocommerce-assistant' ); ?></option>
				</select>
				<?php
				break;

			case 'openai_api_key':
				$masked = $this->mask_api_key( (string) $value );
				printf(
					'<input type="password" class="regular-text" id="%1$s" name="%2$s" value="" autocomplete="off" placeholder="%3$s" />',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_attr( $masked )
				);
				if ( '' !== (string) $value ) {
					echo '<p class="description">' . esc_html__( 'Key is saved. Leave blank to keep the current key, or enter a new one to replace it.', 'ai-woocommerce-assistant' ) . '</p>';
				}
				break;

			case 'openai_model':
				?>
				<select id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>">
					<option value="gpt-5.4-mini" <?php selected( 'gpt-5.4-mini', $value ); ?>><?php esc_html_e( 'gpt-5.4-mini', 'ai-woocommerce-assistant' ); ?></option>
					<option value="gpt-5.4" <?php selected( 'gpt-5.4', $value ); ?>><?php esc_html_e( 'gpt-5.4', 'ai-woocommerce-assistant' ); ?></option>
					<option value="gpt-4.1-mini" <?php selected( 'gpt-4.1-mini', $value ); ?>><?php esc_html_e( 'gpt-4.1-mini', 'ai-woocommerce-assistant' ); ?></option>
				</select>
				<?php
				break;

			case 'temperature':
				printf(
					'<input type="number" step="0.1" min="0" max="1" id="%1$s" name="%2$s" value="%3$s" />',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
				break;

			case 'max_context_products':
				printf(
					'<input type="number" min="1" max="10" id="%1$s" name="%2$s" value="%3$s" />',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
				break;

			case 'panel_title':
				printf(
					'<input type="text" class="regular-text" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" />',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( get_bloginfo( 'name' ) )
				);
				echo '<p class="description">' . esc_html__( 'Leave blank to use the site name.', 'ai-woocommerce-assistant' ) . '</p>';
				break;

			case 'panel_subtitle':
			case 'chat_placeholder':
				printf(
					'<input type="text" class="regular-text" id="%1$s" name="%2$s" value="%3$s" />',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
				break;

			case 'company_logo':
				?>
				<div class="aiwoo-icon-picker">
					<input type="text" class="regular-text aiwoo-media-url" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" />
					<button type="button" class="button aiwoo-upload-button" data-target="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Upload logo', 'ai-woocommerce-assistant' ); ?></button>
				</div>
				<?php if ( ! empty( $value ) ) : ?>
					<p><img src="<?php echo esc_url( (string) $value ); ?>" alt="" style="width:48px;height:48px;border-radius:50%;object-fit:cover;margin-top:6px;" /></p>
				<?php endif; ?>
				<?php
				break;

			case 'employee_photo':
				?>
				<div class="aiwoo-icon-picker">
					<input type="text" class="regular-text aiwoo-media-url" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" />
					<button type="button" class="button aiwoo-upload-button" data-target="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Upload photo', 'ai-woocommerce-assistant' ); ?></button>
				</div>
				<?php if ( ! empty( $value ) ) : ?>
					<p><img src="<?php echo esc_url( (string) $value ); ?>" alt="" style="width:48px;height:48px;border-radius:50%;object-fit:cover;margin-top:6px;" /></p>
				<?php endif; ?>
				<p class="description"><?php esc_html_e( 'Shown as a circular avatar next to each assistant reply.', 'ai-woocommerce-assistant' ); ?></p>
				<?php
				break;

			case 'primary_color':
				printf(
					'<input type="text" class="aiwoo-color-field" id="%1$s" name="%2$s" value="%3$s" data-default-color="%4$s" />',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( '#9a162d' )
				);
				break;

			case 'welcome_message':
				printf(
					'<textarea class="large-text" rows="4" id="%1$s" name="%2$s">%3$s</textarea>',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_textarea( (string) $value )
				);
				break;

			case 'chat_icon':
				?>
				<div class="aiwoo-icon-picker">
					<input type="text" class="regular-text aiwoo-media-url" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" />
					<button type="button" class="button aiwoo-upload-button" data-target="<?php echo esc_attr( $field_id ); ?>"><?php esc_html_e( 'Upload icon', 'ai-woocommerce-assistant' ); ?></button>
				</div>
				<?php if ( ! empty( $value ) ) : ?>
					<p><img src="<?php echo esc_url( (string) $value ); ?>" alt="" style="max-width:48px;height:auto;margin-top:6px;" /></p>
				<?php endif; ?>
				<?php
				break;

			case 'system_prompt':
				printf(
					'<textarea class="large-text code" rows="7" id="%1$s" name="%2$s">%3$s</textarea>',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_textarea( (string) $value )
				);
				break;

			case 'max_message_length':
				printf(
					'<input type="number" min="10" max="1000" id="%1$s" name="%2$s" value="%3$s" />',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
				echo '<p class="description">' . esc_html__( 'Characters allowed per chat message (10–1000). Requests exceeding this limit will be auto-blocked by IP.', 'ai-woocommerce-assistant' ) . '</p>';
				break;

			case 'border_radius':
				printf(
					'<input type="number" min="0" max="24" id="%1$s" name="%2$s" value="%3$s" /> px',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
				echo '<p class="description">' . esc_html__( '0 = sharp corners, 24 = fully rounded. Controls the panel, message bubbles, and enquiry box.', 'ai-woocommerce-assistant' ) . '</p>';
				break;

			case 'enquiry_title':
				printf(
					'<input type="text" class="regular-text" id="%1$s" name="%2$s" value="%3$s" placeholder="%4$s" />',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr__( 'e.g. Contact Us', 'ai-woocommerce-assistant' )
				);
				echo '<p class="description">' . esc_html__( 'Leave blank to hide the title above the enquiry form.', 'ai-woocommerce-assistant' ) . '</p>';
				break;

			case 'enquiry_content':
				printf(
					'<textarea class="large-text" rows="3" id="%1$s" name="%2$s">%3$s</textarea>',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_textarea( (string) $value )
				);
				echo '<p class="description">' . esc_html__( 'Intro text shown above the enquiry form fields. Leave blank to hide.', 'ai-woocommerce-assistant' ) . '</p>';
				break;

			case 'card_show_price':
			case 'card_show_stock':
			case 'card_show_image':
			case 'card_show_desc':
			case 'card_show_view_link':
				printf(
					'<label><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s /> %4$s</label>',
					esc_attr( $field_id ),
					esc_attr( $name ),
					checked( 'yes', $value, false ),
					esc_html__( 'Yes', 'ai-woocommerce-assistant' )
				);
				break;

			case 'no_match_text':
				printf(
					'<textarea class="large-text" rows="2" id="%1$s" name="%2$s">%3$s</textarea>',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_textarea( (string) $value )
				);
				echo '<p class="description">' . esc_html__( 'Shown when no matching products are found.', 'ai-woocommerce-assistant' ) . '</p>';
				break;

			default:
				// Generic colour field — handles all color_* keys.
				if ( str_starts_with( $key, 'color_' ) ) {
					$placeholder_map = array(
						'color_primary_hover'     => '#7d1125',
						'color_surface'           => '#ffffff',
						'color_bg'                => '#f7f7f7',
						'color_border'            => '#e0e0e0',
						'color_text'              => '#111111',
						'color_text_soft'         => '#666666',
						'color_header_bg'         => __( '(same as accent)', 'ai-woocommerce-assistant' ),
						'color_header_text'       => '#ffffff',
						'color_user_bubble_bg'    => __( '(same as accent)', 'ai-woocommerce-assistant' ),
						'color_user_bubble_text'  => '#ffffff',
						'color_agent_bubble_bg'   => '#f0f0f0',
						'color_agent_bubble_text' => '#111111',
						'color_send_bg'           => __( '(same as accent)', 'ai-woocommerce-assistant' ),
						'color_send_text'         => '#ffffff',
						'color_send_hover_bg'     => __( '(same as accent hover)', 'ai-woocommerce-assistant' ),
						'color_input_bg'          => '#ffffff',
						'color_input_text'        => '#111111',
						'color_loading_bg'        => __( '(same as surface)', 'ai-woocommerce-assistant' ),
						'color_loading_text'      => __( '(same as soft text)', 'ai-woocommerce-assistant' ),
						'color_counter_bg'        => __( '(same as surface)', 'ai-woocommerce-assistant' ),
						'color_counter_text'      => __( '(same as soft text)', 'ai-woocommerce-assistant' ),
						'color_avatar_assistant_bg' => '#ffffff',
						'color_avatar_user_bg'    => '#ababab',
						'color_panel_border'      => '#ededed',
						'color_header_border_bottom' => __( '(same as accent hover)', 'ai-woocommerce-assistant' ),
						'color_form_bg'           => '#ffffff',
						'color_form_border'       => '#000000',
					);

					$default_hint = $placeholder_map[ $key ] ?? '';
					printf(
						'<input type="text" class="aiwoo-color-field" id="%1$s" name="%2$s" value="%3$s" data-default-color="%4$s" />',
						esc_attr( $field_id ),
						esc_attr( $name ),
						esc_attr( (string) $value ),
						esc_attr( str_starts_with( $default_hint, '#' ) ? $default_hint : '' )
					);
					if ( '' !== $default_hint && ! str_starts_with( $default_hint, '#' ) ) {
						echo '<p class="description">' . esc_html__( 'Default:', 'ai-woocommerce-assistant' ) . ' ' . esc_html( $default_hint ) . '</p>';
					}
				}
				break;
		}
	}

	public function render_settings_page() {
		$settings = $this; // Available as $settings inside the template.
		require AI_WOO_ASSISTANT_PATH . 'admin/settings-page.php';
	}

	private function get_supported_openai_models() {
		return array(
			'gpt-5.4-mini',
			'gpt-5.4',
			'gpt-4.1-mini',
		);
	}

	/**
	 * Mask an API key for display — shows first 4 and last 4 characters.
	 * Returns empty string for empty/short keys.
	 */
	private function mask_api_key( $key ) {
		$key = (string) $key;
		$len = strlen( $key );

		if ( $len < 8 ) {
			return '' !== $key ? str_repeat( '*', $len ) : '';
		}

		return substr( $key, 0, 4 ) . str_repeat( '*', $len - 8 ) . substr( $key, -4 );
	}

	private function normalize_claude_model( $model ) {
		$model     = sanitize_text_field( (string) $model );
		$supported = array(
			'claude-opus-4-6',
			'claude-sonnet-4-6',
			'claude-haiku-4-5-20251001',
		);

		return in_array( $model, $supported, true ) ? $model : $this->defaults['claude_model'];
	}

	private function normalize_gemini_model( $model ) {
		$model     = sanitize_text_field( (string) $model );
		$supported = array(
			'gemini-2.5-flash',
			'gemini-2.5-pro',
			'gemini-2.5-flash-lite',
			'gemini-2.0-flash-lite',
			'gemini-1.5-pro',
			'gemini-1.5-flash',
		);

		return in_array( $model, $supported, true ) ? $model : $this->defaults['gemini_model'];
	}

	private function normalize_openai_model( $model ) {
		$model           = sanitize_text_field( (string) $model );
		$legacy_aliases  = array(
			'gpt-5.1-mini' => 'gpt-5.4-mini',
			'gpt-5.1'      => 'gpt-5.4',
		);
		$normalized      = $legacy_aliases[ $model ] ?? $model;
		$supported       = $this->get_supported_openai_models();

		if ( in_array( $normalized, $supported, true ) ) {
			return $normalized;
		}

		return $this->defaults['openai_model'];
	}
}
