<?php
namespace PASAT\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Participant {
	public static function display_name( array $participant, bool $redact_minor = false ): string {
		$age = isset( $participant['age'] ) ? (int) $participant['age'] : null;
		if ( $redact_minor && ( null === $age || $age < 18 ) ) {
			return __( 'Minor participant', 'pasat' );
		}

		return trim( ( $participant['first_name'] ?? '' ) . ' ' . ( $participant['last_name'] ?? '' ) );
	}
}
