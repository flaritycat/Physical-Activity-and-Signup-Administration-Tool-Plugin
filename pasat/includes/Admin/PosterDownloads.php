<?php
namespace PASAT\Admin;

use PASAT\Capabilities;
use PASAT\Database\ActivitiesRepository;
use PASAT\Poster\ActivityPosterPdf;
use PASAT\Security\Nonces;
use ZipArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class PosterDownloads {
	public static function single(): void {
		check_admin_referer( Nonces::action( 'activity_poster' ) );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified above with the default _wpnonce field.
		$activity_id = absint( wp_unslash( $_GET['id'] ?? 0 ) );
		if ( ! $activity_id || ! Capabilities::can_manage_activity( $activity_id ) ) {
			wp_die( esc_html__( 'You do not have permission to download this activity poster.', 'pasat' ) );
		}

		$activity = ( new ActivitiesRepository() )->get_with_venue( $activity_id );
		if ( ! $activity ) {
			wp_die( esc_html__( 'Activity not found.', 'pasat' ) );
		}

		$pdf      = ActivityPosterPdf::render( $activity );
		$filename = self::poster_filename( $activity );

		self::send_download( $pdf, $filename, 'application/pdf' );
	}

	public static function zip(): void {
		check_admin_referer( Nonces::action( 'activity_posters_zip' ) );

		if ( ! current_user_can( 'pasat_manage_all_activities' ) && ! current_user_can( 'pasat_manage_assigned_activities' ) ) {
			wp_die( esc_html__( 'You do not have permission to download activity posters.', 'pasat' ) );
		}

		if ( ! class_exists( ZipArchive::class ) ) {
			wp_die( esc_html__( 'Bulk poster ZIP downloads require the PHP ZipArchive extension. Single poster PDF downloads still work.', 'pasat' ) );
		}

		$repo = new ActivitiesRepository();
		$args = array( 'limit' => 500 );
		if ( ! current_user_can( 'pasat_manage_all_activities' ) ) {
			$args['assigned_user_id'] = get_current_user_id();
		}

		$activities = $repo->list( $args );
		if ( ! $activities ) {
			wp_die( esc_html__( 'No activities are available for poster export.', 'pasat' ) );
		}

		$zip_path = trailingslashit( get_temp_dir() ) . 'pasat-activity-posters-' . wp_generate_uuid4() . '.zip';
		$zip      = new ZipArchive();
		if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			wp_die( esc_html__( 'Could not create the poster ZIP file.', 'pasat' ) );
		}

		foreach ( $activities as $activity ) {
			$zip->addFromString( self::poster_filename( $activity ), ActivityPosterPdf::render( $activity ) );
		}
		$zip->close();

		self::send_file( $zip_path, 'pasat-activity-posters.zip', 'application/zip' );
	}

	public static function single_url( int $activity_id ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'pasat_activity_poster',
					'id'     => $activity_id,
				),
				admin_url( 'admin-post.php' )
			),
			Nonces::action( 'activity_poster' )
		);
	}

	public static function zip_url(): string {
		return wp_nonce_url(
			add_query_arg(
				array( 'action' => 'pasat_activity_posters_zip' ),
				admin_url( 'admin-post.php' )
			),
			Nonces::action( 'activity_posters_zip' )
		);
	}

	private static function poster_filename( array $activity ): string {
		$date = '';
		if ( ! empty( $activity['starts_at'] ) ) {
			$timestamp = strtotime( $activity['starts_at'] . ' UTC' );
			$date      = $timestamp ? gmdate( 'Y-m-d-', $timestamp ) : '';
		}

		$title = sanitize_title( (string) ( $activity['title'] ?? 'activity' ) ) ?: 'activity';
		return sanitize_file_name( 'pasat-' . $date . $title . '-' . absint( $activity['id'] ?? 0 ) . '.pdf' );
	}

	private static function send_download( string $content, string $filename, string $content_type ): void {
		if ( headers_sent() ) {
			wp_die( esc_html__( 'Could not send the poster download because output has already started.', 'pasat' ) );
		}

		nocache_headers();
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $content ) );
		header( 'X-Content-Type-Options: nosniff' );
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary file download.
		exit;
	}

	private static function send_file( string $path, string $filename, string $content_type ): void {
		if ( ! is_readable( $path ) ) {
			wp_die( esc_html__( 'The poster ZIP file could not be read.', 'pasat' ) );
		}
		if ( headers_sent() ) {
			wp_delete_file( $path );
			wp_die( esc_html__( 'Could not send the poster ZIP because output has already started.', 'pasat' ) );
		}

		nocache_headers();
		header( 'Content-Type: ' . $content_type );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . (string) filesize( $path ) );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile, WordPress.Security.EscapeOutput.OutputNotEscaped -- Binary file download.
		wp_delete_file( $path );
		exit;
	}
}
