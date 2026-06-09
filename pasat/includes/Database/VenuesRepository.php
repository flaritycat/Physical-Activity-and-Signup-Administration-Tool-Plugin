<?php
namespace PASAT\Database;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class VenuesRepository extends Repository {
	public function __construct() {
		parent::__construct( 'venues' );
	}

	public function save( array $input, int $id = 0 ): int {
		$now  = Helpers::now();
		$data = array(
			'name'        => sanitize_text_field( $input['name'] ?? '' ),
			'description' => wp_kses_post( $input['description'] ?? '' ),
			'address'     => sanitize_textarea_field( $input['address'] ?? '' ),
			'latitude'    => $this->nullable_decimal( $input['latitude'] ?? null ),
			'longitude'   => $this->nullable_decimal( $input['longitude'] ?? null ),
			'venue_type'  => sanitize_text_field( $input['venue_type'] ?? '' ),
			'capacity'    => $this->nullable_int( $input['capacity'] ?? null ),
			'updated_at'  => $now,
		);

		if ( 0 === $id ) {
			$data['created_at'] = $now;
			return $this->insert( $data, array( '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%s', '%s' ) );
		}

		$this->update( $id, $data, array( '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%s' ) );
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
}
