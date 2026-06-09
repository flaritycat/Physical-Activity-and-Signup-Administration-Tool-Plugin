<?php
namespace PASAT\Database;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	public function user_is_host_for_activity( int $user_id, int $activity_id ): bool {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE activity_id = %d AND user_id = %d",
				$activity_id,
				$user_id
			)
		) > 0;
	}

	public function remove( int $activity_id, int $user_id ): bool {
		return false !== $this->wpdb->delete(
			$this->table,
			array( 'activity_id' => $activity_id, 'user_id' => $user_id ),
			array( '%d', '%d' )
		);
	}
}
