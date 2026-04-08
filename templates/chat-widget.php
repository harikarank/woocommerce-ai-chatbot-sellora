<?php
/**
 * Frontend chat widget template.
 *
 * @package AIWooAssistant
 */

defined( 'ABSPATH' ) || exit;

$display_title    = '' !== $panel_title ? $panel_title : $company_name;
$display_subtitle = '' !== $panel_subtitle ? $panel_subtitle : __( 'Ask about products, comparisons, and buying advice.', 'ai-woocommerce-assistant' );
?>
<div class="aiwoo-widget" data-aiwoo-widget>

	<!-- Launcher button -->
	<button
		class="aiwoo-launcher"
		type="button"
		aria-expanded="false"
		aria-controls="aiwoo-chat-panel"
		aria-label="<?php esc_attr_e( 'Open chat assistant', 'ai-woocommerce-assistant' ); ?>"
	>
		<?php if ( ! empty( $icon_url ) ) : ?>
			<img class="aiwoo-launcher__icon" src="<?php echo esc_url( $icon_url ); ?>" alt="" />
		<?php else : ?>
			<!-- Default chat icon -->
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true">
				<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
			</svg>
		<?php endif; ?>
	</button>

	<!-- Chat panel -->
	<section class="aiwoo-panel" id="aiwoo-chat-panel" aria-hidden="true">

		<!-- Header -->
		<header class="aiwoo-panel__header">
			<div class="aiwoo-panel__header-info">
				<?php if ( ! empty( $company_logo ) ) : ?>
					<img class="aiwoo-panel__logo" src="<?php echo esc_url( $company_logo ); ?>" alt="" />
				<?php endif; ?>
				<div>
					<h2><?php echo esc_html( $display_title ); ?></h2>
					<p>
						<span class="aiwoo-panel__online" aria-hidden="true"></span>
						<?php echo esc_html( $display_subtitle ); ?>
					</p>
				</div>
			</div>
			<button
				class="aiwoo-close"
				type="button"
				aria-label="<?php esc_attr_e( 'Close chat assistant', 'ai-woocommerce-assistant' ); ?>"
			>
				<svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="square" aria-hidden="true">
					<line x1="1" y1="1" x2="13" y2="13"/>
					<line x1="13" y1="1" x2="1" y2="13"/>
				</svg>
			</button>
		</header>

		<!-- Message list -->
		<div class="aiwoo-messages" aria-live="polite"></div>

		<!-- Typing indicator -->
		<div class="aiwoo-loading" hidden>
			<span class="aiwoo-loading__dot" aria-hidden="true"></span>
			<span class="aiwoo-loading__dot" aria-hidden="true"></span>
			<span class="aiwoo-loading__dot" aria-hidden="true"></span>
			<span class="aiwoo-loading__text" aria-hidden="true"><?php esc_html_e( 'Typing…', 'ai-woocommerce-assistant' ); ?></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Assistant is thinking...', 'ai-woocommerce-assistant' ); ?></span>
		</div>

		<!-- Input form -->
		<form class="aiwoo-form">
			<textarea
				class="aiwoo-input"
				rows="1"
				placeholder="<?php echo esc_attr( '' !== $chat_placeholder ? $chat_placeholder : __( 'Ask about products…', 'ai-woocommerce-assistant' ) ); ?>"
			></textarea>
			<button class="aiwoo-send" type="submit" aria-label="<?php esc_attr_e( 'Send message', 'ai-woocommerce-assistant' ); ?>">
				<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true">
					<line x1="5" y1="12" x2="19" y2="12"/>
					<polyline points="12 5 19 12 12 19"/>
				</svg>
			</button>
		</form>

	</section>
</div>
