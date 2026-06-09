<?php
namespace PASAT\Database;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ParticipantsRepository extends Repository {
	public function __construct() {
		parent::__construct( 'participants' );
	}

	public function find_by_email( string $email ): ?array {
		$email = strtolower( sanitize_email( $email ) );
		$row   = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE LOWER(email) = %s ORDER BY id DESC LIMIT 1", $email ),
			ARRAY_A
		);

		return $row ?: null;
	}

	public function create_or_update_from_signup( array $input ): int {
		$existing = $this->find_by_email( (string) ( $input['email'] ?? '' ) );
		$now      = Helpers::now();
		$data     = array(
			'first_name'      => sanitize_text_field( $input['first_name'] ?? '' ),
			'last_name'       => sanitize_text_field( $input['last_name'] ?? '' ),
			'nickname'        => sanitize_text_field( $input['nickname'] ?? '' ),
			'email'           => strtolower( sanitize_email( $input['email'] ?? '' ) ),
			'phone'           => sanitize_text_field( $input['phone'] ?? '' ),
			'age'             => $this->nullable_int( $input['age'] ?? null ),
			'consent_given'   => ! empty( $input['consent_given'] ) ? 1 : 0,
			'consent_version' => sanitize_text_field( $input['consent_version'] ?? '0.1.0' ),
			'consented_at'    => ! empty( $input['consent_given'] ) ? $now : null,
			'updated_at'      => $now,
		);

		if ( $existing ) {
			$this->update( (int) $existing['id'], $data, array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' ) );
			return (int) $existing['id'];
		}

		$data['created_at'] = $now;
		return $this->insert( $data, array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' ) );
	}

	public function search( string $query = '', int $limit = 100 ): array {
		$params = array();
		$where  = '1=1';
		if ( '' !== trim( $query ) ) {
			$like    = '%' . $this->wpdb->esc_like( sanitize_text_field( $query ) ) . '%';
			$where   = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)';
			$params  = array( $like, $like, $like );
		}

		$sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY updated_at DESC LIMIT " . absint( $limit );
		return $this->wpdb->get_results( $this->prepare_or_raw( $sql, $params ), ARRAY_A ) ?: array();
	}

	public function anonymize( int $id ): bool {
		$data = array(
			'first_name'      => __( 'Anonymized', 'pasat' ),
			'last_name'       => sprintf( __( 'Participant %d', 'pasat' ), $id ),
			'nickname'        => null,
			'email'           => 'anonymous-' . $id . '@example.invalid',
			'phone'           => null,
			'age'             => null,
			'consent_given'   => 0,
			'consent_version' => null,
			'consented_at'    => null,
			'updated_at'      => Helpers::now(),
		);

		return $this->update( $id, $data, array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' ) );
	}

	public function signups_for_email( string $email ): array {
		$signups    = Helpers::table( 'signups' );
		$activities = Helpers::table( 'activities' );
		$venues     = Helpers::table( 'venues' );
		$email      = strtolower( sanitize_email( $email ) );

		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT s.*, a.title AS activity_title, a.starts_at, v.name AS venue_name
				FROM {$this->table} p
				INNER JOIN {$signups} s ON s.participant_id = p.id
				INNER JOIN {$activities} a ON a.id = s.activity_id
				LEFT JOIN {$venues} v ON v.id = a.venue_id
				WHERE LOWER(p.email) = %s
				ORDER BY a.starts_at DESC, s.created_at DESC",
				$email
			),
			ARRAY_A
		) ?: array();
	}
}
