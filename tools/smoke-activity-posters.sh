#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB_IMAGE="${PASAT_SMOKE_DB_IMAGE:-mariadb:10.11}"
WP_CLI_IMAGE="${PASAT_SMOKE_WP_CLI_IMAGE:-wordpress:cli-php8.1}"
SUFFIX="${PASAT_SMOKE_ID:-$(date +%s)-$$}"
NETWORK_NAME="pasat-poster-smoke-${SUFFIX}"
DB_CONTAINER="pasat-poster-smoke-db-${SUFFIX}"
WORK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/pasat-poster-smoke.XXXXXX")"

chmod 0777 "${WORK_DIR}"

cleanup() {
	docker rm -f "${DB_CONTAINER}" >/dev/null 2>&1 || true
	docker network rm "${NETWORK_NAME}" >/dev/null 2>&1 || true

	if [[ -d "${WORK_DIR}" ]]; then
		docker run --rm -v "${WORK_DIR}:/work" alpine:3.20 sh -lc 'rm -rf /work/* /work/.[!.]* /work/..?*' >/dev/null 2>&1 || true
		rmdir "${WORK_DIR}" >/dev/null 2>&1 || true
	fi
}

require_command() {
	if ! command -v "$1" >/dev/null 2>&1; then
		echo "The '$1' command is required for this smoke test." >&2
		exit 1
	fi
}

parse_version() {
	sed -nE 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*(.+)$/\1/p' "${ROOT_DIR}/pasat/pasat.php" \
		| head -n 1 \
		| tr -d '\r'
}

wait_for_db() {
	local attempt

	for attempt in $(seq 1 60); do
		if docker exec "${DB_CONTAINER}" mariadb-admin ping -h127.0.0.1 -uwp -pwp --silent >/dev/null 2>&1; then
			return 0
		fi

		sleep 2
	done

	docker logs "${DB_CONTAINER}" >&2 || true
	echo "MariaDB did not become ready in time." >&2
	return 1
}

write_smoke_file() {
	cat > "${WORK_DIR}/pasat-poster-smoke.php" <<'PHP'
<?php
use PASAT\Admin\PosterDownloads;
use PASAT\Database\ActivitiesRepository;
use PASAT\Database\VenuesRepository;
use PASAT\Helpers;
use PASAT\Poster\ActivityPosterPdf;
use PASAT\Security\QrCode;

$admin_id = get_user_by( 'login', 'admin' )->ID;
wp_set_current_user( $admin_id );

$venues     = new VenuesRepository();
$activities = new ActivitiesRepository();
$now        = time();

$venue_id = $venues->save(
	array(
		'name'       => 'Poster Studio',
		'address'    => '3 Print Lane',
		'venue_type' => 'studio',
		'capacity'   => 30,
	)
);

$activity_id = $activities->save(
	array(
		'title'             => 'Poster Yoga',
		'description'       => 'Bring comfortable clothes and scan the poster to sign up.',
		'activity_type'     => 'yoga',
		'starts_at'         => gmdate( 'Y-m-d H:i:s', $now + DAY_IN_SECONDS ),
		'venue_id'          => $venue_id,
		'capacity'          => 12,
		'waitlist_enabled'  => 1,
		'status'            => 'published',
		'public_visibility' => 'public',
	)
);

$page_id = wp_insert_post(
	array(
		'post_title'   => 'Activities',
		'post_status'  => 'publish',
		'post_type'    => 'page',
		'post_content' => '[pasat_activity_signup]',
	)
);

$upload = wp_upload_dir();
wp_mkdir_p( $upload['path'] );
$logo_path = trailingslashit( $upload['path'] ) . 'pasat-poster-logo.jpg';

if ( function_exists( 'imagecreatetruecolor' ) ) {
	$image = imagecreatetruecolor( 320, 120 );
	$bg    = imagecolorallocate( $image, 255, 255, 255 );
	$blue  = imagecolorallocate( $image, 21, 52, 86 );
	imagefilledrectangle( $image, 0, 0, 319, 119, $bg );
	imagefilledrectangle( $image, 18, 24, 302, 96, $blue );
	imagejpeg( $image, $logo_path, 90 );
	imagedestroy( $image );
}

if ( ! is_readable( $logo_path ) ) {
	fwrite( STDERR, "Could not create poster logo fixture.\n" );
	exit( 1 );
}

$logo_id = wp_insert_attachment(
	array(
		'post_mime_type' => 'image/jpeg',
		'post_title'     => 'PASAT Poster Logo',
		'post_status'    => 'inherit',
	),
	$logo_path
);

$settings                   = Helpers::settings();
$settings['public_page_id'] = $page_id;
$settings['poster_logo_id'] = $logo_id;
update_option( 'pasat_settings', $settings );

$activity = $activities->get_with_venue( $activity_id );
$pdf      = ActivityPosterPdf::render( $activity );

if ( 0 !== strpos( $pdf, '%PDF-1.4' ) ) {
	fwrite( STDERR, "Poster PDF header was missing.\n" );
	exit( 1 );
}

foreach ( array( '/Subtype /Image', '/Im1 Do', 'Scan to sign up', 'Direct signup link' ) as $expected ) {
	if ( false === strpos( $pdf, $expected ) ) {
		fwrite( STDERR, "Poster PDF missing expected content: {$expected}\n" );
		exit( 1 );
	}
}

$matrix = QrCode::matrix( Helpers::activity_qr_url( $activity_id ) );
if ( ! is_array( $matrix ) || count( $matrix ) < 21 ) {
	fwrite( STDERR, "QR matrix was not generated.\n" );
	exit( 1 );
}

$single_url = PosterDownloads::single_url( $activity_id );
$zip_url    = PosterDownloads::zip_url();
if ( false === strpos( $single_url, 'pasat_activity_poster' ) || false === strpos( $zip_url, 'pasat_activity_posters_zip' ) ) {
	fwrite( STDERR, "Poster download URLs were not generated.\n" );
	exit( 1 );
}

if ( class_exists( ZipArchive::class ) ) {
	$zip_path = trailingslashit( get_temp_dir() ) . 'pasat-poster-smoke.zip';
	$zip      = new ZipArchive();
	if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
		fwrite( STDERR, "Could not create poster smoke ZIP.\n" );
		exit( 1 );
	}
	$zip->addFromString( 'poster-yoga.pdf', $pdf );
	$zip->close();
	if ( ! is_readable( $zip_path ) || filesize( $zip_path ) <= 0 ) {
		fwrite( STDERR, "Poster smoke ZIP was empty.\n" );
		exit( 1 );
	}
	wp_delete_file( $zip_path );
}

