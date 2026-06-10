<?php
namespace PASAT\Database;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
// PASAT table identifiers come from fixed plugin table names via $wpdb->prefix; user values remain prepared or sanitized.
final class BadgesRepository extends Repository {
	public const TYPES = array( 'year_participation', 'placement', 'manual' );

	public function __construct() {
		parent::__construct( 'participant_badges' );
	}

	public function award( array $input ): int {
		$now       = Helpers::now();
		$type      = $this->sanitize_type( (string) ( $input['badge_type'] ?? '' ) );
		$key       = sanitize_text_field( $input['badge_key'] ?? '' );
		$participant_id = absint( $input['participant_id'] ?? 0 );
		if ( ! $participant_id || '' === $key ) {
			return 0;
		}

		$data = array(
			'participant_id'        => $participant_id,
			'badge_type'            => $type,
			'badge_key'             => $key,
			'label'                 => sanitize_text_field( $input['label'] ?? '' ),
			'season_year'           => $this->nullable_int( $input['season_year'] ?? null ),
			'activity_id'           => $this->nullable_int( $input['activity_id'] ?? null ),
			'participation_log_id'  => $this->nullable_int( $input['participation_log_id'] ?? null ),
			'placement'             => $this->nullable_int( $input['placement'] ?? null ),
			'metadata'              => isset( $input['metadata'] ) ? wp_json_encode( $input['metadata'] ) : null,
			'awarded_by'            => get_current_user_id() ?: null,
			'awarded_at'            => $now,
			'revoked_at'            => null,
			'updated_at'            => $now,
		);

		$existing = $this->find_by_key( $participant_id, $type, $key );
		if ( $existing ) {
			$this->update( (int) $existing['id'], $data, array( '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s' ) );
			return (int) $existing['id'];
		}

		$data['created_at'] = $now;
		return $this->insert( $data, array( '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%s' ) );
	}

	public function active_for_participant( int $participant_id ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE participant_id = %d AND revoked_at IS NULL ORDER BY badge_type ASC, season_year ASC, placement ASC, awarded_at DESC",
				$participant_id
			),
			ARRAY_A
		) ?: array();
	}

	public function all_for_participant( int $participant_id ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE participant_id = %d ORDER BY awarded_at DESC, id DESC",
				$participant_id
			),
			ARRAY_A
		) ?: array();
	}

	public function summary_labels( int $participant_id ): string {
		return implode( ', ', wp_list_pluck( $this->active_for_participant( $participant_id ), 'label' ) );
	}

	public function revoke_year( int $participant_id, int $year ): void {
		$this->revoke_by_key( $participant_id, 'year_participation', 'year:' . $year );
	}

	public function revoke_placements_for_log( int $log_id ): void {
		$this->wpdb->update(
			$this->table,
			array(
				'revoked_at' => Helpers::now(),
				'updated_at' => Helpers::now(),
			),
			array(
				'badge_type'           => 'placement',
				'participation_log_id' => $log_id,
				'revoked_at'           => null,
			),
			array( '%s', '%s' ),
			array( '%s', '%d', '%s' )
		);
	}

	public function revoke_by_key( int $participant_id, string $type, string $key ): void {
		$this->wpdb->update(
			$this->table,
			array(
				'revoked_at' => Helpers::now(),
				'updated_at' => Helpers::now(),
			),
			array(
				'participant_id' => $participant_id,
				'badge_type'     => $this->sanitize_type( $type ),
				'badge_key'      => sanitize_text_field( $key ),
				'revoked_at'     => null,
			),
			array( '%s', '%s' ),
			array( '%d', '%s', '%s', '%s' )
		);
	}

	public function anonymize_for_participant( int $participant_id ): void {
		$this->wpdb->update(
			$this->table,
			array(
				'metadata'   => null,
				'awarded_by' => null,
				'updated_at' => Helpers::now(),
			),
			array( 'participant_id' => $participant_id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);
	}

	public function delete_for_participant( int $participant_id ): void {
		$this->wpdb->delete( $this->table, array( 'participant_id' => $participant_id ), array( '%d' ) );
	}

	private function find_by_key( int $participant_id, string $type, string $key ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE participant_id = %d AND badge_type = %s AND badge_key = %s ORDER BY id DESC LIMIT 1",
				$participant_id,
				$type,
				$key
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	private function sanitize_type( string $type ): string {
		return in_array( $type, self::TYPES, true ) ? $type : 'manual';
	}
}
