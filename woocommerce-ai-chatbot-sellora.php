<?php
/**
 * Plugin Name: WooCommerce AI Chatbot & Shopping Assistant - Sellora AI
 * Plugin URI:  https://selloraii.com
 * Description: AI-powered WooCommerce chatbot and shopping assistant that helps customers find products, get instant answers, and boost sales with smart recommendations.
 * Version:     1.0.0
 * Author:      Sellora AI
 * Author URI:  https://selloraii.com
 * Text Domain: ai-woocommerce-assistant
 * Domain Path: /languages
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Tested PHP up to: 8.4
 * WC requires at least: 7.8
 * WC tested up to: 9.0
 */

defined( 'ABSPATH' ) || exit;

define( 'AI_WOO_ASSISTANT_VERSION', '1.0.0' );
define( 'AI_WOO_ASSISTANT_FILE', __FILE__ );
define( 'AI_WOO_ASSISTANT_PATH', plugin_dir_path( __FILE__ ) );
define( 'AI_WOO_ASSISTANT_URL', plugin_dir_url( __FILE__ ) );

require_once AI_WOO_ASSISTANT_PATH . 'includes/class-aiwoo-assistant-settings.php';
require_once AI_WOO_ASSISTANT_PATH . 'includes/class-aiwoo-assistant-chat-logger.php';
require_once AI_WOO_ASSISTANT_PATH . 'includes/class-aiwoo-assistant-admin-menu.php';
require_once AI_WOO_ASSISTANT_PATH . 'includes/woocommerce-handler.php';
require_once AI_WOO_ASSISTANT_PATH . 'includes/api-handler.php';
require_once AI_WOO_ASSISTANT_PATH . 'includes/class-aiwoo-assistant-plugin.php';

register_activation_hook( AI_WOO_ASSISTANT_FILE, array( 'AIWooAssistant\Chat_Logger', 'create_table' ) );

\AIWooAssistant\Plugin::instance();
