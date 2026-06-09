<?php
namespace PASAT\Database;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
// PASAT table identifiers come from fixed plugin table names via $wpdb->prefix; user values remain prepared or sanitized.
final class AuditLogRepository extends Repository {
	public function __construct() {
		parent::__construct( 'audit_log' );
	}

	public function log( string $action, string $object_type = '', int $object_id = 0, string $message = '' ): int {
		return $this->insert(
			array(
				'user_id'     => get_current_user_id() ?: null,
				'action'      => sanitize_text_field( $action ),
				'object_type' => sanitize_text_field( $object_type ),
				'object_id'   => $object_id ?: null,
				'message'     => sanitize_textarea_field( $message ),
				'ip_hash'     => Helpers::client_ip_hash(),
				'created_at'  => Helpers::now(),
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
		);
	}

	public function recent( int $limit = 50 ): array {
		return $this->wpdb->get_results(
			"SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT " . absint( $limit ),
			ARRAY_A
		) ?: array();
	}
}
