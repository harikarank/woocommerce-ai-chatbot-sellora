<?php
/**
 * IP Blocklist admin page.
 *
 * @package AIWooAssistant
 * @var \AIWooAssistant\IP_Blocker $ip_blocker
 */

defined( 'ABSPATH' ) || exit;

// Status notices.
$msg_map = array(
	'added'     => array( 'updated', __( 'IP / range added to blocklist.', 'ai-woocommerce-assistant' ) ),
	'deleted'   => array( 'updated', __( 'Entry removed from blocklist.', 'ai-woocommerce-assistant' ) ),
	'duplicate' => array( 'notice-warning', __( 'This IP / range is already on the blocklist.', 'ai-woocommerce-assistant' ) ),
	'invalid'   => array( 'error', __( 'Invalid entry. Enter a valid IPv4 or IPv6 address, or CIDR range (e.g. 192.168.1.0/24 or 2001:db8::/32).', 'ai-woocommerce-assistant' ) ),
	'empty'     => array( 'error', __( 'Please enter an IP address or range.', 'ai-woocommerce-assistant' ) ),
	'limit'     => array( 'error', __( 'Blocklist is full (500 entries maximum).', 'ai-woocommerce-assistant' ) ),
);

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$status_key = isset( $_GET['aiwoo_ip_msg'] ) ? sanitize_key( $_GET['aiwoo_ip_msg'] ) : '';
$blocked    = $ip_blocker->get_list();
?>
<div class="wrap">
	<h1 style="display:flex;align-items:center;gap:10px;">
		<img src="<?php echo esc_url( AI_WOO_ASSISTANT_URL . 'assets/img/logo.svg' ); ?>" alt="Sellora AI" style="height:28px;width:auto;" />
		<?php esc_html_e( 'IP Blocklist', 'ai-woocommerce-assistant' ); ?>
	</h1>
	<p><?php esc_html_e( 'Visitors whose IP address matches an entry below will not see the chat widget and their AJAX requests will be silently rejected. Entries are matched as exact addresses or CIDR ranges (IPv4 and IPv6 supported).', 'ai-woocommerce-assistant' ); ?></p>

	<?php if ( '' !== $status_key && isset( $msg_map[ $status_key ] ) ) : ?>
		<div class="notice <?php echo esc_attr( $msg_map[ $status_key ][0] ); ?> is-dismissible">
			<p><?php echo esc_html( $msg_map[ $status_key ][1] ); ?></p>
		</div>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Add entry', 'ai-woocommerce-assistant' ); ?></h2>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="aiwoo_add_blocked_ip" />
		<?php wp_nonce_field( 'aiwoo_add_blocked_ip' ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="aiwoo-ip-entry"><?php esc_html_e( 'IP address or CIDR range', 'ai-woocommerce-assistant' ); ?></label>
				</th>
				<td>
					<input
						type="text"
						id="aiwoo-ip-entry"
						name="ip_entry"
						class="regular-text code"
						placeholder="<?php esc_attr_e( 'e.g. 203.0.113.5 or 203.0.113.0/24', 'ai-woocommerce-assistant' ); ?>"
						autocomplete="off"
						spellcheck="false"
						maxlength="50"
					/>
					<p class="description">
						<?php esc_html_e( 'Exact IPv4, exact IPv6, IPv4 CIDR (e.g. 10.0.0.0/8), or IPv6 CIDR (e.g. 2001:db8::/32).', 'ai-woocommerce-assistant' ); ?>
					</p>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Block IP / Range', 'ai-woocommerce-assistant' ), 'primary', 'submit', false ); ?>
	</form>

	<hr />

	<h2>
		<?php esc_html_e( 'Blocked IPs and ranges', 'ai-woocommerce-assistant' ); ?>
		<span class="title-count">(<?php echo esc_html( count( $blocked ) ); ?> / 500)</span>
	</h2>

	<?php if ( empty( $blocked ) ) : ?>
		<p><em><?php esc_html_e( 'No entries yet. The blocklist is empty.', 'ai-woocommerce-assistant' ); ?></em></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped" style="max-width:620px;">
			<thead>
				<tr>
					<th scope="col" style="width:75%;"><?php esc_html_e( 'IP Address / CIDR Range', 'ai-woocommerce-assistant' ); ?></th>
					<th scope="col" style="width:25%;"><?php esc_html_e( 'Action', 'ai-woocommerce-assistant' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $blocked as $entry ) : ?>
					<tr>
						<td><code><?php echo esc_html( $entry ); ?></code></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
								<input type="hidden" name="action"   value="aiwoo_delete_blocked_ip" />
								<input type="hidden" name="ip_entry" value="<?php echo esc_attr( $entry ); ?>" />
								<?php wp_nonce_field( 'aiwoo_delete_blocked_ip' ); ?>
								<button
									type="submit"
									class="button button-small button-link-delete"
									onclick="return confirm('<?php echo esc_js( __( 'Remove this entry from the blocklist?', 'ai-woocommerce-assistant' ) ); ?>')"
								>
									<?php esc_html_e( 'Delete', 'ai-woocommerce-assistant' ); ?>
								</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>
</div>
