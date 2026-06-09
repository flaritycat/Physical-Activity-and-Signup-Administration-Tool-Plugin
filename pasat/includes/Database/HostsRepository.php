<?php
namespace PASAT\Database;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
// PASAT table identifiers come from fixed plugin table names via $wpdb->prefix; user values remain prepared or sanitized.
final class HostsRepository extends Repository {
	public function __construct() {
		parent::__construct( 'activity_hosts' );
	}

	public function assign( int $activity_id, int $user_id, string $role = 'host' ): int {
		$existing = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE activity_id = %d AND user_id = %d",
				$activity_id,
				$user_id
			),
			ARRAY_A
		);

		if ( $existing ) {
			$this->update( (int) $existing['id'], array( 'role' => sanitize_text_field( $role ) ), array( '%s' ) );
			return (int) $existing['id'];
		}

		return $this->insert(
			array(
				'activity_id' => $activity_id,
				'user_id'     => $user_id,
				'role'        => sanitize_text_field( $role ),
				'created_at'  => Helpers::now(),
			),
			array( '%d', '%d', '%s', '%s' )
		);
	}

	public function list_for_activity( int $activity_id ): array {
		return $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE activity_id = %d ORDER BY created_at ASC",
				$activity_id
			),
			ARRAY_A
		) ?: array();
	}

	public function list_assignments(): array {
		$activities = Helpers::table( 'activities' );
		$users      = $this->wpdb->users;

		return $this->wpdb->get_results(
			"SELECT h.*, a.title AS activity_title, u.display_name, u.user_email
			FROM {$this->table} h
			INNER JOIN {$activities} a ON a.id = h.activity_id
			LEFT JOIN {$users} u ON u.ID = h.user_id
			ORDER BY a.title ASC, u.display_name ASC",
			ARRAY_A
		) ?: array();
	}

	public function user_is_host_for_activity( int $user_id, int $activity_id ): bool {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE activity_id = %d AND user_id = %d",
				$activity_id,
				$user_id
			)
		) > 0;
	}

	public function activity_ids_for_user( int $user_id ): array {
		$ids = $this->wpdb->get_col(
			$this->wpdb->prepare(
				"SELECT activity_id FROM {$this->table} WHERE user_id = %d",
				$user_id
			)
		);

		return array_map( 'absint', $ids ?: array() );
	}

	public function remove( int $activity_id, int $user_id ): bool {
		return false !== $this->wpdb->delete(
			$this->table,
			array( 'activity_id' => $activity_id, 'user_id' => $user_id ),
			array( '%d', '%d' )
		);
	}

	public function remove_by_id( int $id ): bool {
		return false !== $this->wpdb->delete(
			$this->table,
			array( 'id' => $id ),
			array( '%d' )
		);
	}

	public function replace_for_activity( int $activity_id, array $user_ids, string $role = 'host' ): void {
		$user_ids = array_values( array_unique( array_filter( array_map( 'absint', $user_ids ) ) ) );
		$this->wpdb->delete( $this->table, array( 'activity_id' => $activity_id ), array( '%d' ) );

		foreach ( $user_ids as $user_id ) {
			$this->assign( $activity_id, $user_id, $role );
		}
	}
}
