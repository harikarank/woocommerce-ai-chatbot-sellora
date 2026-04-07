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
		'system_prompt'        => "You are a professional customer support assistant for an eCommerce company.\nYour role is to help users find products and answer queries naturally.\n\nRules:\n- Always respond like a human sales assistant.\n- Be polite, concise, and helpful.\n- Recommend products when relevant.\n- If no product available, ask for more details.\n- Do not hallucinate products.",
		'welcome_message'      => 'Hello. I can help you explore products, compare options, and answer store questions.',
		'panel_title'          => '',
		'panel_subtitle'       => 'Ask about products, comparisons, and buying advice.',
		'company_logo'         => '',
		'employee_photo'       => '',
		'primary_color'        => '#102a43',
		'chat_icon'            => '',
		'max_context_products' => 4,
		'temperature'          => 0.4,
	);

	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_settings_page' ) );
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
			'company_logo'         => __( 'Panel header logo', 'ai-woocommerce-assistant' ),
			'employee_photo'       => __( 'Assistant avatar photo', 'ai-woocommerce-assistant' ),
			'primary_color'        => __( 'Widget accent color', 'ai-woocommerce-assistant' ),
			'chat_icon'            => __( 'Chat launcher icon', 'ai-woocommerce-assistant' ),
			'welcome_message'      => __( 'Welcome message', 'ai-woocommerce-assistant' ),
			'system_prompt'        => __( 'Additional system prompt', 'ai-woocommerce-assistant' ),
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

		$settings['enabled']              = ! empty( $input['enabled'] ) ? 'yes' : 'no';
		$settings['provider']             = 'openai';
		$settings['openai_api_key']       = isset( $input['openai_api_key'] ) ? sanitize_text_field( wp_unslash( $input['openai_api_key'] ) ) : '';
		$settings['openai_model']         = isset( $input['openai_model'] ) ? $this->normalize_openai_model( wp_unslash( $input['openai_model'] ) ) : $this->defaults['openai_model'];
		$settings['system_prompt']        = isset( $input['system_prompt'] ) ? sanitize_textarea_field( wp_unslash( $input['system_prompt'] ) ) : '';
		$settings['welcome_message']      = isset( $input['welcome_message'] ) ? sanitize_textarea_field( wp_unslash( $input['welcome_message'] ) ) : $this->defaults['welcome_message'];
		$settings['panel_title']          = isset( $input['panel_title'] ) ? sanitize_text_field( wp_unslash( $input['panel_title'] ) ) : '';
		$settings['panel_subtitle']       = isset( $input['panel_subtitle'] ) ? sanitize_text_field( wp_unslash( $input['panel_subtitle'] ) ) : $this->defaults['panel_subtitle'];
		$settings['company_logo']         = isset( $input['company_logo'] ) ? esc_url_raw( wp_unslash( $input['company_logo'] ) ) : '';
		$settings['employee_photo']       = isset( $input['employee_photo'] ) ? esc_url_raw( wp_unslash( $input['employee_photo'] ) ) : '';
		$settings['primary_color']        = isset( $input['primary_color'] ) ? sanitize_hex_color( $input['primary_color'] ) : $this->defaults['primary_color'];
		$settings['chat_icon']            = isset( $input['chat_icon'] ) ? esc_url_raw( wp_unslash( $input['chat_icon'] ) ) : '';
		$settings['max_context_products'] = isset( $input['max_context_products'] ) ? max( 1, min( 10, absint( $input['max_context_products'] ) ) ) : $this->defaults['max_context_products'];
		$settings['temperature']          = isset( $input['temperature'] ) ? max( 0, min( 1, (float) $input['temperature'] ) ) : $this->defaults['temperature'];

		if ( empty( $settings['primary_color'] ) ) {
			$settings['primary_color'] = $this->defaults['primary_color'];
		}

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
					<option value="openai" <?php selected( 'openai', $value ); ?>><?php esc_html_e( 'OpenAI', 'ai-woocommerce-assistant' ); ?></option>
				</select>
				<p class="description"><?php esc_html_e( 'The architecture is modular for future providers, but only OpenAI is enabled in this release.', 'ai-woocommerce-assistant' ); ?></p>
				<?php
				break;

			case 'openai_api_key':
				printf(
					'<input type="password" class="regular-text" id="%1$s" name="%2$s" value="%3$s" autocomplete="off" />',
					esc_attr( $field_id ),
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
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
					esc_attr( $this->defaults['primary_color'] )
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
		}
	}

	public function render_settings_page() {
		require AI_WOO_ASSISTANT_PATH . 'admin/settings-page.php';
	}

	private function get_supported_openai_models() {
		return array(
			'gpt-5.4-mini',
			'gpt-5.4',
			'gpt-4.1-mini',
		);
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
