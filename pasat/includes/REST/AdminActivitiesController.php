<?php
namespace PASAT\REST;

use PASAT\Capabilities;
use PASAT\Database\ActivitiesRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminActivitiesController {
	public function can_read(): bool {
		return current_user_can( 'pasat_manage_assigned_activities' ) || current_user_can( 'pasat_manage_all_activities' );
	}

	public function can_manage( WP_REST_Request $request ): bool {
		$id = absint( $request['id'] ?? 0 );
		return Capabilities::can_manage_activity( $id );
	}

	public function index(): WP_REST_Response {
		$args = array( 'limit' => 500 );
		if ( ! current_user_can( 'pasat_manage_all_activities' ) ) {
			$args['assigned_user_id'] = get_current_user_id();
		}

		return new WP_REST_Response( ( new ActivitiesRepository() )->list( $args ) );
	}

	public function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id = absint( $request['id'] );
		if ( ! Capabilities::can_manage_activity( $id ) ) {
			return new WP_Error( 'pasat_forbidden', __( 'You do not have permission to view this activity.', 'pasat' ), array( 'status' => 403 ) );
		}

		$item = ( new ActivitiesRepository() )->get_with_venue( $id );
		return $item ? new WP_REST_Response( $item ) : new WP_Error( 'pasat_not_found', __( 'Activity not found.', 'pasat' ), array( 'status' => 404 ) );
	}

	public function create( WP_REST_Request $request ): WP_REST_Response {
		$id = ( new ActivitiesRepository() )->save( $request->get_params() );
		return new WP_REST_Response( ( new ActivitiesRepository() )->get_with_venue( $id ), 201 );
	}

	public function update( WP_REST_Request $request ): WP_REST_Response {
		$id = absint( $request['id'] );
		( new ActivitiesRepository() )->save( $request->get_params(), $id );
		return new WP_REST_Response( ( new ActivitiesRepository() )->get_with_venue( $id ) );
	}

	public function delete( WP_REST_Request $request ): WP_REST_Response {
		$id       = absint( $request['id'] );
		$activity = ( new ActivitiesRepository() )->get( $id );
		if ( $activity ) {
			$activity['status'] = 'archived';
			( new ActivitiesRepository() )->save( $activity, $id );
		}
		return new WP_REST_Response( array( 'deleted' => true ) );
	}
}
