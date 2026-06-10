<?php
namespace PASAT\Database;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
// PASAT table identifiers come from fixed plugin table names via $wpdb->prefix; user values remain prepared or sanitized.
final class VenuesRepository extends Repository {
	public function __construct() {
		parent::__construct( 'venues' );
	}

	public function save( array $input, int $id = 0 ): int {
		$now       = Helpers::now();
		$existing  = $id ? $this->get( $id ) : null;
		$latitude  = $this->nullable_decimal( $input['latitude'] ?? null );
		$longitude = $this->nullable_decimal( $input['longitude'] ?? null );
		$status    = $existing['geocoding_status'] ?? 'not_geocoded';

		if ( null === $latitude || null === $longitude || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180 ) {
			$latitude  = null;
			$longitude = null;
		}

		if ( null !== $latitude && null !== $longitude ) {
			$status = 'manual';
		} else {
			$status = 'not_geocoded';
		}

		$data = array(
			'name'               => sanitize_text_field( $input['name'] ?? '' ),
			'description'        => wp_kses_post( $input['description'] ?? '' ),
			'address'            => sanitize_textarea_field( $input['address'] ?? '' ),
			'latitude'           => $latitude,
			'longitude'          => $longitude,
			'venue_type'         => sanitize_text_field( $input['venue_type'] ?? '' ),
			'capacity'           => $this->nullable_int( $input['capacity'] ?? null ),
			'geocoded_at'        => null,
			'geocoding_status'   => $status,
			'geocoding_error'    => '',
			'geocoding_provider' => null !== $latitude && null !== $longitude ? 'manual' : '',
			'updated_at'         => $now,
		);

		if ( 0 === $id ) {
			$data['created_at'] = $now;
			return $this->insert( $data, array( '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ) );
		}

		$this->update( $id, $data, array( '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ) );
		return $id;
	}

	public function list(): array {
		return $this->wpdb->get_results( "SELECT * FROM {$this->table} ORDER BY name ASC", ARRAY_A ) ?: array();
	}

	public function is_used( int $venue_id ): bool {
		$activities = Helpers::table( 'activities' );
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare( "SELECT COUNT(*) FROM {$activities} WHERE venue_id = %d", $venue_id )
		) > 0;
	}

	public function has_coordinates( array $venue ): bool {
		return '' !== (string) ( $venue['latitude'] ?? '' ) && '' !== (string) ( $venue['longitude'] ?? '' );
	}

	public function map_url( array $venue ): string {
		if ( ! $this->has_coordinates( $venue ) ) {
			return '';
		}

		$lat = (string) $venue['latitude'];
		$lng = (string) $venue['longitude'];

		return add_query_arg(
			array(
				'mlat' => $lat,
				'mlon' => $lng,
			),
			'https://www.openstreetmap.org/'
		) . '#map=15/' . rawurlencode( $lat ) . '/' . rawurlencode( $lng );
	}

	public function save_geocode_success( int $venue_id, float $latitude, float $longitude, string $provider ): bool {
		return $this->update(
			$venue_id,
			array(
				'latitude'           => $latitude,
				'longitude'          => $longitude,
				'geocoded_at'        => Helpers::now(),
				'geocoding_status'   => 'geocoded',
				'geocoding_error'    => '',
				'geocoding_provider' => sanitize_text_field( $provider ),
				'updated_at'         => Helpers::now(),
			),
			array( '%f', '%f', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	public function save_geocode_failure( int $venue_id, string $message, string $provider ): bool {
		return $this->update(
			$venue_id,
			array(
				'geocoding_status'   => 'failed',
				'geocoding_error'    => sanitize_textarea_field( $message ),
				'geocoding_provider' => sanitize_text_field( $provider ),
				'updated_at'         => Helpers::now(),
			),
			array( '%s', '%s', '%s', '%s' )
		);
	}
}
