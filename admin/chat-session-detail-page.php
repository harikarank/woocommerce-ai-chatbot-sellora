<?php
/**
 * Single chat session detail view.
 *
 * Available variables (set by Admin_Menu::render_chat_history):
 *   $session_id  string
 *   $messages    array  rows from DB
 *   $back_url    string
 *
 * @package AIWooAssistant
 */

defined( 'ABSPATH' ) || exit;

$first = ! empty( $messages ) ? $messages[0] : null;
?>
<div class="wrap">
	<h1 style="display:flex;align-items:center;gap:10px;">
		<img src="<?php echo esc_url( AI_WOO_ASSISTANT_URL . 'assets/img/logo.svg' ); ?>" alt="Sellora AI" style="height:28px;width:auto;" />
		<?php esc_html_e( 'Chat Session', 'ai-woocommerce-assistant' ); ?>
	</h1>
	<a href="<?php echo esc_url( $back_url ); ?>" style="display:inline-block;margin-bottom:12px;text-decoration:none;font-size:13px;">&#8592; <?php esc_html_e( 'Back to Chat History', 'ai-woocommerce-assistant' ); ?></a>

	<?php if ( $first ) : ?>
	<table class="form-table" style="max-width:600px;">
		<tr>
			<th><?php esc_html_e( 'Session ID', 'ai-woocommerce-assistant' ); ?></th>
			<td><code><?php echo esc_html( $session_id ); ?></code></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Started', 'ai-woocommerce-assistant' ); ?></th>
			<td><?php echo esc_html( $first->created_at ); ?></td>
		</tr>
		<tr>
			<th><?php esc_html_e( 'Messages', 'ai-woocommerce-assistant' ); ?></th>
			<td><?php echo count( $messages ); ?></td>
		</tr>
	</table>
	<?php endif; ?>

	<?php if ( empty( $messages ) ) : ?>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'No messages found for this session.', 'ai-woocommerce-assistant' ); ?></p></div>
	<?php else : ?>
	<div style="max-width:760px;margin-top:24px;display:flex;flex-direction:column;gap:16px;">
		<?php foreach ( $messages as $msg ) : ?>

		<!-- User message -->
		<div style="display:flex;justify-content:flex-end;">
			<div style="background:#102a43;color:#fff;border-radius:18px 18px 4px 18px;padding:12px 16px;max-width:75%;font-size:14px;line-height:1.5;">
				<?php echo esc_html( $msg->user_message ); ?>
				<div style="margin-top:6px;font-size:11px;opacity:0.6;"><?php echo esc_html( $msg->created_at ); ?></div>
			</div>
		</div>

		<!-- AI response -->
		<div style="display:flex;justify-content:flex-start;">
			<div style="background:#f0f4f8;color:#102a43;border-radius:18px 18px 18px 4px;padding:12px 16px;max-width:75%;font-size:14px;line-height:1.5;">
				<?php echo esc_html( $msg->ai_response ); ?>
			</div>
		</div>

		<?php endforeach; ?>
	</div>
	<?php endif; ?>
</div>
