<?php
namespace PASAT\Migration;

use PASAT\Database\ActivitiesRepository;
use PASAT\Database\AuditLogRepository;
use PASAT\Database\HostsRepository;
use PASAT\Database\ParticipantsRepository;
use PASAT\Database\SignupsRepository;
use PASAT\Database\VenuesRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
// PASAT imports query fixed plugin table names only; imported values are sanitized and prepared before use.
final class HsfImporter {
	private const MAX_FILE_SIZE = 2097152;

	private $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public function supported_sources(): array {
		return array( 'venues', 'activities', 'participants', 'signups', 'hosts', 'winners' );
	}

	public function importable_sources(): array {
		return array( 'venues', 'activities', 'participants', 'signups', 'hosts' );
	}

	public function describe(): string {
		return __( 'PASAT imports structured JSON or CSV exports for venues, activities, participants, signups, and host assignments. Passwords and external authentication records are not imported; map hosts and administrators to WordPress users instead.', 'pasat' );
	}

	public function import_uploaded_file( array $file, string $source ): array|\WP_Error {
		if ( empty( $file ) || empty( $file['tmp_name'] ) ) {
			return new \WP_Error( 'pasat_import_missing_file', __( 'Choose a JSON or CSV file to import.', 'pasat' ) );
		}

		$error = absint( $file['error'] ?? UPLOAD_ERR_OK );
		if ( UPLOAD_ERR_OK !== $error ) {
			return new \WP_Error( 'pasat_import_upload_error', __( 'The import file could not be uploaded.', 'pasat' ) );
		}

		$size = absint( $file['size'] ?? 0 );
		if ( $size > self::MAX_FILE_SIZE ) {
			return new \WP_Error( 'pasat_import_file_too_large', __( 'The import file is too large. Use a file smaller than 2 MB.', 'pasat' ) );
		}

		return $this->import_path( (string) $file['tmp_name'], $source, sanitize_file_name( $file['name'] ?? '' ) );
	}

	public function import_path( string $path, string $source, string $original_name = '' ): array|\WP_Error {
		$source = sanitize_key( $source );
		if ( ! in_array( $source, $this->importable_sources(), true ) ) {
			return new \WP_Error( 'pasat_import_source_unsupported', __( 'This import source is not supported yet.', 'pasat' ) );
		}

		if ( ! is_readable( $path ) ) {
			return new \WP_Error( 'pasat_import_unreadable', __( 'The import file could not be read.', 'pasat' ) );
		}

		$name      = '' !== $original_name ? $original_name : $path;
		$extension = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );
		$rows      = 'json' === $extension ? $this->rows_from_json( $path, $source ) : $this->rows_from_csv( $path );
		if ( is_wp_error( $rows ) ) {
			return $rows;
		}

		if ( ! $rows ) {
			return new \WP_Error( 'pasat_import_empty', __( 'No import rows were found.', 'pasat' ) );
		}

		$result = array(
			'source'    => $source,
			'processed' => 0,
			'imported'  => 0,
			'skipped'   => 0,
			'ids'       => array(),
			'errors'    => array(),
		);

		foreach ( $rows as $index => $row ) {
			$result['processed']++;
			$normalized = $this->normalize_row( is_array( $row ) ? $row : array() );
			$imported   = $this->import_row( $source, $normalized );

			if ( is_wp_error( $imported ) ) {
				$result['skipped']++;
				$result['errors'][] = sprintf(
					/* translators: 1: row number, 2: error message. */
					__( 'Row %1$d: %2$s', 'pasat' ),
					$index + 1,
					$imported->get_error_message()
				);
				continue;
			}

			$result['imported']++;
			$result['ids'][] = $imported;
		}

		( new AuditLogRepository() )->log(
			'legacy_import',
			$source,
			0,
			sprintf(
				/* translators: 1: imported count, 2: skipped count, 3: source name. */
				__( 'Imported %1$d rows and skipped %2$d rows from %3$s.', 'pasat' ),
				$result['imported'],
				$result['skipped'],
				$source
			)
		);

