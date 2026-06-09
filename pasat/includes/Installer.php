<?php
namespace PASAT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Installer {
	public static function install_defaults(): void {
		if ( false === get_option( 'pasat_settings', false ) ) {
			add_option( 'pasat_settings', Helpers::default_settings() );
		} else {
			update_option( 'pasat_settings', wp_parse_args( get_option( 'pasat_settings', array() ), Helpers::default_settings() ) );
		}
	}

	public static function schedule_events(): void {
		if ( ! wp_next_scheduled( 'pasat_daily_retention_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'pasat_daily_retention_cleanup' );
		}
	}

	public static function clear_scheduled_events(): void {
		wp_clear_scheduled_hook( 'pasat_daily_retention_cleanup' );
	}
}
