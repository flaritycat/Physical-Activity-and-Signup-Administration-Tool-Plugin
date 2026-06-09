<?php
namespace PASAT\REST;

use PASAT\Database\SignupsRepository;
use PASAT\Email\Mailer;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class AdminSignupsController {
	public function can_read(): bool {
		return current_user_can( 'pasat_view_signups' );
	}

	public function can_manage(): bool {
		return current_user_can( 'pasat_manage_signups' );
	}

	public function index( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response(
			( new SignupsRepository() )->list(
				array(
					'activity_id' => absint( $request->get_param( 'activity_id' ) ),
					'status'      => sanitize_key( $request->get_param( 'status' ) ),
					'search'      => sanitize_text_field( $request->get_param( 'search' ) ),
				)
			)
		);
	}

	public function update( WP_REST_Request $request ): WP_REST_Response {
		$id     = absint( $request['id'] );
		$status = sanitize_key( $request->get_param( 'status' ) );
		if ( 'confirmed' === $status ) {
			( new SignupsRepository() )->confirm_waitlisted( $id );
		}
		return new WP_REST_Response( ( new SignupsRepository() )->get_with_details( $id ) );
	}

	public function cancel_signup( WP_REST_Request $request ): WP_REST_Response {
		$id     = absint( $request['id'] );
		$repo   = new SignupsRepository();
		$signup = $repo->get_with_details( $id );
		$result = $repo->cancel( $id, __( 'Cancelled by administrator.', 'pasat' ) );
		if ( $signup ) {
			Mailer::send_cancellation_confirmation( $signup );
		}
		return new WP_REST_Response( $result );
	}
}
