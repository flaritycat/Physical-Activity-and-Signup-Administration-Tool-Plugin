<?php
namespace PASAT\Database;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
// PASAT table identifiers come from fixed plugin table names via $wpdb->prefix; user values remain prepared or sanitized.
final class ParticipantsRepository extends Repository {
	public const MEMBERSHIP_STATUSES = array( 'none', 'interested', 'pending', 'active', 'declined', 'expired' );

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
		$formats  = array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' );

		if ( ! empty( $input['membership_opt_in'] ) ) {
			$current_status = $existing['membership_status'] ?? 'none';
			$data['membership_opted_in'] = 1;
			$data['membership_opted_in_at'] = ! empty( $existing['membership_opted_in_at'] ) ? $existing['membership_opted_in_at'] : $now;
			$formats[] = '%d';
			$formats[] = '%s';

			if ( 'none' === $current_status ) {
				$data['membership_status'] = $this->sanitize_membership_status( (string) ( $input['membership_default_status'] ?? 'interested' ) );
				$data['membership_status_updated_at'] = $now;
				$formats[] = '%s';
				$formats[] = '%s';
			}
		}

		if ( $existing ) {
			$this->update( (int) $existing['id'], $data, $formats );
			return (int) $existing['id'];
		}

		$data['created_at'] = $now;
		$formats[] = '%s';
		return $this->insert( $data, $formats );
	}

	public function search( string $query = '', int $limit = 100, array $args = array() ): array {
		$params = array();
		$where  = array( '1=1' );
		if ( '' !== trim( $query ) ) {
			$like    = '%' . $this->wpdb->esc_like( sanitize_text_field( $query ) ) . '%';
			$where[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s)';
			$params  = array_merge( $params, array( $like, $like, $like ) );
		}

		if ( ! empty( $args['membership_status'] ) && in_array( $args['membership_status'], self::MEMBERSHIP_STATUSES, true ) ) {
			$where[]  = 'membership_status = %s';
			$params[] = $args['membership_status'];
		}

		$sql = "SELECT * FROM {$this->table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY updated_at DESC LIMIT ' . absint( $limit );
		return $this->wpdb->get_results( $this->prepare_or_raw( $sql, $params ), ARRAY_A ) ?: array();
	}

	public function update_membership( int $id, array $input ): bool {
		$status = $this->sanitize_membership_status( (string) ( $input['membership_status'] ?? 'none' ) );

		return $this->update(
			$id,
			array(
				'membership_status'            => $status,
				'membership_status_updated_at' => Helpers::now(),
				'membership_number'            => sanitize_text_field( $input['membership_number'] ?? '' ),
				'membership_notes'             => sanitize_textarea_field( $input['membership_notes'] ?? '' ),
				'updated_at'                   => Helpers::now(),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);
	}

	public function anonymize( int $id ): bool {
		$data = array(
			'first_name'      => __( 'Anonymized', 'pasat' ),
			'last_name'       => sprintf(
				/* translators: %d is the anonymized participant ID. */
				__( 'Participant %d', 'pasat' ),
				$id
			),
			'nickname'        => null,
			'email'           => 'anonymous-' . $id . '@example.invalid',
			'phone'           => null,
			'age'             => null,
			'consent_given'   => 0,
			'consent_version' => null,
			'consented_at'    => null,
			'membership_status' => 'none',
			'membership_opted_in' => 0,
			'membership_opted_in_at' => null,
			'membership_status_updated_at' => null,
			'membership_number' => null,
			'membership_notes' => null,
			'updated_at'      => Helpers::now(),
		);

		return $this->update( $id, $data, array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' ) );
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

	public function signups_for_participant( int $participant_id ): array {
		$signups    = Helpers::table( 'signups' );
		$activities = Helpers::table( 'activities' );
		$venues     = Helpers::table( 'venues' );

		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT s.*, a.title AS activity_title, a.starts_at, v.name AS venue_name
				FROM {$signups} s
				INNER JOIN {$activities} a ON a.id = s.activity_id
				LEFT JOIN {$venues} v ON v.id = a.venue_id
				WHERE s.participant_id = %d
				ORDER BY a.starts_at DESC, s.created_at DESC",
				$participant_id
			),
			ARRAY_A
		) ?: array();
	}

	public function sanitize_membership_status( string $status ): string {
		return in_array( $status, self::MEMBERSHIP_STATUSES, true ) ? $status : 'none';
	}
}
