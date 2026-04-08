<?php
/**
 * Top Requests analytics page.
 *
 * @package AIWooAssistant
 * @var object[]                              $rows                Paginated aggregated rows.
 * @var int                                   $total_rows          Total rows after filtering.
 * @var int                                   $current_page        Current pagination page.
 * @var int                                   $per_page            Rows per page (20).
 * @var string                                $search              Active search term.
 * @var string                                $filter_type         Active type filter (all|quick_reply|ai).
 * @var string                                $filter_date         Active date filter (all|7|30).
 * @var array<string, true>                   $qr_response_set     Hash-set of QR response strings.
 * @var \AIWooAssistant\Quick_Reply_Service   $quick_reply_service
 */

defined( 'ABSPATH' ) || exit;

$msg_map = array(
	'saved'     => array( 'updated',       __( 'Quick reply saved successfully.',           'ai-woocommerce-assistant' ) ),
	'duplicate' => array( 'notice-warning', __( 'A rule with one of those keywords already exists.', 'ai-woocommerce-assistant' ) ),
	'invalid'   => array( 'notice-error',  __( 'Keywords and response are required.',       'ai-woocommerce-assistant' ) ),
);

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$status_key   = isset( $_GET['aiwoo_tr_msg'] ) ? sanitize_key( $_GET['aiwoo_tr_msg'] ) : '';
$total_pages  = (int) ceil( $total_rows / $per_page );
$base_url     = admin_url( 'admin.php?page=sellora-ai-top-requests' );
$export_nonce = wp_create_nonce( 'aiwoo_export_top_requests' );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Sellora AI — Top Requests', 'ai-woocommerce-assistant' ); ?></h1>
	<p><?php esc_html_e( 'See which messages users send most often. Convert high-frequency AI responses into Quick Reply rules to reduce AI usage.', 'ai-woocommerce-assistant' ); ?></p>

	<?php if ( '' !== $status_key && isset( $msg_map[ $status_key ] ) ) : ?>
		<div class="notice <?php echo esc_attr( $msg_map[ $status_key ][0] ); ?> is-dismissible">
			<p><?php echo esc_html( $msg_map[ $status_key ][1] ); ?></p>
		</div>
	<?php endif; ?>

	<?php /* ── Filters ──────────────────────────────────────────────────── */ ?>
	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin:16px 0 10px; display:flex; flex-wrap:wrap; gap:8px; align-items:center;">
		<input type="hidden" name="page" value="sellora-ai-top-requests" />

		<input
			type="search"
			name="search"
			value="<?php echo esc_attr( $search ); ?>"
			placeholder="<?php esc_attr_e( 'Search queries…', 'ai-woocommerce-assistant' ); ?>"
			style="min-width:220px;"
		/>

		<select name="filter_type">
			<option value="all"         <?php selected( $filter_type, 'all' ); ?>><?php esc_html_e( 'All types',    'ai-woocommerce-assistant' ); ?></option>
			<option value="quick_reply" <?php selected( $filter_type, 'quick_reply' ); ?>><?php esc_html_e( 'Quick Reply', 'ai-woocommerce-assistant' ); ?></option>
			<option value="ai"          <?php selected( $filter_type, 'ai' ); ?>><?php esc_html_e( 'AI Response',  'ai-woocommerce-assistant' ); ?></option>
		</select>

		<select name="filter_date">
			<option value="all" <?php selected( $filter_date, 'all' ); ?>><?php esc_html_e( 'All time',   'ai-woocommerce-assistant' ); ?></option>
			<option value="7"   <?php selected( $filter_date, '7' ); ?>><?php esc_html_e( 'Last 7 days',  'ai-woocommerce-assistant' ); ?></option>
			<option value="30"  <?php selected( $filter_date, '30' ); ?>><?php esc_html_e( 'Last 30 days', 'ai-woocommerce-assistant' ); ?></option>
		</select>

		<?php submit_button( __( 'Filter', 'ai-woocommerce-assistant' ), 'secondary', 'submit', false ); ?>

		<?php if ( '' !== $search || 'all' !== $filter_type || 'all' !== $filter_date ) : ?>
			<a href="<?php echo esc_url( $base_url ); ?>" class="button"><?php esc_html_e( 'Reset', 'ai-woocommerce-assistant' ); ?></a>
		<?php endif; ?>

		<a
			href="<?php echo esc_url( add_query_arg( array( 'export' => 'csv', '_wpnonce' => $export_nonce, 'search' => $search, 'filter_date' => $filter_date ), $base_url ) ); ?>"
			class="button"
			style="margin-left:auto;"
		>
			<?php esc_html_e( '⬇ Export CSV', 'ai-woocommerce-assistant' ); ?>
		</a>
	</form>

	<p style="color:#666; font-size:12px; margin-bottom:8px;">
		<?php
		printf(
			/* translators: %d: number of unique queries */
			esc_html__( '%d unique queries found (capped at 500 for performance).', 'ai-woocommerce-assistant' ),
			(int) $total_rows
		);
		?>
	</p>

	<?php /* ── Table ────────────────────────────────────────────────────── */ ?>
	<?php if ( empty( $rows ) ) : ?>
		<p><em><?php esc_html_e( 'No chat messages found matching the current filters.', 'ai-woocommerce-assistant' ); ?></em></p>
	<?php else : ?>
		<table class="wp-list-table widefat fixed striped" id="aiwoo-top-requests-table">
			<thead>
				<tr>
					<th style="width:32%;"><?php esc_html_e( 'Query', 'ai-woocommerce-assistant' ); ?></th>
					<th style="width:7%;  text-align:center;"><?php esc_html_e( 'Count', 'ai-woocommerce-assistant' ); ?></th>
					<th style="width:33%;"><?php esc_html_e( 'Response Preview', 'ai-woocommerce-assistant' ); ?></th>
					<th style="width:12%; text-align:center;"><?php esc_html_e( 'Type', 'ai-woocommerce-assistant' ); ?></th>
					<th style="width:16%;"><?php esc_html_e( 'Action', 'ai-woocommerce-assistant' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $i => $row ) :
					$is_qr        = isset( $qr_response_set[ trim( $row->last_response ) ] );
					$row_id       = 'aiwoo-qr-form-' . $i;
					$popular      = ( (int) $row->total >= 100 );
					$query_words  = array_filter( explode( ' ', $row->query ) );
					$query_slug   = implode( ' ', array_slice( $query_words, 0, 5 ) );
				?>
					<tr>
						<td>
							<?php echo esc_html( $row->query ); ?>
							<?php if ( $popular ) : ?>
								<span style="color:#ef4444; font-weight:700; font-size:11px; margin-left:5px;">🔥 <?php esc_html_e( 'Popular', 'ai-woocommerce-assistant' ); ?></span>
							<?php endif; ?>
						</td>
						<td style="text-align:center; font-weight:700;"><?php echo esc_html( number_format_i18n( (int) $row->total ) ); ?></td>
						<td style="color:#555; font-size:12px;"><?php echo esc_html( mb_substr( $row->last_response, 0, 100 ) . ( mb_strlen( $row->last_response ) > 100 ? '…' : '' ) ); ?></td>
						<td style="text-align:center;">
							<?php if ( $is_qr ) : ?>
								<span style="display:inline-block; padding:2px 8px; border-radius:10px; background:#dcfce7; color:#15803d; font-size:11px; font-weight:700;">
									<?php esc_html_e( 'Quick Reply', 'ai-woocommerce-assistant' ); ?>
								</span>
							<?php else : ?>
								<span style="display:inline-block; padding:2px 8px; border-radius:10px; background:#dbeafe; color:#1d4ed8; font-size:11px; font-weight:700;">
									<?php esc_html_e( 'AI Response', 'ai-woocommerce-assistant' ); ?>
								</span>
							<?php endif; ?>
						</td>
						<td>
							<?php if ( ! $is_qr ) : ?>
								<button
									type="button"
									class="button button-small aiwoo-tr-toggle"
									data-target="<?php echo esc_attr( $row_id ); ?>"
									style="white-space:nowrap;"
								>
									<?php esc_html_e( '+ Save as Quick Reply', 'ai-woocommerce-assistant' ); ?>
								</button>
							<?php else : ?>
								<span style="color:#aaa; font-size:12px;"><?php esc_html_e( 'Rule exists', 'ai-woocommerce-assistant' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>

					<?php if ( ! $is_qr ) : ?>
					<tr id="<?php echo esc_attr( $row_id ); ?>" class="aiwoo-qr-inline-form" style="display:none; background:#f9f9f9;">
						<td colspan="5" style="padding:14px 18px;">
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action"       value="aiwoo_save_quick_reply_from_ai" />
								<input type="hidden" name="source_query" value="<?php echo esc_attr( $row->query ); ?>" />
								<?php wp_nonce_field( 'aiwoo_save_qr_from_ai' ); ?>

								<table style="border-collapse:collapse; width:100%;">
									<tr>
										<td style="width:110px; padding:5px 10px 5px 0; font-size:13px; font-weight:600; vertical-align:top;">
											<label for="<?php echo esc_attr( $row_id ); ?>-kw">
												<?php esc_html_e( 'Keywords', 'ai-woocommerce-assistant' ); ?>
											</label>
										</td>
										<td style="padding:4px 0;">
											<input
												type="text"
												id="<?php echo esc_attr( $row_id ); ?>-kw"
												name="keywords"
												class="large-text"
												value="<?php echo esc_attr( $row->query ); ?>"
												placeholder="<?php esc_attr_e( 'comma-separated keywords', 'ai-woocommerce-assistant' ); ?>"
												required
											/>
											<p style="margin:3px 0 0; color:#888; font-size:11px;">
												<?php esc_html_e( 'Comma-separated. Edit to add variations.', 'ai-woocommerce-assistant' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<td style="padding:5px 10px 5px 0; font-size:13px; font-weight:600; vertical-align:top;">
											<label for="<?php echo esc_attr( $row_id ); ?>-resp">
												<?php esc_html_e( 'Response', 'ai-woocommerce-assistant' ); ?>
											</label>
										</td>
										<td style="padding:4px 0;">
											<textarea
												id="<?php echo esc_attr( $row_id ); ?>-resp"
												name="response"
												class="large-text"
												rows="3"
												required
												style="resize:none;"
											><?php echo esc_textarea( $row->last_response ); ?></textarea>
										</td>
									</tr>
									<tr>
										<td style="padding:5px 10px 5px 0; font-size:13px; font-weight:600;">
											<label for="<?php echo esc_attr( $row_id ); ?>-pri">
												<?php esc_html_e( 'Priority', 'ai-woocommerce-assistant' ); ?>
											</label>
										</td>
										<td style="padding:4px 0;">
											<input
												type="number"
												id="<?php echo esc_attr( $row_id ); ?>-pri"
												name="priority"
												min="0"
												max="100"
												value="60"
												style="width:80px;"
											/>
											<span style="margin-left:6px; color:#888; font-size:11px;">
												<?php esc_html_e( '0–100. Higher = checked first.', 'ai-woocommerce-assistant' ); ?>
											</span>
										</td>
									</tr>
									<tr>
										<td></td>
										<td style="padding:8px 0 4px;">
											<?php submit_button( __( 'Save Quick Reply', 'ai-woocommerce-assistant' ), 'primary small', 'submit', false ); ?>
											<button
												type="button"
												class="button button-small aiwoo-tr-toggle"
												data-target="<?php echo esc_attr( $row_id ); ?>"
												style="margin-left:6px;"
											>
												<?php esc_html_e( 'Cancel', 'ai-woocommerce-assistant' ); ?>
											</button>
										</td>
									</tr>
								</table>
							</form>
						</td>
					</tr>
					<?php endif; ?>

				<?php endforeach; ?>
			</tbody>
		</table>

		<?php /* ── Pagination ──────────────────────────────────────────────── */ ?>
		<?php if ( $total_pages > 1 ) :
			$paginate_args = array(
				'base'      => add_query_arg( 'paged', '%#%', $base_url ),
				'format'    => '',
				'current'   => $current_page,
				'total'     => $total_pages,
				'add_args'  => array_filter( array(
					'search'      => $search,
					'filter_type' => 'all' !== $filter_type ? $filter_type : false,
					'filter_date' => 'all' !== $filter_date ? $filter_date : false,
				) ),
			);
		?>
			<div class="tablenav bottom" style="margin-top:12px;">
				<div class="tablenav-pages">
					<?php echo wp_kses_post( paginate_links( $paginate_args ) ); ?>
				</div>
			</div>
		<?php endif; ?>

	<?php endif; ?>
</div>

<script>
( function () {
	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.aiwoo-tr-toggle' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var targetId = btn.getAttribute( 'data-target' );
				var row = document.getElementById( targetId );
				if ( ! row ) return;
				var isHidden = row.style.display === 'none' || row.style.display === '';
				row.style.display = isHidden ? 'table-row' : 'none';
			} );
		} );
	} );
}() );
</script>
