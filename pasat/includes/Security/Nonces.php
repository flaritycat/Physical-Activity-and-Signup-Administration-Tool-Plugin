<?php
namespace PASAT\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Nonces {
	public static function action( string $name ): string {
		return 'pasat_' . sanitize_key( $name );
	}

	public static function field( string $name ): void {
		wp_nonce_field( self::action( $name ), '_pasat_nonce' );
	}

	public static function verify( string $name ): void {
		check_admin_referer( self::action( $name ), '_pasat_nonce' );
	}
}
