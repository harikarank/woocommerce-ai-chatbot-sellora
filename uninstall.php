<?php
/**
 * Uninstall routine — runs when the plugin is deleted from the WordPress admin.
 *
 * @package AIWooAssistant
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Remove plugin settings.
delete_option( 'ai_woo_assistant_settings' );

// Remove IP blocklist.
delete_option( 'aiwoo_blocked_ips' );

// Remove DB version flag.
delete_option( 'aiwoo_db_version' );

// Drop chat log table.
global $wpdb;
// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->prefix . 'aiwoo_chat_logs`' );

// Remove all stored enquiry posts and their meta.
$enquiry_ids = get_posts(
	array(
		'post_type'      => 'aiwoo_enquiry',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $enquiry_ids as $post_id ) {
	wp_delete_post( (int) $post_id, true );
}
