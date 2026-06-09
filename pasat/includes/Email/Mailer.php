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

	private static function send( string $to, string $subject, string $body ): bool {
		$headers = array();
		$sender  = trim( (string) Helpers::setting( 'sender_name', '' ) );
		if ( '' !== $sender ) {
			$headers[] = 'From: ' . $sender . ' <' . get_option( 'admin_email' ) . '>';
		}

		return wp_mail( $to, wp_specialchars_decode( $subject, ENT_QUOTES ), $body, $headers );
	}
}
