<?php
namespace PASAT\Database;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SignupsRepository extends Repository {
	public const STATUSES = array( 'confirmed', 'waitlisted', 'cancelled' );

	public function __construct() {
		parent::__construct( 'signups' );
	}

	public function create( int $activity_id, int $participant_id, string $status, array $extra = array() ): int {
		$now       = Helpers::now();
		$wait_pos  = 'waitlisted' === $status ? $this->next_waitlist_position( $activity_id ) : null;
		$token_hash = $extra['cancellation_token_hash'] ?? null;

		return $this->insert(
			array(
				'activity_id'              => $activity_id,
				'participant_id'           => $participant_id,
				'status'                   => $this->sanitize_status( $status ),
				'waitlist_position'        => $wait_pos,
				'cancellation_token_hash'  => $token_hash,
				'cancelled_at'             => null,
				'cancellation_reason'      => null,
				'source'                   => sanitize_text_field( $extra['source'] ?? 'public' ),
				'ip_hash'                  => Helpers::client_ip_hash(),
				'user_agent_hash'          => Helpers::user_agent_hash(),
				'warning_acknowledged'     => ! empty( $extra['warning_acknowledged'] ) ? 1 : 0,
				'created_at'               => $now,
				'updated_at'               => $now,
			),
			array( '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
	}

	public function update_token_hash( int $signup_id, string $hash ): bool {
		return $this->update( $signup_id, array( 'cancellation_token_hash' => $hash, 'updated_at' => Helpers::now() ), array( '%s', '%s' ) );
	}

	public function duplicate_active_by_email( int $activity_id, string $email ): ?array {
		$participants = Helpers::table( 'participants' );
		$email        = strtolower( sanitize_email( $email ) );
		$row          = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT s.*
				FROM {$this->table} s
				INNER JOIN {$participants} p ON p.id = s.participant_id
				WHERE s.activity_id = %d
					AND s.status IN ('confirmed', 'waitlisted')
					AND LOWER(p.email) = %s
				LIMIT 1",
				$activity_id,
				$email
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	public function confirmed_count( int $activity_id ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE activity_id = %d AND status = 'confirmed'",
				$activity_id
			)
		);
	}

	public function waitlisted_count( int $activity_id ): int {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE activity_id = %d AND status = 'waitlisted'",
				$activity_id
			)
		);
	}

	public function next_waitlist_position( int $activity_id ): int {
		$current = (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT MAX(waitlist_position) FROM {$this->table} WHERE activity_id = %d",
				$activity_id
			)
		);

		return $current + 1;
	}

	public function capacity_snapshot( array $activity ): array {
		$activity_id = (int) $activity['id'];
		$capacity    = isset( $activity['capacity'] ) ? (int) $activity['capacity'] : 0;
		$confirmed   = $this->confirmed_count( $activity_id );
		$waitlisted  = $this->waitlisted_count( $activity_id );

		return array(
			'capacity'   => $capacity,
			'confirmed'  => $confirmed,
			'waitlisted' => $waitlisted,
			'remaining'  => $capacity > 0 ? max( 0, $capacity - $confirmed ) : null,
			'is_full'    => $capacity > 0 && $confirmed >= $capacity,
		);
	}

	public function get_with_details( int $signup_id ): ?array {
		$participants = Helpers::table( 'participants' );
		$activities   = Helpers::table( 'activities' );
		$venues       = Helpers::table( 'venues' );

		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT s.*, p.first_name, p.last_name, p.nickname, p.email, p.phone, p.age,
					a.title AS activity_title, a.starts_at, a.ends_at, a.waitlist_enabled, a.capacity,
					v.name AS venue_name
				FROM {$this->table} s
				INNER JOIN {$participants} p ON p.id = s.participant_id
				INNER JOIN {$activities} a ON a.id = s.activity_id
				LEFT JOIN {$venues} v ON v.id = a.venue_id
				WHERE s.id = %d",
				$signup_id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	public function list( array $args = array() ): array {
		$participants = Helpers::table( 'participants' );
		$activities   = Helpers::table( 'activities' );
		$where        = array( '1=1' );
		$params       = array();

		if ( ! empty( $args['activity_id'] ) ) {
			$where[]  = 's.activity_id = %d';
			$params[] = absint( $args['activity_id'] );
		}

		if ( isset( $args['activity_ids'] ) && is_array( $args['activity_ids'] ) ) {
			$ids = array_values( array_filter( array_map( 'absint', $args['activity_ids'] ) ) );
			if ( ! $ids ) {
				$where[] = '0=1';
			} else {
				$where[] = 's.activity_id IN (' . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ')';
				$params  = array_merge( $params, $ids );
			}
		}

		if ( ! empty( $args['status'] ) && in_array( $args['status'], self::STATUSES, true ) ) {
			$where[]  = 's.status = %s';
			$params[] = $args['status'];
		}

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $this->wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]  = '(p.first_name LIKE %s OR p.last_name LIKE %s OR p.email LIKE %s OR a.title LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$sql = "SELECT s.*, p.first_name, p.last_name, p.email, p.phone, p.age, a.title AS activity_title, a.starts_at
			FROM {$this->table} s
			INNER JOIN {$participants} p ON p.id = s.participant_id
			INNER JOIN {$activities} a ON a.id = s.activity_id
			WHERE " . implode( ' AND ', $where ) . '
			ORDER BY s.created_at DESC
			LIMIT 500';

		return $this->wpdb->get_results( $this->prepare_or_raw( $sql, $params ), ARRAY_A ) ?: array();
	}

	public function cancel( int $signup_id, string $reason = '', bool $promote = true ): array {
		$signup = $this->get( $signup_id );
		if ( ! $signup ) {
			return array( 'cancelled' => false, 'promoted_signup_id' => 0, 'previous_status' => '' );
		}

		if ( 'cancelled' === $signup['status'] ) {
			return array( 'cancelled' => true, 'promoted_signup_id' => 0, 'previous_status' => 'cancelled' );
		}

		$previous_status = $signup['status'];
		$this->update(
			$signup_id,
			array(
				'status'              => 'cancelled',
				'cancelled_at'        => Helpers::now(),
				'cancellation_reason' => sanitize_textarea_field( $reason ),
				'cancellation_token_hash' => null,
				'updated_at'          => Helpers::now(),
			),
			array( '%s', '%s', '%s', '%s', '%s' )
		);

		$promoted_signup_id = 0;
		if ( $promote && 'confirmed' === $previous_status ) {
			$promoted_signup_id = $this->promote_next_waitlisted( (int) $signup['activity_id'] );
		}

		return array(
			'cancelled'          => true,
			'promoted_signup_id' => $promoted_signup_id,
			'previous_status'    => $previous_status,
		);
	}

	public function promote_next_waitlisted( int $activity_id ): int {
		$next = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table}
				WHERE activity_id = %d AND status = 'waitlisted'
				ORDER BY waitlist_position ASC, created_at ASC
				LIMIT 1",
				$activity_id
			),
			ARRAY_A
		);

		if ( ! $next ) {
			return 0;
		}

		$this->update(
			(int) $next['id'],
			array(
				'status'            => 'confirmed',
				'waitlist_position' => null,
				'updated_at'        => Helpers::now(),
			),
			array( '%s', '%d', '%s' )
		);

		return (int) $next['id'];
	}

	public function confirm_waitlisted( int $signup_id ): bool {
		$signup = $this->get( $signup_id );
		if ( ! $signup || 'waitlisted' !== $signup['status'] ) {
			return false;
		}

		return $this->update(
			$signup_id,
			array(
				'status'            => 'confirmed',
				'waitlist_position' => null,
				'updated_at'        => Helpers::now(),
			),
			array( '%s', '%d', '%s' )
		);
	}

	public function totals(): array {
		$rows = $this->wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$this->table} GROUP BY status", ARRAY_A ) ?: array();
		$out  = array_fill_keys( self::STATUSES, 0 );
		foreach ( $rows as $row ) {
			$out[ $row['status'] ] = (int) $row['total'];
		}

		return $out;
	}

	private function sanitize_status( string $status ): string {
		return in_array( $status, self::STATUSES, true ) ? $status : 'confirmed';
	}
}
