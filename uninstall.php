<?php
/**
 * Uninstall routine.
 *
 * @package AIWooAssistant
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ai_woo_assistant_settings' );
