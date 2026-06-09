<?php
namespace PASAT\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Tokens {
	public static function generate_for_signup( int $signup_id ): array {
		$secret = wp_generate_password( 48, false, false );
		$token  = $signup_id . ':' . $secret;

		return array(
			'token' => $token,
			'hash'  => self::hash( $token ),
		);
	}

	public static function hash( string $token ): string {
		return hash_hmac( 'sha256', $token, wp_salt( 'secure_auth' ) );
	}

	public static function extract_signup_id( string $token ): int {
		$parts = explode( ':', $token, 2 );
		return isset( $parts[0] ) ? absint( $parts[0] ) : 0;
	}

	public static function verify( string $token, string $stored_hash ): bool {
		if ( '' === $token || '' === $stored_hash ) {
			return false;
		}

		return hash_equals( $stored_hash, self::hash( $token ) );
	}
}
