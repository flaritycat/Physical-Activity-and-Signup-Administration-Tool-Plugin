<?php
/**
 * PASAT uninstall cleanup.
 *
 * @package PASAT
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
// Uninstall cleanup intentionally drops only PASAT tables built from fixed plugin table names.
global $wpdb;

$pasat_tables = array(
	$wpdb->prefix . 'pasat_audit_log',
	$wpdb->prefix . 'pasat_activity_hosts',
	$wpdb->prefix . 'pasat_participant_badges',
	$wpdb->prefix . 'pasat_participation_logs',
	$wpdb->prefix . 'pasat_signups',
	$wpdb->prefix . 'pasat_participants',
	$wpdb->prefix . 'pasat_activities',
	$wpdb->prefix . 'pasat_venues',
);

foreach ( $pasat_tables as $pasat_table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$pasat_table}" );
}

delete_option( 'pasat_settings' );
delete_option( 'pasat_db_version' );
delete_option( 'pasat_mail_last_test_at' );
wp_clear_scheduled_hook( 'pasat_daily_retention_cleanup' );
