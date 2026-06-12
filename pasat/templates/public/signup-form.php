<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended
// Public query values in this template are read-only status messages or activity preselection and are sanitized before use.
$pasat_activities       = $pasat['activities'] ?? array();
$pasat_activity         = $pasat['activity'] ?? null;
$pasat_settings         = $pasat['settings'] ?? PASAT\Helpers::settings();
$pasat_selected         = absint( $pasat['selected_activity_id'] ?? 0 );
$pasat_selected         = $pasat_selected ?: ( $pasat_activity ? (int) $pasat_activity['id'] : ( isset( $_GET['pasat_activity_id'] ) ? absint( wp_unslash( $_GET['pasat_activity_id'] ) ) : 0 ) );
$pasat_activity_repo    = new PASAT\Database\ActivitiesRepository();
$pasat_signups_repo     = new PASAT\Database\SignupsRepository();
$pasat_default_warning  = (string) ( $pasat_settings['default_warning_text'] ?? '' );
$pasat_form_id          = wp_unique_id( 'pasat-signup-' );

$pasat_format_date = static function ( array $activity ): string {
	if ( empty( $activity['starts_at'] ) ) {
		return __( 'Date to be announced', 'pasat' );
	}

	$timestamp = strtotime( $activity['starts_at'] . ' UTC' );
	if ( ! $timestamp ) {
		return (string) $activity['starts_at'];
	}

	return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
};

$pasat_age_note = static function ( array $activity ): string {
	$minimum = isset( $activity['minimum_age'] ) && '' !== (string) $activity['minimum_age'] ? absint( $activity['minimum_age'] ) : 0;
	$maximum = isset( $activity['maximum_age'] ) && '' !== (string) $activity['maximum_age'] ? absint( $activity['maximum_age'] ) : 0;

	if ( $minimum && $maximum ) {
		return sprintf(
			/* translators: 1: minimum age, 2: maximum age. */
			__( 'Ages %1$d-%2$d', 'pasat' ),
			$minimum,
			$maximum
		);
	}

	if ( $minimum ) {
		return sprintf(
			/* translators: %d is the minimum participant age. */
			__( 'Minimum age %d', 'pasat' ),
			$minimum
		);
	}

	if ( $maximum ) {
		return sprintf(
			/* translators: %d is the maximum participant age. */
			__( 'Maximum age %d', 'pasat' ),
			$maximum
		);
	}

	return '';
};

$pasat_activity_summary = static function ( array $activity ) use ( $pasat_activity_repo, $pasat_signups_repo, $pasat_format_date, $pasat_age_note ): array {
	$capacity    = $pasat_signups_repo->capacity_snapshot( $activity );
	$signup_open = $pasat_activity_repo->is_public_signup_open( $activity );
	$status_key  = 'open';
	$status      = __( 'Open', 'pasat' );

	if ( 'cancelled' === ( $activity['status'] ?? '' ) ) {
		$status_key = 'cancelled';
		$status     = __( 'Cancelled', 'pasat' );
	} elseif ( ! $signup_open ) {
		$status_key = 'signup-closed';
		$status     = __( 'Signup closed', 'pasat' );
	} elseif ( ! empty( $capacity['is_full'] ) && ! empty( $activity['waitlist_enabled'] ) ) {
		$status_key = 'waitlist-open';
		$status     = __( 'Waitlist open', 'pasat' );
	} elseif ( ! empty( $capacity['is_full'] ) ) {
		$status_key = 'full';
		$status     = __( 'Full', 'pasat' );
	} elseif ( null !== $capacity['remaining'] ) {
		$status_key = (int) $capacity['remaining'] <= 3 ? 'few-spots' : 'spots-left';
		$status     = sprintf(
			/* translators: %d is the number of remaining confirmed signup spots. */
			__( '%d spots left', 'pasat' ),
			(int) $capacity['remaining']
		);
	}

	if ( empty( $capacity['capacity'] ) || (int) $capacity['capacity'] <= 0 ) {
		$capacity_label = __( 'No capacity limit', 'pasat' );
	} else {
		$capacity_label = sprintf(
			/* translators: 1: confirmed signup count, 2: capacity. */
			__( '%1$d of %2$d spots filled', 'pasat' ),
			(int) $capacity['confirmed'],
			(int) $capacity['capacity']
		);
		if ( ! empty( $capacity['waitlisted'] ) ) {
			$capacity_label .= sprintf(
				/* translators: %d is the number of waitlisted signups. */
				__( ', %d waitlisted', 'pasat' ),
				(int) $capacity['waitlisted']
			);
		}
	}

	return array(
		'title'          => (string) ( $activity['title'] ?? '' ),
		'type'           => (string) ( $activity['activity_type'] ?? '' ),
		'date'           => $pasat_format_date( $activity ),
		'venue'          => (string) ( $activity['venue_name'] ?? '' ),
		'status'         => $status,
		'status_key'     => $status_key,
		'capacity'       => $capacity_label,
		'description'    => wp_trim_words( wp_strip_all_tags( (string) ( $activity['description'] ?? '' ) ), 22 ),
		'age_note'       => $pasat_age_note( $activity ),
	);
};

