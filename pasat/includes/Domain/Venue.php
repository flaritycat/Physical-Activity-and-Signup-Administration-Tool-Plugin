<?php
namespace PASAT\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Venue {
	public static function label( ?array $venue ): string {
		return $venue && ! empty( $venue['name'] ) ? (string) $venue['name'] : __( 'No venue', 'pasat' );
	}
}
