<?php
namespace PASAT\REST;

use PASAT\Database\VenuesRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminVenuesController {
	public function can_manage(): bool {
		return current_user_can( 'pasat_manage_venues' );
	}

	public function index(): WP_REST_Response {
		return new WP_REST_Response( ( new VenuesRepository() )->list() );
	}

	public function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$item = ( new VenuesRepository() )->get( absint( $request['id'] ) );
		return $item ? new WP_REST_Response( $item ) : new WP_Error( 'pasat_not_found', __( 'Venue not found.', 'pasat' ), array( 'status' => 404 ) );
	}

	public function create( WP_REST_Request $request ): WP_REST_Response {
		$id = ( new VenuesRepository() )->save( $request->get_params() );
		return new WP_REST_Response( ( new VenuesRepository() )->get( $id ), 201 );
	}

	public function update( WP_REST_Request $request ): WP_REST_Response {
		$id = absint( $request['id'] );
		( new VenuesRepository() )->save( $request->get_params(), $id );
		return new WP_REST_Response( ( new VenuesRepository() )->get( $id ) );
	}

	public function delete( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$repo = new VenuesRepository();
		$id   = absint( $request['id'] );
		if ( $repo->is_used( $id ) ) {
			return new WP_Error( 'pasat_venue_used', __( 'Venue is used by at least one activity.', 'pasat' ), array( 'status' => 409 ) );
		}
		$repo->delete( $id );
		return new WP_REST_Response( array( 'deleted' => true ) );
	}
}
