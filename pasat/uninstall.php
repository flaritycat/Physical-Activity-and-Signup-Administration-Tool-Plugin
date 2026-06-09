<?php
/**
 * PASAT uninstall cleanup.
 *
 * @package PASAT
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'pasat_audit_log',
	$wpdb->prefix . 'pasat_activity_hosts',
	$wpdb->prefix . 'pasat_signups',
	$wpdb->prefix . 'pasat_participants',
	$wpdb->prefix . 'pasat_activities',
	$wpdb->prefix . 'pasat_venues',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

delete_option( 'pasat_settings' );
delete_option( 'pasat_db_version' );
wp_clear_scheduled_hook( 'pasat_daily_retention_cleanup' );
