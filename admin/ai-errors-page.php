<?php
/**
 * AI Error Log admin page — backend only, never exposed to frontend users.
 *
 * Available variables (set by Admin_Menu::render_ai_errors):
 *   $errors        array
 *   $total         int
 *   $per_page      int
 *   $current_page  int
 *
 * @package AIWooAssistant
 */

defined( 'ABSPATH' ) || exit;

$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;
$base_url    = admin_url( 'admin.php?page=sellora-ai-errors' );

$context_labels = array(
	'ajax'   => array( 'label' => 'No Response', 'color' => '#dc2626', 'bg' => '#fee2e2' ),
	'mcp'    => array( 'label' => 'MCP Fallback', 'color' => '#b45309', 'bg' => '#fef3c7' ),
	'legacy' => array( 'label' => 'Legacy Fallback', 'color' => '#b45309', 'bg' => '#fef3c7' ),
);
?>
<div class="wrap">
	<h1 style="display:flex;align-items:center;gap:10px;">
		<img src="<?php echo esc_url( AI_WOO_ASSISTANT_URL . 'assets/img/logo.svg' ); ?>" alt="Sellora AI" style="height:28px;width:auto;" />
		<?php esc_html_e( 'AI Error Log', 'ai-woocommerce-assistant' ); ?>
	</h1>
	<p style="color:#6b7280;margin-top:4px;">
		<?php esc_html_e( 'Failed or degraded AI responses. Visible to admins only — users never see these details.', 'ai-woocommerce-assistant' ); ?>
	</p>

	<?php if ( empty( $errors ) ) : ?>
		<div style="margin-top:24px;padding:24px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:6px;text-align:center;color:#6b7280;">
			<?php esc_html_e( 'No AI errors recorded yet.', 'ai-woocommerce-assistant' ); ?>
		</div>
	<?php else : ?>
		<p style="margin-top:8px;color:#374151;">
			<?php
			printf(
				/* translators: 1: total error count */
				esc_html__( '%d error(s) recorded, latest first.', 'ai-woocommerce-assistant' ),
				esc_html( $total )
			);
			?>
		</p>

		<table class="wp-list-table widefat fixed striped" style="margin-top:12px;">
			<thead>
				<tr>
					<th style="width:160px;"><?php esc_html_e( 'Time', 'ai-woocommerce-assistant' ); ?></th>
					<th style="width:140px;"><?php esc_html_e( 'Type', 'ai-woocommerce-assistant' ); ?></th>
					<th style="width:110px;"><?php esc_html_e( 'IP', 'ai-woocommerce-assistant' ); ?></th>
					<th><?php esc_html_e( 'User Message', 'ai-woocommerce-assistant' ); ?></th>
					<th><?php esc_html_e( 'Error Detail', 'ai-woocommerce-assistant' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $errors as $error ) : ?>
					<?php
					$ctx    = isset( $context_labels[ $error->error_context ] ) ? $context_labels[ $error->error_context ] : array( 'label' => esc_html( $error->error_context ), 'color' => '#374151', 'bg' => '#f3f4f6' );
					$dt_raw = $error->created_at;
					try {
						$dt  = new DateTime( $dt_raw, new DateTimeZone( wp_timezone_string() ) );
						$dt_display = $dt->format( 'Y-m-d H:i:s' );
					} catch ( \Exception $ex ) {
						$dt_display = esc_html( $dt_raw );
					}
					?>
					<tr>
						<td style="font-size:12px;color:#374151;"><?php echo esc_html( $dt_display ); ?></td>
						<td>
							<span style="display:inline-block;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;color:<?php echo esc_attr( $ctx['color'] ); ?>;background:<?php echo esc_attr( $ctx['bg'] ); ?>;">
								<?php echo esc_html( $ctx['label'] ); ?>
							</span>
						</td>
						<td style="font-size:12px;font-family:monospace;">
							<?php echo esc_html( $error->ip_address ); ?>
						</td>
						<td style="font-size:13px;">
							<?php echo esc_html( mb_substr( $error->user_message, 0, 120 ) ); ?>
							<?php if ( mb_strlen( $error->user_message ) > 120 ) : ?>
								<span style="color:#9ca3af;">…</span>
							<?php endif; ?>
						</td>
						<td style="font-size:12px;color:#6b7280;word-break:break-word;">
							<?php echo esc_html( mb_substr( $error->error_message, 0, 300 ) ); ?>
							<?php if ( mb_strlen( $error->error_message ) > 300 ) : ?>
								<span style="color:#9ca3af;">…</span>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php if ( $total_pages > 1 ) : ?>
			<div style="margin-top:16px;display:flex;gap:6px;align-items:center;">
				<?php if ( $current_page > 1 ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1, $base_url ) ); ?>" class="button">&laquo; <?php esc_html_e( 'Prev', 'ai-woocommerce-assistant' ); ?></a>
				<?php endif; ?>
				<span style="color:#6b7280;font-size:13px;">
					<?php
					printf(
						/* translators: 1: current page, 2: total pages */
						esc_html__( 'Page %1$d of %2$d', 'ai-woocommerce-assistant' ),
						esc_html( $current_page ),
						esc_html( $total_pages )
					);
					?>
				</span>
				<?php if ( $current_page < $total_pages ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1, $base_url ) ); ?>" class="button"><?php esc_html_e( 'Next', 'ai-woocommerce-assistant' ); ?> &raquo;</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	<?php endif; ?>
</div>
