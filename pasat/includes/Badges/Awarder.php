<?php
namespace PASAT\Badges;

use PASAT\Database\BadgesRepository;
use PASAT\Database\ParticipationLogsRepository;
use PASAT\Helpers;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Awarder {
	private BadgesRepository $badges;
	private ParticipationLogsRepository $logs;

	public function __construct() {
		$this->badges = new BadgesRepository();
		$this->logs   = new ParticipationLogsRepository();
	}

	public function recalculate_log( int $log_id ): array|WP_Error {
		$log = $this->logs->get_with_details( $log_id );
		if ( ! $log ) {
			return new WP_Error( 'pasat_log_not_found', __( 'Participation log not found.', 'pasat' ), array( 'status' => 404 ) );
		}

		if ( empty( Helpers::setting( 'badges_enabled', 1 ) ) ) {
			return array( 'awarded' => array(), 'revoked' => array() );
		}

		$participant_id = (int) $log['participant_id'];
		$year           = self::activity_year( $log );
		$awarded        = array();
		$revoked        = array();

		if ( $year > 0 ) {
			if ( $this->logs->participant_has_qualifying_year( $participant_id, $year ) ) {
				$this->badges->award(
					array(
						'participant_id' => $participant_id,
						'badge_type'     => 'year_participation',
						'badge_key'      => 'year:' . $year,
						'label'          => self::year_label( $year ),
						'season_year'    => $year,
						'metadata'       => array( 'source' => 'participation_log' ),
					)
				);
				$awarded[] = 'year:' . $year;
			} else {
				$this->badges->revoke_year( $participant_id, $year );
				$revoked[] = 'year:' . $year;
			}
		}

		$this->badges->revoke_placements_for_log( $log_id );
		if ( $this->qualifies_for_placement_badge( $log ) ) {
			$placement = (int) $log['placement'];
			$this->badges->award(
				array(
					'participant_id'       => $participant_id,
					'badge_type'           => 'placement',
					'badge_key'            => 'placement:' . (int) $log['activity_id'] . ':' . $placement,
					'label'                => self::placement_label( $placement ),
					'activity_id'          => (int) $log['activity_id'],
					'participation_log_id' => $log_id,
					'placement'            => $placement,
					'metadata'             => array( 'activity_title' => $log['activity_title'] ?? '' ),
				)
			);
			$awarded[] = 'placement:' . $placement;
		} else {
			$revoked[] = 'placement';
		}

		return array(
			'awarded' => array_values( array_unique( $awarded ) ),
			'revoked' => array_values( array_unique( $revoked ) ),
		);
	}

	public function recalculate_activity( int $activity_id ): array {
		$count = 0;
		foreach ( $this->logs->ids_for_activity( $activity_id ) as $log_id ) {
			$this->recalculate_log( $log_id );
			++$count;
		}

		return array( 'processed' => $count );
	}

	public static function activity_year( array $activity_or_log ): int {
		if ( ! empty( $activity_or_log['season_year'] ) ) {
			return (int) $activity_or_log['season_year'];
		}

		if ( ! empty( $activity_or_log['starts_at'] ) ) {
			$timestamp = strtotime( (string) $activity_or_log['starts_at'] . ' UTC' );
			return $timestamp ? (int) gmdate( 'Y', $timestamp ) : 0;
		}

		return 0;
	}

	private function qualifies_for_placement_badge( array $log ): bool {
		return in_array( $log['attendance_status'] ?? '', ParticipationLogsRepository::QUALIFYING_STATUSES, true )
			&& in_array( (int) ( $log['placement'] ?? 0 ), array( 1, 2, 3 ), true )
			&& in_array( $log['activity_status'] ?? '', array( 'published', 'archived' ), true );
	}

	private static function year_label( int $year ): string {
		return str_replace( '{year}', (string) $year, (string) Helpers::setting( 'badge_year_label_template', __( '{year} Participant', 'pasat' ) ) );
	}

	private static function placement_label( int $placement ): string {
		$labels = array(
			1 => (string) Helpers::setting( 'badge_first_place_label', __( '1st Place', 'pasat' ) ),
			2 => (string) Helpers::setting( 'badge_second_place_label', __( '2nd Place', 'pasat' ) ),
			3 => (string) Helpers::setting( 'badge_third_place_label', __( '3rd Place', 'pasat' ) ),
		);

		return $labels[ $placement ] ?? '';
	}
}
