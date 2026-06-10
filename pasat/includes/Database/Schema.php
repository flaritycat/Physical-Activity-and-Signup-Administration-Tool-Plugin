<?php
namespace PASAT\Database;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Schema {
	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		$sql = array();

		$sql[] = 'CREATE TABLE ' . Helpers::table( 'venues' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			description LONGTEXT NULL,
			address TEXT NULL,
			latitude DECIMAL(10,7) NULL,
			longitude DECIMAL(10,7) NULL,
			venue_type VARCHAR(100) NULL,
			capacity INT UNSIGNED NULL,
			geocoded_at DATETIME NULL,
			geocoding_status VARCHAR(30) NOT NULL DEFAULT 'not_geocoded',
			geocoding_error TEXT NULL,
			geocoding_provider VARCHAR(100) NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY name (name(191)),
			KEY geocoding_status (geocoding_status)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . Helpers::table( 'activities' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			title VARCHAR(255) NOT NULL,
			description LONGTEXT NULL,
			activity_type VARCHAR(100) NULL,
			season_year SMALLINT UNSIGNED NULL,
			starts_at DATETIME NULL,
			ends_at DATETIME NULL,
			venue_id BIGINT UNSIGNED NULL,
			capacity INT UNSIGNED NULL,
			waitlist_enabled TINYINT(1) NOT NULL DEFAULT 1,
			signup_opens_at DATETIME NULL,
			signup_closes_at DATETIME NULL,
			status VARCHAR(30) NOT NULL DEFAULT 'draft',
			public_visibility VARCHAR(30) NOT NULL DEFAULT 'public',
			minimum_age INT UNSIGNED NULL,
			maximum_age INT UNSIGNED NULL,
			requires_warning_ack TINYINT(1) NOT NULL DEFAULT 0,
			warning_text LONGTEXT NULL,
			created_by BIGINT UNSIGNED NULL,
			updated_by BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY starts_at (starts_at),
			KEY venue_id (venue_id)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . Helpers::table( 'participants' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			first_name VARCHAR(120) NOT NULL,
			last_name VARCHAR(120) NOT NULL,
			nickname VARCHAR(120) NULL,
			email VARCHAR(190) NOT NULL,
			phone VARCHAR(60) NULL,
			age INT UNSIGNED NULL,
			consent_given TINYINT(1) NOT NULL DEFAULT 0,
			consent_version VARCHAR(50) NULL,
			consented_at DATETIME NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY email (email)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . Helpers::table( 'signups' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			activity_id BIGINT UNSIGNED NOT NULL,
			participant_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(30) NOT NULL DEFAULT 'confirmed',
			waitlist_position INT UNSIGNED NULL,
			cancellation_token_hash VARCHAR(255) NULL,
			cancelled_at DATETIME NULL,
			cancellation_reason TEXT NULL,
			source VARCHAR(50) NULL,
			ip_hash VARCHAR(255) NULL,
			user_agent_hash VARCHAR(255) NULL,
			warning_acknowledged TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY activity_id (activity_id),
			KEY participant_id (participant_id),
			KEY status (status),
			KEY activity_status_created (activity_id,status,created_at)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . Helpers::table( 'activity_hosts' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			activity_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL,
			role VARCHAR(50) NOT NULL DEFAULT 'host',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY activity_user (activity_id,user_id),
			KEY activity_id (activity_id),
			KEY user_id (user_id)
		) $charset;";

		$sql[] = 'CREATE TABLE ' . Helpers::table( 'audit_log' ) . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NULL,
			action VARCHAR(120) NOT NULL,
			object_type VARCHAR(120) NULL,
			object_id BIGINT UNSIGNED NULL,
			message TEXT NULL,
			ip_hash VARCHAR(255) NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY action (action),
			KEY object_ref (object_type,object_id),
			KEY user_id (user_id)
		) $charset;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		update_option( 'pasat_db_version', PASAT_DB_VERSION );
	}
}
