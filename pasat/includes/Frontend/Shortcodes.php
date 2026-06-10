<?php
namespace PASAT\Frontend;

use PASAT\Database\ActivitiesRepository;
use PASAT\Database\ParticipantsRepository;
use PASAT\Database\SignupsRepository;
use PASAT\Database\VenuesRepository;
use PASAT\Email\Mailer;
use PASAT\Helpers;
use PASAT\REST\PublicSignupController;
use PASAT\Security\RateLimiter;
use PASAT\Security\Tokens;

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
		$query_id    = isset( $_GET['pasat_activity_id'] ) ? absint( wp_unslash( $_GET['pasat_activity_id'] ) ) : 0;
		$activity_id = absint( $atts['activity_id'] ?: $query_id );
		$message     = '';
		$error       = '';
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

		if ( 'POST' === $request_method && isset( $_POST['pasat_public_signup'] ) ) {
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
		$items    = array();
		$notice   = __( 'Enter your e-mail address to receive a private signup lookup link.', 'pasat' );
		$error    = '';
		$verified = false;
		$email    = '';
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';

		if ( isset( $_GET['pasat_lookup_token'] ) ) {
			$token = sanitize_text_field( wp_unslash( $_GET['pasat_lookup_token'] ) );
			$email = self::email_from_lookup_token( $token );
			if ( $email ) {
				$verified = true;
				$items    = ( new ParticipantsRepository() )->signups_for_email( $email );
				$notice   = $items ? __( 'Your verified signups are listed below.', 'pasat' ) : __( 'No signups were found for this verified e-mail address.', 'pasat' );
			} else {
				$error = __( 'The signup lookup link is invalid or expired.', 'pasat' );
			}
		}

		if ( 'POST' === $request_method && isset( $_POST['pasat_my_signups'] ) ) {
			if ( ! isset( $_POST['pasat_my_signups_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pasat_my_signups_nonce'] ) ), 'pasat_my_signups' ) ) {
				$error = __( 'The lookup form expired. Please try again.', 'pasat' );
			} else {
				$limited = RateLimiter::check( 'public_my_signups', 5, 300 );
				if ( is_wp_error( $limited ) ) {
					$error = $limited->get_error_message();
				} else {
					$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
					if ( is_email( $email ) ) {
						$token = wp_generate_password( 48, false, false );
						set_transient( self::lookup_transient_key( $token ), strtolower( $email ), 30 * MINUTE_IN_SECONDS );
						Mailer::send_lookup_link( $email, self::lookup_url( $token ) );
						$notice = __( 'If that e-mail address can receive mail, a private signup lookup link has been sent.', 'pasat' );
					} else {
						$error = __( 'Please enter a valid e-mail address.', 'pasat' );
					}
				}
			}
		}

		return Renderer::render(
			'public/my-signups.php',
			array(
				'items'    => $items,
				'notice'   => $notice,
				'error'    => $error,
				'verified' => $verified,
				'email'    => $email,
			)
		);
	}

	public static function venue_map(): string {
		Assets::enqueue();
		$venues = array_values(
			array_filter(
				( new VenuesRepository() )->list(),
				static fn( array $venue ): bool => '' !== (string) ( $venue['latitude'] ?? '' ) && '' !== (string) ( $venue['longitude'] ?? '' )
			)
		);

		return Renderer::render( 'public/venue-map.php', array( 'venues' => $venues ) );
	}

	public static function activity_board( array $atts = array() ): string {
		Assets::enqueue();
		$atts = shortcode_atts(
			array(
				'mode'          => '',
				'show_qr'       => '0',
				'venue_id'      => '0',
				'activity_type' => '',
				'host_id'       => '0',
				'refresh'       => '60000',
				'limit'         => '20',
				'few_spots'     => '3',
			),
			$atts,
			'pasat_activity_board'
		);

		$mode          = 'kiosk' === sanitize_key( (string) $atts['mode'] ) ? 'kiosk' : '';
		$show_qr       = in_array( strtolower( (string) $atts['show_qr'] ), array( '1', 'true', 'yes', 'on' ), true );
		$venue_id      = absint( $atts['venue_id'] );
		$activity_type = sanitize_text_field( (string) $atts['activity_type'] );
		$host_id       = absint( $atts['host_id'] );
		$refresh       = max( 15000, absint( $atts['refresh'] ) ?: 60000 );
		$limit         = max( 1, min( 100, absint( $atts['limit'] ) ?: 20 ) );
		$few_spots     = max( 1, absint( $atts['few_spots'] ) ?: 3 );
		$query         = array(
			'public'   => true,
			'upcoming' => true,
			'limit'    => $limit,
		);

		if ( $venue_id > 0 ) {
			$query['venue_id'] = $venue_id;
		}

		if ( '' !== $activity_type ) {
			$query['activity_type'] = $activity_type;
		}

		if ( $host_id > 0 ) {
			$query['host_id'] = $host_id;
		}

		$activities = ( new ActivitiesRepository() )->list( $query );

		return Renderer::render(
			'public/activity-list.php',
			array(
				'activities'    => $activities,
				'signups'       => new SignupsRepository(),
				'board'         => true,
				'board_options' => array(
					'mode'          => $mode,
					'show_qr'       => $show_qr,
					'venue_id'      => $venue_id,
					'activity_type' => $activity_type,
					'host_id'       => $host_id,
					'refresh'       => $refresh,
					'limit'         => $limit,
					'few_spots'     => $few_spots,
				),
			)
		);
	}

	public static function handle_cancellation_link(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public cancellation links use high-entropy signed tokens, not WordPress session nonces.
		$token  = isset( $_GET['token'] ) ? rawurldecode( sanitize_text_field( wp_unslash( $_GET['token'] ) ) ) : '';
		$result = PublicSignupController::process_cancellation( $token );
		$key    = is_wp_error( $result ) ? 'pasat_cancel_error' : 'pasat_cancelled';
		$value  = is_wp_error( $result ) ? rawurlencode( $result->get_error_message() ) : '1';
		$page   = absint( Helpers::setting( 'public_page_id', 0 ) );
		$url    = $page ? get_permalink( $page ) : home_url( '/' );

		wp_safe_redirect( add_query_arg( $key, $value, $url ) );
		exit;
	}

	public static function handle_activity_qr_redirect(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public QR redirects intentionally use an activity id, not private data.
		$activity_id = absint( wp_unslash( $_GET['psa'] ?? 0 ) );
		if ( ! $activity_id ) {
			return;
		}

		wp_safe_redirect( Helpers::public_signup_url( $activity_id ) );
		exit;
	}

	private static function lookup_url( string $token ): string {
		$page_id = absint( Helpers::setting( 'public_page_id', 0 ) );
		$url     = $page_id ? get_permalink( $page_id ) : home_url( '/' );

		return add_query_arg( 'pasat_lookup_token', rawurlencode( $token ), $url );
	}

	private static function lookup_transient_key( string $token ): string {
		return 'pasat_lookup_' . Tokens::hash( $token );
	}

	private static function email_from_lookup_token( string $token ): string {
		if ( '' === $token ) {
			return '';
		}

		$email = get_transient( self::lookup_transient_key( $token ) );
		return is_string( $email ) && is_email( $email ) ? $email : '';
	}
}