$pasat_warning_for_activity = static function ( array $activity ) use ( $pasat_default_warning ): string {
	$warning = ! empty( $activity['warning_text'] ) ? (string) $activity['warning_text'] : $pasat_default_warning;
	if ( '' === trim( $warning ) && ! empty( $activity['requires_warning_ack'] ) ) {
		$warning = __( 'I acknowledge the activity warning.', 'pasat' );
	}

	return $warning;
};

$pasat_selected_activity = null;
foreach ( $pasat_activities as $pasat_item ) {
	if ( $pasat_selected && (int) $pasat_item['id'] === $pasat_selected ) {
		$pasat_selected_activity = $pasat_item;
		break;
	}
}

if ( ! $pasat_selected_activity && 1 === count( $pasat_activities ) ) {
	$pasat_selected_activity = reset( $pasat_activities );
	$pasat_selected          = (int) $pasat_selected_activity['id'];
}

$pasat_selected_summary  = $pasat_selected_activity ? $pasat_activity_summary( $pasat_selected_activity ) : null;
$pasat_warning_text      = $pasat_selected_activity ? $pasat_warning_for_activity( $pasat_selected_activity ) : $pasat_default_warning;
$pasat_warning_required  = $pasat_selected_activity && ! empty( $pasat_selected_activity['requires_warning_ack'] );
$pasat_warning_visible   = '' !== trim( $pasat_warning_text );
$pasat_has_static_checks = ! empty( $pasat_settings['require_consent'] ) || ! empty( $pasat_settings['membership_enabled'] );
?>
<div id="pasat-signup" class="pasat-public pasat-public--signup pasat-signup">
	<div class="pasat-notice-region" data-pasat-notice-region aria-live="polite">
		<?php if ( ! empty( $_GET['pasat_cancelled'] ) ) : ?>
			<div class="pasat-notice pasat-notice--success" role="status"><?php esc_html_e( 'Your signup has been cancelled.', 'pasat' ); ?></div>
		<?php endif; ?>
		<?php if ( ! empty( $_GET['pasat_cancel_error'] ) ) : ?>
			<div class="pasat-notice pasat-notice--error" role="alert"><?php echo esc_html( rawurldecode( sanitize_text_field( wp_unslash( $_GET['pasat_cancel_error'] ) ) ) ); ?></div>
		<?php endif; ?>
		<?php if ( ! empty( $pasat['message'] ) ) : ?>
			<div class="pasat-notice pasat-notice--success" role="status"><?php echo esc_html( $pasat['message'] ); ?></div>
		<?php endif; ?>
		<?php if ( ! empty( $pasat['error'] ) ) : ?>
			<div class="pasat-notice pasat-notice--error" role="alert"><?php echo esc_html( $pasat['error'] ); ?></div>
		<?php endif; ?>
	</div>
	<?php if ( ! empty( $pasat['map_html'] ) ) : ?>
		<?php echo $pasat['map_html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered by PASAT template with escaped values. ?>
	<?php endif; ?>
	<?php if ( empty( $pasat_activities ) ) : ?>
		<p class="pasat-empty"><?php esc_html_e( 'No activities are currently open for signup.', 'pasat' ); ?></p>
	<?php else : ?>
		<div class="pasat-signup-summary<?php echo $pasat_selected_summary ? '' : ' pasat-signup-summary--empty'; ?>" data-pasat-signup-summary data-pasat-empty-title="<?php esc_attr_e( 'No activity selected', 'pasat' ); ?>" data-pasat-empty-meta="<?php esc_attr_e( 'Select an activity before submitting.', 'pasat' ); ?>">
			<div class="pasat-signup-summary__body">
				<span class="pasat-signup-summary__eyebrow"><?php esc_html_e( 'Selected activity', 'pasat' ); ?></span>
				<h2 class="pasat-signup-summary__title" data-pasat-summary-title><?php echo esc_html( $pasat_selected_summary['title'] ?? __( 'No activity selected', 'pasat' ) ); ?></h2>
				<div class="pasat-signup-summary__meta">
					<span data-pasat-summary-date<?php echo empty( $pasat_selected_summary['date'] ) ? ' hidden' : ''; ?>><?php echo esc_html( $pasat_selected_summary['date'] ?? '' ); ?></span>
					<span data-pasat-summary-venue<?php echo empty( $pasat_selected_summary['venue'] ) ? ' hidden' : ''; ?>><?php echo esc_html( $pasat_selected_summary['venue'] ?? '' ); ?></span>
					<span data-pasat-summary-type<?php echo empty( $pasat_selected_summary['type'] ) ? ' hidden' : ''; ?>><?php echo esc_html( $pasat_selected_summary['type'] ?? '' ); ?></span>
					<span data-pasat-summary-age<?php echo empty( $pasat_selected_summary['age_note'] ) ? ' hidden' : ''; ?>><?php echo esc_html( $pasat_selected_summary['age_note'] ?? '' ); ?></span>
				</div>
				<p class="pasat-signup-summary__description" data-pasat-summary-description<?php echo empty( $pasat_selected_summary['description'] ) ? ' hidden' : ''; ?>><?php echo esc_html( $pasat_selected_summary['description'] ?? '' ); ?></p>
			</div>
			<div class="pasat-signup-summary__aside">
				<span class="pasat-status <?php echo $pasat_selected_summary ? 'pasat-status--' . esc_attr( $pasat_selected_summary['status_key'] ) : 'pasat-is-hidden'; ?>" data-pasat-summary-status><?php echo esc_html( $pasat_selected_summary['status'] ?? '' ); ?></span>
				<span class="pasat-signup-summary__capacity" data-pasat-summary-capacity<?php echo empty( $pasat_selected_summary['capacity'] ) ? ' hidden' : ''; ?>><?php echo esc_html( $pasat_selected_summary['capacity'] ?? '' ); ?></span>
			</div>
		</div>

		<form class="pasat-form pasat-signup-form" method="post" data-pasat-signup-form>
			<?php wp_nonce_field( 'pasat_public_signup', 'pasat_public_nonce' ); ?>
			<input type="hidden" name="pasat_public_signup" value="1">

			<fieldset class="pasat-form__section">
				<legend><?php esc_html_e( 'Activity', 'pasat' ); ?></legend>
				<label class="pasat-field" for="<?php echo esc_attr( $pasat_form_id ); ?>activity">
					<span><?php esc_html_e( 'Activity', 'pasat' ); ?></span>
					<select id="<?php echo esc_attr( $pasat_form_id ); ?>activity" name="activity_id" required>
						<option value=""><?php esc_html_e( 'Choose activity', 'pasat' ); ?></option>
						<?php foreach ( $pasat_activities as $pasat_item ) : ?>
							<?php
							$pasat_option_summary = $pasat_activity_summary( $pasat_item );
							$pasat_option_warning_text = $pasat_warning_for_activity( $pasat_item );
							?>
							<option
								value="<?php echo esc_attr( (string) $pasat_item['id'] ); ?>"
								data-pasat-title="<?php echo esc_attr( $pasat_option_summary['title'] ); ?>"
								data-pasat-date="<?php echo esc_attr( $pasat_option_summary['date'] ); ?>"
								data-pasat-venue="<?php echo esc_attr( $pasat_option_summary['venue'] ); ?>"
								data-pasat-type="<?php echo esc_attr( $pasat_option_summary['type'] ); ?>"
								data-pasat-status="<?php echo esc_attr( $pasat_option_summary['status'] ); ?>"
								data-pasat-status-key="<?php echo esc_attr( $pasat_option_summary['status_key'] ); ?>"
								data-pasat-capacity="<?php echo esc_attr( $pasat_option_summary['capacity'] ); ?>"
								data-pasat-description="<?php echo esc_attr( $pasat_option_summary['description'] ); ?>"
								data-pasat-age-note="<?php echo esc_attr( $pasat_option_summary['age_note'] ); ?>"
								data-pasat-warning="<?php echo esc_attr( $pasat_option_warning_text ); ?>"
								data-pasat-warning-required="<?php echo ! empty( $pasat_item['requires_warning_ack'] ) ? '1' : '0'; ?>"
								<?php selected( $pasat_selected, (int) $pasat_item['id'] ); ?>
							><?php echo esc_html( $pasat_item['title'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</fieldset>

			<fieldset class="pasat-form__section">
				<legend><?php esc_html_e( 'Participant', 'pasat' ); ?></legend>
				<div class="pasat-grid">
					<label class="pasat-field" for="<?php echo esc_attr( $pasat_form_id ); ?>first-name"><span><?php esc_html_e( 'First name', 'pasat' ); ?></span><input id="<?php echo esc_attr( $pasat_form_id ); ?>first-name" name="first_name" required autocomplete="given-name"></label>
					<label class="pasat-field" for="<?php echo esc_attr( $pasat_form_id ); ?>last-name"><span><?php esc_html_e( 'Last name', 'pasat' ); ?></span><input id="<?php echo esc_attr( $pasat_form_id ); ?>last-name" name="last_name" required autocomplete="family-name"></label>
				</div>
				<div class="pasat-grid">
					<label class="pasat-field" for="<?php echo esc_attr( $pasat_form_id ); ?>nickname"><span><?php esc_html_e( 'Nickname', 'pasat' ); ?> <small><?php esc_html_e( 'Optional', 'pasat' ); ?></small></span><input id="<?php echo esc_attr( $pasat_form_id ); ?>nickname" name="nickname" autocomplete="nickname"></label>
					<label class="pasat-field" for="<?php echo esc_attr( $pasat_form_id ); ?>age"><span><?php esc_html_e( 'Age', 'pasat' ); ?> <small><?php esc_html_e( 'Optional', 'pasat' ); ?></small></span><input id="<?php echo esc_attr( $pasat_form_id ); ?>age" name="age" type="number" min="0" max="130" inputmode="numeric"><small class="pasat-field-note" data-pasat-age-note<?php echo empty( $pasat_selected_summary['age_note'] ) ? ' hidden' : ''; ?>><?php echo esc_html( $pasat_selected_summary['age_note'] ?? '' ); ?></small></label>
				</div>
			</fieldset>

			<fieldset class="pasat-form__section">
				<legend><?php esc_html_e( 'Contact', 'pasat' ); ?></legend>
				<div class="pasat-grid">
					<label class="pasat-field" for="<?php echo esc_attr( $pasat_form_id ); ?>email"><span><?php esc_html_e( 'E-mail', 'pasat' ); ?></span><input id="<?php echo esc_attr( $pasat_form_id ); ?>email" name="email" type="email" required autocomplete="email"></label>
					<label class="pasat-field" for="<?php echo esc_attr( $pasat_form_id ); ?>phone"><span><?php esc_html_e( 'Phone', 'pasat' ); ?> <small><?php esc_html_e( 'Optional', 'pasat' ); ?></small></span><input id="<?php echo esc_attr( $pasat_form_id ); ?>phone" name="phone" autocomplete="tel"></label>
				</div>
			</fieldset>

			<fieldset class="pasat-form__section pasat-form__section--checks<?php echo ( $pasat_has_static_checks || $pasat_warning_visible ) ? '' : ' pasat-is-hidden'; ?>" data-pasat-ack-section>
				<legend><?php esc_html_e( 'Confirmations', 'pasat' ); ?></legend>
				<?php if ( ! empty( $pasat_settings['require_consent'] ) ) : ?>
					<label class="pasat-check"><input type="checkbox" name="consent_given" value="1" required> <span><?php echo esc_html( $pasat_settings['consent_text'] ); ?></span></label>
				<?php endif; ?>
				<?php if ( ! empty( $pasat_settings['membership_enabled'] ) ) : ?>
					<label class="pasat-check"><input type="checkbox" name="membership_opt_in" value="1"> <span><?php echo esc_html( $pasat_settings['membership_opt_in_text'] ); ?></span></label>
				<?php endif; ?>
				<label class="pasat-check<?php echo $pasat_warning_visible ? '' : ' pasat-is-hidden'; ?>" data-pasat-warning-check data-pasat-default-warning="<?php echo esc_attr( $pasat_default_warning ); ?>">
					<input type="checkbox" name="warning_acknowledged" value="1" <?php echo $pasat_warning_required ? 'required' : ''; ?>>
					<span data-pasat-warning-text><?php echo esc_html( $pasat_warning_text ); ?></span>
				</label>
			</fieldset>

			<div class="pasat-form__actions">
				<button class="pasat-button" type="submit" data-pasat-submit-label="<?php esc_attr_e( 'Submit signup', 'pasat' ); ?>"><span data-pasat-submit-text><?php esc_html_e( 'Submit signup', 'pasat' ); ?></span></button>
			</div>
		</form>
	<?php endif; ?>
</div>
