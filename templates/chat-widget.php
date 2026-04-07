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
	<button class="aiwoo-launcher" type="button" aria-expanded="false" aria-controls="aiwoo-chat-panel" aria-label="<?php esc_attr_e( 'Open chat assistant', 'ai-woocommerce-assistant' ); ?>">
		<?php if ( ! empty( $icon_url ) ) : ?>
			<img class="aiwoo-launcher__icon" src="<?php echo esc_url( $icon_url ); ?>" alt="" />
		<?php else : ?>
			<span class="aiwoo-launcher__dot"></span>
			<span class="aiwoo-launcher__label">AI</span>
		<?php endif; ?>
	</button>
	<section class="aiwoo-panel" id="aiwoo-chat-panel" aria-hidden="true">
		<header class="aiwoo-panel__header">
			<div class="aiwoo-panel__header-info">
				<?php if ( ! empty( $company_logo ) ) : ?>
					<img class="aiwoo-panel__logo" src="<?php echo esc_url( $company_logo ); ?>" alt="" />
				<?php endif; ?>
				<div>
					<h2><?php echo esc_html( $display_title ); ?></h2>
					<p><?php echo esc_html( $display_subtitle ); ?></p>
				</div>
			</div>
			<button class="aiwoo-close" type="button" aria-label="<?php esc_attr_e( 'Close chat assistant', 'ai-woocommerce-assistant' ); ?>">&times;</button>
		</header>
		<div class="aiwoo-messages" aria-live="polite"></div>
		<div class="aiwoo-loading" hidden>
			<span class="aiwoo-loading__dot"></span>
			<span class="aiwoo-loading__dot"></span>
			<span class="aiwoo-loading__dot"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Assistant is thinking...', 'ai-woocommerce-assistant' ); ?></span>
		</div>
		<form class="aiwoo-form">
			<textarea class="aiwoo-input" rows="1" placeholder="<?php esc_attr_e( 'Ask about products...', 'ai-woocommerce-assistant' ); ?>"></textarea>
			<button class="aiwoo-send" type="submit"><?php esc_html_e( 'Send', 'ai-woocommerce-assistant' ); ?></button>
		</form>
	</section>
</div>
