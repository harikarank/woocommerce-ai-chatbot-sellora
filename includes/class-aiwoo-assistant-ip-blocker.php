<?php
/**
 * IP blocklist — exact addresses and CIDR ranges (IPv4 + IPv6).
 *
 * @package AIWooAssistant
 */

namespace AIWooAssistant;

defined( 'ABSPATH' ) || exit;

final class IP_Blocker {

	const OPTION_KEY  = 'aiwoo_blocked_ips';
	const MAX_ENTRIES = 500;

	public function __construct() {
		add_action( 'admin_post_aiwoo_add_blocked_ip',    array( $this, 'handle_add' ) );
		add_action( 'admin_post_aiwoo_delete_blocked_ip', array( $this, 'handle_delete' ) );
	}

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Return true if the given IP matches any entry on the blocklist.
	 * Always returns false for invalid/empty $ip values.
	 */
	public function is_blocked( $ip ) {
		$ip = trim( (string) $ip );

		if ( '' === $ip || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		foreach ( $this->get_list() as $entry ) {
			if ( $this->ip_matches_entry( $ip, $entry ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return the current blocklist as a plain array of strings.
	 */
	public function get_list() {
		$list = get_option( self::OPTION_KEY, array() );

		return is_array( $list ) ? array_values( $list ) : array();
	}

	/**
	 * Retrieve the current visitor's IP address.
	 * Reads only REMOTE_ADDR (the TCP peer) — safe against header spoofing.
	 * If you run behind a trusted reverse proxy, configure proxy-aware IP
	 * resolution at the web-server level before PHP sees the request.
	 */
	public static function get_visitor_ip() {
		return isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '';
	}

	// -------------------------------------------------------------------------
	// Admin-post handlers
	// -------------------------------------------------------------------------

	public function handle_add() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ai-woocommerce-assistant' ) );
		}

		check_admin_referer( 'aiwoo_add_blocked_ip' );

		$redirect = admin_url( 'admin.php?page=sellora-ai-ip-blocklist' );
		$entry    = isset( $_POST['ip_entry'] )
			? trim( sanitize_text_field( wp_unslash( $_POST['ip_entry'] ) ) )
			: '';

		if ( '' === $entry ) {
			wp_safe_redirect( add_query_arg( 'aiwoo_ip_msg', 'empty', $redirect ) );
			exit;
		}

		$valid = $this->validate_entry( $entry );

		if ( is_wp_error( $valid ) ) {
			wp_safe_redirect( add_query_arg( 'aiwoo_ip_msg', 'invalid', $redirect ) );
			exit;
		}

		$list = $this->get_list();

		if ( count( $list ) >= self::MAX_ENTRIES ) {
			wp_safe_redirect( add_query_arg( 'aiwoo_ip_msg', 'limit', $redirect ) );
			exit;
		}

		if ( in_array( $entry, $list, true ) ) {
			wp_safe_redirect( add_query_arg( 'aiwoo_ip_msg', 'duplicate', $redirect ) );
			exit;
		}

		$list[] = $entry;
		update_option( self::OPTION_KEY, $list, false ); // autoload = false

		wp_safe_redirect( add_query_arg( 'aiwoo_ip_msg', 'added', $redirect ) );
		exit;
	}

	public function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'ai-woocommerce-assistant' ) );
		}

		check_admin_referer( 'aiwoo_delete_blocked_ip' );

		$entry = isset( $_POST['ip_entry'] )
			? trim( sanitize_text_field( wp_unslash( $_POST['ip_entry'] ) ) )
			: '';

		$list = $this->get_list();
		$list = array_values( array_filter( $list, fn( $e ) => $e !== $entry ) );
		update_option( self::OPTION_KEY, $list, false );

