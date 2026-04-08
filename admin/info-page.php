<?php
/**
 * Sellora AI — Information / Documentation page.
 *
 * @package AIWooAssistant
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1 style="display:flex;align-items:center;gap:12px;">
		<img src="<?php echo esc_url( AI_WOO_ASSISTANT_URL . 'assets/img/logo.svg' ); ?>" alt="Sellora AI" style="height:32px;width:auto;" />
		<?php esc_html_e( 'Sellora AI — Plugin Guide', 'ai-woocommerce-assistant' ); ?>
	</h1>
	<p style="color:#6b7280;margin-top:4px;"><?php esc_html_e( 'Everything you need to know to set up and get the most from Sellora AI.', 'ai-woocommerce-assistant' ); ?></p>

	<?php
	$sections = array(
		array(
			'id'    => 'overview',
			'title' => __( 'What is Sellora AI?', 'ai-woocommerce-assistant' ),
			'icon'  => '💬',
			'body'  => array(
				__( 'Sellora AI is a WooCommerce-native AI shopping assistant widget. It sits in the corner of your store, helping customers find products, compare options, and get instant answers — all powered by your choice of AI provider (OpenAI, Claude, or Gemini).', 'ai-woocommerce-assistant' ),
				__( 'Conversations are stored in your database so you can review them under <strong>Chat History</strong>. No data is sent anywhere except to the AI provider you configure.', 'ai-woocommerce-assistant' ),
			),
		),
		array(
			'id'    => 'providers',
			'title' => __( 'AI Providers', 'ai-woocommerce-assistant' ),
			'icon'  => '🤖',
			'body'  => array(
				__( 'Go to <strong>Settings → General</strong> and select your preferred AI provider. Then enter your API key for that provider.', 'ai-woocommerce-assistant' ),
			),
			'table' => array(
				'headers' => array( __( 'Provider', 'ai-woocommerce-assistant' ), __( 'Models', 'ai-woocommerce-assistant' ), __( 'Best for', 'ai-woocommerce-assistant' ) ),
				'rows'    => array(
					array( 'OpenAI', 'gpt-5.4-mini, gpt-5.4, gpt-4.1-mini', __( 'Fast, cost-effective responses', 'ai-woocommerce-assistant' ) ),
					array( 'Claude (Anthropic)', 'Sonnet 4.6, Opus 4.6, Haiku 4.5', __( 'Best for MCP tool-calling mode', 'ai-woocommerce-assistant' ) ),
					array( 'Gemini (Google)', 'Gemini 2.5 Flash, 2.5 Pro, 1.5 series', __( 'Competitive pricing, multilingual', 'ai-woocommerce-assistant' ) ),
				),
			),
		),
		array(
			'id'    => 'widget',
			'title' => __( 'Widget Settings', 'ai-woocommerce-assistant' ),
			'icon'  => '🎨',
			'body'  => array(
				__( 'Configure the chat widget appearance under <strong>Settings → Widget</strong> and <strong>Settings → Appearance</strong>.', 'ai-woocommerce-assistant' ),
			),
			'list'  => array(
				__( '<strong>Panel title / subtitle</strong> — The text shown at the top of the chat panel. Defaults to your site name.', 'ai-woocommerce-assistant' ),
				__( '<strong>Company logo</strong> — Shown in the panel header. Leave blank to use the built-in Sellora logo.', 'ai-woocommerce-assistant' ),
				__( '<strong>Chat launcher icon</strong> — The icon on the floating button. Leave blank to use the built-in favicon.', 'ai-woocommerce-assistant' ),
				__( '<strong>Welcome message</strong> — The first message shown when the panel opens.', 'ai-woocommerce-assistant' ),
				__( '<strong>Accent color</strong> — Controls the launcher button, scrollbar, card accents, and send button.', 'ai-woocommerce-assistant' ),
				__( '<strong>Corner radius</strong> — 0 = sharp corners, 24 = fully rounded. Applies to the panel, bubbles, and enquiry form.', 'ai-woocommerce-assistant' ),
				__( '<strong>Form top border color</strong> — The line separating the input area from the messages.', 'ai-woocommerce-assistant' ),
			),
		),
		array(
			'id'    => 'product-cards',
			'title' => __( 'Product Cards', 'ai-woocommerce-assistant' ),
			'icon'  => '🛍️',
			'body'  => array(
				__( 'When the AI recommends products, they are displayed as cards. Go to <strong>Settings → Widget → Product Cards</strong> to control what each card shows.', 'ai-woocommerce-assistant' ),
			),
			'list'  => array(
				__( '<strong>Show price</strong> — Display the formatted WooCommerce price.', 'ai-woocommerce-assistant' ),
				__( '<strong>Show stock status</strong> — Show "In stock", "Out of stock", or "On backorder".', 'ai-woocommerce-assistant' ),
				__( '<strong>Show thumbnail image</strong> — Display the product\'s featured image thumbnail.', 'ai-woocommerce-assistant' ),
				__( '<strong>Show short description</strong> — Show a trimmed excerpt of the product\'s short description.', 'ai-woocommerce-assistant' ),
				__( '<strong>Show "View details" link</strong> — Adds an explicit link below the card content.', 'ai-woocommerce-assistant' ),
				__( '<strong>No-match fallback text</strong> — The message shown when no products match the customer\'s query.', 'ai-woocommerce-assistant' ),
			),
		),
		array(
			'id'    => 'mcp',
			'title' => __( 'MCP Tool-Calling Mode', 'ai-woocommerce-assistant' ),
			'icon'  => '⚡',
			'body'  => array(
				__( 'Enable under <strong>Settings → AI Intelligence → MCP Tool Calling</strong>.', 'ai-woocommerce-assistant' ),
				__( 'In MCP mode the AI decides which products to fetch via tool calls instead of receiving all product data upfront. This reduces token usage and improves accuracy — but requires 1–2 extra API round trips per response.', 'ai-woocommerce-assistant' ),
			),
			'list'  => array(
				__( '<strong>get_products</strong> — Search the catalog by keyword.', 'ai-woocommerce-assistant' ),
				__( '<strong>get_product_details</strong> — Fetch full details for a specific product by ID.', 'ai-woocommerce-assistant' ),
				__( '<strong>get_related_products</strong> — Return WooCommerce upsells and cross-sells (requires "Enable upsell / cross-sell").', 'ai-woocommerce-assistant' ),
				__( '<strong>get_user_context</strong> — Expose viewed products, search history, and cart for personalisation (requires "Enable personalisation").', 'ai-woocommerce-assistant' ),
			),
			'note'  => __( 'MCP mode works best with Claude (Anthropic) models due to their native tool-calling support.', 'ai-woocommerce-assistant' ),
		),
		array(
			'id'    => 'quick-replies',
			'title' => __( 'Quick Replies', 'ai-woocommerce-assistant' ),
			'icon'  => '⚡',
			'body'  => array(
				__( 'Quick Replies let you define rule-based keyword matches that bypass the AI entirely. Great for FAQs like store hours, return policy, or shipping info.', 'ai-woocommerce-assistant' ),
				__( 'Go to <strong>Sellora AI → Quick Replies</strong> to add, edit, or delete rules. Match types: <strong>exact</strong> (full message match), <strong>contains</strong> (keyword anywhere), <strong>prefix</strong> (message starts with keyword).', 'ai-woocommerce-assistant' ),
			),
		),
		array(
			'id'    => 'ip-blocklist',
			'title' => __( 'IP Blocklist', 'ai-woocommerce-assistant' ),
			'icon'  => '🛡️',
			'body'  => array(
				__( 'Block individual IPs or CIDR ranges from using the chat widget. Go to <strong>Sellora AI → IP Blocklist</strong>.', 'ai-woocommerce-assistant' ),
				__( 'IPs that send messages exceeding the configured max length are automatically blocked to protect your token budget.', 'ai-woocommerce-assistant' ),
			),
		),
		array(
			'id'    => 'chat-history',
			'title' => __( 'Chat History & Logs', 'ai-woocommerce-assistant' ),
			'icon'  => '📋',
			'body'  => array(
				__( 'Every conversation is logged in the database. Browse sessions under <strong>Sellora AI → Chat History</strong>. Filter by IP, customer name, or date range.', 'ai-woocommerce-assistant' ),
				__( 'Customer names are backfilled automatically when a visitor submits the enquiry form during their session.', 'ai-woocommerce-assistant' ),
				__( 'AI failures (provider errors or fallbacks) are recorded separately under <strong>Sellora AI → AI Error Log</strong> — visible to admins only, never shown to users.', 'ai-woocommerce-assistant' ),
			),
		),
		array(
			'id'    => 'enquiries',
			'title' => __( 'Enquiries', 'ai-woocommerce-assistant' ),
			'icon'  => '📧',
			'body'  => array(
				__( 'When no products match a customer\'s query, the enquiry form appears. Submissions are stored as private posts under <strong>Sellora AI → Enquiries</strong> and also emailed to your site admin address.', 'ai-woocommerce-assistant' ),
				__( 'A honeypot field silently rejects bot submissions without any disruption to real users.', 'ai-woocommerce-assistant' ),
			),
		),
		array(
			'id'    => 'system-prompt',
			'title' => __( 'Customising the AI Behaviour', 'ai-woocommerce-assistant' ),
			'icon'  => '✏️',
			'body'  => array(
				__( 'Go to <strong>Settings → AI &amp; Prompt</strong> to add custom instructions that are prepended to every conversation. Use this to define the AI\'s tone, restrict topics, or add store-specific rules.', 'ai-woocommerce-assistant' ),
				__( 'The built-in base prompt already handles: store name, currency, anti-hallucination rules, and product-recommendation format. You only need to add instructions that go beyond these defaults.', 'ai-woocommerce-assistant' ),
			),
		),
	);
	?>

	<div style="max-width:860px;margin-top:24px;">
		<?php foreach ( $sections as $section ) : ?>
			<div style="background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:24px 28px;margin-bottom:16px;">
				<h2 style="margin:0 0 12px;font-size:16px;display:flex;align-items:center;gap:8px;">
					<span aria-hidden="true" style="font-size:18px;"><?php echo esc_html( $section['icon'] ); ?></span>
					<?php echo esc_html( $section['title'] ); ?>
				</h2>

				<?php foreach ( $section['body'] as $para ) : ?>
					<p style="color:#374151;line-height:1.7;margin:0 0 10px;"><?php echo wp_kses( $para, array( 'strong' => array() ) ); ?></p>
				<?php endforeach; ?>

				<?php if ( ! empty( $section['list'] ) ) : ?>
					<ul style="margin:8px 0 0 18px;color:#374151;line-height:1.7;">
						<?php foreach ( $section['list'] as $item ) : ?>
							<li style="margin-bottom:4px;"><?php echo wp_kses( $item, array( 'strong' => array() ) ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( ! empty( $section['table'] ) ) : ?>
					<table class="wp-list-table widefat fixed striped" style="margin-top:12px;">
						<thead>
							<tr>
								<?php foreach ( $section['table']['headers'] as $h ) : ?>
									<th><?php echo esc_html( $h ); ?></th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $section['table']['rows'] as $row ) : ?>
								<tr>
									<?php foreach ( $row as $cell ) : ?>
										<td><?php echo esc_html( $cell ); ?></td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<?php if ( ! empty( $section['note'] ) ) : ?>
					<p style="margin-top:12px;padding:10px 14px;background:#eff6ff;border-left:3px solid #3b82f6;border-radius:3px;color:#1e40af;font-size:13px;line-height:1.6;">
						<?php echo esc_html( $section['note'] ); ?>
					</p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
</div>
