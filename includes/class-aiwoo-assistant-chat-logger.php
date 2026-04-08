<?php
/**
 * Chat session logger — stores each exchange in a custom DB table.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class Chat_Logger {

	const DB_VERSION    = '1';
	const DB_OPTION_KEY = 'aiwoo_db_version';

	/** @var string */
	private $table;

	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'aiwoo_chat_logs';
	}

	// -------------------------------------------------------------------------
	// Schema management
	// -------------------------------------------------------------------------

	/**
	 * Create or upgrade the DB table using dbDelta.
	 * Safe to call repeatedly — dbDelta only applies diffs.
	 */
	public static function create_table() {
		global $wpdb;

		$table           = $wpdb->prefix . 'aiwoo_chat_logs';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  session_id varchar(64) NOT NULL DEFAULT '',
  ip_address varchar(45) NOT NULL DEFAULT '',
  customer_name varchar(150) NOT NULL DEFAULT '',
  user_message text NOT NULL,
  ai_response text NOT NULL,
  created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY  (id),
  KEY idx_session_id (session_id),
  KEY idx_ip_address (ip_address(20)),
  KEY idx_customer_name (customer_name(50)),
  KEY idx_created_at (created_at)
) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::DB_OPTION_KEY, self::DB_VERSION );
	}

	/**
	 * Run on plugins_loaded — creates the table if the stored version is behind.
	 */
	public static function maybe_create_table() {
		if ( get_option( self::DB_OPTION_KEY, '' ) !== self::DB_VERSION ) {
			self::create_table();
		}
	}

	/**
	 * Drop the table on plugin uninstall.
	 */
	public static function drop_table() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'DROP TABLE IF EXISTS `' . $wpdb->prefix . 'aiwoo_chat_logs`' );
		delete_option( self::DB_OPTION_KEY );
	}

	// -------------------------------------------------------------------------
	// Write operations
	// -------------------------------------------------------------------------

	/**
	 * Log one user ↔ assistant exchange.
	 * Failures are silently swallowed so the chat response is never interrupted.
	 */
	public function log( $session_id, $ip_address, $user_message, $ai_response ) {
		global $wpdb;

		try {
			$wpdb->insert(
				$this->table,
				array(
					'session_id'    => mb_substr( sanitize_text_field( (string) $session_id ), 0, 64 ),
					'ip_address'    => mb_substr( sanitize_text_field( (string) $ip_address ), 0, 45 ),
					'customer_name' => '',
					'user_message'  => sanitize_textarea_field( (string) $user_message ),
					'ai_response'   => wp_strip_all_tags( (string) $ai_response ),
					'created_at'    => current_time( 'mysql' ),
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s' )
			);
		} catch ( \Exception $e ) {
			// Intentionally silent.
		}
	}

	/**
	 * Backfill customer name for all rows that share a session_id.
	 * Called when the user submits an enquiry form during the session.
	 */
	public function backfill_customer_name( $session_id, $name ) {
		global $wpdb;

		if ( '' === $session_id || '' === $name ) {
			return;
		}

		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->update(
				$this->table,
				array( 'customer_name' => mb_substr( sanitize_text_field( (string) $name ), 0, 150 ) ),
				array( 'session_id'    => mb_substr( sanitize_text_field( (string) $session_id ), 0, 64 ) ),
				array( '%s' ),
				array( '%s' )
			);
		} catch ( \Exception $e ) {
			// Intentionally silent.
		}
	}

	// -------------------------------------------------------------------------
	// Read operations
	// -------------------------------------------------------------------------

	/**
	 * Return distinct sessions matching $filters, latest-first.
	 *
	 * @param array $filters  Keys: ip, name, date_from, date_to  (all optional).
	 * @param int   $per_page
	 * @param int   $offset
	 * @return array
	 */
	public function get_sessions( array $filters = array(), $per_page = 20, $offset = 0 ) {
		global $wpdb;

		$where    = $this->build_where( $filters );
		$per_page = absint( $per_page ) ?: 20;
		$offset   = absint( $offset );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT session_id, ip_address, customer_name,
				        MIN(created_at) AS started_at,
				        MAX(created_at) AS last_at,
				        COUNT(*) AS message_count,
				        SUBSTRING( MIN(user_message), 1, 120 ) AS first_message
				 FROM `{$this->table}`
				 {$where}
				 GROUP BY session_id, ip_address, customer_name
				 ORDER BY last_at DESC
				 LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);
	}

	/**
	 * Count distinct sessions matching $filters.
	 */
	public function count_sessions( array $filters = array() ) {
		global $wpdb;

		$where = $this->build_where( $filters );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT COUNT(DISTINCT session_id) FROM `{$this->table}` {$where}"
		);
	}

	/**
	 * Return all messages for a single session, oldest first.
	 */
	public function get_session_messages( $session_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_message, ai_response, created_at
				 FROM `{$this->table}`
				 WHERE session_id = %s
				 ORDER BY created_at ASC",
				$session_id
			)
		);
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a safe WHERE clause from a filter array.
	 * Every user-supplied value passes through $wpdb->prepare.
	 */
	private function build_where( array $filters ) {
		global $wpdb;
		$conditions = array( '1=1' );

		if ( ! empty( $filters['ip'] ) ) {
			$conditions[] = $wpdb->prepare( 'ip_address = %s', sanitize_text_field( $filters['ip'] ) );
		}

		if ( ! empty( $filters['name'] ) ) {
			$conditions[] = $wpdb->prepare(
				'customer_name LIKE %s',
				'%' . $wpdb->esc_like( sanitize_text_field( $filters['name'] ) ) . '%'
			);
		}

		if ( ! empty( $filters['date_from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $filters['date_from'] ) ) {
			$conditions[] = $wpdb->prepare( 'DATE(created_at) >= %s', $filters['date_from'] );
		}

		if ( ! empty( $filters['date_to'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $filters['date_to'] ) ) {
			$conditions[] = $wpdb->prepare( 'DATE(created_at) <= %s', $filters['date_to'] );
		}

		return 'WHERE ' . implode( ' AND ', $conditions );
	}
}
