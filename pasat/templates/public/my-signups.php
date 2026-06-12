<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pasat_verified      = ! empty( $pasat['verified'] );
$pasat_profile       = is_array( $pasat['profile'] ?? null ) ? $pasat['profile'] : array();
$pasat_items         = is_array( $pasat['items'] ?? null ) ? $pasat['items'] : array();
$pasat_badges        = is_array( $pasat['badges'] ?? null ) ? $pasat['badges'] : array();
$pasat_participation = is_array( $pasat['participation'] ?? null ) ? $pasat['participation'] : array();
$pasat_email         = sanitize_email( (string) ( $pasat['email'] ?? '' ) );
$pasat_membership_on = ! empty( PASAT\Helpers::setting( 'membership_enabled', 0 ) );
$pasat_name_parts    = array_filter(
	array(
		(string) ( $pasat_profile['first_name'] ?? '' ),
		(string) ( $pasat_profile['last_name'] ?? '' ),
	)
);
$pasat_display_name  = $pasat_name_parts ? implode( ' ', $pasat_name_parts ) : __( 'Verified participant', 'pasat' );
$pasat_label         = static function ( string $value ): string {
	return ucwords( str_replace( '_', ' ', $value ) );
};
$pasat_badge_classes = static function ( array $badge ): string {
	$classes = array( 'pasat-badge-card' );
	$type    = sanitize_html_class( (string) ( $badge['badge_type'] ?? 'manual' ) );

	if ( $type ) {
		$classes[] = 'pasat-badge-card--' . $type;
	}

	if ( ! empty( $badge['placement'] ) ) {
		$classes[] = 'pasat-badge-card--place-' . absint( $badge['placement'] );
	}

	return implode( ' ', array_unique( $classes ) );
};
?>
<div class="pasat-public pasat-public--my-signups pasat-my-signups">
	<div class="pasat-notice-region" aria-live="polite">
		<?php if ( ! empty( $pasat['error'] ) ) : ?>
			<div class="pasat-notice pasat-notice--error"><?php echo esc_html( $pasat['error'] ); ?></div>
		<?php endif; ?>
		<?php if ( ! empty( $pasat['notice'] ) ) : ?>
			<div class="pasat-notice pasat-notice--success"><?php echo esc_html( $pasat['notice'] ); ?></div>
		<?php endif; ?>
	</div>

	<?php if ( $pasat_verified ) : ?>
		<section class="pasat-profile-summary" aria-labelledby="pasat-profile-heading">
			<div class="pasat-profile-summary__main">
				<p class="pasat-profile-summary__eyebrow"><?php esc_html_e( 'Private verified view', 'pasat' ); ?></p>
				<h2 id="pasat-profile-heading" class="pasat-profile-summary__title"><?php echo esc_html( $pasat_display_name ); ?></h2>
				<?php if ( $pasat_email ) : ?>
					<p class="pasat-profile-summary__meta"><?php echo esc_html( $pasat_email ); ?></p>
				<?php endif; ?>
			</div>
			<div class="pasat-profile-summary__stats" aria-label="<?php esc_attr_e( 'Participant summary', 'pasat' ); ?>">
				<div>
					<strong><?php echo esc_html( number_format_i18n( count( $pasat_items ) ) ); ?></strong>
					<span><?php esc_html_e( 'Signups', 'pasat' ); ?></span>
				</div>
				<div>
					<strong><?php echo esc_html( number_format_i18n( count( $pasat_badges ) ) ); ?></strong>
					<span><?php esc_html_e( 'Badges', 'pasat' ); ?></span>
				</div>
				<div>
					<strong><?php echo esc_html( number_format_i18n( count( $pasat_participation ) ) ); ?></strong>
					<span><?php esc_html_e( 'Results', 'pasat' ); ?></span>
				</div>
			</div>
		</section>

		<?php if ( ! empty( $pasat_profile ) && $pasat_membership_on ) : ?>
			<?php
			$pasat_membership_status = (string) ( $pasat_profile['membership_status'] ?? 'none' );
			$pasat_member_number     = (string) ( $pasat_profile['membership_number'] ?? '' );
			?>
			<section class="pasat-membership-card" aria-labelledby="pasat-membership-heading">
				<div>
					<p class="pasat-section-kicker"><?php esc_html_e( 'Membership', 'pasat' ); ?></p>
					<h3 id="pasat-membership-heading"><?php echo esc_html( $pasat_label( $pasat_membership_status ) ); ?></h3>
					<?php if ( ! empty( $pasat_profile['membership_opted_in_at'] ) ) : ?>
						<p><?php printf( esc_html__( 'Opted in %s', 'pasat' ), esc_html( PASAT\Helpers::local_datetime( $pasat_profile['membership_opted_in_at'] ) ) ); ?></p>
					<?php else : ?>
						<p><?php esc_html_e( 'Membership details are stored privately with your participant profile.', 'pasat' ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( $pasat_member_number ) : ?>
					<span class="pasat-membership-card__number"><?php echo esc_html( $pasat_member_number ); ?></span>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<section class="pasat-badges" aria-labelledby="pasat-badges-heading">
			<div class="pasat-section-heading">
				<div>
					<p class="pasat-section-kicker"><?php esc_html_e( 'Recognition', 'pasat' ); ?></p>
					<h3 id="pasat-badges-heading"><?php esc_html_e( 'Badges', 'pasat' ); ?></h3>
				</div>
				<span><?php echo esc_html( number_format_i18n( count( $pasat_badges ) ) ); ?></span>
			</div>
			<?php if ( $pasat_badges ) : ?>
				<div class="pasat-badge-grid">
					<?php foreach ( $pasat_badges as $pasat_badge ) : ?>
						<?php
						$pasat_badge_type  = (string) ( $pasat_badge['badge_type'] ?? '' );
						$pasat_badge_label = (string) ( $pasat_badge['label'] ?? __( 'PASAT badge', 'pasat' ) );
						$pasat_placement   = absint( $pasat_badge['placement'] ?? 0 );
						$pasat_season_year = absint( $pasat_badge['season_year'] ?? 0 );
						$pasat_badge_mark  = $pasat_placement ? '#' . $pasat_placement : ( $pasat_season_year ? (string) $pasat_season_year : __( 'PASAT', 'pasat' ) );
						?>
						<article class="<?php echo esc_attr( $pasat_badge_classes( $pasat_badge ) ); ?>">
							<div class="pasat-badge-card__mark"><?php echo esc_html( $pasat_badge_mark ); ?></div>
							<div>
								<h4><?php echo esc_html( $pasat_badge_label ); ?></h4>
								<p>
									<?php
									if ( $pasat_placement ) {
										printf( esc_html__( 'Placement badge, %s place', 'pasat' ), esc_html( number_format_i18n( $pasat_placement ) ) );
									} elseif ( $pasat_season_year ) {
										printf( esc_html__( 'Season participation, %s', 'pasat' ), esc_html( (string) $pasat_season_year ) );
									} else {
										echo esc_html( $pasat_label( $pasat_badge_type ?: 'badge' ) );
									}
									?>
								</p>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="pasat-empty"><?php esc_html_e( 'No badges yet. Completed activities and recorded placements will appear here after organizers publish them.', 'pasat' ); ?></p>
			<?php endif; ?>
		</section>

		<section class="pasat-my-signups__section" aria-labelledby="pasat-signups-heading">
			<div class="pasat-section-heading">
				<div>
					<p class="pasat-section-kicker"><?php esc_html_e( 'Current Activity Plans', 'pasat' ); ?></p>
					<h3 id="pasat-signups-heading"><?php esc_html_e( 'My Signups', 'pasat' ); ?></h3>
				</div>
			</div>
			<?php if ( $pasat_items ) : ?>
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
						<?php foreach ( $pasat_items as $pasat_item ) : ?>
							<?php $pasat_status = sanitize_html_class( (string) ( $pasat_item['status'] ?? '' ) ); ?>
							<tr>
								<td><?php echo esc_html( $pasat_item['activity_title'] ?? '' ); ?></td>
								<td><?php echo esc_html( PASAT\Helpers::local_datetime( $pasat_item['starts_at'] ?? '' ) ); ?></td>
								<td><?php echo esc_html( $pasat_item['venue_name'] ?? '' ); ?></td>
								<td><span class="pasat-status pasat-status--<?php echo esc_attr( $pasat_status ?: 'unknown' ); ?>"><?php echo esc_html( $pasat_label( (string) ( $pasat_item['status'] ?? '' ) ) ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<div class="pasat-my-signups__cards">
					<?php foreach ( $pasat_items as $pasat_item ) : ?>
						<?php $pasat_status = sanitize_html_class( (string) ( $pasat_item['status'] ?? '' ) ); ?>
						<article class="pasat-signup-mini-card">
							<div>
								<h4><?php echo esc_html( $pasat_item['activity_title'] ?? '' ); ?></h4>
								<p><?php echo esc_html( PASAT\Helpers::local_datetime( $pasat_item['starts_at'] ?? '' ) ); ?></p>
								<?php if ( ! empty( $pasat_item['venue_name'] ) ) : ?>
									<p><?php echo esc_html( $pasat_item['venue_name'] ); ?></p>
								<?php endif; ?>
							</div>
							<span class="pasat-status pasat-status--<?php echo esc_attr( $pasat_status ?: 'unknown' ); ?>"><?php echo esc_html( $pasat_label( (string) ( $pasat_item['status'] ?? '' ) ) ); ?></span>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="pasat-empty"><?php esc_html_e( 'No active signups found for this verified e-mail address.', 'pasat' ); ?></p>
			<?php endif; ?>
		</section>

		<section class="pasat-participation" aria-labelledby="pasat-participation-heading">
			<div class="pasat-section-heading">
				<div>
					<p class="pasat-section-kicker"><?php esc_html_e( 'Activity History', 'pasat' ); ?></p>
					<h3 id="pasat-participation-heading"><?php esc_html_e( 'Participation History', 'pasat' ); ?></h3>
				</div>
			</div>
			<?php if ( $pasat_participation ) : ?>
				<div class="pasat-participation-list">
					<?php foreach ( $pasat_participation as $pasat_log ) : ?>
						<?php
						$pasat_attendance = (string) ( $pasat_log['attendance_status'] ?? '' );
						$pasat_placement  = absint( $pasat_log['placement'] ?? 0 );
						$pasat_result     = trim( (string) ( $pasat_log['result_value'] ?? '' ) . ' ' . (string) ( $pasat_log['result_unit'] ?? '' ) );
						?>
						<article class="pasat-participation-card">
							<div>
								<h4><?php echo esc_html( $pasat_log['activity_title'] ?? '' ); ?></h4>
								<p><?php echo esc_html( PASAT\Helpers::local_datetime( $pasat_log['starts_at'] ?? '' ) ); ?></p>
							</div>
							<div class="pasat-participation-card__meta">
								<?php if ( $pasat_attendance ) : ?>
									<span><?php echo esc_html( $pasat_label( $pasat_attendance ) ); ?></span>
								<?php endif; ?>
								<?php if ( $pasat_placement ) : ?>
									<span><?php printf( esc_html__( 'Place %s', 'pasat' ), esc_html( number_format_i18n( $pasat_placement ) ) ); ?></span>
								<?php endif; ?>
								<?php if ( $pasat_result ) : ?>
									<span><?php echo esc_html( $pasat_result ); ?></span>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<p class="pasat-empty"><?php esc_html_e( 'No participation results have been recorded yet.', 'pasat' ); ?></p>
			<?php endif; ?>
		</section>
	<?php endif; ?>

	<section class="pasat-lookup-card" aria-labelledby="pasat-lookup-heading">
		<div class="pasat-section-heading">
			<div>
				<p class="pasat-section-kicker"><?php esc_html_e( 'Private Access', 'pasat' ); ?></p>
				<h3 id="pasat-lookup-heading">
					<?php echo esc_html( $pasat_verified ? __( 'Look Up Another E-mail', 'pasat' ) : __( 'Private Signup Lookup', 'pasat' ) ); ?>
				</h3>
			</div>
		</div>
		<p><?php esc_html_e( 'Enter your e-mail address and PASAT will send a private link for your signups, membership status, badges, and activity history. For privacy, this form does not reveal whether an address is registered.', 'pasat' ); ?></p>
		<form class="pasat-form pasat-form--inline" method="post">
			<?php wp_nonce_field( 'pasat_my_signups', 'pasat_my_signups_nonce' ); ?>
			<input type="hidden" name="pasat_my_signups" value="1">
			<label><span><?php esc_html_e( 'E-mail', 'pasat' ); ?></span><input type="email" name="email" required autocomplete="email"></label>
			<button class="pasat-button" type="submit"><?php esc_html_e( 'Send Private Link', 'pasat' ); ?></button>
		</form>
	</section>
</div>
