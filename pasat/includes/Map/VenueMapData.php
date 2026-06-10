<?php
namespace PASAT\Map;

use PASAT\Database\ActivitiesRepository;
use PASAT\Database\VenuesRepository;
use PASAT\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class VenueMapData {
	public static function public_venues( array $args = array() ): array {
		$args = wp_parse_args(
			$args,
			array(
				'source'      => 'upcoming',
				'activity_id' => 0,
				'limit'       => 500,
			)
		);

		$venues_repo = new VenuesRepository();
		$venues      = array();
		foreach ( $venues_repo->list() as $venue ) {
			$venues[ (int) $venue['id'] ] = self::public_venue( $venue );
		}

		$activity_groups = self::activity_groups( $args );

		if ( 'all' !== $args['source'] || absint( $args['activity_id'] ) > 0 ) {
			$venues = array_intersect_key( $venues, $activity_groups );
		}

		foreach ( $venues as $venue_id => $venue ) {
			$venue['activities'] = $activity_groups[ $venue_id ] ?? array();
			$venues[ $venue_id ] = $venue;
		}

		return array_values( $venues );
	}

	public static function public_venue( array $venue ): array {
		$repo    = new VenuesRepository();
		$has_map = $repo->has_coordinates( $venue );

		return array(
			'id'               => (int) $venue['id'],
			'name'             => (string) $venue['name'],
			'description'      => (string) ( $venue['description'] ?? '' ),
			'address'          => (string) ( $venue['address'] ?? '' ),
			'latitude'         => '' !== (string) ( $venue['latitude'] ?? '' ) ? (float) $venue['latitude'] : null,
			'longitude'        => '' !== (string) ( $venue['longitude'] ?? '' ) ? (float) $venue['longitude'] : null,
			'venue_type'       => (string) ( $venue['venue_type'] ?? '' ),
			'capacity'         => null !== ( $venue['capacity'] ?? null ) ? (int) $venue['capacity'] : null,
			'has_coordinates'  => $has_map,
			'map_url'          => $repo->map_url( $venue ),
		);
	}

	private static function activity_groups( array $args ): array {
		$repo        = new ActivitiesRepository();
		$activity_id = absint( $args['activity_id'] ?? 0 );
		$activities  = array();

		if ( $activity_id > 0 ) {
			$activity = $repo->get_with_venue( $activity_id );
			if ( $activity && 'published' === ( $activity['status'] ?? '' ) && 'public' === ( $activity['public_visibility'] ?? '' ) ) {
				$activities = array( $activity );
			}
		} else {
			$activities = $repo->list(
				array(
					'public'   => true,
					'upcoming' => 'all' !== ( $args['source'] ?? 'upcoming' ),
					'limit'    => max( 1, min( 500, absint( $args['limit'] ?? 500 ) ) ),
				)
			);
		}

		$groups = array();
		foreach ( $activities as $activity ) {
			$venue_id = absint( $activity['venue_id'] ?? 0 );
			if ( ! $venue_id ) {
				continue;
			}

			$groups[ $venue_id ][] = array(
				'id'         => (int) $activity['id'],
				'title'      => (string) $activity['title'],
				'starts_at'  => (string) ( $activity['starts_at'] ?? '' ),
				'date_label' => Helpers::local_datetime( $activity['starts_at'] ?? '' ),
				'signup_url' => Helpers::public_signup_url( (int) $activity['id'] ),
			);
		}

		return $groups;
	}
}
