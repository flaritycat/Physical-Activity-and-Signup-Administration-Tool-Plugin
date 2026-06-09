<?php
namespace PASAT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Capabilities {
	public const CAPABILITIES = array(
		'pasat_manage_settings',
		'pasat_manage_all_activities',
		'pasat_manage_assigned_activities',
		'pasat_manage_venues',
		'pasat_view_signups',
		'pasat_manage_signups',
		'pasat_view_participants',
		'pasat_export_participants',
		'pasat_manage_hosts',
		'pasat_view_audit_log',
		'pasat_run_privacy_tools',
	);

	public static function install(): void {
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::CAPABILITIES as $capability ) {
				$admin->add_cap( $capability );
			}
		}

		add_role(
			'pasat_activity_manager',
			__( 'PASAT Activity Manager', 'pasat' ),
			array(
				'read'                        => true,
				'pasat_manage_all_activities' => true,
				'pasat_manage_venues'         => true,
				'pasat_view_signups'          => true,
				'pasat_manage_signups'        => true,
				'pasat_view_participants'     => true,
				'pasat_export_participants'   => true,
			)
		);

		add_role(
			'pasat_activity_host',
			__( 'PASAT Activity Host', 'pasat' ),
			array(
				'read'                             => true,
				'pasat_manage_assigned_activities' => true,
				'pasat_view_signups'               => true,
			)
		);
	}

	public static function can_manage_activity( int $activity_id = 0 ): bool {
		if ( current_user_can( 'pasat_manage_all_activities' ) ) {
			return true;
		}

		if ( $activity_id > 0 && current_user_can( 'pasat_manage_assigned_activities' ) ) {
			$hosts = new Database\HostsRepository();
			return $hosts->user_is_host_for_activity( get_current_user_id(), $activity_id );
		}

		return false;
	}
}
