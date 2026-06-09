<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$activities = $pasat['activities'] ?? array();
$signups    = $pasat['signups'] ?? null;
$board      = ! empty( $pasat['board'] );
?>
<div class="pasat-activity-list<?php echo $board ? ' pasat-activity-board' : ''; ?>">
	<?php foreach ( $activities as $activity ) : ?>
		<?php
		$capacity = $signups ? $signups->capacity_snapshot( $activity ) : array( 'confirmed' => 0, 'waitlisted' => 0, 'remaining' => null, 'is_full' => false );
		$link     = add_query_arg( 'pasat_activity_id', absint( $activity['id'] ), get_permalink() ?: home_url( '/' ) ) . '#pasat-signup';
		?>
		<article class="pasat-card">
			<div class="pasat-card__body">
				<h3 class="pasat-card__title"><?php echo esc_html( $activity['title'] ); ?></h3>
				<?php if ( ! empty( $activity['starts_at'] ) ) : ?>
					<p class="pasat-card__meta"><?php echo esc_html( PASAT\Helpers::local_datetime( $activity['starts_at'] ) ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $activity['venue_name'] ) ) : ?>
					<p class="pasat-card__meta"><?php echo esc_html( $activity['venue_name'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $activity['description'] ) ) : ?>
					<div class="pasat-card__description"><?php echo wp_kses_post( wpautop( $activity['description'] ) ); ?></div>
				<?php endif; ?>
			</div>
			<div class="pasat-card__aside">
				<span class="pasat-status">
					<?php
					if ( $capacity['is_full'] && ! empty( $activity['waitlist_enabled'] ) ) {
						esc_html_e( 'Waitlist open', 'pasat' );
					} elseif ( $capacity['is_full'] ) {
						esc_html_e( 'Full', 'pasat' );
					} else {
						echo esc_html(
							null === $capacity['remaining']
								? __( 'Open', 'pasat' )
								: sprintf( __( '%d spots left', 'pasat' ), (int) $capacity['remaining'] )
						);
					}
					?>
				</span>
				<a class="pasat-button" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Sign Up', 'pasat' ); ?></a>
			</div>
		</article>
	<?php endforeach; ?>
	<?php if ( ! $activities ) : ?>
		<p class="pasat-empty"><?php esc_html_e( 'No public activities are currently available.', 'pasat' ); ?></p>
	<?php endif; ?>
</div>
