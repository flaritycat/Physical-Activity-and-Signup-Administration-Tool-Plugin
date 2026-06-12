<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pasat_activities = $pasat['activities'] ?? array();
$pasat_signups    = $pasat['signups'] ?? null;
$pasat_board      = ! empty( $pasat['board'] );
$pasat_board_options = wp_parse_args(
	$pasat['board_options'] ?? array(),
	array(
		'mode'          => '',
		'show_qr'       => false,
		'venue_id'      => 0,
		'activity_type' => '',
		'host_id'       => 0,
		'refresh'       => 60000,
		'limit'         => 20,
		'few_spots'     => 3,
	)
);
$pasat_board_mode = sanitize_key( (string) $pasat_board_options['mode'] );
$pasat_board_mode = in_array( $pasat_board_mode, array( 'grid', 'kiosk', 'list' ), true ) ? $pasat_board_mode : 'list';
$pasat_activity_repo    = new PASAT\Database\ActivitiesRepository();
$pasat_board_attrs      = array();
$pasat_selected_id      = isset( $_GET['pasat_activity_id'] ) ? absint( wp_unslash( $_GET['pasat_activity_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only public preselection.
$pasat_types            = array();
$pasat_venues           = array();
$pasat_filter_count_id = wp_unique_id( 'pasat-filter-count-' );

if ( $pasat_board ) {
	$pasat_board_attrs = array(
		'data-pasat-activity-board'      => '',
		'data-pasat-poll-interval'       => (string) max( 15000, absint( $pasat_board_options['refresh'] ) ),
		'data-pasat-limit'               => (string) max( 1, absint( $pasat_board_options['limit'] ) ),
		'data-pasat-venue-id'            => (string) absint( $pasat_board_options['venue_id'] ),
		'data-pasat-activity-type'       => (string) $pasat_board_options['activity_type'],
		'data-pasat-host-id'             => (string) absint( $pasat_board_options['host_id'] ),
		'data-pasat-show-qr'             => ! empty( $pasat_board_options['show_qr'] ) ? '1' : '0',
		'data-pasat-few-spots-threshold' => (string) max( 1, absint( $pasat_board_options['few_spots'] ) ),
		'data-pasat-mode'                => $pasat_board_mode,
	);
}

if ( ! $pasat_board ) {
	foreach ( $pasat_activities as $pasat_filter_activity ) {
		if ( ! empty( $pasat_filter_activity['activity_type'] ) ) {
			$pasat_types[ (string) $pasat_filter_activity['activity_type'] ] = (string) $pasat_filter_activity['activity_type'];
		}
		if ( ! empty( $pasat_filter_activity['venue_name'] ) ) {
			$pasat_venues[ (string) $pasat_filter_activity['venue_name'] ] = (string) $pasat_filter_activity['venue_name'];
		}
	}
	natcasesort( $pasat_types );
	natcasesort( $pasat_venues );
}

$pasat_board_status = static function ( array $pasat_activity, array $pasat_capacity, bool $pasat_signup_open, int $pasat_few_spots ): array {
	$pasat_remaining = $pasat_capacity['remaining'];
	$pasat_start     = ! empty( $pasat_activity['starts_at'] ) ? strtotime( $pasat_activity['starts_at'] . ' UTC' ) : false;
	$pasat_until     = $pasat_start ? $pasat_start - time() : null;

	if ( 'cancelled' === ( $pasat_activity['status'] ?? '' ) ) {
		return array( 'cancelled', __( 'Cancelled', 'pasat' ) );
	}

	if ( ! $pasat_signup_open ) {
		return array( 'signup-closed', __( 'Signup closed', 'pasat' ) );
	}

	if ( null !== $pasat_until && $pasat_until >= 0 && $pasat_until <= HOUR_IN_SECONDS ) {
		return array( 'starting-soon', __( 'Starting soon', 'pasat' ) );
	}

	if ( ! empty( $pasat_capacity['is_full'] ) && ! empty( $pasat_activity['waitlist_enabled'] ) ) {
		return array( 'waitlist-open', __( 'Waitlist open', 'pasat' ) );
	}

	if ( ! empty( $pasat_capacity['is_full'] ) ) {
		return array( 'full', __( 'Full', 'pasat' ) );
	}

	if ( null === $pasat_remaining ) {
		return array( 'open', __( 'Open', 'pasat' ) );
	}

	if ( (int) $pasat_remaining <= $pasat_few_spots ) {
		return array( 'few-spots', __( 'Few spots left', 'pasat' ) );
	}

	return array(
		'spots-left',
		sprintf(
			/* translators: %d is the number of remaining confirmed signup spots. */
			__( '%d spots left', 'pasat' ),
			(int) $pasat_remaining
		),
	);
};
?>
<div class="pasat-public pasat-public--activities pasat-activity-list<?php echo $pasat_board ? ' pasat-activity-board pasat-activity-board--' . esc_attr( $pasat_board_mode ) : ''; ?>"<?php foreach ( $pasat_board_attrs as $pasat_attr => $pasat_value ) : ?> <?php echo esc_attr( $pasat_attr ); ?><?php echo '' !== $pasat_value ? '="' . esc_attr( $pasat_value ) . '"' : ''; ?><?php endforeach; ?>>
	<?php if ( ! $pasat_board && count( $pasat_activities ) > 5 ) : ?>
		<div class="pasat-activity-list__tools" data-pasat-activity-filters>
			<label class="pasat-filter-field">
				<span><?php esc_html_e( 'Search activities', 'pasat' ); ?></span>
				<input type="search" data-pasat-filter-search placeholder="<?php esc_attr_e( 'Name, venue, or description', 'pasat' ); ?>" autocomplete="off" aria-describedby="<?php echo esc_attr( $pasat_filter_count_id ); ?>">
			</label>
			<?php if ( $pasat_types ) : ?>
				<label class="pasat-filter-field">
					<span><?php esc_html_e( 'Type', 'pasat' ); ?></span>
					<select data-pasat-filter-type aria-describedby="<?php echo esc_attr( $pasat_filter_count_id ); ?>">
						<option value=""><?php esc_html_e( 'All types', 'pasat' ); ?></option>
						<?php foreach ( $pasat_types as $pasat_type ) : ?>
							<option value="<?php echo esc_attr( sanitize_title( $pasat_type ) ); ?>"><?php echo esc_html( $pasat_type ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>
			<?php if ( $pasat_venues ) : ?>
				<label class="pasat-filter-field">
					<span><?php esc_html_e( 'Venue', 'pasat' ); ?></span>
					<select data-pasat-filter-venue aria-describedby="<?php echo esc_attr( $pasat_filter_count_id ); ?>">
						<option value=""><?php esc_html_e( 'All venues', 'pasat' ); ?></option>
						<?php foreach ( $pasat_venues as $pasat_venue ) : ?>
							<option value="<?php echo esc_attr( sanitize_title( $pasat_venue ) ); ?>"><?php echo esc_html( $pasat_venue ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endif; ?>
			<button type="button" class="pasat-button pasat-button--secondary" data-pasat-filter-reset disabled aria-disabled="true"><?php esc_html_e( 'Reset', 'pasat' ); ?></button>
			<p
				id="<?php echo esc_attr( $pasat_filter_count_id ); ?>"
				class="pasat-filter-count"
				data-pasat-filter-count
				data-pasat-filter-total="<?php echo esc_attr( (string) count( $pasat_activities ) ); ?>"
				data-pasat-filter-template="<?php esc_attr_e( 'Showing %1$d of %2$d activities', 'pasat' ); ?>"
				role="status"
				aria-live="polite"
				aria-atomic="true"
			>
				<?php
				printf(
					/* translators: 1: visible activity count, 2: total activity count. */
					esc_html__( 'Showing %1$d of %2$d activities', 'pasat' ),
					count( $pasat_activities ),
					count( $pasat_activities )
				);
				?>
			</p>
		</div>
	<?php endif; ?>
	<?php if ( $pasat_board ) : ?>
		<div class="pasat-board-toolbar">
			<div>
				<p class="pasat-section-kicker"><?php esc_html_e( 'Display Board', 'pasat' ); ?></p>
				<h2 class="pasat-board-title"><?php esc_html_e( 'Activity Board', 'pasat' ); ?></h2>
			</div>
			<p class="pasat-board-updated" data-pasat-board-updated role="status" aria-live="polite" aria-atomic="true"><?php esc_html_e( 'Updated just now', 'pasat' ); ?></p>
		</div>
		<div class="pasat-board-items" data-pasat-board-items>
	<?php endif; ?>
	<?php foreach ( $pasat_activities as $pasat_activity ) : ?>
		<?php
		$pasat_capacity    = $pasat_signups ? $pasat_signups->capacity_snapshot( $pasat_activity ) : array( 'capacity' => null, 'confirmed' => 0, 'waitlisted' => 0, 'remaining' => null, 'is_full' => false );
		$pasat_link        = PASAT\Helpers::public_signup_url( absint( $pasat_activity['id'] ) );
		$pasat_qr_link     = PASAT\Helpers::activity_qr_url( absint( $pasat_activity['id'] ) );
		$pasat_signup_open = $pasat_activity_repo->is_public_signup_open( $pasat_activity );
		$pasat_status      = $pasat_board_status( $pasat_activity, $pasat_capacity, $pasat_signup_open, (int) $pasat_board_options['few_spots'] );
		$pasat_timestamp   = ! empty( $pasat_activity['starts_at'] ) ? strtotime( $pasat_activity['starts_at'] . ' UTC' ) : false;
		$pasat_type        = (string) ( $pasat_activity['activity_type'] ?? '' );
		$pasat_venue_name  = (string) ( $pasat_activity['venue_name'] ?? '' );
		$pasat_description = wp_trim_words( wp_strip_all_tags( (string) ( $pasat_activity['description'] ?? '' ) ), 24 );
		$pasat_search_text = strtolower( trim( $pasat_activity['title'] . ' ' . $pasat_type . ' ' . $pasat_venue_name . ' ' . $pasat_description ) );
		$pasat_state       = wp_json_encode(
			array(
				'status'    => $pasat_status[0],
				'remaining' => $pasat_capacity['remaining'],
				'confirmed' => (int) $pasat_capacity['confirmed'],
				'waitlisted' => (int) $pasat_capacity['waitlisted'],
				'signup_open' => $pasat_signup_open,
			)
		);
		?>
		<article
			class="pasat-card<?php echo $pasat_board ? ' pasat-board-card' : ''; ?><?php echo $pasat_selected_id === (int) $pasat_activity['id'] ? ' pasat-card--selected' : ''; ?>"
			data-pasat-activity-card
			data-pasat-activity-id="<?php echo esc_attr( (string) $pasat_activity['id'] ); ?>"
			data-pasat-search="<?php echo esc_attr( $pasat_search_text ); ?>"
			data-pasat-type="<?php echo esc_attr( sanitize_title( $pasat_type ) ); ?>"
			data-pasat-venue="<?php echo esc_attr( sanitize_title( $pasat_venue_name ) ); ?>"
			<?php echo $pasat_board ? ' data-pasat-board-state="' . esc_attr( (string) $pasat_state ) . '"' : ''; ?>
		>
			<div class="pasat-card__date" aria-label="<?php echo esc_attr( $pasat_timestamp ? PASAT\Helpers::local_datetime( $pasat_activity['starts_at'] ) : __( 'Date to be announced', 'pasat' ) ); ?>">
				<?php if ( $pasat_timestamp ) : ?>
					<span class="pasat-card__date-month"><?php echo esc_html( wp_date( 'M', $pasat_timestamp ) ); ?></span>
					<span class="pasat-card__date-day"><?php echo esc_html( wp_date( 'j', $pasat_timestamp ) ); ?></span>
					<span class="pasat-card__date-time"><?php echo esc_html( wp_date( get_option( 'time_format' ), $pasat_timestamp ) ); ?></span>
				<?php else : ?>
					<span class="pasat-card__date-month"><?php esc_html_e( 'Date', 'pasat' ); ?></span>
					<span class="pasat-card__date-day">-</span>
					<span class="pasat-card__date-time"><?php esc_html_e( 'TBA', 'pasat' ); ?></span>
				<?php endif; ?>
			</div>
			<div class="pasat-card__body">
				<?php if ( $pasat_type ) : ?>
					<span class="pasat-chip"><?php echo esc_html( $pasat_type ); ?></span>
				<?php endif; ?>
				<h3 class="pasat-card__title"><?php echo esc_html( $pasat_activity['title'] ); ?></h3>
				<div class="pasat-card__details">
					<?php if ( $pasat_timestamp ) : ?>
						<span><?php echo esc_html( PASAT\Helpers::local_datetime( $pasat_activity['starts_at'] ) ); ?></span>
					<?php endif; ?>
					<?php if ( $pasat_venue_name ) : ?>
						<span><?php echo esc_html( $pasat_venue_name ); ?></span>
					<?php endif; ?>
				</div>
				<?php if ( $pasat_description ) : ?>
					<p class="pasat-card__description"><?php echo esc_html( $pasat_description ); ?></p>
				<?php endif; ?>
				<?php if ( ! $pasat_board ) : ?>
					<p class="pasat-card__count">
						<?php
						if ( null !== $pasat_capacity['capacity'] ) {
							printf(
								/* translators: 1: confirmed signup count, 2: capacity. */
								esc_html__( '%1$d confirmed of %2$d spots', 'pasat' ),
								(int) $pasat_capacity['confirmed'],
								(int) $pasat_capacity['capacity']
							);
						} else {
							printf(
								/* translators: %d is the confirmed signup count. */
								esc_html__( '%d confirmed', 'pasat' ),
								(int) $pasat_capacity['confirmed']
							);
						}
						if ( ! empty( $pasat_capacity['waitlisted'] ) ) {
							printf(
								/* translators: %d is the waitlisted signup count. */
								esc_html__( ', %d waitlisted', 'pasat' ),
								(int) $pasat_capacity['waitlisted']
							);
						}
						?>
					</p>
				<?php endif; ?>
			</div>
			<div class="pasat-card__aside">
				<span class="pasat-status pasat-status--<?php echo esc_attr( $pasat_status[0] ); ?>"<?php echo $pasat_board ? ' data-pasat-status data-pasat-status-key="' . esc_attr( $pasat_status[0] ) . '"' : ''; ?>>
					<?php echo esc_html( $pasat_status[1] ); ?>
				</span>
				<?php if ( $pasat_board ) : ?>
					<span class="pasat-board-counts" data-pasat-counts>
						<?php
						printf(
							/* translators: 1: confirmed signup count, 2: waitlisted signup count. */
							esc_html__( '%1$d confirmed, %2$d waitlisted', 'pasat' ),
							(int) $pasat_capacity['confirmed'],
							(int) $pasat_capacity['waitlisted']
						);
						?>
					</span>
					<?php if ( ! empty( $pasat_board_options['show_qr'] ) ) : ?>
						<div class="pasat-board-qr-wrap">
							<span class="pasat-board-qr" data-pasat-qr-value="<?php echo esc_attr( $pasat_qr_link ); ?>" aria-label="<?php echo esc_attr( sprintf(
								/* translators: %s is activity title. */
								__( 'Signup QR code for %s', 'pasat' ),
								$pasat_activity['title']
							) ); ?>">
								<span class="pasat-board-qr__fallback"><?php esc_html_e( 'Signup QR', 'pasat' ); ?></span>
							</span>
							<a class="pasat-board-qr-link" href="<?php echo esc_url( $pasat_link ); ?>" aria-label="<?php echo esc_attr( sprintf(
								/* translators: %s is activity title. */
								__( 'Sign up for %s', 'pasat' ),
								$pasat_activity['title']
							) ); ?>"><?php esc_html_e( 'Sign up', 'pasat' ); ?></a>
						</div>
					<?php endif; ?>
				<?php else : ?>
					<a class="pasat-button" href="<?php echo esc_url( $pasat_link ); ?>" aria-label="<?php echo esc_attr( sprintf(
						/* translators: %s is activity title. */
						__( 'Sign up for %s', 'pasat' ),
						$pasat_activity['title']
					) ); ?>"><?php esc_html_e( 'Sign Up', 'pasat' ); ?></a>
				<?php endif; ?>
			</div>
		</article>
	<?php endforeach; ?>
	<?php if ( $pasat_board ) : ?>
		<?php if ( ! $pasat_activities ) : ?>
			<p class="pasat-empty"><?php esc_html_e( 'No public activities are currently available.', 'pasat' ); ?></p>
		<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php if ( ! $pasat_board && count( $pasat_activities ) > 5 ) : ?>
		<p class="pasat-empty pasat-activity-list__no-results" data-pasat-filter-empty role="status" aria-live="polite" hidden><?php esc_html_e( 'No activities match those filters.', 'pasat' ); ?></p>
	<?php endif; ?>
	<?php if ( ! $pasat_board && ! $pasat_activities ) : ?>
		<p class="pasat-empty"><?php esc_html_e( 'No public activities are currently available.', 'pasat' ); ?></p>
	<?php endif; ?>
</div>
