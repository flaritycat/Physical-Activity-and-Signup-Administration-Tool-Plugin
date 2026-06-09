<?php
namespace PASAT\REST;

use PASAT\Capabilities;
use PASAT\Database\HostsRepository;
use PASAT\Database\SignupsRepository;
use PASAT\Email\Mailer;
use PASAT\Security\Tokens;
use WP_Error;
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
		$args = array(
			'activity_id' => absint( $request->get_param( 'activity_id' ) ),
			'status'      => sanitize_key( $request->get_param( 'status' ) ),
			'search'      => sanitize_text_field( $request->get_param( 'search' ) ),
		);
		if ( ! current_user_can( 'pasat_manage_all_activities' ) ) {
			$args['activity_ids'] = ( new HostsRepository() )->activity_ids_for_user( get_current_user_id() );
		}

		return new WP_REST_Response(
			( new SignupsRepository() )->list( $args )
		);
	}

	public function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id     = absint( $request['id'] );
		$status = sanitize_key( $request->get_param( 'status' ) );
		$repo   = new SignupsRepository();
		if ( ! $this->can_manage_signup( $id, $repo ) ) {
			return new WP_Error( 'pasat_forbidden', __( 'You do not have permission to manage this signup.', 'pasat' ), array( 'status' => 403 ) );
		}

		if ( 'confirmed' === $status ) {
			if ( ! $repo->confirm_waitlisted( $id ) ) {
				return new WP_REST_Response( array( 'confirmed' => false, 'reason' => __( 'Capacity is not available for this waitlisted signup.', 'pasat' ) ), 409 );
			}
			$signup = $repo->get_with_details( $id );
			if ( $signup ) {
				$token = Tokens::generate_for_signup( (int) $signup['id'] );
				$repo->update_token_hash( (int) $signup['id'], $token['hash'] );
				Mailer::send_waitlist_promotion( $signup, $token['token'] );
			}
		}
		return new WP_REST_Response( ( new SignupsRepository() )->get_with_details( $id ) );
	}

	public function cancel_signup( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$id     = absint( $request['id'] );
		$repo   = new SignupsRepository();
		if ( ! $this->can_manage_signup( $id, $repo ) ) {
			return new WP_Error( 'pasat_forbidden', __( 'You do not have permission to manage this signup.', 'pasat' ), array( 'status' => 403 ) );
		}

		$signup = $repo->get_with_details( $id );
		$result = $repo->cancel( $id, __( 'Cancelled by administrator.', 'pasat' ) );
		if ( $signup ) {
			Mailer::send_cancellation_confirmation( $signup );
		}
		if ( ! empty( $result['promoted_signup_id'] ) ) {
			$promoted = $repo->get_with_details( (int) $result['promoted_signup_id'] );
			if ( $promoted ) {
				$token = Tokens::generate_for_signup( (int) $promoted['id'] );
				$repo->update_token_hash( (int) $promoted['id'], $token['hash'] );
				Mailer::send_waitlist_promotion( $promoted, $token['token'] );
			}
		}
		return new WP_REST_Response( $result );
	}

	private function can_manage_signup( int $signup_id, SignupsRepository $repo ): bool {
		if ( current_user_can( 'pasat_manage_all_activities' ) ) {
			return true;
		}

		$signup = $repo->get_with_details( $signup_id );
		return $signup && Capabilities::can_manage_activity( (int) $signup['activity_id'] );
	}
}
