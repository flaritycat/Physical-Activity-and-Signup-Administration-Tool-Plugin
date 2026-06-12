<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pasat_venues  = $pasat['venues'] ?? array();
$pasat_options = wp_parse_args(
	$pasat['options'] ?? array(),
	array(
		'activity_id' => 0,
		'height'      => 420,
		'show_cards'  => true,
		'interactive' => true,
	)
);
$pasat_canvas_venues = array_values(
	array_filter(
		$pasat_venues,
		static fn( array $pasat_venue ): bool => ! empty( $pasat_venue['has_coordinates'] )
	)
);
$pasat_is_activity_map = ! empty( $pasat_options['activity_id'] );
?>
<div class="pasat-public pasat-public--venue-map pasat-venue-map<?php echo $pasat_is_activity_map ? ' pasat-venue-map--activity' : ''; ?>" data-pasat-venue-map data-pasat-map-enabled="<?php echo ! empty( $pasat_options['interactive'] ) ? '1' : '0'; ?>" data-venues="<?php echo esc_attr( wp_json_encode( $pasat_canvas_venues ) ); ?>">
	<?php if ( $pasat_canvas_venues && ! empty( $pasat_options['interactive'] ) ) : ?>
		<div
			class="pasat-venue-map__canvas"
			data-pasat-map-canvas
			style="min-height: <?php echo esc_attr( (string) max( 240, absint( $pasat_options['height'] ) ) ); ?>px"
			role="region"
			aria-label="<?php esc_attr_e( 'Venue map', 'pasat' ); ?>"
		></div>
	<?php endif; ?>

	<?php if ( ! empty( $pasat_options['show_cards'] ) ) : ?>
		<div class="pasat-venue-map__cards">
			<?php foreach ( $pasat_venues as $pasat_venue ) : ?>
				<?php
				$pasat_venue_activities = $pasat_venue['activities'] ?? array();
				$pasat_activity_count   = count( $pasat_venue_activities );
				$pasat_next_activity    = $pasat_activity_count ? reset( $pasat_venue_activities ) : null;
				?>
				<article class="pasat-venue-card" data-pasat-venue-card data-pasat-venue-id="<?php echo esc_attr( (string) $pasat_venue['id'] ); ?>">
					<div class="pasat-venue-card__marker" aria-hidden="true"></div>
					<div class="pasat-venue-card__body">
						<div class="pasat-venue-card__header">
							<h3 class="pasat-venue-card__title"><?php echo esc_html( $pasat_venue['name'] ); ?></h3>
							<div class="pasat-venue-card__chips">
								<?php if ( ! empty( $pasat_venue['venue_type'] ) ) : ?>
									<span class="pasat-chip"><?php echo esc_html( $pasat_venue['venue_type'] ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $pasat_venue['capacity'] ) ) : ?>
									<span class="pasat-chip">
										<?php
										printf(
											/* translators: %d is a venue capacity. */
											esc_html__( 'Capacity %d', 'pasat' ),
											(int) $pasat_venue['capacity']
										);
										?>
									</span>
								<?php endif; ?>
								<?php if ( $pasat_activity_count ) : ?>
									<span class="pasat-chip">
										<?php
										printf(
											/* translators: %d is the number of activities at a venue. */
											esc_html( _n( '%d activity', '%d activities', $pasat_activity_count, 'pasat' ) ),
											$pasat_activity_count
										);
										?>
									</span>
								<?php endif; ?>
							</div>
						</div>
						<?php if ( ! empty( $pasat_venue['address'] ) ) : ?>
							<p class="pasat-venue-card__address"><?php echo esc_html( $pasat_venue['address'] ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $pasat_venue['description'] ) ) : ?>
							<p class="pasat-venue-card__description"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( (string) $pasat_venue['description'] ), 24 ) ); ?></p>
						<?php endif; ?>
						<?php if ( $pasat_next_activity ) : ?>
							<div class="pasat-venue-card__next">
								<span class="pasat-venue-card__label"><?php esc_html_e( 'Next activity', 'pasat' ); ?></span>
								<a href="<?php echo esc_url( $pasat_next_activity['signup_url'] ); ?>"><?php echo esc_html( $pasat_next_activity['title'] ); ?></a>
								<?php if ( ! empty( $pasat_next_activity['date_label'] ) ) : ?>
									<span><?php echo esc_html( $pasat_next_activity['date_label'] ); ?></span>
								<?php endif; ?>
							</div>
						<?php elseif ( empty( $pasat_venue['has_coordinates'] ) ) : ?>
							<p class="pasat-venue-card__meta"><?php esc_html_e( 'Map position is not available yet.', 'pasat' ); ?></p>
						<?php endif; ?>
					</div>
					<div class="pasat-venue-card__actions">
						<?php if ( ! empty( $pasat_venue['has_coordinates'] ) && ! empty( $pasat_options['interactive'] ) ) : ?>
							<button class="pasat-button pasat-button--secondary pasat-venue-card__link" type="button" data-pasat-map-focus="<?php echo esc_attr( (string) $pasat_venue['id'] ); ?>" aria-label="<?php echo esc_attr( sprintf(
								/* translators: %s is venue name. */
								__( 'Show %s on map', 'pasat' ),
								$pasat_venue['name']
							) ); ?>"><?php esc_html_e( 'Show on map', 'pasat' ); ?></button>
						<?php endif; ?>
						<?php if ( ! empty( $pasat_venue['map_url'] ) ) : ?>
							<a class="pasat-button pasat-venue-card__link" href="<?php echo esc_url( $pasat_venue['map_url'] ); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr( sprintf(
								/* translators: %s is venue name. */
								__( 'Directions to %s', 'pasat' ),
								$pasat_venue['name']
							) ); ?>"><?php esc_html_e( 'Directions', 'pasat' ); ?></a>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
			<?php if ( ! $pasat_venues ) : ?>
				<p class="pasat-empty"><?php esc_html_e( 'No public venue map information is currently available.', 'pasat' ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
