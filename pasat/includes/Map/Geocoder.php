<?php
namespace PASAT\Map;

use PASAT\Database\VenuesRepository;
use PASAT\Helpers;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Geocoder {
	public function geocode_venue( int $venue_id ): array|WP_Error {
		$repo  = new VenuesRepository();
		$venue = $repo->get( $venue_id );

		if ( ! $venue ) {
			return new WP_Error( 'pasat_venue_not_found', __( 'Venue not found.', 'pasat' ) );
		}

		$address = trim( (string) ( $venue['address'] ?? '' ) );
		if ( '' === $address ) {
			$repo->save_geocode_failure( $venue_id, __( 'Venue has no address to geocode.', 'pasat' ), 'none' );
			return new WP_Error( 'pasat_geocode_no_address', __( 'Venue has no address to geocode.', 'pasat' ) );
		}

		if ( ! Helpers::setting( 'geocoding_enabled', 0 ) ) {
			return new WP_Error( 'pasat_geocode_disabled', __( 'Address geocoding is disabled in PASAT settings.', 'pasat' ) );
		}

		$limited = $this->rate_limit();
		if ( is_wp_error( $limited ) ) {
			return $limited;
		}

		$endpoint = esc_url_raw( (string) Helpers::setting( 'geocoding_endpoint', 'https://nominatim.openstreetmap.org/search' ) );
		if ( '' === $endpoint ) {
			return new WP_Error( 'pasat_geocode_endpoint', __( 'No geocoding endpoint is configured.', 'pasat' ) );
		}

		$url = add_query_arg(
			array(
				'format' => 'json',
				'limit'  => 1,
				'q'      => $address,
			),
			$endpoint
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 12,
				'headers' => array(
					'Accept'     => 'application/json',
					'User-Agent' => 'PASAT/' . PASAT_VERSION . '; ' . home_url( '/' ),
					'Referer'    => home_url( '/' ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$repo->save_geocode_failure( $venue_id, $response->get_error_message(), $endpoint );
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			$message = sprintf(
				/* translators: %d is the HTTP status code from the geocoding provider. */
				__( 'Geocoding provider returned HTTP %d.', 'pasat' ),
				$code
			);
			$repo->save_geocode_failure( $venue_id, $message, $endpoint );
			return new WP_Error( 'pasat_geocode_http', $message );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body[0]['lat'] ) || empty( $body[0]['lon'] ) ) {
			$message = __( 'No coordinates were found for this address.', 'pasat' );
			$repo->save_geocode_failure( $venue_id, $message, $endpoint );
			return new WP_Error( 'pasat_geocode_empty', $message );
		}

		$latitude  = (float) $body[0]['lat'];
		$longitude = (float) $body[0]['lon'];

		if ( $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180 ) {
			$message = __( 'The geocoding provider returned invalid coordinates.', 'pasat' );
			$repo->save_geocode_failure( $venue_id, $message, $endpoint );
			return new WP_Error( 'pasat_geocode_invalid', $message );
		}

		$repo->save_geocode_success( $venue_id, $latitude, $longitude, $endpoint );

		return array(
			'venue_id'  => $venue_id,
			'latitude'  => $latitude,
			'longitude' => $longitude,
			'provider'  => $endpoint,
		);
	}

	private function rate_limit(): bool|WP_Error {
		$throttle = max( 1, absint( Helpers::setting( 'geocoding_throttle_seconds', 1 ) ) );
		$key      = 'pasat_geocode_last_request';
		$last     = (int) get_transient( $key );
		$now      = time();

		if ( $last && ( $now - $last ) < $throttle ) {
			return new WP_Error( 'pasat_geocode_throttled', __( 'Please wait before running another geocoding request.', 'pasat' ) );
		}

		set_transient( $key, $now, $throttle + MINUTE_IN_SECONDS );
		return true;
	}
}
