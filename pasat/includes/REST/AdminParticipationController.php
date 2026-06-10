<?php
namespace PASAT\REST;

use PASAT\Badges\Awarder;
use PASAT\Capabilities;
use PASAT\Database\ParticipationLogsRepository;
use PASAT\Helpers;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminParticipationController {
	public function can_activity( WP_REST_Request $request ): bool {
		return Capabilities::can_manage_participation( absint( $request['id'] ) );
	}

	public function can_log( WP_REST_Request $request ): bool {
		$log = ( new ParticipationLogsRepository() )->get( absint( $request['id'] ) );
		return $log && Capabilities::can_manage_participation( (int) $log['activity_id'] );
	}

	public function list_activity( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( ( new ParticipationLogsRepository() )->list_for_activity( absint( $request['id'] ) ) );
	}

	public function create_for_activity( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$params = $request->get_params();
		$params['activity_id'] = absint( $request['id'] );
		$params = $this->filter_placement_permission( $params );
		$log_id = ( new ParticipationLogsRepository() )->save( $params );
		if ( ! $log_id ) {
			return new WP_Error( 'pasat_invalid_participation', __( 'Participation log could not be saved.', 'pasat' ), array( 'status' => 400 ) );
		}
		( new Awarder() )->recalculate_log( $log_id );
		return new WP_REST_Response( ( new ParticipationLogsRepository() )->get_with_details( $log_id ), 201 );
	}

	public function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$log = ( new ParticipationLogsRepository() )->get_with_details( absint( $request['id'] ) );
		return $log ? new WP_REST_Response( $log ) : new WP_Error( 'pasat_not_found', __( 'Participation log not found.', 'pasat' ), array( 'status' => 404 ) );
	}

	public function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$repo = new ParticipationLogsRepository();
		$id   = absint( $request['id'] );
		$log  = $repo->get( $id );
		if ( ! $log ) {
			return new WP_Error( 'pasat_not_found', __( 'Participation log not found.', 'pasat' ), array( 'status' => 404 ) );
		}

		$repo->save( $this->filter_placement_permission( $request->get_params() ), $id );
		( new Awarder() )->recalculate_log( $id );
		return new WP_REST_Response( $repo->get_with_details( $id ) );
	}

	public function recalculate( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( ( new Awarder() )->recalculate_activity( absint( $request['id'] ) ) );
	}

	private function filter_placement_permission( array $params ): array {
		if ( ! current_user_can( 'pasat_manage_all_activities' ) && empty( Helpers::setting( 'hosts_can_record_placements', 1 ) ) ) {
			unset( $params['placement'], $params['placement_label'] );
		}

		return $params;
	}
}
