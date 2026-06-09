<?php
namespace PASAT\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Waitlist {
	public static function status_label( string $status ): string {
		return match ( $status ) {
			'confirmed' => __( 'Confirmed', 'pasat' ),
			'waitlisted' => __( 'Waitlisted', 'pasat' ),
			'cancelled' => __( 'Cancelled', 'pasat' ),
			default => ucfirst( $status ),
		};
	}
}
