<?php
namespace PASAT\Privacy;

use PASAT\Database\BadgesRepository;
use PASAT\Database\ParticipationLogsRepository;
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
					array( 'name' => __( 'Membership status', 'pasat' ), 'value' => $person['membership_status'] ),
					array( 'name' => __( 'Membership opted in', 'pasat' ), 'value' => ! empty( $person['membership_opted_in'] ) ? __( 'Yes', 'pasat' ) : __( 'No', 'pasat' ) ),
					array( 'name' => __( 'Membership opted in at', 'pasat' ), 'value' => Helpers::local_datetime( $person['membership_opted_in_at'] ) ),
					array( 'name' => __( 'Membership number', 'pasat' ), 'value' => $person['membership_number'] ),
					array( 'name' => __( 'Membership notes', 'pasat' ), 'value' => $person['membership_notes'] ),
				),
			);

			foreach ( ( new ParticipationLogsRepository() )->list_for_participant( (int) $person['id'] ) as $log ) {
				$data[] = array(
					'group_id'    => 'pasat-participation',
					'group_label' => __( 'PASAT Participation', 'pasat' ),
					'item_id'     => 'participation-' . $log['id'],
					'data'        => array(
						array( 'name' => __( 'Activity', 'pasat' ), 'value' => $log['activity_title'] ),
						array( 'name' => __( 'Attendance status', 'pasat' ), 'value' => $log['attendance_status'] ),
						array( 'name' => __( 'Placement', 'pasat' ), 'value' => $log['placement'] ),
						array( 'name' => __( 'Result value', 'pasat' ), 'value' => $log['result_value'] ),
						array( 'name' => __( 'Result unit', 'pasat' ), 'value' => $log['result_unit'] ),
						array( 'name' => __( 'Result notes', 'pasat' ), 'value' => $log['result_notes'] ),
						array( 'name' => __( 'Private notes', 'pasat' ), 'value' => $log['private_notes'] ),
					),
				);
			}

			foreach ( ( new BadgesRepository() )->all_for_participant( (int) $person['id'] ) as $badge ) {
				$data[] = array(
					'group_id'    => 'pasat-badges',
					'group_label' => __( 'PASAT Badges', 'pasat' ),
					'item_id'     => 'badge-' . $badge['id'],
					'data'        => array(
						array( 'name' => __( 'Badge label', 'pasat' ), 'value' => $badge['label'] ),
						array( 'name' => __( 'Badge type', 'pasat' ), 'value' => $badge['badge_type'] ),
						array( 'name' => __( 'Season year', 'pasat' ), 'value' => $badge['season_year'] ),
						array( 'name' => __( 'Placement', 'pasat' ), 'value' => $badge['placement'] ),
						array( 'name' => __( 'Awarded at', 'pasat' ), 'value' => Helpers::local_datetime( $badge['awarded_at'] ) ),
						array( 'name' => __( 'Revoked at', 'pasat' ), 'value' => Helpers::local_datetime( $badge['revoked_at'] ) ),
					),
				);
			}
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
