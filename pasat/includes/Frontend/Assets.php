<?php
namespace PASAT\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {
	public static function register(): void {
		wp_register_style( 'pasat-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
		wp_register_style( 'pasat-public', PASAT_PLUGIN_URL . 'assets/css/public.css', array(), PASAT_VERSION );
		wp_register_script( 'pasat-leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
		wp_register_script( 'pasat-public', PASAT_PLUGIN_URL . 'assets/js/public.js', array(), PASAT_VERSION, true );
		wp_localize_script(
			'pasat-public',
			'PASAT_PUBLIC',
			array(
				'restUrl'       => esc_url_raw( rest_url( 'pasat/v1' ) ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'signupSuccess' => __( 'Signup received. Please check your e-mail.', 'pasat' ),
				'signupFailed'  => __( 'Signup failed.', 'pasat' ),
				'map'           => array(
					'tileUrl'     => sanitize_text_field( (string) \PASAT\Helpers::setting( 'map_tile_url', 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png' ) ),
					'attribution' => wp_kses_post( (string) \PASAT\Helpers::setting( 'map_tile_attribution', __( '&copy; OpenStreetMap contributors', 'pasat' ) ) ),
					'zoom'        => max( 1, min( 20, absint( \PASAT\Helpers::setting( 'map_default_zoom', 13 ) ) ) ),
					'activity'    => __( 'Activity', 'pasat' ),
					'activities'  => __( 'Activities', 'pasat' ),
					'signUp'      => __( 'Sign up', 'pasat' ),
				),
				'board'         => array(
					'cancelled'    => __( 'Cancelled', 'pasat' ),
					'connectionLost' => __( 'Connection lost. Showing last saved board.', 'pasat' ),
					'dateTba'       => __( 'Date to be announced', 'pasat' ),
					/* translators: %d is the number of confirmed signups for an activity. */
					'confirmed'    => __( '%d confirmed', 'pasat' ),
					'fewSpots'     => __( 'Few spots left', 'pasat' ),
					'full'         => __( 'Full', 'pasat' ),
					'noActivities' => __( 'No public activities are currently available.', 'pasat' ),
					'open'         => __( 'Open', 'pasat' ),
					'qrFallback'   => __( 'Signup QR', 'pasat' ),
					'refreshing'   => __( 'Refreshing...', 'pasat' ),
					'signUp'       => __( 'Sign up', 'pasat' ),
					'signupClosed' => __( 'Signup closed', 'pasat' ),
					/* translators: %d is the number of remaining confirmed signup spots. */
					'spotsLeft'    => __( '%d spots left', 'pasat' ),
					'startingSoon' => __( 'Starting soon', 'pasat' ),
					/* translators: %d is the number of seconds since the activity board last refreshed. */
					'updatedSecondsAgo' => __( 'Updated %d seconds ago', 'pasat' ),
					/* translators: %d is the number of minutes since the activity board last refreshed. */
					'updatedMinutesAgo' => __( 'Updated %d minutes ago', 'pasat' ),
					'updated'      => __( 'Updated just now', 'pasat' ),
					'waitlistOpen' => __( 'Waitlist open', 'pasat' ),
					/* translators: %d is the number of waitlisted signups for an activity. */
					'waitlisted'   => __( '%d waitlisted', 'pasat' ),
				),
			)
		);
	}

	public static function enqueue(): void {
		if ( ! wp_style_is( 'pasat-public', 'registered' ) || ! wp_script_is( 'pasat-public', 'registered' ) ) {
			self::register();
		}

		wp_enqueue_style( 'pasat-public' );
		wp_enqueue_script( 'pasat-public' );
	}

	public static function enqueue_map(): void {
		if ( ! wp_style_is( 'pasat-public', 'registered' ) || ! wp_script_is( 'pasat-public', 'registered' ) ) {
			self::register();
		}

		if ( ! empty( \PASAT\Helpers::setting( 'map_enabled', 1 ) ) ) {
			if ( wp_style_is( 'pasat-public', 'enqueued' ) ) {
				wp_dequeue_style( 'pasat-public' );
			}
			if ( wp_script_is( 'pasat-public', 'enqueued' ) ) {
				wp_dequeue_script( 'pasat-public' );
			}
			wp_enqueue_style( 'pasat-leaflet' );
			wp_enqueue_script( 'pasat-leaflet' );
		}

		wp_enqueue_style( 'pasat-public' );
		wp_enqueue_script( 'pasat-public' );
	}
}
