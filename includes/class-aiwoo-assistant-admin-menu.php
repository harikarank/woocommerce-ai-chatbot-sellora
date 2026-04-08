<?php
/**
 * Top-level admin menu and sub-page controller.
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class Admin_Menu {

	/** @var Settings */
	private $settings;

	/** @var Chat_Logger */
	private $chat_logger;

	/** @var IP_Blocker */
	private $ip_blocker;

	/** @var Quick_Reply_Service */
	private $quick_reply_service;

	/** Hook suffixes returned by add_submenu_page — used for asset enqueueing. */
	private $hook_chat_history   = '';
	private $hook_enquiries       = '';
	private $hook_ip_blocklist    = '';
	private $hook_quick_replies   = '';
	private $hook_top_requests    = '';
	private $hook_settings        = '';

	public function __construct( Settings $settings, Chat_Logger $chat_logger, IP_Blocker $ip_blocker, Quick_Reply_Service $quick_reply_service ) {
		$this->settings            = $settings;
		$this->chat_logger         = $chat_logger;
		$this->ip_blocker          = $ip_blocker;
		$this->quick_reply_service = $quick_reply_service;

		add_action( 'admin_menu', array( $this, 'register_menus' ) );
	}

	// -------------------------------------------------------------------------
	// Menu registration
	// -------------------------------------------------------------------------

	public function register_menus() {
		add_menu_page(
			__( 'Sellora AI', 'ai-woocommerce-assistant' ),
			__( 'Sellora AI', 'ai-woocommerce-assistant' ),
			'manage_options',
			'sellora-ai',
			array( $this, 'render_chat_history' ),
			'dashicons-format-chat',
			58
		);

		// First sub-menu replaces the auto-duplicate top-level entry.
		$this->hook_chat_history = (string) add_submenu_page(
			'sellora-ai',
			__( 'Chat History', 'ai-woocommerce-assistant' ),
			__( 'Chat History', 'ai-woocommerce-assistant' ),
			'manage_options',
			'sellora-ai',
			array( $this, 'render_chat_history' )
		);

		$this->hook_enquiries = (string) add_submenu_page(
			'sellora-ai',
			__( 'Enquiries', 'ai-woocommerce-assistant' ),
			__( 'Enquiries', 'ai-woocommerce-assistant' ),
			'manage_options',
			'sellora-ai-enquiries',
			array( $this, 'render_enquiries' )
		);

		$this->hook_ip_blocklist = (string) add_submenu_page(
			'sellora-ai',
			__( 'IP Blocklist', 'ai-woocommerce-assistant' ),
			__( 'IP Blocklist', 'ai-woocommerce-assistant' ),
			'manage_options',
			'sellora-ai-ip-blocklist',
			array( $this, 'render_ip_blocklist' )
		);

		$this->hook_quick_replies = (string) add_submenu_page(
			'sellora-ai',
			__( 'Quick Replies', 'ai-woocommerce-assistant' ),
			__( 'Quick Replies', 'ai-woocommerce-assistant' ),
			'manage_options',
			'sellora-ai-quick-replies',
			array( $this, 'render_quick_replies' )
		);

		$this->hook_top_requests = (string) add_submenu_page(
			'sellora-ai',
			__( 'Top Requests', 'ai-woocommerce-assistant' ),
			__( 'Top Requests', 'ai-woocommerce-assistant' ),
			'manage_options',
			'sellora-ai-top-requests',
			array( $this, 'render_top_requests' )
		);

		$this->hook_settings = (string) add_submenu_page(
			'sellora-ai',
			__( 'Sellora AI Settings', 'ai-woocommerce-assistant' ),
			__( 'Settings', 'ai-woocommerce-assistant' ),
			'manage_options',
			'ai-woo-assistant',
			array( $this->settings, 'render_settings_page' )
		);
	}

	// -------------------------------------------------------------------------
	// Hook suffix accessors (used by Plugin for asset enqueueing)
	// -------------------------------------------------------------------------

	public function get_settings_hook() {
		return $this->hook_settings;
	}

	public function get_all_hooks() {
		return array( $this->hook_chat_history, $this->hook_enquiries, $this->hook_ip_blocklist, $this->hook_quick_replies, $this->hook_top_requests, $this->hook_settings );
	}

	// -------------------------------------------------------------------------
	// Page renderers
	// -------------------------------------------------------------------------

	public function render_chat_history() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'ai-woocommerce-assistant' ) );
		}

		// Single-session detail view.
		$session_id = isset( $_GET['session'] ) ? sanitize_text_field( wp_unslash( $_GET['session'] ) ) : '';
		if ( '' !== $session_id ) {
			$messages = $this->chat_logger->get_session_messages( $session_id );
			$back_url = admin_url( 'admin.php?page=sellora-ai' );
			require AI_WOO_ASSISTANT_PATH . 'admin/chat-session-detail-page.php';
			return;
		}

		// List view with filters.
		$filters = array(
			'ip'        => isset( $_GET['filter_ip'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_ip'] ) ) : '',
			'name'      => isset( $_GET['filter_name'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_name'] ) ) : '',
			'date_from' => isset( $_GET['filter_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_from'] ) ) : '',
			'date_to'   => isset( $_GET['filter_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date_to'] ) ) : '',
		);

		$per_page     = 20;
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset       = ( $current_page - 1 ) * $per_page;
		$total        = $this->chat_logger->count_sessions( $filters );
		$sessions     = $this->chat_logger->get_sessions( $filters, $per_page, $offset );

		require AI_WOO_ASSISTANT_PATH . 'admin/chat-history-page.php';
	}

	public function render_ip_blocklist() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'ai-woocommerce-assistant' ) );
		}

		$ip_blocker = $this->ip_blocker;
		require AI_WOO_ASSISTANT_PATH . 'admin/ip-blocklist-page.php';
	}

	public function render_top_requests() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'ai-woocommerce-assistant' ) );
		}

		global $wpdb;
		$log_table = $wpdb->prefix . 'aiwoo_chat_logs';

		// ── Filters ───────────────────────────────────────────────────────────
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$search      = isset( $_GET['search'] )      ? sanitize_text_field( wp_unslash( $_GET['search'] ) )      : '';
		$filter_type = isset( $_GET['filter_type'] ) ? sanitize_key( $_GET['filter_type'] )                      : 'all';
		$filter_date = isset( $_GET['filter_date'] ) ? sanitize_key( $_GET['filter_date'] )                      : 'all';
		$current_page = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 );
		$export_csv  = isset( $_GET['export'] ) && 'csv' === $_GET['export'];
		// phpcs:enable

		$per_page = 20;

		// ── CSV export — validate nonce then stream and exit ──────────────────
		if ( $export_csv ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			check_admin_referer( 'aiwoo_export_top_requests' );
			$this->export_top_requests_csv( $log_table, $search, $filter_date );
			exit;
		}

		// ── Build WHERE (date filter) ─────────────────────────────────────────
		$where = $this->build_top_requests_where( $filter_date );

		// ── Build HAVING (search) ─────────────────────────────────────────────
		$having = '';
		if ( '' !== $search ) {
			$having = $wpdb->prepare(
				'HAVING LOWER(TRIM(user_message)) LIKE %s',
				'%' . $wpdb->esc_like( strtolower( $search ) ) . '%'
			);
		}

		// Fetch up to 500 aggregated rows (response-type filter applied in PHP).
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$all_rows = $wpdb->get_results(
			"SELECT LOWER(TRIM(user_message)) AS query,
			        COUNT(*) AS total,
			        MAX(ai_response) AS last_response
			 FROM `{$log_table}`
			 {$where}
			 GROUP BY LOWER(TRIM(user_message))
			 {$having}
			 ORDER BY total DESC
			 LIMIT 500"
		);

		if ( ! is_array( $all_rows ) ) {
			$all_rows = array();
		}

		// ── Response-type detection ───────────────────────────────────────────
		$qr_response_set = $this->quick_reply_service->get_response_set();

		if ( 'quick_reply' === $filter_type ) {
			$all_rows = array_values( array_filter(
				$all_rows,
				static function ( $r ) use ( $qr_response_set ) {
					return isset( $qr_response_set[ trim( $r->last_response ) ] );
				}
			) );
		} elseif ( 'ai' === $filter_type ) {
			$all_rows = array_values( array_filter(
				$all_rows,
				static function ( $r ) use ( $qr_response_set ) {
					return ! isset( $qr_response_set[ trim( $r->last_response ) ] );
				}
			) );
		}

		$total_rows = count( $all_rows );
		$offset     = ( $current_page - 1 ) * $per_page;
		$rows       = array_slice( $all_rows, $offset, $per_page );

		$quick_reply_service = $this->quick_reply_service;

		require AI_WOO_ASSISTANT_PATH . 'admin/top-requests-page.php';
	}

	/** Build a WHERE clause string for the date filter. */
	private function build_top_requests_where( $filter_date ) {
		if ( '7' === $filter_date ) {
			return "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
		}
		if ( '30' === $filter_date ) {
			return "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
		}
		return '';
	}

	/** Stream a CSV of all (unfiltered) top-request rows and exit. */
	private function export_top_requests_csv( $log_table, $search, $filter_date ) {
		global $wpdb;

		$where  = $this->build_top_requests_where( $filter_date );
		$having = '';
		if ( '' !== $search ) {
			$having = $wpdb->prepare(
				'HAVING LOWER(TRIM(user_message)) LIKE %s',
				'%' . $wpdb->esc_like( strtolower( $search ) ) . '%'
			);
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery
		$rows = $wpdb->get_results(
			"SELECT LOWER(TRIM(user_message)) AS query,
			        COUNT(*) AS total,
			        MAX(ai_response) AS last_response
			 FROM `{$log_table}`
			 {$where}
			 GROUP BY LOWER(TRIM(user_message))
			 {$having}
			 ORDER BY total DESC
			 LIMIT 5000"
		);

		if ( ! is_array( $rows ) ) {
			$rows = array();
		}

		$filename = 'sellora-top-requests-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'Query', 'Count', 'Response Preview' ) );

		foreach ( $rows as $row ) {
			fputcsv( $out, array(
				$this->sanitize_csv_cell( $row->query ),
				(int) $row->total,
				$this->sanitize_csv_cell( mb_substr( $row->last_response, 0, 200 ) ),
			) );
		}

		fclose( $out );
	}

	/**
	 * Prevent CSV formula injection (a.k.a. CSV injection / formula injection).
	 * Spreadsheet software treats cells starting with =, +, -, @ as formulas.
	 * Prefixing with a tab neutralises the cell without altering visible content.
	 *
	 * @param string $value Raw cell value.
	 * @return string Safe cell value.
	 */
	private function sanitize_csv_cell( $value ) {
		$value = (string) $value;
		if ( '' !== $value && in_array( $value[0], array( '=', '+', '-', '@', "\t", "\r" ), true ) ) {
			$value = "\t" . $value;
		}
		return $value;
	}

	public function render_quick_replies() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'ai-woocommerce-assistant' ) );
		}

		$quick_reply_service = $this->quick_reply_service;
		require AI_WOO_ASSISTANT_PATH . 'admin/quick-replies-page.php';
	}

	public function render_enquiries() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'ai-woocommerce-assistant' ) );
		}

		$filter_name  = isset( $_GET['filter_name'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_name'] ) ) : '';
		$filter_email = isset( $_GET['filter_email'] ) ? sanitize_email( wp_unslash( $_GET['filter_email'] ?? '' ) ) : '';
		$filter_date  = isset( $_GET['filter_date'] ) ? sanitize_text_field( wp_unslash( $_GET['filter_date'] ) ) : '';

		$per_page     = 20;
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;

		$query_args = array(
			'post_type'      => 'aiwoo_enquiry',
			'post_status'    => 'private',
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		$meta_conditions = array();

		if ( '' !== $filter_name ) {
			$meta_conditions[] = array(
				'key'     => '_aiwoo_name',
				'value'   => $filter_name,
				'compare' => 'LIKE',
			);
		}

		if ( '' !== $filter_email ) {
			$meta_conditions[] = array(
				'key'     => '_aiwoo_email',
				'value'   => $filter_email,
				'compare' => 'LIKE',
			);
		}

		if ( ! empty( $meta_conditions ) ) {
			$query_args['meta_query'] = array_merge(
				array( 'relation' => 'AND' ),
				$meta_conditions
			);
		}

		if ( '' !== $filter_date && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $filter_date ) ) {
			$query_args['date_query'] = array(
				array(
					'year'  => (int) substr( $filter_date, 0, 4 ),
					'month' => (int) substr( $filter_date, 5, 2 ),
					'day'   => (int) substr( $filter_date, 8, 2 ),
				),
			);
		}

		$query     = new \WP_Query( $query_args );
		$enquiries = $query->posts;
		$total     = $query->found_posts;

		require AI_WOO_ASSISTANT_PATH . 'admin/enquiries-page.php';
	}
}