		return $result;
	}

	private function rows_from_json( string $path, string $source ): array|\WP_Error {
		$contents = $this->read_file( $path );
		if ( is_wp_error( $contents ) ) {
			return $contents;
		}

		$decoded = json_decode( $contents, true );
		if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
			return new \WP_Error( 'pasat_import_json_invalid', __( 'The JSON import file is invalid.', 'pasat' ) );
		}

		if ( isset( $decoded[ $source ] ) && is_array( $decoded[ $source ] ) ) {
			$decoded = $decoded[ $source ];
		}

		return $this->is_list_array( $decoded ) ? $decoded : array( $decoded );
	}

	private function rows_from_csv( string $path ): array|\WP_Error {
		$contents = $this->read_file( $path );
		if ( is_wp_error( $contents ) ) {
			return $contents;
		}

		$lines = preg_split( '/\r\n|\r|\n/', trim( $contents ) );
		if ( ! is_array( $lines ) || ! $lines ) {
			return new \WP_Error( 'pasat_import_csv_header', __( 'The CSV import file needs a header row.', 'pasat' ) );
		}

		$header = str_getcsv( array_shift( $lines ) );
		if ( ! is_array( $header ) ) {
			return new \WP_Error( 'pasat_import_csv_header', __( 'The CSV import file needs a header row.', 'pasat' ) );
		}

		$keys = array_map( array( $this, 'normalize_key' ), $header );
		$rows = array();
		foreach ( $lines as $line ) {
			if ( '' === trim( $line ) ) {
				continue;
			}

			$values = str_getcsv( $line );
			$row = array();
			foreach ( $keys as $offset => $key ) {
				if ( '' !== $key ) {
					$row[ $key ] = $values[ $offset ] ?? '';
				}
			}
			if ( array_filter( $row, static fn( mixed $value ): bool => '' !== trim( (string) $value ) ) ) {
				$rows[] = $row;
			}
		}

		return $rows;
	}

	private function import_row( string $source, array $row ): int|\WP_Error {
		return match ( $source ) {
			'venues'       => $this->import_venue( $row ),
			'activities'   => $this->import_activity( $row ),
			'participants' => $this->import_participant( $row ),
			'signups'      => $this->import_signup( $row ),
			'hosts'        => $this->import_host( $row ),
			default        => new \WP_Error( 'pasat_import_source_unknown', __( 'Unknown import source.', 'pasat' ) ),
		};
	}

	private function import_venue( array $row ): int|\WP_Error {
		$name = $this->value( $row, array( 'name', 'venue_name', 'location_name' ) );
		if ( '' === $name ) {
			return new \WP_Error( 'pasat_import_venue_name', __( 'Venue name is required.', 'pasat' ) );
		}

		return ( new VenuesRepository() )->save(
			array(
				'name'        => $name,
				'description' => $this->value( $row, array( 'description', 'notes' ) ),
				'address'     => $this->value( $row, array( 'address', 'location_address' ) ),
				'latitude'    => $this->value( $row, array( 'latitude', 'lat' ) ),
				'longitude'   => $this->value( $row, array( 'longitude', 'lng', 'lon' ) ),
				'venue_type'  => $this->value( $row, array( 'venue_type', 'type' ) ),
				'capacity'    => $this->value( $row, 'capacity' ),
			)
		);
	}

	private function import_activity( array $row ): int|\WP_Error {
		$title = $this->value( $row, array( 'title', 'activity_title', 'event_title', 'name' ) );
		if ( '' === $title ) {
			return new \WP_Error( 'pasat_import_activity_title', __( 'Activity title is required.', 'pasat' ) );
		}

		$venue_id = absint( $this->value( $row, 'venue_id' ) );
		if ( ! $venue_id ) {
			$venue_id = $this->find_venue_id_by_name( $this->value( $row, array( 'venue_name', 'location', 'venue' ) ) );
		}

		return ( new ActivitiesRepository() )->save(
			array(
				'title'                => $title,
				'description'          => $this->value( $row, array( 'description', 'notes' ) ),
				'activity_type'        => $this->value( $row, array( 'activity_type', 'event_type', 'type' ) ),
				'season_year'          => $this->value( $row, array( 'season_year', 'program_year', 'year' ) ),
				'starts_at'            => $this->value( $row, array( 'starts_at', 'start_at', 'start', 'date' ) ),
				'ends_at'              => $this->value( $row, array( 'ends_at', 'end_at', 'end' ) ),
				'venue_id'             => $venue_id,
				'capacity'             => $this->value( $row, 'capacity' ),
				'waitlist_enabled'     => $this->bool_value( $this->value( $row, array( 'waitlist_enabled', 'has_waitlist' ), '1' ) ),
				'signup_opens_at'      => $this->value( $row, array( 'signup_opens_at', 'signup_open', 'registration_opens_at' ) ),
				'signup_closes_at'     => $this->value( $row, array( 'signup_closes_at', 'signup_close', 'registration_closes_at' ) ),
				'status'               => $this->value( $row, 'status', 'draft' ),
				'public_visibility'    => $this->value( $row, array( 'public_visibility', 'visibility' ), 'public' ),
				'minimum_age'          => $this->value( $row, array( 'minimum_age', 'min_age' ) ),
				'maximum_age'          => $this->value( $row, array( 'maximum_age', 'max_age' ) ),
				'requires_warning_ack' => $this->bool_value( $this->value( $row, array( 'requires_warning_ack', 'warning_required' ) ) ),
				'warning_text'         => $this->value( $row, array( 'warning_text', 'warning' ) ),
			)
		);
	}

	private function import_participant( array $row ): int|\WP_Error {
		$email = sanitize_email( $this->value( $row, array( 'email', 'email_address' ) ) );
		if ( ! is_email( $email ) ) {
			return new \WP_Error( 'pasat_import_participant_email', __( 'A valid participant e-mail is required.', 'pasat' ) );
		}

		return ( new ParticipantsRepository() )->create_or_update_from_signup(
			array(
				'first_name'      => $this->value( $row, array( 'first_name', 'firstname', 'given_name' ) ),
				'last_name'       => $this->value( $row, array( 'last_name', 'lastname', 'family_name', 'surname' ) ),
				'nickname'        => $this->value( $row, array( 'nickname', 'display_name' ) ),
				'email'           => $email,
				'phone'           => $this->value( $row, array( 'phone', 'telephone', 'mobile' ) ),
				'age'             => $this->value( $row, 'age' ),
				'consent_given'   => $this->bool_value( $this->value( $row, 'consent_given' ) ),
				'consent_version' => $this->value( $row, 'consent_version', PASAT_VERSION ),
			)
		);
	}

	private function import_signup( array $row ): int|\WP_Error {
		$activity_id = absint( $this->value( $row, 'activity_id' ) );
		if ( ! $activity_id ) {
			$activity_id = $this->find_activity_id_by_title( $this->value( $row, array( 'activity_title', 'event_title', 'activity' ) ) );
		}
		if ( ! $activity_id ) {
			return new \WP_Error( 'pasat_import_signup_activity', __( 'Signup activity could not be matched.', 'pasat' ) );
		}

		$participant_id = absint( $this->value( $row, 'participant_id' ) );
		$email          = sanitize_email( $this->value( $row, array( 'email', 'participant_email', 'email_address' ) ) );
		if ( ! $participant_id && is_email( $email ) ) {
			$participant_id = $this->import_participant( $row );
			if ( is_wp_error( $participant_id ) ) {
				return $participant_id;
			}
		}
		if ( ! $participant_id ) {
			return new \WP_Error( 'pasat_import_signup_participant', __( 'Signup participant could not be matched.', 'pasat' ) );
		}

		$status = $this->status_value( $this->value( $row, 'status', 'confirmed' ) );
		if ( is_email( $email ) && in_array( $status, array( 'confirmed', 'waitlisted' ), true ) && ( new SignupsRepository() )->duplicate_active_by_email( $activity_id, $email ) ) {
			return new \WP_Error( 'pasat_import_signup_duplicate', __( 'An active signup already exists for this participant and activity.', 'pasat' ) );
		}

		return ( new SignupsRepository() )->create(
			$activity_id,
			$participant_id,
			$status,
			array(
				'source'               => 'import',
				'warning_acknowledged' => $this->bool_value( $this->value( $row, array( 'warning_acknowledged', 'warning_ack' ) ) ),
			)
		);
	}

	private function import_host( array $row ): int|\WP_Error {
		$activity_id = absint( $this->value( $row, 'activity_id' ) );
		if ( ! $activity_id ) {
			$activity_id = $this->find_activity_id_by_title( $this->value( $row, array( 'activity_title', 'event_title', 'activity' ) ) );
		}
		if ( ! $activity_id ) {
			return new \WP_Error( 'pasat_import_host_activity', __( 'Host activity could not be matched.', 'pasat' ) );
		}

		$user_id = absint( $this->value( $row, 'user_id' ) );
		if ( ! $user_id ) {
			$user_id = $this->find_user_id( $this->value( $row, array( 'user_email', 'email', 'user_login', 'login' ) ) );
		}
		if ( ! $user_id ) {
			return new \WP_Error( 'pasat_import_host_user', __( 'Host WordPress user could not be matched.', 'pasat' ) );
		}

		return ( new HostsRepository() )->assign( $activity_id, $user_id, $this->value( $row, 'role', 'host' ) );
	}

	private function normalize_row( array $row ): array {
		$normalized = array();
		foreach ( $row as $key => $value ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				continue;
			}
			$normalized[ $this->normalize_key( (string) $key ) ] = is_scalar( $value ) ? trim( (string) $value ) : '';
		}

		return $normalized;
	}

	private function normalize_key( string $key ): string {
		$key = strtolower( trim( $key ) );
		$key = preg_replace( '/[^a-z0-9]+/', '_', $key );
		return trim( (string) $key, '_' );
	}

	private function value( array $row, string|array $keys, mixed $default = '' ): string {
		foreach ( (array) $keys as $key ) {
			$normalized = $this->normalize_key( (string) $key );
			if ( array_key_exists( $normalized, $row ) && '' !== trim( (string) $row[ $normalized ] ) ) {
				return sanitize_text_field( (string) $row[ $normalized ] );
			}
		}

		return sanitize_text_field( (string) $default );
	}

	private function bool_value( mixed $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		return in_array( strtolower( trim( (string) $value ) ), array( '1', 'true', 'yes', 'y', 'on' ), true );
	}

	private function status_value( string $status ): string {
		$status = sanitize_key( strtolower( $status ) );
		return in_array( $status, SignupsRepository::STATUSES, true ) ? $status : 'confirmed';
	}

	private function find_venue_id_by_name( string $name ): int {
		if ( '' === $name ) {
			return 0;
		}

		$table = $this->wpdb->prefix . 'pasat_venues';
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare( "SELECT id FROM {$table} WHERE name = %s ORDER BY id DESC LIMIT 1", $name )
		);
	}

	private function find_activity_id_by_title( string $title ): int {
		if ( '' === $title ) {
			return 0;
		}

		$table = $this->wpdb->prefix . 'pasat_activities';
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare( "SELECT id FROM {$table} WHERE title = %s ORDER BY id DESC LIMIT 1", $title )
		);
	}

	private function find_user_id( string $identifier ): int {
		if ( '' === $identifier ) {
			return 0;
		}

		$user = is_email( $identifier ) ? get_user_by( 'email', $identifier ) : get_user_by( 'login', $identifier );
		return $user ? (int) $user->ID : 0;
	}

	private function read_file( string $path ): string|\WP_Error {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! \WP_Filesystem() || ! $wp_filesystem ) {
			return new \WP_Error( 'pasat_import_filesystem', __( 'WordPress filesystem access is unavailable.', 'pasat' ) );
		}

		$contents = $wp_filesystem->get_contents( $path );
		if ( false === $contents ) {
			return new \WP_Error( 'pasat_import_file_unreadable', __( 'The import file could not be read.', 'pasat' ) );
		}

		return (string) $contents;
	}

	private function is_list_array( array $items ): bool {
		$expected = 0;
		foreach ( $items as $key => $_value ) {
			if ( $key !== $expected ) {
				return false;
			}
			$expected++;
		}

		return true;
	}
}
