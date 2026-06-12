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
$pasat_activity_repo = $pasat_board ? new PASAT\Database\ActivitiesRepository() : null;
$pasat_board_attrs   = array();

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
		'data-pasat-mode'                => (string) $pasat_board_options['mode'],
	);
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
<div class="pasat-public pasat-public--activities pasat-activity-list<?php echo $pasat_board ? ' pasat-activity-board' : ''; ?><?php echo $pasat_board && 'kiosk' === $pasat_board_options['mode'] ? ' pasat-activity-board--kiosk' : ''; ?>"<?php foreach ( $pasat_board_attrs as $pasat_attr => $pasat_value ) : ?> <?php echo esc_attr( $pasat_attr ); ?><?php echo '' !== $pasat_value ? '="' . esc_attr( $pasat_value ) . '"' : ''; ?><?php endforeach; ?>>
	<?php foreach ( $pasat_activities as $pasat_activity ) : ?>
		<?php
		$pasat_capacity    = $pasat_signups ? $pasat_signups->capacity_snapshot( $pasat_activity ) : array( 'capacity' => null, 'confirmed' => 0, 'waitlisted' => 0, 'remaining' => null, 'is_full' => false );
		$pasat_link        = PASAT\Helpers::public_signup_url( absint( $pasat_activity['id'] ) );
		$pasat_qr_link     = PASAT\Helpers::activity_qr_url( absint( $pasat_activity['id'] ) );
		$pasat_signup_open = $pasat_activity_repo ? $pasat_activity_repo->is_public_signup_open( $pasat_activity ) : true;
		$pasat_status      = $pasat_board_status( $pasat_activity, $pasat_capacity, $pasat_signup_open, (int) $pasat_board_options['few_spots'] );
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
		<article class="pasat-card"<?php echo $pasat_board ? ' data-pasat-activity-id="' . esc_attr( (string) $pasat_activity['id'] ) . '" data-pasat-board-state="' . esc_attr( (string) $pasat_state ) . '"' : ''; ?>>
			<div class="pasat-card__body">
				<h3 class="pasat-card__title"><?php echo esc_html( $pasat_activity['title'] ); ?></h3>
				<?php if ( ! empty( $pasat_activity['starts_at'] ) ) : ?>
					<p class="pasat-card__meta"><?php echo esc_html( PASAT\Helpers::local_datetime( $pasat_activity['starts_at'] ) ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $pasat_activity['venue_name'] ) ) : ?>
					<p class="pasat-card__meta"><?php echo esc_html( $pasat_activity['venue_name'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $pasat_activity['description'] ) ) : ?>
					<div class="pasat-card__description"><?php echo wp_kses_post( wpautop( $pasat_activity['description'] ) ); ?></div>
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
						<span class="pasat-board-qr" data-pasat-qr-value="<?php echo esc_attr( $pasat_qr_link ); ?>" aria-label="<?php echo esc_attr( sprintf(
							/* translators: %s is activity title. */
							__( 'Signup QR code for %s', 'pasat' ),
							$pasat_activity['title']
						) ); ?>">
							<span class="pasat-board-qr__fallback"><?php esc_html_e( 'Signup QR', 'pasat' ); ?></span>
						</span>
						<a class="pasat-board-qr-link" href="<?php echo esc_url( $pasat_link ); ?>"><?php esc_html_e( 'Sign up', 'pasat' ); ?></a>
					<?php endif; ?>
				<?php else : ?>
					<a class="pasat-button" href="<?php echo esc_url( $pasat_link ); ?>"><?php esc_html_e( 'Sign Up', 'pasat' ); ?></a>
				<?php endif; ?>
			</div>
		</article>
	<?php endforeach; ?>
	<?php if ( ! $pasat_activities ) : ?>
		<p class="pasat-empty"><?php esc_html_e( 'No public activities are currently available.', 'pasat' ); ?></p>
	<?php endif; ?>
	<?php if ( $pasat_board ) : ?>
		<p class="pasat-board-updated" data-pasat-board-updated><?php esc_html_e( 'Updated just now', 'pasat' ); ?></p>
	<?php endif; ?>
</div>
