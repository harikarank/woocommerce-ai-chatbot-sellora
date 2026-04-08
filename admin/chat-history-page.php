<?php
/**
 * Chat History admin list page.
 *
 * Available variables (set by Admin_Menu::render_chat_history):
 *   $filters       array
 *   $sessions      array
 *   $total         int
 *   $per_page      int
 *   $current_page  int
 *
 * @package AIWooAssistant
 */

defined( 'ABSPATH' ) || exit;

$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;
$base_url    = admin_url( 'admin.php?page=sellora-ai' );

/**
 * Build a pagination URL preserving active filters.
 */
$filter_query = http_build_query(
	array_filter(
		array(
			'filter_ip'        => $filters['ip'],
			'filter_name'      => $filters['name'],
			'filter_date_from' => $filters['date_from'],
			'filter_date_to'   => $filters['date_to'],
		)
	)
);
?>
<div class="wrap">
	<h1 style="display:flex;align-items:center;gap:10px;">
		<img src="<?php echo esc_url( AI_WOO_ASSISTANT_URL . 'assets/img/logo.svg' ); ?>" alt="Sellora AI" style="height:28px;width:auto;" />
		<?php esc_html_e( 'Chat History', 'ai-woocommerce-assistant' ); ?>
	</h1>

	<!-- Filter form -->
	<form method="get" action="<?php echo esc_url( $base_url ); ?>" style="margin:16px 0;">
		<input type="hidden" name="page" value="sellora-ai" />
		<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
			<label>
				<span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'IP Address', 'ai-woocommerce-assistant' ); ?></span>
				<input type="text" name="filter_ip" value="<?php echo esc_attr( $filters['ip'] ); ?>" placeholder="e.g. 192.168.1.1" class="regular-text" />
			</label>
			<label>
				<span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Customer Name', 'ai-woocommerce-assistant' ); ?></span>
				<input type="text" name="filter_name" value="<?php echo esc_attr( $filters['name'] ); ?>" placeholder="<?php esc_attr_e( 'Search name…', 'ai-woocommerce-assistant' ); ?>" class="regular-text" />
			</label>
			<label>
				<span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'From', 'ai-woocommerce-assistant' ); ?></span>
				<input type="date" name="filter_date_from" value="<?php echo esc_attr( $filters['date_from'] ); ?>" />
			</label>
			<label>
				<span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'To', 'ai-woocommerce-assistant' ); ?></span>
				<input type="date" name="filter_date_to" value="<?php echo esc_attr( $filters['date_to'] ); ?>" />
			</label>
			<div>
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Filter', 'ai-woocommerce-assistant' ); ?>" />
				<?php if ( array_filter( $filters ) ) : ?>
					<a href="<?php echo esc_url( $base_url ); ?>" class="button" style="margin-left:6px;"><?php esc_html_e( 'Clear', 'ai-woocommerce-assistant' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</form>

	<!-- Results info -->
	<p style="color:#666;">
		<?php
		printf(
			/* translators: 1: number of sessions */
			esc_html__( '%d session(s) found.', 'ai-woocommerce-assistant' ),
			(int) $total
		);
		?>
	</p>

	<?php if ( empty( $sessions ) ) : ?>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'No chat sessions found.', 'ai-woocommerce-assistant' ); ?></p></div>
	<?php else : ?>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width:160px;"><?php esc_html_e( 'Started', 'ai-woocommerce-assistant' ); ?></th>
				<th style="width:160px;"><?php esc_html_e( 'Last Message', 'ai-woocommerce-assistant' ); ?></th>
				<th style="width:130px;"><?php esc_html_e( 'IP Address', 'ai-woocommerce-assistant' ); ?></th>
				<th style="width:130px;"><?php esc_html_e( 'Customer', 'ai-woocommerce-assistant' ); ?></th>
				<th style="width:60px;text-align:center;"><?php esc_html_e( 'Msgs', 'ai-woocommerce-assistant' ); ?></th>
				<th><?php esc_html_e( 'First Message', 'ai-woocommerce-assistant' ); ?></th>
				<th style="width:80px;"><?php esc_html_e( 'Actions', 'ai-woocommerce-assistant' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $sessions as $session ) : ?>
			<?php
			$view_url = add_query_arg(
				array(
					'page'    => 'sellora-ai',
					'session' => rawurlencode( $session->session_id ),
				),
				admin_url( 'admin.php' )
			);
			?>
			<tr>
				<td><?php echo esc_html( $session->started_at ); ?></td>
				<td><?php echo esc_html( $session->last_at ); ?></td>
				<td><code><?php echo esc_html( $session->ip_address ); ?></code></td>
				<td>
					<?php if ( '' !== $session->customer_name ) : ?>
						<?php echo esc_html( $session->customer_name ); ?>
					<?php else : ?>
						<span style="color:#aaa;">—</span>
					<?php endif; ?>
				</td>
				<td style="text-align:center;"><?php echo (int) $session->message_count; ?></td>
				<td><?php echo esc_html( $session->first_message ); ?></td>
				<td>
					<a href="<?php echo esc_url( $view_url ); ?>" class="button button-small">
						<?php esc_html_e( 'View', 'ai-woocommerce-assistant' ); ?>
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<!-- Pagination -->
	<?php if ( $total_pages > 1 ) : ?>
	<div class="tablenav bottom" style="margin-top:12px;">
		<div class="tablenav-pages">
			<?php
			$page_links = paginate_links(
				array(
					'base'      => esc_url( add_query_arg( 'paged', '%#%', $base_url . ( $filter_query ? '&' . $filter_query : '' ) ) ),
					'format'    => '',
					'current'   => $current_page,
					'total'     => $total_pages,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				)
			);
			if ( $page_links ) {
				echo wp_kses_post( $page_links );
			}
			?>
		</div>
	</div>
	<?php endif; ?>

	<?php endif; ?>
</div>
