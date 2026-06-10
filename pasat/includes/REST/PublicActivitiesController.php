<?php
namespace PASAT\REST;

use PASAT\Database\ActivitiesRepository;
use PASAT\Database\SignupsRepository;
use PASAT\Helpers;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PublicActivitiesController {
	public function index( WP_REST_Request $request ): WP_REST_Response {
		$activities = ( new ActivitiesRepository() )->list(
			array(
				'public'        => true,
				'upcoming'      => true,
				'limit'         => absint( $request->get_param( 'limit' ) ?: 100 ),
				'venue_id'      => absint( $request->get_param( 'venue_id' ) ?: 0 ),
				'activity_type' => sanitize_text_field( (string) ( $request->get_param( 'activity_type' ) ?: '' ) ),
				'host_id'       => absint( $request->get_param( 'host_id' ) ?: 0 ),
			)
		);
		$signups    = new SignupsRepository();

		return new WP_REST_Response( array_map( array( $this, 'public_activity' ), $activities, array_fill( 0, count( $activities ), $signups ) ) );
	}

	public function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$activity = ( new ActivitiesRepository() )->get_with_venue( absint( $request['id'] ) );
		if ( ! $activity || 'published' !== $activity['status'] || 'public' !== $activity['public_visibility'] ) {
			return new WP_Error( 'pasat_not_found', __( 'Activity not found.', 'pasat' ), array( 'status' => 404 ) );
		}

		return new WP_REST_Response( $this->public_activity( $activity, new SignupsRepository() ) );
	}

	private function public_activity( array $activity, SignupsRepository $signups ): array {
		$capacity = $signups->capacity_snapshot( $activity );
		return array(
			'id'                 => (int) $activity['id'],
			'title'              => $activity['title'],
			'description'        => wp_strip_all_tags( (string) $activity['description'] ),
			'activity_type'      => $activity['activity_type'],
			'status'             => $activity['status'],
			'starts_at'          => $activity['starts_at'],
			'ends_at'            => $activity['ends_at'],
			'venue_id'           => (int) $activity['venue_id'],
			'venue_name'         => $activity['venue_name'],
			'capacity'           => $capacity['capacity'],
			'confirmed'          => $capacity['confirmed'],
			'waitlisted'         => $capacity['waitlisted'],
			'remaining'          => $capacity['remaining'],
			'waitlist_enabled'   => (bool) $activity['waitlist_enabled'],
			'signup_open'        => ( new ActivitiesRepository() )->is_public_signup_open( $activity ),
			'signup_url'         => esc_url_raw( Helpers::public_signup_url( (int) $activity['id'] ) ),
			'requires_warning_ack' => (bool) $activity['requires_warning_ack'],
		);
	}
}
