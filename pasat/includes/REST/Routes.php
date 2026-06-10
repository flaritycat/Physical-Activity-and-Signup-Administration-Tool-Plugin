<?php
namespace PASAT\REST;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Routes {
	public static function register(): void {
		$public_activities = new PublicActivitiesController();
		$public_venues     = new PublicVenuesController();
		$public_signup     = new PublicSignupController();
		$admin_activities  = new AdminActivitiesController();
		$admin_participants = new AdminParticipantsController();
		$admin_participation = new AdminParticipationController();
		$admin_venues      = new AdminVenuesController();
		$admin_signups     = new AdminSignupsController();

		register_rest_route( 'pasat/v1', '/activities', array( 'methods' => 'GET', 'callback' => array( $public_activities, 'index' ), 'permission_callback' => '__return_true', 'args' => self::list_args() ) );
		register_rest_route( 'pasat/v1', '/activities/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( $public_activities, 'show' ), 'permission_callback' => '__return_true', 'args' => self::id_arg() ) );
		register_rest_route( 'pasat/v1', '/venues', array( 'methods' => 'GET', 'callback' => array( $public_venues, 'index' ), 'permission_callback' => '__return_true', 'args' => self::venue_map_args() ) );
		register_rest_route( 'pasat/v1', '/signups', array( 'methods' => 'POST', 'callback' => array( $public_signup, 'create' ), 'permission_callback' => '__return_true', 'args' => self::public_signup_args() ) );
		register_rest_route( 'pasat/v1', '/signups/cancel', array( 'methods' => 'POST', 'callback' => array( $public_signup, 'cancel' ), 'permission_callback' => '__return_true', 'args' => self::cancel_args() ) );

		register_rest_route( 'pasat/v1', '/admin/activities', array( 'methods' => 'GET', 'callback' => array( $admin_activities, 'index' ), 'permission_callback' => array( $admin_activities, 'can_read' ), 'args' => self::list_args() ) );
		register_rest_route( 'pasat/v1', '/admin/activities', array( 'methods' => 'POST', 'callback' => array( $admin_activities, 'create' ), 'permission_callback' => array( $admin_activities, 'can_manage' ), 'args' => self::activity_args() ) );
		register_rest_route( 'pasat/v1', '/admin/activities/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( $admin_activities, 'show' ), 'permission_callback' => array( $admin_activities, 'can_read' ), 'args' => self::id_arg() ) );
		register_rest_route( 'pasat/v1', '/admin/activities/(?P<id>\d+)', array( 'methods' => array( 'PUT', 'PATCH' ), 'callback' => array( $admin_activities, 'update' ), 'permission_callback' => array( $admin_activities, 'can_manage' ), 'args' => array_merge( self::id_arg(), self::activity_args() ) ) );
		register_rest_route( 'pasat/v1', '/admin/activities/(?P<id>\d+)', array( 'methods' => 'DELETE', 'callback' => array( $admin_activities, 'delete' ), 'permission_callback' => array( $admin_activities, 'can_manage' ), 'args' => self::id_arg() ) );
		register_rest_route( 'pasat/v1', '/admin/activities/(?P<id>\d+)/participation', array( 'methods' => 'GET', 'callback' => array( $admin_participation, 'list_activity' ), 'permission_callback' => array( $admin_participation, 'can_activity' ), 'args' => self::id_arg() ) );
		register_rest_route( 'pasat/v1', '/admin/activities/(?P<id>\d+)/participation', array( 'methods' => 'POST', 'callback' => array( $admin_participation, 'create_for_activity' ), 'permission_callback' => array( $admin_participation, 'can_activity' ), 'args' => array_merge( self::id_arg(), self::participation_args() ) ) );
		register_rest_route( 'pasat/v1', '/admin/activities/(?P<id>\d+)/badges/recalculate', array( 'methods' => 'POST', 'callback' => array( $admin_participation, 'recalculate' ), 'permission_callback' => array( $admin_participation, 'can_activity' ), 'args' => self::id_arg() ) );

		register_rest_route( 'pasat/v1', '/admin/participants/(?P<id>\d+)/badges', array( 'methods' => 'GET', 'callback' => array( $admin_participants, 'badges' ), 'permission_callback' => array( $admin_participants, 'can_view' ), 'args' => self::id_arg() ) );
		register_rest_route( 'pasat/v1', '/admin/participants/(?P<id>\d+)/participation', array( 'methods' => 'GET', 'callback' => array( $admin_participants, 'participation' ), 'permission_callback' => array( $admin_participants, 'can_view' ), 'args' => self::id_arg() ) );
		register_rest_route( 'pasat/v1', '/admin/participants/(?P<id>\d+)/membership', array( 'methods' => array( 'PUT', 'PATCH' ), 'callback' => array( $admin_participants, 'update_membership' ), 'permission_callback' => array( $admin_participants, 'can_manage_memberships' ), 'args' => array_merge( self::id_arg(), self::membership_args() ) ) );
		register_rest_route( 'pasat/v1', '/admin/participation/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( $admin_participation, 'show' ), 'permission_callback' => array( $admin_participation, 'can_log' ), 'args' => self::id_arg() ) );
		register_rest_route( 'pasat/v1', '/admin/participation/(?P<id>\d+)', array( 'methods' => array( 'PUT', 'PATCH' ), 'callback' => array( $admin_participation, 'update' ), 'permission_callback' => array( $admin_participation, 'can_log' ), 'args' => array_merge( self::id_arg(), self::participation_args() ) ) );

		register_rest_route( 'pasat/v1', '/admin/venues', array( 'methods' => 'GET', 'callback' => array( $admin_venues, 'index' ), 'permission_callback' => array( $admin_venues, 'can_manage' ), 'args' => self::list_args() ) );
		register_rest_route( 'pasat/v1', '/admin/venues', array( 'methods' => 'POST', 'callback' => array( $admin_venues, 'create' ), 'permission_callback' => array( $admin_venues, 'can_manage' ), 'args' => self::venue_args() ) );
		register_rest_route( 'pasat/v1', '/admin/venues/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( $admin_venues, 'show' ), 'permission_callback' => array( $admin_venues, 'can_manage' ), 'args' => self::id_arg() ) );
		register_rest_route( 'pasat/v1', '/admin/venues/(?P<id>\d+)', array( 'methods' => array( 'PUT', 'PATCH' ), 'callback' => array( $admin_venues, 'update' ), 'permission_callback' => array( $admin_venues, 'can_manage' ), 'args' => array_merge( self::id_arg(), self::venue_args() ) ) );
		register_rest_route( 'pasat/v1', '/admin/venues/(?P<id>\d+)', array( 'methods' => 'DELETE', 'callback' => array( $admin_venues, 'delete' ), 'permission_callback' => array( $admin_venues, 'can_manage' ), 'args' => self::id_arg() ) );
		register_rest_route( 'pasat/v1', '/admin/venues/(?P<id>\d+)/geocode', array( 'methods' => 'POST', 'callback' => array( $admin_venues, 'geocode' ), 'permission_callback' => array( $admin_venues, 'can_manage' ), 'args' => self::id_arg() ) );

		register_rest_route( 'pasat/v1', '/admin/signups', array( 'methods' => 'GET', 'callback' => array( $admin_signups, 'index' ), 'permission_callback' => array( $admin_signups, 'can_read' ), 'args' => self::signup_list_args() ) );
		register_rest_route( 'pasat/v1', '/admin/signups/(?P<id>\d+)', array( 'methods' => array( 'PUT', 'PATCH' ), 'callback' => array( $admin_signups, 'update' ), 'permission_callback' => array( $admin_signups, 'can_manage' ), 'args' => array_merge( self::id_arg(), self::signup_update_args() ) ) );
		register_rest_route( 'pasat/v1', '/admin/signups/(?P<id>\d+)/cancel', array( 'methods' => 'POST', 'callback' => array( $admin_signups, 'cancel_signup' ), 'permission_callback' => array( $admin_signups, 'can_manage' ), 'args' => self::id_arg() ) );
	}

	private static function id_arg(): array {
		return array(
			'id' => array(
				'required'          => true,
				'sanitize_callback' => 'absint',
				'validate_callback' => static fn( mixed $value ): bool => is_numeric( $value ) && (int) $value > 0,
			),
		);
	}

	private static function list_args(): array {
		return array(
			'limit' => array(
				'sanitize_callback' => 'absint',
				'validate_callback' => static fn( mixed $value ): bool => null === $value || ( is_numeric( $value ) && (int) $value >= 1 && (int) $value <= 500 ),
			),
			'search' => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'venue_id' => array(
				'sanitize_callback' => 'absint',
				'validate_callback' => static fn( mixed $value ): bool => null === $value || '' === $value || ( is_numeric( $value ) && (int) $value >= 0 ),
			),
			'activity_type' => array(
				'sanitize_callback' => 'sanitize_text_field',
			),
			'host_id' => array(
				'sanitize_callback' => 'absint',
				'validate_callback' => static fn( mixed $value ): bool => null === $value || '' === $value || ( is_numeric( $value ) && (int) $value >= 0 ),
			),
		);
	}

	private static function venue_map_args(): array {
		return array(
			'source' => array(
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => static fn( mixed $value ): bool => null === $value || in_array( $value, array( 'upcoming', 'all' ), true ),
			),
			'activity_id' => array(
				'sanitize_callback' => 'absint',
				'validate_callback' => static fn( mixed $value ): bool => null === $value || '' === $value || ( is_numeric( $value ) && (int) $value >= 0 ),
			),
			'limit' => array(
				'sanitize_callback' => 'absint',
				'validate_callback' => static fn( mixed $value ): bool => null === $value || ( is_numeric( $value ) && (int) $value >= 1 && (int) $value <= 500 ),
			),
		);
	}

	private static function public_signup_args(): array {
		return array(
			'activity_id' => array( 'required' => true, 'sanitize_callback' => 'absint', 'validate_callback' => static fn( mixed $value ): bool => is_numeric( $value ) && (int) $value > 0 ),
			'first_name'  => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
			'last_name'   => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
			'email'       => array( 'required' => true, 'sanitize_callback' => 'sanitize_email', 'validate_callback' => static fn( mixed $value ): bool => is_string( $value ) && is_email( $value ) ),
			'nickname'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'phone'       => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'age'         => array( 'sanitize_callback' => 'absint' ),
			'consent_given' => array( 'sanitize_callback' => 'rest_sanitize_boolean' ),
			'membership_opt_in' => array( 'sanitize_callback' => 'rest_sanitize_boolean' ),
			'warning_acknowledged' => array( 'sanitize_callback' => 'rest_sanitize_boolean' ),
		);
	}

	private static function cancel_args(): array {
		return array(
			'token' => array(
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => static fn( mixed $value ): bool => is_string( $value ) && '' !== trim( $value ),
			),
		);
	}

	private static function activity_args(): array {
		return array(
			'title'             => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'description'       => array( 'sanitize_callback' => 'wp_kses_post' ),
			'activity_type'     => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'season_year'       => array( 'sanitize_callback' => 'absint' ),
			'venue_id'          => array( 'sanitize_callback' => 'absint' ),
			'capacity'          => array( 'sanitize_callback' => 'absint' ),
			'status'            => array( 'sanitize_callback' => 'sanitize_key', 'validate_callback' => static fn( mixed $value ): bool => null === $value || in_array( $value, array( 'draft', 'published', 'cancelled', 'archived' ), true ) ),
			'public_visibility' => array( 'sanitize_callback' => 'sanitize_key', 'validate_callback' => static fn( mixed $value ): bool => null === $value || in_array( $value, array( 'public', 'private', 'unlisted' ), true ) ),
			'minimum_age'       => array( 'sanitize_callback' => 'absint' ),
			'maximum_age'       => array( 'sanitize_callback' => 'absint' ),
			'warning_text'      => array( 'sanitize_callback' => 'sanitize_textarea_field' ),
		);
	}

	private static function venue_args(): array {
		return array(
			'name'        => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'description' => array( 'sanitize_callback' => 'sanitize_textarea_field' ),
			'address'     => array( 'sanitize_callback' => 'sanitize_textarea_field' ),
			'venue_type'  => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'capacity'    => array( 'sanitize_callback' => 'absint' ),
			'latitude'    => array( 'sanitize_callback' => static fn( mixed $value ): string => sanitize_text_field( (string) $value ) ),
			'longitude'   => array( 'sanitize_callback' => static fn( mixed $value ): string => sanitize_text_field( (string) $value ) ),
		);
	}

	private static function signup_list_args(): array {
		return array_merge(
			self::list_args(),
			array(
				'activity_id' => array( 'sanitize_callback' => 'absint' ),
				'status'      => array( 'sanitize_callback' => 'sanitize_key', 'validate_callback' => static fn( mixed $value ): bool => null === $value || '' === $value || in_array( $value, array( 'confirmed', 'waitlisted', 'cancelled' ), true ) ),
			)
		);
	}

	private static function signup_update_args(): array {
		return array(
			'status' => array(
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => static fn( mixed $value ): bool => null === $value || in_array( $value, array( 'confirmed', 'waitlisted', 'cancelled' ), true ),
			),
		);
	}

	private static function membership_args(): array {
		return array(
			'membership_status' => array(
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => static fn( mixed $value ): bool => null === $value || in_array( $value, \PASAT\Database\ParticipantsRepository::MEMBERSHIP_STATUSES, true ),
			),
			'membership_number' => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'membership_notes'  => array( 'sanitize_callback' => 'sanitize_textarea_field' ),
		);
	}

	private static function participation_args(): array {
		return array(
			'signup_id'         => array( 'sanitize_callback' => 'absint' ),
			'participant_id'    => array( 'sanitize_callback' => 'absint' ),
			'attendance_status' => array(
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => static fn( mixed $value ): bool => null === $value || in_array( $value, \PASAT\Database\ParticipationLogsRepository::ATTENDANCE_STATUSES, true ),
			),
			'placement'         => array( 'sanitize_callback' => 'absint' ),
			'placement_label'   => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'result_value'      => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'result_unit'       => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'result_notes'      => array( 'sanitize_callback' => 'sanitize_textarea_field' ),
			'private_notes'     => array( 'sanitize_callback' => 'sanitize_textarea_field' ),
		);
	}
}
