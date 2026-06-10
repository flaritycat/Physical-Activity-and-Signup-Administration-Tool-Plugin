<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pasat-my-signups">
	<?php if ( ! empty( $pasat['error'] ) ) : ?>
		<div class="pasat-notice pasat-notice--error"><?php echo esc_html( $pasat['error'] ); ?></div>
	<?php endif; ?>
	<?php if ( ! empty( $pasat['notice'] ) ) : ?>
		<div class="pasat-notice pasat-notice--success"><?php echo esc_html( $pasat['notice'] ); ?></div>
	<?php endif; ?>
	<?php if ( ! empty( $pasat['verified'] ) ) : ?>
		<?php if ( ! empty( $pasat['profile'] ) && ! empty( PASAT\Helpers::setting( 'membership_enabled', 0 ) ) ) : ?>
			<div class="pasat-profile-summary">
				<strong><?php esc_html_e( 'Membership status', 'pasat' ); ?>:</strong>
				<?php echo esc_html( ucfirst( str_replace( '_', ' ', (string) ( $pasat['profile']['membership_status'] ?? 'none' ) ) ) ); ?>
			</div>
		<?php endif; ?>
		<?php if ( ! empty( $pasat['badges'] ) ) : ?>
			<div class="pasat-badges">
				<strong><?php esc_html_e( 'Badges', 'pasat' ); ?></strong>
				<ul class="pasat-badge-list">
					<?php foreach ( $pasat['badges'] as $pasat_badge ) : ?>
						<li><?php echo esc_html( $pasat_badge['label'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<table class="pasat-my-signups__table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Activity', 'pasat' ); ?></th>
					<th><?php esc_html_e( 'Date', 'pasat' ); ?></th>
					<th><?php esc_html_e( 'Venue', 'pasat' ); ?></th>
					<th><?php esc_html_e( 'Status', 'pasat' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $pasat['items'] ?? array() as $pasat_item ) : ?>
					<tr>
						<td><?php echo esc_html( $pasat_item['activity_title'] ?? '' ); ?></td>
						<td><?php echo esc_html( PASAT\Helpers::local_datetime( $pasat_item['starts_at'] ?? '' ) ); ?></td>
						<td><?php echo esc_html( $pasat_item['venue_name'] ?? '' ); ?></td>
						<td><?php echo esc_html( $pasat_item['status'] ?? '' ); ?></td>
					</tr>
				<?php endforeach; ?>
				<?php if ( empty( $pasat['items'] ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No signups found.', 'pasat' ); ?></td></tr>
				<?php endif; ?>
			</tbody>
		</table>
		<?php if ( ! empty( $pasat['participation'] ) ) : ?>
			<h3><?php esc_html_e( 'Participation History', 'pasat' ); ?></h3>
			<ul class="pasat-participation-list">
				<?php foreach ( $pasat['participation'] as $pasat_log ) : ?>
					<li>
						<?php
						echo esc_html(
							trim(
								( $pasat_log['activity_title'] ?? '' )
								. ' - '
								. ( $pasat_log['attendance_status'] ?? '' )
								. ( ! empty( $pasat_log['placement'] ) ? ' - #' . (int) $pasat_log['placement'] : '' )
							)
						);
						?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	<?php endif; ?>
	<form class="pasat-form" method="post">
		<?php wp_nonce_field( 'pasat_my_signups', 'pasat_my_signups_nonce' ); ?>
		<input type="hidden" name="pasat_my_signups" value="1">
		<label><span><?php esc_html_e( 'E-mail', 'pasat' ); ?></span><input type="email" name="email" required autocomplete="email"></label>
		<button class="pasat-button" type="submit"><?php esc_html_e( 'Request Lookup', 'pasat' ); ?></button>
	</form>
</div>
