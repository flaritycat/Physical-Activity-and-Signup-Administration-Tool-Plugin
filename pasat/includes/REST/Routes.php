<?php
namespace PASAT\REST;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Routes {
	public static function register(): void {
		$public_activities = new PublicActivitiesController();
		$public_signup     = new PublicSignupController();
		$admin_activities  = new AdminActivitiesController();
		$admin_venues      = new AdminVenuesController();
		$admin_signups     = new AdminSignupsController();

		register_rest_route( 'pasat/v1', '/activities', array( 'methods' => 'GET', 'callback' => array( $public_activities, 'index' ), 'permission_callback' => '__return_true' ) );
		register_rest_route( 'pasat/v1', '/activities/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( $public_activities, 'show' ), 'permission_callback' => '__return_true' ) );
		register_rest_route( 'pasat/v1', '/signups', array( 'methods' => 'POST', 'callback' => array( $public_signup, 'create' ), 'permission_callback' => '__return_true' ) );
		register_rest_route( 'pasat/v1', '/signups/cancel', array( 'methods' => 'POST', 'callback' => array( $public_signup, 'cancel' ), 'permission_callback' => '__return_true' ) );

		register_rest_route( 'pasat/v1', '/admin/activities', array( 'methods' => 'GET', 'callback' => array( $admin_activities, 'index' ), 'permission_callback' => array( $admin_activities, 'can_read' ) ) );
		register_rest_route( 'pasat/v1', '/admin/activities', array( 'methods' => 'POST', 'callback' => array( $admin_activities, 'create' ), 'permission_callback' => array( $admin_activities, 'can_manage' ) ) );
		register_rest_route( 'pasat/v1', '/admin/activities/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( $admin_activities, 'show' ), 'permission_callback' => array( $admin_activities, 'can_read' ) ) );
		register_rest_route( 'pasat/v1', '/admin/activities/(?P<id>\d+)', array( 'methods' => 'PUT,PATCH', 'callback' => array( $admin_activities, 'update' ), 'permission_callback' => array( $admin_activities, 'can_manage' ) ) );
		register_rest_route( 'pasat/v1', '/admin/activities/(?P<id>\d+)', array( 'methods' => 'DELETE', 'callback' => array( $admin_activities, 'delete' ), 'permission_callback' => array( $admin_activities, 'can_manage' ) ) );

		register_rest_route( 'pasat/v1', '/admin/venues', array( 'methods' => 'GET', 'callback' => array( $admin_venues, 'index' ), 'permission_callback' => array( $admin_venues, 'can_manage' ) ) );
		register_rest_route( 'pasat/v1', '/admin/venues', array( 'methods' => 'POST', 'callback' => array( $admin_venues, 'create' ), 'permission_callback' => array( $admin_venues, 'can_manage' ) ) );
		register_rest_route( 'pasat/v1', '/admin/venues/(?P<id>\d+)', array( 'methods' => 'GET', 'callback' => array( $admin_venues, 'show' ), 'permission_callback' => array( $admin_venues, 'can_manage' ) ) );
		register_rest_route( 'pasat/v1', '/admin/venues/(?P<id>\d+)', array( 'methods' => 'PUT,PATCH', 'callback' => array( $admin_venues, 'update' ), 'permission_callback' => array( $admin_venues, 'can_manage' ) ) );
		register_rest_route( 'pasat/v1', '/admin/venues/(?P<id>\d+)', array( 'methods' => 'DELETE', 'callback' => array( $admin_venues, 'delete' ), 'permission_callback' => array( $admin_venues, 'can_manage' ) ) );

		register_rest_route( 'pasat/v1', '/admin/signups', array( 'methods' => 'GET', 'callback' => array( $admin_signups, 'index' ), 'permission_callback' => array( $admin_signups, 'can_read' ) ) );
		register_rest_route( 'pasat/v1', '/admin/signups/(?P<id>\d+)', array( 'methods' => 'PUT,PATCH', 'callback' => array( $admin_signups, 'update' ), 'permission_callback' => array( $admin_signups, 'can_manage' ) ) );
		register_rest_route( 'pasat/v1', '/admin/signups/(?P<id>\d+)/cancel', array( 'methods' => 'POST', 'callback' => array( $admin_signups, 'cancel_signup' ), 'permission_callback' => array( $admin_signups, 'can_manage' ) ) );
	}
}
