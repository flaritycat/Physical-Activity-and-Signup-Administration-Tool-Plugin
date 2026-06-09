<?php
namespace PASAT\Frontend;

use PASAT\Database\ActivitiesRepository;
use PASAT\Database\ParticipantsRepository;
use PASAT\Database\SignupsRepository;
use PASAT\Database\VenuesRepository;
use PASAT\Helpers;
use PASAT\REST\PublicSignupController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Shortcodes {
	public static function register(): void {
		add_shortcode( 'pasat_activity_list', array( self::class, 'activity_list' ) );
		add_shortcode( 'pasat_activity_signup', array( self::class, 'activity_signup' ) );
		add_shortcode( 'pasat_my_signups', array( self::class, 'my_signups' ) );
		add_shortcode( 'pasat_venue_map', array( self::class, 'venue_map' ) );
		add_shortcode( 'pasat_activity_board', array( self::class, 'activity_board' ) );
	}

	public static function activity_list( array $atts = array() ): string {
		Assets::enqueue();
		$atts       = shortcode_atts( array( 'limit' => 100 ), $atts, 'pasat_activity_list' );
		$activities = ( new ActivitiesRepository() )->list(
			array(
				'public'   => true,
				'upcoming' => true,
				'limit'    => absint( $atts['limit'] ),
			)
		);
		$signups = new SignupsRepository();

		return Renderer::render(
			'public/activity-list.php',
			array(
				'activities' => $activities,
				'signups'    => $signups,
			)
		);
	}

	public static function activity_signup( array $atts = array() ): string {
		Assets::enqueue();
		$atts        = shortcode_atts( array( 'activity_id' => 0 ), $atts, 'pasat_activity_signup' );
		$activity_id = absint( $atts['activity_id'] ?: ( $_GET['pasat_activity_id'] ?? 0 ) );
		$message     = '';
		$error       = '';

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['pasat_public_signup'] ) ) {
			if ( ! isset( $_POST['pasat_public_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pasat_public_nonce'] ) ), 'pasat_public_signup' ) ) {
				$error = __( 'The signup form expired. Please try again.', 'pasat' );
			} else {
				$result = PublicSignupController::process_signup( wp_unslash( $_POST ) );
				if ( is_wp_error( $result ) ) {
					$error = $result->get_error_message();
				} else {
					$message = sprintf(
						/* translators: %s is signup status. */
						__( 'Signup received. Your status is %s. Please check your e-mail for details.', 'pasat' ),
						$result['status']
					);
				}
			}
		}

		$repo       = new ActivitiesRepository();
		$activities = $activity_id ? array_filter( array( $repo->get_with_venue( $activity_id ) ) ) : $repo->list( array( 'public' => true, 'upcoming' => true, 'limit' => 100 ) );
		$activity   = $activity_id ? reset( $activities ) : null;

		return Renderer::render(
			'public/signup-form.php',
			array(
				'activities' => $activities,
				'activity'   => $activity ?: null,
				'message'    => $message,
				'error'      => $error,
				'settings'   => Helpers::settings(),
			)
		);
	}

	public static function my_signups(): string {
		Assets::enqueue();
		$items = array();
		$notice = __( 'Enter your e-mail address to request a private signup lookup. This MVP does not display private signup data until e-mail verification is completed.', 'pasat' );
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['pasat_my_signups'] ) ) {
			$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
			if ( is_email( $email ) ) {
				$items  = array();
				$notice = __( 'If signups exist for that e-mail address, a secure lookup flow can be enabled in a future release. No private signup data has been exposed.', 'pasat' );
			}
		}

		return Renderer::render( 'public/my-signups.php', array( 'items' => $items, 'notice' => $notice ) );
	}

	public static function venue_map(): string {
		Assets::enqueue();
		$venues = array_filter(
			( new VenuesRepository() )->list(),
			static fn( array $venue ): bool => '' !== (string) ( $venue['latitude'] ?? '' ) && '' !== (string) ( $venue['longitude'] ?? '' )
		);

		return '<div class="pasat-venue-map" data-venues="' . esc_attr( wp_json_encode( array_values( $venues ) ) ) . '">' . esc_html__( 'Venue map data is available for theme or script integration.', 'pasat' ) . '</div>';
	}

	public static function activity_board(): string {
		Assets::enqueue();
		$activities = ( new ActivitiesRepository() )->list( array( 'public' => true, 'upcoming' => true, 'limit' => 20 ) );
		return Renderer::render( 'public/activity-list.php', array( 'activities' => $activities, 'signups' => new SignupsRepository(), 'board' => true ) );
	}

	public static function handle_cancellation_link(): void {
		$token  = isset( $_GET['token'] ) ? rawurldecode( sanitize_text_field( wp_unslash( $_GET['token'] ) ) ) : '';
		$result = PublicSignupController::process_cancellation( $token );
		$key    = is_wp_error( $result ) ? 'pasat_cancel_error' : 'pasat_cancelled';
		$value  = is_wp_error( $result ) ? rawurlencode( $result->get_error_message() ) : '1';
		$page   = absint( Helpers::setting( 'public_page_id', 0 ) );
		$url    = $page ? get_permalink( $page ) : home_url( '/' );

		wp_safe_redirect( add_query_arg( $key, $value, $url ) );
		exit;
	}
}
