<?php
namespace PASAT\Privacy;

use PASAT\Database\AuditLogRepository;
use PASAT\Database\ParticipantsRepository;
use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// Privacy erasure intentionally deletes from PASAT custom tables when the configured erasure mode requires deletion.
final class Eraser {
	public static function register( array $erasers ): array {
		$erasers['pasat'] = array(
			'eraser_friendly_name' => __( 'PASAT activity signups', 'pasat' ),
			'callback'             => array( self::class, 'erase' ),
		);

		return $erasers;
	}

	public static function erase( string $email_address, int $page = 1 ): array {
		$repo   = new ParticipantsRepository();
		$person = $repo->find_by_email( $email_address );
		if ( ! $person ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$mode = Helpers::setting( 'erasure_mode', 'anonymize' );
		if ( 'delete' === $mode ) {
			self::delete_participant( (int) $person['id'] );
			$removed = true;
		} else {
			$repo->anonymize( (int) $person['id'] );
			$removed = false;
		}

		( new AuditLogRepository() )->log( 'privacy.erase', 'participant', (int) $person['id'], 'WordPress privacy eraser processed participant' );

		return array(
			'items_removed'  => $removed,
			'items_retained' => ! $removed,
			'messages'       => array( __( 'PASAT participant data was processed according to the configured erasure mode.', 'pasat' ) ),
			'done'           => true,
		);
	}

	public static function delete_participant( int $participant_id ): void {
		global $wpdb;
		$signups      = Helpers::table( 'signups' );
		$participants = Helpers::table( 'participants' );

		$wpdb->delete( $signups, array( 'participant_id' => $participant_id ), array( '%d' ) );
		$wpdb->delete( $participants, array( 'id' => $participant_id ), array( '%d' ) );
	}
}
