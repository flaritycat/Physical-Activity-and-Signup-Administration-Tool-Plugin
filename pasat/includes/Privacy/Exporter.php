<?php
namespace PASAT\Privacy;

use PASAT\Database\ParticipantsRepository;
use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Exporter {
	public static function register( array $exporters ): array {
		$exporters['pasat'] = array(
			'exporter_friendly_name' => __( 'PASAT activity signups', 'pasat' ),
			'callback'               => array( self::class, 'export' ),
		);

		return $exporters;
	}

	public static function export( string $email_address, int $page = 1 ): array {
		$repo   = new ParticipantsRepository();
		$person = $repo->find_by_email( $email_address );
		$data   = array();

		if ( $person ) {
			$data[] = array(
				'group_id'    => 'pasat-participant',
				'group_label' => __( 'PASAT Participant', 'pasat' ),
				'item_id'     => 'participant-' . $person['id'],
				'data'        => array(
					array( 'name' => __( 'First name', 'pasat' ), 'value' => $person['first_name'] ),
					array( 'name' => __( 'Last name', 'pasat' ), 'value' => $person['last_name'] ),
					array( 'name' => __( 'Nickname', 'pasat' ), 'value' => $person['nickname'] ),
					array( 'name' => __( 'E-mail', 'pasat' ), 'value' => $person['email'] ),
					array( 'name' => __( 'Phone', 'pasat' ), 'value' => $person['phone'] ),
					array( 'name' => __( 'Age', 'pasat' ), 'value' => $person['age'] ),
					array( 'name' => __( 'Consent given', 'pasat' ), 'value' => $person['consent_given'] ? __( 'Yes', 'pasat' ) : __( 'No', 'pasat' ) ),
					array( 'name' => __( 'Consented at', 'pasat' ), 'value' => Helpers::local_datetime( $person['consented_at'] ) ),
				),
			);
		}

		foreach ( $repo->signups_for_email( $email_address ) as $signup ) {
			$data[] = array(
				'group_id'    => 'pasat-signups',
				'group_label' => __( 'PASAT Signups', 'pasat' ),
				'item_id'     => 'signup-' . $signup['id'],
				'data'        => array(
					array( 'name' => __( 'Activity', 'pasat' ), 'value' => $signup['activity_title'] ),
					array( 'name' => __( 'Venue', 'pasat' ), 'value' => $signup['venue_name'] ),
					array( 'name' => __( 'Status', 'pasat' ), 'value' => $signup['status'] ),
					array( 'name' => __( 'Created at', 'pasat' ), 'value' => Helpers::local_datetime( $signup['created_at'] ) ),
					array( 'name' => __( 'Cancelled at', 'pasat' ), 'value' => Helpers::local_datetime( $signup['cancelled_at'] ) ),
				),
			);
		}

		return array(
			'data' => $data,
			'done' => true,
		);
	}
}
