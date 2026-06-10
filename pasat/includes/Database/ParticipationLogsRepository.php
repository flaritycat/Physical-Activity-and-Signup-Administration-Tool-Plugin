<?php
namespace PASAT\Database;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
// PASAT table identifiers come from fixed plugin table names via $wpdb->prefix; user values remain prepared or sanitized.
final class ParticipationLogsRepository extends Repository {
	public const ATTENDANCE_STATUSES = array( 'unknown', 'attended', 'completed', 'no_show', 'excused', 'disqualified' );
	public const QUALIFYING_STATUSES = array( 'attended', 'completed' );

	public function __construct() {
		parent::__construct( 'participation_logs' );
	}

	public function save( array $input, int $id = 0 ): int {
		$existing = $id ? $this->get( $id ) : null;
		$now      = Helpers::now();
		$status   = $this->sanitize_attendance_status( (string) ( $input['attendance_status'] ?? ( $existing['attendance_status'] ?? 'unknown' ) ) );
		$data     = array(
			'signup_id'          => $this->nullable_int( $input['signup_id'] ?? ( $existing['signup_id'] ?? null ) ),
			'activity_id'        => absint( $input['activity_id'] ?? ( $existing['activity_id'] ?? 0 ) ),
			'participant_id'     => absint( $input['participant_id'] ?? ( $existing['participant_id'] ?? 0 ) ),
			'attendance_status'  => $status,
			'checked_in_at'      => $this->datetime_value( $input['checked_in_at'] ?? ( $this->should_set_checked_in( $status, $existing ) ? $now : ( $existing['checked_in_at'] ?? null ) ) ),
			'completed_at'       => $this->datetime_value( $input['completed_at'] ?? ( 'completed' === $status && empty( $existing['completed_at'] ) ? $now : ( $existing['completed_at'] ?? null ) ) ),
			'placement'          => $this->nullable_int( $input['placement'] ?? ( $existing['placement'] ?? null ) ),
			'placement_label'    => sanitize_text_field( $input['placement_label'] ?? '' ),
			'result_value'       => sanitize_text_field( $input['result_value'] ?? '' ),
			'result_unit'        => sanitize_text_field( $input['result_unit'] ?? '' ),
			'result_notes'       => sanitize_textarea_field( $input['result_notes'] ?? '' ),
			'private_notes'      => sanitize_textarea_field( $input['private_notes'] ?? '' ),
			'recorded_by'        => get_current_user_id() ?: null,
			'updated_at'         => $now,
		);

		if ( ! $data['activity_id'] || ! $data['participant_id'] ) {
			return 0;
		}

		if ( 0 === $id ) {
			$existing = $this->find_for_activity_participant( (int) $data['activity_id'], (int) $data['participant_id'] );
			if ( $existing ) {
				return $this->save( $input, (int) $existing['id'] );
			}

			$data['created_at'] = $now;
			return $this->insert( $data, array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' ) );
		}

		$this->update( $id, $data, array( '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ) );
		return $id;
	}

	public function find_for_activity_participant( int $activity_id, int $participant_id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE activity_id = %d AND participant_id = %d ORDER BY id DESC LIMIT 1",
				$activity_id,
				$participant_id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	public function list_for_activity( int $activity_id ): array {
		$participants = Helpers::table( 'participants' );
		$signups      = Helpers::table( 'signups' );

		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT l.*, p.first_name, p.last_name, p.email, s.status AS signup_status
				FROM {$this->table} l
				INNER JOIN {$participants} p ON p.id = l.participant_id
				LEFT JOIN {$signups} s ON s.id = l.signup_id
				WHERE l.activity_id = %d
				ORDER BY p.last_name ASC, p.first_name ASC, l.updated_at DESC",
				$activity_id
			),
			ARRAY_A
		) ?: array();
	}

	public function list_for_participant( int $participant_id ): array {
		$activities = Helpers::table( 'activities' );

		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT l.*, a.title AS activity_title, a.starts_at, a.season_year, a.status AS activity_status
				FROM {$this->table} l
				INNER JOIN {$activities} a ON a.id = l.activity_id
				WHERE l.participant_id = %d
				ORDER BY COALESCE(a.starts_at, l.created_at) DESC, l.id DESC",
				$participant_id
			),
			ARRAY_A
		) ?: array();
	}

	public function get_with_details( int $id ): ?array {
		$activities = Helpers::table( 'activities' );

		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT l.*, a.title AS activity_title, a.starts_at, a.season_year, a.status AS activity_status
				FROM {$this->table} l
				INNER JOIN {$activities} a ON a.id = l.activity_id
				WHERE l.id = %d",
				$id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	public function participant_has_qualifying_year( int $participant_id, int $year ): bool {
		$activities = Helpers::table( 'activities' );

		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*)
				FROM {$this->table} l
				INNER JOIN {$activities} a ON a.id = l.activity_id
				WHERE l.participant_id = %d
					AND l.attendance_status IN ('attended', 'completed')
					AND a.status IN ('published', 'archived')
					AND COALESCE(NULLIF(a.season_year, 0), YEAR(a.starts_at)) = %d",
				$participant_id,
				$year
			)
		) > 0;
	}

	public function ids_for_activity( int $activity_id ): array {
		$ids = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT id FROM {$this->table} WHERE activity_id = %d",
				$activity_id
			)
		);

		return array_map( 'absint', $ids ?: array() );
	}

	public function anonymize_for_participant( int $participant_id ): void {
		$this->wpdb->update(
			$this->table,
			array(
				'result_notes'  => '',
				'private_notes' => '',
				'updated_at'    => Helpers::now(),
			),
			array( 'participant_id' => $participant_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	public function delete_for_participant( int $participant_id ): void {
		$this->wpdb->delete( $this->table, array( 'participant_id' => $participant_id ), array( '%d' ) );
	}

	public function sanitize_attendance_status( string $status ): string {
		return in_array( $status, self::ATTENDANCE_STATUSES, true ) ? $status : 'unknown';
	}

	private function should_set_checked_in( string $status, ?array $existing ): bool {
		return in_array( $status, array( 'attended', 'completed' ), true ) && empty( $existing['checked_in_at'] );
	}

	private function datetime_value( mixed $value ): ?string {
		if ( empty( $value ) ) {
			return null;
		}

		if ( is_string( $value ) && preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value ) ) {
			return $value;
		}

		return Helpers::mysql_from_local_input( (string) $value );
	}
}
