<?php
namespace PASAT\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Assets {
	public static function register(): void {
		wp_register_style( 'pasat-public', PASAT_PLUGIN_URL . 'assets/css/public.css', array(), PASAT_VERSION );
		wp_register_script( 'pasat-public', PASAT_PLUGIN_URL . 'assets/js/public.js', array(), PASAT_VERSION, true );
		wp_localize_script(
			'pasat-public',
			'PASAT_PUBLIC',
			array(
				'restUrl'       => esc_url_raw( rest_url( 'pasat/v1' ) ),
				'nonce'         => wp_create_nonce( 'wp_rest' ),
				'signupSuccess' => __( 'Signup received. Please check your e-mail.', 'pasat' ),
				'signupFailed'  => __( 'Signup failed.', 'pasat' ),
				'board'         => array(
					'cancelled'    => __( 'Cancelled', 'pasat' ),
					'connectionLost' => __( 'Connection lost. Showing last saved board.', 'pasat' ),
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
}
