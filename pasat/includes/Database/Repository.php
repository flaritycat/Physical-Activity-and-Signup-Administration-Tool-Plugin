<?php
namespace PASAT\Database;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
// PASAT table identifiers come from fixed plugin table names via $wpdb->prefix; user values remain prepared or sanitized.
abstract class Repository {
	protected $wpdb;
	protected string $table;

	public function __construct( string $table_name ) {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = Helpers::table( $table_name );
	}

	public function table(): string {
		return $this->table;
	}

	public function get( int $id ): ?array {
		$row = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", $id ),
			ARRAY_A
		);

		return $row ?: null;
	}

	public function delete( int $id ): bool {
		return false !== $this->wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
	}

	protected function insert( array $data, array $format ): int {
		$this->wpdb->insert( $this->table, $data, $format );
		return (int) $this->wpdb->insert_id;
	}

	protected function update( int $id, array $data, array $format ): bool {
		return false !== $this->wpdb->update( $this->table, $data, array( 'id' => $id ), $format, array( '%d' ) );
	}

	protected function prepare_or_raw( string $sql, array $params = array() ): string {
		return $params ? $this->wpdb->prepare( $sql, $params ) : $sql;
	}

	protected function nullable_int( mixed $value ): ?int {
		if ( '' === $value || null === $value ) {
			return null;
		}

		return max( 0, (int) $value );
	}

	protected function nullable_decimal( mixed $value ): ?float {
		if ( '' === $value || null === $value ) {
			return null;
		}

		return (float) $value;
	}
}
