<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pasat_activities = $pasat['activities'] ?? array();
$pasat_signups    = $pasat['signups'] ?? null;
$pasat_board      = ! empty( $pasat['board'] );
?>
<div class="pasat-activity-list<?php echo $pasat_board ? ' pasat-activity-board' : ''; ?>"<?php echo $pasat_board ? ' data-pasat-activity-board data-pasat-poll-interval="60000"' : ''; ?>>
	<?php foreach ( $pasat_activities as $pasat_activity ) : ?>
		<?php
		$pasat_capacity = $pasat_signups ? $pasat_signups->capacity_snapshot( $pasat_activity ) : array( 'confirmed' => 0, 'waitlisted' => 0, 'remaining' => null, 'is_full' => false );
		$pasat_link     = add_query_arg( 'pasat_activity_id', absint( $pasat_activity['id'] ), get_permalink() ?: home_url( '/' ) ) . '#pasat-signup';
		?>
		<article class="pasat-card"<?php echo $pasat_board ? ' data-pasat-activity-id="' . esc_attr( (string) $pasat_activity['id'] ) . '"' : ''; ?>>
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
				<span class="pasat-status"<?php echo $pasat_board ? ' data-pasat-status' : ''; ?>>
					<?php
					if ( $pasat_capacity['is_full'] && ! empty( $pasat_activity['waitlist_enabled'] ) ) {
						esc_html_e( 'Waitlist open', 'pasat' );
					} elseif ( $pasat_capacity['is_full'] ) {
						esc_html_e( 'Full', 'pasat' );
					} else {
						echo esc_html(
							null === $pasat_capacity['remaining']
								? __( 'Open', 'pasat' )
								: sprintf(
									/* translators: %d is the number of remaining confirmed signup spots. */
									__( '%d spots left', 'pasat' ),
									(int) $pasat_capacity['remaining']
								)
						);
					}
					?>
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
		<p class="pasat-board-updated" data-pasat-board-updated></p>
	<?php endif; ?>
</div>
