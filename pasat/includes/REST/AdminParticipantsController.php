<?php
namespace PASAT\REST;

use PASAT\Database\AuditLogRepository;
use PASAT\Database\BadgesRepository;
use PASAT\Database\ParticipationLogsRepository;
use PASAT\Database\ParticipantsRepository;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminParticipantsController {
	public function can_view(): bool {
		return current_user_can( 'pasat_view_participants' );
	}

	public function can_manage_memberships(): bool {
		return current_user_can( 'pasat_manage_memberships' );
	}

	public function badges( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( ( new BadgesRepository() )->active_for_participant( absint( $request['id'] ) ) );
	}

	public function participation( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( ( new ParticipationLogsRepository() )->list_for_participant( absint( $request['id'] ) ) );
	}

	public function update_membership( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id   = absint( $request['id'] );
		$repo = new ParticipantsRepository();
		if ( ! $repo->get( $id ) ) {
			return new WP_Error( 'pasat_not_found', __( 'Participant not found.', 'pasat' ), array( 'status' => 404 ) );
		}

		$repo->update_membership( $id, $request->get_params() );
		( new AuditLogRepository() )->log( 'participant.membership_update', 'participant', $id, 'Updated participant membership status through REST' );
		return new WP_REST_Response( $repo->get( $id ) );
	}
}
