<?php
namespace PASAT\REST;

use PASAT\Map\VenueMapData;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PublicVenuesController {
	public function index( WP_REST_Request $request ): WP_REST_Response {
		$venues = VenueMapData::public_venues(
			array(
				'source'      => sanitize_key( (string) ( $request->get_param( 'source' ) ?: 'upcoming' ) ),
				'activity_id' => absint( $request->get_param( 'activity_id' ) ?: 0 ),
				'limit'       => absint( $request->get_param( 'limit' ) ?: 500 ),
			)
		);

		return new WP_REST_Response( $venues );
	}
}
