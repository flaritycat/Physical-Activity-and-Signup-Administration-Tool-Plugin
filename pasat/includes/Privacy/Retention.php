<?php
namespace PASAT\Privacy;

use PASAT\Database\AuditLogRepository;
use PASAT\Database\ParticipantsRepository;
use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
// Retention scans PASAT custom tables directly; table identifiers are fixed plugin tables and values are prepared.
final class Retention {
	public static function run_scheduled(): void {
		self::run_cleanup();
	}

	public static function run_cleanup(): array {
		global $wpdb;

		$days         = max( 1, absint( Helpers::setting( 'retention_period_days', 365 ) ) );
		$cutoff       = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );
		$participants = Helpers::table( 'participants' );
		$signups      = Helpers::table( 'signups' );
		$activities   = Helpers::table( 'activities' );

		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.id
				FROM {$participants} p
				WHERE p.updated_at < %s
					AND NOT EXISTS (
						SELECT 1
						FROM {$signups} s
						INNER JOIN {$activities} a ON a.id = s.activity_id
						WHERE s.participant_id = p.id
							AND s.status IN ('confirmed', 'waitlisted')
							AND (a.ends_at IS NULL OR a.ends_at >= %s)
					)
				LIMIT 200",
				$cutoff,
				Helpers::now()
			)
		);

		$mode = Helpers::setting( 'erasure_mode', 'anonymize' );
		$repo = new ParticipantsRepository();
		foreach ( $ids as $id ) {
			$id = absint( $id );
			if ( 'delete' === $mode ) {
				Eraser::delete_participant( $id );
			} else {
				$repo->anonymize( $id );
			}
		}

		( new AuditLogRepository() )->log( 'privacy.retention_cleanup', 'privacy', 0, 'Retention cleanup processed ' . count( $ids ) . ' participants' );

		return array(
			'processed' => count( $ids ),
			'mode'      => $mode,
			'cutoff'    => $cutoff,
		);
	}
}
