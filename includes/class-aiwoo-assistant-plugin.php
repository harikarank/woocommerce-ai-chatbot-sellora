<?php
/**
 * Main plugin bootstrap.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	private static $instance = null;

	private $settings;

	private $catalog_service;

	private $chat_service;

	private $chat_logger;

	private $ajax_controller;

	private $admin_menu;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		Chat_Logger::maybe_create_table();

		$this->settings        = new Settings();
		$this->catalog_service = new Catalog_Service( $this->settings );
		$this->chat_service    = new Chat_Service( $this->settings, $this->catalog_service );
		$this->chat_logger     = new Chat_Logger();
		$this->ajax_controller = new Ajax_Controller( $this->settings, $this->chat_service, $this->chat_logger );
		$this->admin_menu      = new Admin_Menu( $this->settings, $this->chat_logger );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'before_woocommerce_init', array( $this, 'declare_wc_compatibility' ) );
		add_action( 'init', array( $this, 'register_enquiry_post_type' ) );
		add_action( 'admin_init', array( $this, 'maybe_warn_if_woocommerce_missing' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_widget_template' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'ai-woocommerce-assistant', false, dirname( plugin_basename( AI_WOO_ASSISTANT_FILE ) ) . '/languages' );
	}

	public function declare_wc_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', AI_WOO_ASSISTANT_FILE, true );
		}
	}

	public function maybe_warn_if_woocommerce_missing() {
		if ( class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action(
			'admin_notices',
			static function() {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}

				echo '<div class="notice notice-warning"><p>';
				echo esc_html__( 'Sellora AI is active, but WooCommerce is not detected. Product-aware responses will remain unavailable until WooCommerce is installed and activated.', 'ai-woocommerce-assistant' );
				echo '</p></div>';
			}
		);
	}

	public function register_enquiry_post_type() {
		register_post_type(
			'aiwoo_enquiry',
			array(
				'labels'              => array(
					'name' => __( 'Sellora AI Enquiries', 'ai-woocommerce-assistant' ),
				),
				'public'              => false,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'supports'            => array( 'title', 'editor' ),
				'capability_type'     => 'post',
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
			)
		);
	}

	public function enqueue_assets() {
		if ( is_admin() || ! $this->settings->is_enabled() ) {
			return;
		}

		wp_register_style(
			'ai-woo-assistant-widget',
			AI_WOO_ASSISTANT_URL . 'assets/css/style.css',
			array(),
			AI_WOO_ASSISTANT_VERSION
		);

		wp_register_script(
			'ai-woo-assistant-widget',
			AI_WOO_ASSISTANT_URL . 'assets/js/chat.js',
			array(),
			AI_WOO_ASSISTANT_VERSION,
			true
		);

		wp_enqueue_style( 'ai-woo-assistant-widget' );
		wp_enqueue_script( 'ai-woo-assistant-widget' );

		wp_localize_script(
			'ai-woo-assistant-widget',
			'AIWooAssistant',
			array(
				'ajaxUrl'        => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
				'nonce'          => wp_create_nonce( 'ai_woo_assistant_nonce' ),
				'actions'        => array(
					'chat'    => 'ai_woo_assistant_chat',
					'enquiry' => 'ai_woo_assistant_enquiry',
				),
				'strings'        => array(
					'title'           => __( 'Sellora AI Shopping Assistant', 'ai-woocommerce-assistant' ),
					'companyName'     => get_bloginfo( 'name' ),
					'subtitle'        => __( 'Ask about products, comparisons, and buying advice.', 'ai-woocommerce-assistant' ),
					'placeholder'     => __( 'Ask about products...', 'ai-woocommerce-assistant' ),
					'send'            => __( 'Send', 'ai-woocommerce-assistant' ),
					'open'            => __( 'Open Sellora AI chat assistant', 'ai-woocommerce-assistant' ),
					'close'           => __( 'Close chat assistant', 'ai-woocommerce-assistant' ),
					'typing'          => __( 'Sellora AI is thinking...', 'ai-woocommerce-assistant' ),
					'error'           => __( 'The assistant is temporarily unavailable. Please try again.', 'ai-woocommerce-assistant' ),
					'welcome'         => $this->settings->get( 'welcome_message' ),
					'emptyValidation' => __( 'Enter a message before sending.', 'ai-woocommerce-assistant' ),
					'enquiryIntro'    => __( 'I could not find a strong product match yet. Share your details and our team can help directly.', 'ai-woocommerce-assistant' ),
					'enquiryName'     => __( 'Name', 'ai-woocommerce-assistant' ),
					'enquiryEmail'    => __( 'Email', 'ai-woocommerce-assistant' ),
					'enquiryMessage'  => __( 'Message', 'ai-woocommerce-assistant' ),
					'enquirySubmit'   => __( 'Send enquiry', 'ai-woocommerce-assistant' ),
				),
				'ui'             => array(
					'primaryColor'  => $this->settings->get( 'primary_color' ),
					'iconUrl'       => $this->settings->get( 'chat_icon' ),
					'companyLogo'   => $this->settings->get( 'company_logo' ),
					'employeePhoto' => $this->settings->get( 'employee_photo' ),
				),
				'storeContext'   => array(
					'currencySymbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : get_option( 'woocommerce_currency', 'USD' ),
					'pageUrl'        => esc_url_raw( home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) ) ),
					'product'        => $this->catalog_service->get_current_product_context(),
				),
				'featureFlags'   => array(
					'hasWooCommerce' => class_exists( 'WooCommerce' ),
				),
				'widgetStateKey' => 'ai_woo_assistant_widget_state',
			)
		);
	}

	public function enqueue_admin_assets( $hook ) {
		if ( $hook !== $this->admin_menu->get_settings_hook() ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'ai-woo-assistant-admin',
			AI_WOO_ASSISTANT_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			AI_WOO_ASSISTANT_VERSION,
			true
		);
	}

	public function render_widget_template() {
		if ( is_admin() || ! $this->settings->is_enabled() ) {
			return;
		}

		$icon_url      = (string) $this->settings->get( 'chat_icon' );
		$company_name  = get_bloginfo( 'name' );
		$panel_title   = (string) $this->settings->get( 'panel_title' );
		$panel_subtitle = (string) $this->settings->get( 'panel_subtitle' );
		$company_logo  = (string) $this->settings->get( 'company_logo' );
		require AI_WOO_ASSISTANT_PATH . 'templates/chat-widget.php';
	}
}
