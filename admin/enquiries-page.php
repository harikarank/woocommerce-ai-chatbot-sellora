<?php
/**
 * Enquiries admin list page.
 *
 * Available variables (set by Admin_Menu::render_enquiries):
 *   $filter_name   string
 *   $filter_email  string
 *   $filter_date   string
 *   $enquiries     WP_Post[]
 *   $total         int
 *   $per_page      int
 *   $current_page  int
 *
 * @package AIWooAssistant
 */

defined( 'ABSPATH' ) || exit;

$total_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 1;
$base_url    = admin_url( 'admin.php?page=sellora-ai-enquiries' );

$filter_query = http_build_query(
	array_filter(
		array(
			'filter_name'  => $filter_name,
			'filter_email' => $filter_email,
			'filter_date'  => $filter_date,
		)
	)
);
?>
<div class="wrap">
	<h1 style="display:flex;align-items:center;gap:10px;">
		<img src="<?php echo esc_url( AI_WOO_ASSISTANT_URL . 'assets/img/logo.svg' ); ?>" alt="Sellora AI" style="height:28px;width:auto;" />
		<?php esc_html_e( 'Enquiries', 'ai-woocommerce-assistant' ); ?>
	</h1>

	<!-- Filter form -->
	<form method="get" action="<?php echo esc_url( $base_url ); ?>" style="margin:16px 0;">
		<input type="hidden" name="page" value="sellora-ai-enquiries" />
		<div style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
			<label>
				<span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Name', 'ai-woocommerce-assistant' ); ?></span>
				<input type="text" name="filter_name" value="<?php echo esc_attr( $filter_name ); ?>" placeholder="<?php esc_attr_e( 'Search name…', 'ai-woocommerce-assistant' ); ?>" class="regular-text" />
			</label>
			<label>
				<span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Email', 'ai-woocommerce-assistant' ); ?></span>
				<input type="email" name="filter_email" value="<?php echo esc_attr( $filter_email ); ?>" placeholder="<?php esc_attr_e( 'Search email…', 'ai-woocommerce-assistant' ); ?>" class="regular-text" />
			</label>
			<label>
				<span style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Date', 'ai-woocommerce-assistant' ); ?></span>
				<input type="date" name="filter_date" value="<?php echo esc_attr( $filter_date ); ?>" />
			</label>
			<div>
				<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Filter', 'ai-woocommerce-assistant' ); ?>" />
				<?php if ( $filter_name || $filter_email || $filter_date ) : ?>
					<a href="<?php echo esc_url( $base_url ); ?>" class="button" style="margin-left:6px;"><?php esc_html_e( 'Clear', 'ai-woocommerce-assistant' ); ?></a>
				<?php endif; ?>
			</div>
		</div>
	</form>

	<p style="color:#666;">
		<?php
		printf(
			/* translators: 1: number of enquiries */
			esc_html__( '%d enquiry/enquiries found.', 'ai-woocommerce-assistant' ),
			(int) $total
		);
		?>
	</p>

	<?php if ( empty( $enquiries ) ) : ?>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'No enquiries found.', 'ai-woocommerce-assistant' ); ?></p></div>
	<?php else : ?>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width:150px;"><?php esc_html_e( 'Date', 'ai-woocommerce-assistant' ); ?></th>
				<th style="width:140px;"><?php esc_html_e( 'Name', 'ai-woocommerce-assistant' ); ?></th>
				<th style="width:180px;"><?php esc_html_e( 'Email', 'ai-woocommerce-assistant' ); ?></th>
				<th style="width:120px;"><?php esc_html_e( 'Phone', 'ai-woocommerce-assistant' ); ?></th>
				<th><?php esc_html_e( 'Message', 'ai-woocommerce-assistant' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ( $enquiries as $enquiry ) : ?>
			<?php
			$enq_name    = (string) get_post_meta( $enquiry->ID, '_aiwoo_name', true );
			$enq_email   = (string) get_post_meta( $enquiry->ID, '_aiwoo_email', true );
			$enq_phone   = (string) get_post_meta( $enquiry->ID, '_aiwoo_phone', true );
			$enq_message = $enquiry->post_content;
			?>
			<tr>
				<td><?php echo esc_html( get_the_date( 'Y-m-d H:i', $enquiry ) ); ?></td>
				<td><?php echo esc_html( $enq_name ); ?></td>
				<td>
					<?php if ( '' !== $enq_email ) : ?>
						<a href="mailto:<?php echo esc_attr( $enq_email ); ?>"><?php echo esc_html( $enq_email ); ?></a>
					<?php endif; ?>
				</td>
				<td><?php echo '' !== $enq_phone ? esc_html( $enq_phone ) : '<span style="color:#aaa;">—</span>'; ?></td>
				<td><?php echo esc_html( wp_trim_words( $enq_message, 20 ) ); ?></td>
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
