<?php
namespace PASAT\Database;

use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ActivitiesRepository extends Repository {
	public const STATUSES = array( 'draft', 'published', 'cancelled', 'archived' );
	public const VISIBILITIES = array( 'public', 'private', 'unlisted' );

	public function __construct() {
		parent::__construct( 'activities' );
	}

	public function save( array $input, int $id = 0 ): int {
		$now  = Helpers::now();
		$data = array(
			'title'                => sanitize_text_field( $input['title'] ?? '' ),
			'description'          => wp_kses_post( $input['description'] ?? '' ),
			'activity_type'        => sanitize_text_field( $input['activity_type'] ?? '' ),
			'season_year'          => $this->nullable_int( $input['season_year'] ?? null ),
			'starts_at'            => $this->datetime_value( $input['starts_at'] ?? null ),
			'ends_at'              => $this->datetime_value( $input['ends_at'] ?? null ),
			'venue_id'             => $this->nullable_int( $input['venue_id'] ?? null ),
			'capacity'             => $this->nullable_int( $input['capacity'] ?? null ),
			'waitlist_enabled'     => ! empty( $input['waitlist_enabled'] ) ? 1 : 0,
			'signup_opens_at'      => $this->datetime_value( $input['signup_opens_at'] ?? null ),
			'signup_closes_at'     => $this->datetime_value( $input['signup_closes_at'] ?? null ),
			'status'               => $this->sanitize_status( $input['status'] ?? 'draft' ),
			'public_visibility'    => $this->sanitize_visibility( $input['public_visibility'] ?? 'public' ),
			'minimum_age'          => $this->nullable_int( $input['minimum_age'] ?? null ),
			'maximum_age'          => $this->nullable_int( $input['maximum_age'] ?? null ),
			'requires_warning_ack' => ! empty( $input['requires_warning_ack'] ) ? 1 : 0,
			'warning_text'         => sanitize_textarea_field( $input['warning_text'] ?? '' ),
			'updated_by'           => get_current_user_id() ?: null,
			'updated_at'           => $now,
		);

		if ( 0 === $id ) {
			$data['created_by'] = get_current_user_id() ?: null;
			$data['created_at'] = $now;

			return $this->insert(
				$data,
				array( '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%s', '%d', '%s' )
			);
		}

		$this->update(
			$id,
			$data,
			array( '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s', '%d', '%s' )
		);

		return $id;
	}

	public function duplicate( int $id ): int {
		$activity = $this->get( $id );
		if ( ! $activity ) {
			return 0;
		}

		unset( $activity['id'], $activity['created_at'], $activity['updated_at'] );
		$activity['title']  = sprintf(
			/* translators: %s is the source activity title. */
			__( '%s copy', 'pasat' ),
			$activity['title']
		);
		$activity['status'] = 'draft';

		return $this->save( $activity );
	}

	public function list( array $args = array() ): array {
		$venues = Helpers::table( 'venues' );
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'a.status = %s';
			$params[] = $this->sanitize_status( (string) $args['status'] );
		}

		if ( ! empty( $args['public'] ) ) {
			$where[] = "a.status = 'published'";
			$where[] = "a.public_visibility = 'public'";
		}

		if ( ! empty( $args['upcoming'] ) ) {
			$where[]  = '(a.starts_at IS NULL OR a.starts_at >= %s)';
			$params[] = Helpers::now();
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = '(a.title LIKE %s OR a.description LIKE %s)';
			$like     = '%' . $this->wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$params[] = $like;
			$params[] = $like;
		}

		$join_hosts = '';
		if ( ! empty( $args['assigned_user_id'] ) ) {
			$hosts      = Helpers::table( 'activity_hosts' );
			$join_hosts = " INNER JOIN {$hosts} ah ON ah.activity_id = a.id";
			$where[]    = 'ah.user_id = %d';
			$params[]   = absint( $args['assigned_user_id'] );
		}

		$limit = isset( $args['limit'] ) ? max( 1, min( 200, (int) $args['limit'] ) ) : 200;
		$sql   = "SELECT DISTINCT a.*, v.name AS venue_name, v.address AS venue_address
			FROM {$this->table} a
			{$join_hosts}
			LEFT JOIN {$venues} v ON v.id = a.venue_id
			WHERE " . implode( ' AND ', $where ) . '
			ORDER BY COALESCE(a.starts_at, a.created_at) ASC, a.title ASC
			LIMIT ' . absint( $limit );

		return $this->wpdb->get_results( $this->prepare_or_raw( $sql, $params ), ARRAY_A ) ?: array();
	}

	public function get_with_venue( int $id ): ?array {
		$venues = Helpers::table( 'venues' );
		$row    = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT a.*, v.name AS venue_name, v.address AS venue_address
				FROM {$this->table} a
				LEFT JOIN {$venues} v ON v.id = a.venue_id
				WHERE a.id = %d",
				$id
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	public function counts(): array {
		$rows = $this->wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$this->table} GROUP BY status", ARRAY_A ) ?: array();
		$out  = array_fill_keys( self::STATUSES, 0 );
		foreach ( $rows as $row ) {
			$out[ $row['status'] ] = (int) $row['total'];
		}

		return $out;
	}

	public function is_public_signup_open( array $activity ): bool {
		if ( 'published' !== ( $activity['status'] ?? '' ) || 'public' !== ( $activity['public_visibility'] ?? '' ) ) {
			return false;
		}

		$now = time();
		if ( ! empty( $activity['signup_opens_at'] ) && strtotime( $activity['signup_opens_at'] . ' UTC' ) > $now ) {
			return false;
		}

		if ( ! empty( $activity['signup_closes_at'] ) && strtotime( $activity['signup_closes_at'] . ' UTC' ) < $now ) {
			return false;
		}

		return true;
	}

	private function sanitize_status( string $status ): string {
		return in_array( $status, self::STATUSES, true ) ? $status : 'draft';
	}

	private function sanitize_visibility( string $visibility ): string {
		return in_array( $visibility, self::VISIBILITIES, true ) ? $visibility : 'public';
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
