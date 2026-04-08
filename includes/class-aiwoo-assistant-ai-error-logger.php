<?php
/**
 * AI error logger — stores failed or degraded AI responses in a custom DB table.
 *
 * Captures three contexts:
 *  - 'ajax'    : unhandled exception that reached the AJAX controller (user saw generic error).
 *  - 'mcp'     : exception inside the MCP tool-calling path (user saw fallback product cards).
 *  - 'legacy'  : exception inside the legacy prompt path (user saw fallback product cards).
 *
 * Frontend users never see this data; it is admin-only.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class AI_Error_Logger {

	const DB_VERSION    = '1';
	const DB_OPTION_KEY = 'aiwoo_ai_error_log_db_version';

	/** @var string */
	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'aiwoo_ai_error_logs';
	}

	// -------------------------------------------------------------------------
	// Schema management
	// -------------------------------------------------------------------------

	public static function create_table() {
		global $wpdb;

		$table           = $wpdb->prefix . 'aiwoo_ai_error_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  session_id varchar(64) NOT NULL DEFAULT '',
  ip_address varchar(45) NOT NULL DEFAULT '',
  user_message text NOT NULL,
  error_context varchar(20) NOT NULL DEFAULT '',
  error_message text NOT NULL,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY idx_created_at (created_at),
  KEY idx_error_context (error_context),
  KEY idx_session_id (session_id)
) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::DB_OPTION_KEY, self::DB_VERSION );
	}

	public static function maybe_create_table() {
		if ( get_option( self::DB_OPTION_KEY, '' ) !== self::DB_VERSION ) {
			self::create_table();
		}
	}

	public static function drop_table() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->prefix . 'aiwoo_ai_error_logs`' );
		delete_option( self::DB_OPTION_KEY );
	}

	// -------------------------------------------------------------------------
	// Write
	// -------------------------------------------------------------------------

	/**
	 * Record one AI failure.
	 * Silently swallowed so it never interrupts the request.
	 *
	 * @param string $session_id
	 * @param string $ip_address
	 * @param string $user_message   The message that triggered the failure.
	 * @param string $error_context  'ajax' | 'mcp' | 'legacy'
	 * @param string $error_message  Exception or error detail (never sent to frontend).
	 */
	public function log( $session_id, $ip_address, $user_message, $error_context, $error_message ) {
		global $wpdb;

		try {
			$wpdb->insert(
				$this->table,
				array(
					'session_id'    => mb_substr( sanitize_text_field( (string) $session_id ), 0, 64 ),
					'ip_address'    => mb_substr( sanitize_text_field( (string) $ip_address ), 0, 45 ),
					'user_message'  => sanitize_textarea_field( (string) $user_message ),
					'error_context' => mb_substr( sanitize_key( (string) $error_context ), 0, 20 ),
					'error_message' => sanitize_textarea_field( (string) $error_message ),
					'created_at'    => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		} catch ( \Exception $e ) {
			// Intentionally silent.
		}
	}

	// -------------------------------------------------------------------------
	// Read
	// -------------------------------------------------------------------------

	/**
	 * Return errors, latest first.
	 *
	 * @param int $per_page
	 * @param int $offset
	 * @return array
	 */
	public function get_errors( $per_page = 30, $offset = 0 ) {
		global $wpdb;

		$per_page = absint( $per_page ) ?: 30;
		$offset   = absint( $offset );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, session_id, ip_address, user_message, error_context, error_message, created_at
				 FROM `{$this->table}`
				 ORDER BY created_at DESC
				 LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);
	}

	/**
	 * Total number of logged errors.
	 *
	 * @return int
	 */
	public function count_errors() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$this->table}`" );
	}
}