		$redirect = admin_url( 'admin.php?page=sellora-ai-ip-blocklist' );
		wp_safe_redirect( add_query_arg( 'aiwoo_ip_msg', 'deleted', $redirect ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Validation
	// -------------------------------------------------------------------------

	/**
	 * Returns true on success or WP_Error on failure.
	 *
	 * @param string $entry Exact IP or CIDR range.
	 * @return true|\WP_Error
	 */
	public function validate_entry( $entry ) {
		if ( str_contains( $entry, '/' ) ) {
			return $this->validate_cidr( $entry );
		}

		if ( ! filter_var( $entry, FILTER_VALIDATE_IP ) ) {
			return new \WP_Error( 'invalid_ip', 'Invalid IP address.' );
		}

		return true;
	}

	private function validate_cidr( $cidr ) {
		$parts = explode( '/', $cidr, 2 );

		if ( 2 !== count( $parts ) ) {
			return new \WP_Error( 'invalid_cidr', 'Invalid CIDR notation.' );
		}

		[ $subnet, $prefix ] = $parts;

		if ( ! filter_var( $subnet, FILTER_VALIDATE_IP ) ) {
			return new \WP_Error( 'invalid_subnet', 'Invalid subnet address in CIDR.' );
		}

		if ( ! ctype_digit( $prefix ) ) {
			return new \WP_Error( 'invalid_prefix', 'Prefix length must be a non-negative integer.' );
		}

		$prefix = (int) $prefix;
		$is_v6  = (bool) filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
		$max    = $is_v6 ? 128 : 32;

		if ( $prefix < 0 || $prefix > $max ) {
			/* translators: %d: maximum prefix length */
			return new \WP_Error( 'prefix_range', sprintf( 'Prefix must be 0–%d for this address family.', $max ) );
		}

		return true;
	}

	// -------------------------------------------------------------------------
	// Matching
	// -------------------------------------------------------------------------

	private function ip_matches_entry( $ip, $entry ) {
		if ( str_contains( $entry, '/' ) ) {
			return $this->ip_in_cidr( $ip, $entry );
		}

		return $ip === $entry;
	}

	private function ip_in_cidr( $ip, $cidr ) {
		[ $subnet, $prefix ] = explode( '/', $cidr, 2 );
		$prefix = (int) $prefix;

		$is_v6_ip     = (bool) filter_var( $ip,     FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
		$is_v6_subnet = (bool) filter_var( $subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );

		// Address families must match; never cross-compare.
		if ( $is_v6_ip !== $is_v6_subnet ) {
			return false;
		}

		return $is_v6_ip
			? $this->ipv6_in_cidr( $ip, $subnet, $prefix )
			: $this->ipv4_in_cidr( $ip, $subnet, $prefix );
	}

	private function ipv4_in_cidr( $ip, $subnet, $prefix ) {
		if ( $prefix < 0 || $prefix > 32 ) {
			return false;
		}

		$ip_long     = ip2long( $ip );
		$subnet_long = ip2long( $subnet );

		if ( false === $ip_long || false === $subnet_long ) {
			return false;
		}

		if ( 0 === $prefix ) {
			return true; // 0.0.0.0/0 — match all IPv4
		}

		$mask = (int) ( -1 << ( 32 - $prefix ) );

		return ( $ip_long & $mask ) === ( $subnet_long & $mask );
	}

	private function ipv6_in_cidr( $ip, $subnet, $prefix ) {
		if ( $prefix < 0 || $prefix > 128 ) {
			return false;
		}

		$ip_bin     = inet_pton( $ip );
		$subnet_bin = inet_pton( $subnet );

		if ( false === $ip_bin || false === $subnet_bin ) {
			return false;
		}

		if ( 0 === $prefix ) {
			return true; // ::/0 — match all IPv6
		}

		$full_bytes    = (int) floor( $prefix / 8 );
		$trailing_bits = $prefix % 8;

		// Compare complete bytes.
		if ( substr( $ip_bin, 0, $full_bytes ) !== substr( $subnet_bin, 0, $full_bytes ) ) {
			return false;
		}

		// Compare the partial byte (if prefix is not on a byte boundary).
		if ( $trailing_bits > 0 && $full_bytes < 16 ) {
			$mask = 0xFF & ( 0xFF << ( 8 - $trailing_bits ) );

			if ( ( ord( $ip_bin[ $full_bytes ] ) & $mask ) !== ( ord( $subnet_bin[ $full_bytes ] ) & $mask ) ) {
				return false;
			}
		}

		return true;
	}
}
