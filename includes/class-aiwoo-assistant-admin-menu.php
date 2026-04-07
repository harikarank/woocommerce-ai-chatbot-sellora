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

	/** Hook suffixes returned by add_submenu_page — used for asset enqueueing. */
	private $hook_chat_history = '';
	private $hook_enquiries    = '';
	private $hook_settings     = '';

	public function __construct( Settings $settings, Chat_Logger $chat_logger ) {
		$this->settings    = $settings;
		$this->chat_logger = $chat_logger;

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
		return array( $this->hook_chat_history, $this->hook_enquiries, $this->hook_settings );
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
