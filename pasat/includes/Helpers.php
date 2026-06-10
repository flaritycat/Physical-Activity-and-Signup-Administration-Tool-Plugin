<?php
namespace PASAT;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Helpers {
	public static function default_settings(): array {
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		return array(
			'organization_name'                  => $site_name ?: __( 'Organization', 'pasat' ),
			'public_page_id'                     => 0,
			'activity_label'                     => __( 'Activity', 'pasat' ),
			'host_label'                         => __( 'Host', 'pasat' ),
			'default_season_year'                => (int) gmdate( 'Y' ),
			'default_capacity'                   => 20,
			'default_waitlist_enabled'           => 1,
			'confirmation_subject'               => __( 'Signup confirmation: {activity_title}', 'pasat' ),
			'confirmation_body'                  => "Hello {participant_name},\n\nYour signup for {activity_title} is {signup_status}.\n\nDate: {activity_date}\nTime: {activity_time}\nVenue: {venue_name}\n\nCancel your signup:\n{cancellation_url}\n\n{organization_name}",
			'cancellation_subject'               => __( 'Signup cancelled: {activity_title}', 'pasat' ),
			'cancellation_body'                  => "Hello {participant_name},\n\nYour signup for {activity_title} has been cancelled.\n\n{organization_name}",
			'waitlist_promotion_subject'         => __( 'You are confirmed: {activity_title}', 'pasat' ),
			'waitlist_promotion_body'            => "Hello {participant_name},\n\nA space opened for {activity_title}. Your waitlist signup is now confirmed.\n\nDate: {activity_date}\nTime: {activity_time}\nVenue: {venue_name}\n\nCancel your signup:\n{cancellation_url}\n\n{organization_name}",
			'activity_cancelled_subject'         => __( 'Activity cancelled: {activity_title}', 'pasat' ),
			'activity_cancelled_body'            => "Hello {participant_name},\n\n{activity_title} has been cancelled.\n\n{organization_name}",
			'sender_name'                        => $site_name,
			'retention_period_days'              => 365,
			'erasure_mode'                       => 'anonymize',
			'require_consent'                    => 1,
			'consent_text'                       => __( 'I consent to the processing of my signup information for activity administration.', 'pasat' ),
			'default_warning_text'               => __( 'I acknowledge the activity information and any safety warnings.', 'pasat' ),
			'pasat_strict_email_delivery'        => 0,
			'allow_duplicate_email_per_activity' => 0,
			'map_enabled'                        => 0,
		);
	}

	public static function settings(): array {
		return wp_parse_args( get_option( 'pasat_settings', array() ), self::default_settings() );
	}

	public static function setting( string $key, mixed $default = null ): mixed {
		$settings = self::settings();
		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	public static function now(): string {
		return current_time( 'mysql', true );
	}

	public static function local_datetime( ?string $mysql_datetime ): string {
		if ( empty( $mysql_datetime ) ) {
			return '';
		}

		$timestamp = strtotime( $mysql_datetime . ' UTC' );
		return $timestamp ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) : '';
	}

	public static function mysql_from_local_input( string $value ): ?string {
		$value = trim( $value );
		if ( '' === $value ) {
			return null;
		}

		$timestamp = strtotime( $value, current_time( 'timestamp' ) );
		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
	}

	public static function table( string $name ): string {
		global $wpdb;
		return $wpdb->prefix . 'pasat_' . $name;
	}

	public static function hash_identifier( string $value ): string {
		$value = trim( $value );
		if ( '' === $value ) {
			return '';
		}

		return hash_hmac( 'sha256', $value, wp_salt( 'auth' ) );
	}

	public static function client_ip_hash(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return self::hash_identifier( $ip );
	}

	public static function user_agent_hash(): string {
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return self::hash_identifier( $ua );
	}

	public static function public_signup_url( int $activity_id = 0 ): string {
		$page_id = absint( self::setting( 'public_page_id', 0 ) );
		$url     = $page_id ? get_permalink( $page_id ) : home_url( '/' );

		if ( ! $url ) {
			$url = home_url( '/' );
		}

		if ( $activity_id > 0 ) {
			$url = add_query_arg( 'pasat_activity_id', $activity_id, $url ) . '#pasat-signup';
		}

		return $url;
	}

	public static function csv_cell( mixed $value ): string {
		$value = (string) $value;
		return preg_match( '/^[=\-+@]/', $value ) ? "'" . $value : $value;
	}
}
