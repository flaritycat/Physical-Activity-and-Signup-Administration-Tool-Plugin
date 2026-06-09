<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$activities = $pasat['activities'] ?? array();
$activity   = $pasat['activity'] ?? null;
$settings   = $pasat['settings'] ?? PASAT\Helpers::settings();
$selected   = $activity ? (int) $activity['id'] : absint( $_GET['pasat_activity_id'] ?? 0 );
$warning_text = $activity && ! empty( $activity['warning_text'] ) ? $activity['warning_text'] : ( $settings['default_warning_text'] ?? '' );
$warning_required = $activity && ! empty( $activity['requires_warning_ack'] );
?>
<div id="pasat-signup" class="pasat-signup">
	<?php if ( ! empty( $_GET['pasat_cancelled'] ) ) : ?>
		<div class="pasat-notice pasat-notice--success"><?php esc_html_e( 'Your signup has been cancelled.', 'pasat' ); ?></div>
	<?php endif; ?>
	<?php if ( ! empty( $_GET['pasat_cancel_error'] ) ) : ?>
		<div class="pasat-notice pasat-notice--error"><?php echo esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['pasat_cancel_error'] ) ) ) ); ?></div>
	<?php endif; ?>
	<?php if ( ! empty( $pasat['message'] ) ) : ?>
		<div class="pasat-notice pasat-notice--success"><?php echo esc_html( $pasat['message'] ); ?></div>
	<?php endif; ?>
	<?php if ( ! empty( $pasat['error'] ) ) : ?>
		<div class="pasat-notice pasat-notice--error"><?php echo esc_html( $pasat['error'] ); ?></div>
	<?php endif; ?>
	<form class="pasat-form" method="post" data-pasat-signup-form>
		<?php wp_nonce_field( 'pasat_public_signup', 'pasat_public_nonce' ); ?>
		<input type="hidden" name="pasat_public_signup" value="1">
		<label>
			<span><?php esc_html_e( 'Activity', 'pasat' ); ?></span>
			<select name="activity_id" required>
				<option value=""><?php esc_html_e( 'Choose activity', 'pasat' ); ?></option>
				<?php foreach ( $activities as $item ) : ?>
					<option value="<?php echo esc_attr( (string) $item['id'] ); ?>" <?php selected( $selected, (int) $item['id'] ); ?>><?php echo esc_html( $item['title'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<div class="pasat-grid">
			<label><span><?php esc_html_e( 'First Name', 'pasat' ); ?></span><input name="first_name" required autocomplete="given-name"></label>
			<label><span><?php esc_html_e( 'Last Name', 'pasat' ); ?></span><input name="last_name" required autocomplete="family-name"></label>
		</div>
		<div class="pasat-grid">
			<label><span><?php esc_html_e( 'Nickname', 'pasat' ); ?></span><input name="nickname" autocomplete="nickname"></label>
			<label><span><?php esc_html_e( 'Age', 'pasat' ); ?></span><input name="age" type="number" min="0" max="130"></label>
		</div>
		<div class="pasat-grid">
			<label><span><?php esc_html_e( 'E-mail', 'pasat' ); ?></span><input name="email" type="email" required autocomplete="email"></label>
			<label><span><?php esc_html_e( 'Phone', 'pasat' ); ?></span><input name="phone" autocomplete="tel"></label>
		</div>
		<?php if ( ! empty( $settings['require_consent'] ) ) : ?>
			<label class="pasat-check"><input type="checkbox" name="consent_given" value="1" required> <span><?php echo esc_html( $settings['consent_text'] ); ?></span></label>
		<?php endif; ?>
		<?php if ( '' !== trim( (string) $warning_text ) ) : ?>
			<label class="pasat-check"><input type="checkbox" name="warning_acknowledged" value="1" <?php required( $warning_required ); ?>> <span><?php echo esc_html( $warning_text ); ?></span></label>
		<?php endif; ?>
		<button class="pasat-button" type="submit"><?php esc_html_e( 'Submit Signup', 'pasat' ); ?></button>
	</form>
</div>
