<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pasat_venues = $pasat['venues'] ?? array();
?>
<div class="pasat-venue-map" data-pasat-venue-map data-venues="<?php echo esc_attr( wp_json_encode( $pasat_venues ) ); ?>">
	<?php foreach ( $pasat_venues as $pasat_venue ) : ?>
		<?php
		$pasat_lat = (string) ( $pasat_venue['latitude'] ?? '' );
		$pasat_lng = (string) ( $pasat_venue['longitude'] ?? '' );
		$pasat_url = add_query_arg(
			array(
				'mlat' => $pasat_lat,
				'mlon' => $pasat_lng,
			),
			'https://www.openstreetmap.org/'
		) . '#map=15/' . rawurlencode( $pasat_lat ) . '/' . rawurlencode( $pasat_lng );
		?>
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
				<p class="pasat-venue-card__coordinates"><?php echo esc_html( $pasat_lat . ', ' . $pasat_lng ); ?></p>
			</div>
			<a class="pasat-button pasat-venue-card__link" href="<?php echo esc_url( $pasat_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Map', 'pasat' ); ?></a>
		</article>
	<?php endforeach; ?>
	<?php if ( ! $pasat_venues ) : ?>
		<p class="pasat-empty"><?php esc_html_e( 'No venue coordinates are currently available.', 'pasat' ); ?></p>
	<?php endif; ?>
</div>
