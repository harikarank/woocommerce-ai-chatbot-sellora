<?php
/**
 * Admin settings page template.
 *
 * @package AIWooAssistant
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Sellora AI — WooCommerce Chatbot & Shopping Assistant', 'ai-woocommerce-assistant' ); ?></h1>
	<p><?php esc_html_e( 'Deploy an AI-powered chatbot that helps customers find products, get instant answers, and boost sales with smart recommendations.', 'ai-woocommerce-assistant' ); ?></p>
	<form action="options.php" method="post">
		<?php
		settings_fields( 'ai_woo_assistant' );
		do_settings_sections( 'ai-woo-assistant' );
		submit_button();
		?>
	</form>
</div>
