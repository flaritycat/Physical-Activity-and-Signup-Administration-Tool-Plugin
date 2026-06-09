<?php
namespace PASAT\Email;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Templates {
	public static function replace( string $template, array $context ): string {
		$defaults = array(
			'organization_name' => Helpers::setting( 'organization_name', get_bloginfo( 'name' ) ),
			'activity_title'    => '',
			'activity_date'     => '',
			'activity_time'     => '',
			'venue_name'        => '',
			'participant_name'  => '',
			'signup_status'     => '',
			'cancellation_url'  => '',
			'site_name'         => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'site_url'          => home_url( '/' ),
		);

		$context = wp_parse_args( $context, $defaults );
		$replace = array();
		foreach ( $context as $key => $value ) {
			$replace[ '{' . $key . '}' ] = (string) $value;
		}

		return strtr( $template, $replace );
	}

	public static function context_from_signup( array $signup, string $cancellation_url = '' ): array {
		$timestamp = ! empty( $signup['starts_at'] ) ? strtotime( $signup['starts_at'] . ' UTC' ) : false;

		return array(
			'activity_title'   => $signup['activity_title'] ?? '',
			'activity_date'    => $timestamp ? wp_date( get_option( 'date_format' ), $timestamp ) : '',
			'activity_time'    => $timestamp ? wp_date( get_option( 'time_format' ), $timestamp ) : '',
			'venue_name'       => $signup['venue_name'] ?? '',
			'participant_name' => trim( ( $signup['first_name'] ?? '' ) . ' ' . ( $signup['last_name'] ?? '' ) ),
			'signup_status'    => ucfirst( (string) ( $signup['status'] ?? '' ) ),
			'cancellation_url' => $cancellation_url,
		);
	}
}
