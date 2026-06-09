<?php
namespace PASAT\Email;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Mailer {
	public static function cancellation_url( string $token ): string {
		return add_query_arg(
			array(
				'action' => 'pasat_cancel_signup',
				'token'  => rawurlencode( $token ),
			),
			admin_url( 'admin-post.php' )
		);
	}

	public static function send_signup_confirmation( array $signup, string $token ): bool {
		$url      = self::cancellation_url( $token );
		$context  = Templates::context_from_signup( $signup, $url );
		$settings = Helpers::settings();

		return self::send(
			(string) $signup['email'],
			Templates::replace( $settings['confirmation_subject'], $context ),
			Templates::replace( $settings['confirmation_body'], $context )
		);
	}

	public static function send_cancellation_confirmation( array $signup ): bool {
		$context  = Templates::context_from_signup( $signup );
		$settings = Helpers::settings();

		return self::send(
			(string) $signup['email'],
			Templates::replace( $settings['cancellation_subject'], $context ),
			Templates::replace( $settings['cancellation_body'], $context )
		);
	}

	public static function send_waitlist_promotion( array $signup, string $token = '' ): bool {
		$url      = $token ? self::cancellation_url( $token ) : '';
		$context  = Templates::context_from_signup( $signup, $url );
		$settings = Helpers::settings();

		return self::send(
			(string) $signup['email'],
			Templates::replace( $settings['waitlist_promotion_subject'], $context ),
			Templates::replace( $settings['waitlist_promotion_body'], $context )
		);
	}

	public static function send_activity_cancellation( array $signup ): bool {
		$context  = Templates::context_from_signup( $signup );
		$settings = Helpers::settings();

		return self::send(
			(string) $signup['email'],
			Templates::replace( $settings['activity_cancelled_subject'], $context ),
			Templates::replace( $settings['activity_cancelled_body'], $context )
		);
	}

	public static function send_lookup_link( string $recipient_email, string $lookup_url ): bool {
		$settings = Helpers::settings();
		$context  = array(
			'participant_name' => __( 'Participant', 'pasat' ),
			'site_name'        => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'site_url'         => home_url( '/' ),
			'organization_name' => $settings['organization_name'] ?? get_bloginfo( 'name' ),
		);

		$subject = Templates::replace( __( 'Your signup lookup link for {site_name}', 'pasat' ), $context );
		$body    = Templates::replace(
			sprintf(
				/* translators: %s is a private signup lookup URL. */
				__( "Use this private link to view your activity signups:\n\n%s\n\nThe link expires soon. If you did not request it, you can ignore this e-mail.\n\n{organization_name}", 'pasat' ),
				$lookup_url
			),
			$context
		);

		return self::send( $recipient_email, $subject, $body );
	}

	public static function send_test_email( string $recipient_email ): bool {
		$settings = Helpers::settings();
		$context  = array(
			'organization_name' => $settings['organization_name'] ?? get_bloginfo( 'name' ),
			'site_name'         => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'site_url'          => home_url( '/' ),
		);

		$subject = Templates::replace( __( 'PASAT test e-mail for {site_name}', 'pasat' ), $context );
		$body    = Templates::replace(
			__( "This is a PASAT test e-mail from {site_name}.\n\nIf you received it, WordPress mail delivery is working for PASAT notifications.\n\n{organization_name}\n{site_url}", 'pasat' ),
			$context
		);

		return self::send( $recipient_email, $subject, $body );
	}

	private static function send( string $to, string $subject, string $body ): bool {
		$headers = array();
		$sender  = trim( (string) Helpers::setting( 'sender_name', '' ) );
		if ( '' !== $sender ) {
			$headers[] = 'From: ' . $sender . ' <' . get_option( 'admin_email' ) . '>';
		}

		return wp_mail( $to, wp_specialchars_decode( $subject, ENT_QUOTES ), $body, $headers );
	}
}