echo "activity-posters:ok\n";
PHP
}

run_wp_cli() {
	local version="$1"
	local archive="/workspace/dist/pasat-${version}.zip"

	docker run --rm \
		--network "${NETWORK_NAME}" \
		-v "${WORK_DIR}:/var/www/html" \
		-v "${ROOT_DIR}:/workspace:ro" \
		-w /var/www/html \
		-e HOME=/tmp \
		"${WP_CLI_IMAGE}" \
		sh -lc "
			set -eu
			WP='php -d memory_limit=512M /usr/local/bin/wp'
			\$WP core download --allow-root
			\$WP config create --dbname=wordpress --dbuser=wp --dbpass=wp --dbhost=${DB_CONTAINER}:3306 --allow-root
			\$WP core install --url=http://pasat-poster-smoke.test --title='PASAT Poster Smoke' --admin_user=admin --admin_password='pasat-smoke-pass' --admin_email=admin@example.test --skip-email --allow-root
			\$WP plugin install '${archive}' --activate --allow-root
			\$WP eval-file pasat-poster-smoke.php --allow-root
		"
}

require_command docker
trap cleanup EXIT

VERSION="$(parse_version)"
if [[ -z "${VERSION}" ]]; then
	echo "Could not determine PASAT version from pasat/pasat.php." >&2
	exit 1
fi

write_smoke_file
"${ROOT_DIR}/tools/build-release.sh"

docker network create "${NETWORK_NAME}" >/dev/null
docker run -d \
	--name "${DB_CONTAINER}" \
	--network "${NETWORK_NAME}" \
	-e MARIADB_DATABASE=wordpress \
	-e MARIADB_USER=wp \
	-e MARIADB_PASSWORD=wp \
	-e MARIADB_ROOT_PASSWORD=root \
	"${DB_IMAGE}" >/dev/null

wait_for_db
run_wp_cli "${VERSION}"

echo "PASAT Activity Poster smoke test passed for pasat-${VERSION}.zip"
