<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pasat_venues  = $pasat['venues'] ?? array();
$pasat_options = wp_parse_args(
	$pasat['options'] ?? array(),
	array(
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
?>
<div class="pasat-public pasat-public--venue-map pasat-venue-map" data-pasat-venue-map data-pasat-map-enabled="<?php echo ! empty( $pasat_options['interactive'] ) ? '1' : '0'; ?>" data-venues="<?php echo esc_attr( wp_json_encode( $pasat_canvas_venues ) ); ?>">
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
				<article class="pasat-venue-card" data-pasat-venue-id="<?php echo esc_attr( (string) $pasat_venue['id'] ); ?>">
					<div class="pasat-venue-card__pin" aria-hidden="true"></div>
					<div class="pasat-venue-card__body">
						<h3 class="pasat-venue-card__title"><?php echo esc_html( $pasat_venue['name'] ); ?></h3>
						<?php if ( ! empty( $pasat_venue['address'] ) ) : ?>
							<p class="pasat-venue-card__meta"><?php echo esc_html( $pasat_venue['address'] ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $pasat_venue['venue_type'] ) || ! empty( $pasat_venue['capacity'] ) ) : ?>
							<p class="pasat-venue-card__meta">
								<?php
								$pasat_bits = array_filter(
									array(
										$pasat_venue['venue_type'] ?? '',
										! empty( $pasat_venue['capacity'] )
											? sprintf(
												/* translators: %d is a venue capacity. */
												__( 'Capacity %d', 'pasat' ),
												(int) $pasat_venue['capacity']
											)
											: '',
									)
								);
								echo esc_html( implode( ' - ', $pasat_bits ) );
								?>
							</p>
						<?php endif; ?>
						<?php if ( ! empty( $pasat_venue['description'] ) ) : ?>
							<div class="pasat-venue-card__description"><?php echo wp_kses_post( wpautop( $pasat_venue['description'] ) ); ?></div>
						<?php endif; ?>
						<?php if ( ! empty( $pasat_venue['has_coordinates'] ) ) : ?>
							<p class="pasat-venue-card__coordinates"><?php echo esc_html( (string) $pasat_venue['latitude'] . ', ' . (string) $pasat_venue['longitude'] ); ?></p>
						<?php else : ?>
							<p class="pasat-venue-card__coordinates"><?php esc_html_e( 'Coordinates not available yet.', 'pasat' ); ?></p>
						<?php endif; ?>
						<?php if ( ! empty( $pasat_venue['activities'] ) ) : ?>
							<ul class="pasat-venue-card__activities">
								<?php foreach ( $pasat_venue['activities'] as $pasat_activity ) : ?>
									<li><a href="<?php echo esc_url( $pasat_activity['signup_url'] ); ?>"><?php echo esc_html( $pasat_activity['title'] ); ?></a><?php echo ! empty( $pasat_activity['date_label'] ) ? ' ' . esc_html( $pasat_activity['date_label'] ) : ''; ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $pasat_venue['map_url'] ) ) : ?>
						<a class="pasat-button pasat-venue-card__link" href="<?php echo esc_url( $pasat_venue['map_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Map', 'pasat' ); ?></a>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
			<?php if ( ! $pasat_venues ) : ?>
				<p class="pasat-empty"><?php esc_html_e( 'No public venue map information is currently available.', 'pasat' ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
