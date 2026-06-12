#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB_IMAGE="${PASAT_SMOKE_DB_IMAGE:-mariadb:10.11}"
WP_CLI_IMAGE="${PASAT_SMOKE_WP_CLI_IMAGE:-wordpress:cli-php8.1}"
SUFFIX="${PASAT_SMOKE_ID:-$(date +%s)-$$}"
NETWORK_NAME="pasat-map-smoke-${SUFFIX}"
DB_CONTAINER="pasat-map-smoke-db-${SUFFIX}"
WORK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/pasat-map-smoke.XXXXXX")"

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

parse_db_version() {
	sed -nE "s/^define\\( 'PASAT_DB_VERSION', '([^']+)' \\);/\\1/p" "${ROOT_DIR}/pasat/pasat.php" \
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
	cat > "${WORK_DIR}/pasat-venue-map-smoke.php" <<'PHP'
<?php
use PASAT\Database\ActivitiesRepository;
use PASAT\Database\VenuesRepository;
use PASAT\Helpers;
use PASAT\Map\Geocoder;

$admin_id = get_user_by( 'login', 'admin' )->ID;
wp_set_current_user( $admin_id );

$venues     = new VenuesRepository();
$activities = new ActivitiesRepository();
$now        = time();

$settings                              = Helpers::settings();
$settings['map_enabled']               = 1;
$settings['show_map_on_signup']        = 0;
$settings['geocoding_enabled']         = 1;
$settings['geocoding_endpoint']        = 'https://example.test/geocode';
$settings['geocoding_throttle_seconds'] = 1;
$settings['require_consent']           = 1;
$settings['consent_text']              = 'I consent to PASAT storing my signup data.';
$settings['membership_enabled']        = 1;
$settings['membership_opt_in_text']    = 'I would like to become a member.';
$settings['default_warning_text']      = 'I acknowledge the activity safety information.';
update_option( 'pasat_settings', $settings );

$mapped_venue_id = $venues->save(
	array(
		'name'       => 'Mapped Studio',
		'address'    => '1 Map Street',
		'latitude'   => '60.3913000',
		'longitude'  => '5.3221000',
		'venue_type' => 'studio',
		'capacity'   => 30,
	)
);

$address_venue_id = $venues->save(
	array(
		'name'       => 'Address Hall',
		'address'    => '2 Address Road',
		'venue_type' => 'hall',
		'capacity'   => 80,
	)
);

$activities->save(
	array(
		'title'             => 'Mapped Yoga',
		'activity_type'     => 'yoga',
		'starts_at'         => gmdate( 'Y-m-d H:i:s', $now + HOUR_IN_SECONDS ),
		'venue_id'          => $mapped_venue_id,
		'capacity'          => 12,
		'waitlist_enabled'  => 1,
		'status'            => 'published',
		'public_visibility' => 'public',
	)
);

$activities->save(
	array(
		'title'             => 'Address Dance',
		'activity_type'     => 'dance',
		'starts_at'         => gmdate( 'Y-m-d H:i:s', $now + 2 * HOUR_IN_SECONDS ),
		'venue_id'          => $address_venue_id,
		'capacity'          => 16,
		'waitlist_enabled'  => 1,
		'status'            => 'published',
		'public_visibility' => 'public',
	)
);

$map_html = do_shortcode( '[pasat_venue_map source="upcoming" height="360" show_cards="1"]' );
foreach ( array( 'data-pasat-map-canvas', 'Mapped Studio', 'Address Hall', 'aria-label="Show Mapped Studio on map"', 'aria-label="Directions to Mapped Studio"', 'openstreetmap.org' ) as $expected ) {
	if ( false === strpos( $map_html, $expected ) ) {
		fwrite( STDERR, "Venue map markup missing expected content: {$expected}\n" );
		exit( 1 );
	}
}

if ( false === strpos( $map_html, '60.3913' ) || false === strpos( $map_html, 'Mapped Yoga' ) ) {
	fwrite( STDERR, "Venue map did not include coordinate venue/activity data.\n" );
	exit( 1 );
}

if ( ! wp_script_is( 'pasat-leaflet', 'enqueued' ) || ! wp_script_is( 'pasat-public', 'enqueued' ) ) {
	fwrite( STDERR, "Venue map assets were not enqueued.\n" );
	exit( 1 );
}

$signup_html = do_shortcode( '[pasat_activity_signup show_map="1"]' );
if ( false === strpos( $signup_html, 'data-pasat-venue-map' ) || false === strpos( $signup_html, 'pasat-form' ) ) {
	fwrite( STDERR, "Signup shortcode did not include the venue map and signup form.\n" );
	exit( 1 );
}

foreach ( array( 'data-pasat-notice-region aria-live="polite" aria-atomic="true"', 'aria-describedby="pasat-signup-', 'name="consent_given"', 'name="membership_opt_in"', 'data-pasat-warning-check' ) as $expected ) {
	if ( false === strpos( $signup_html, $expected ) ) {
		fwrite( STDERR, "Signup accessibility markup missing expected content: {$expected}\n" );
		exit( 1 );
	}
}

foreach (
	array(
		'consent'    => '/<label class="pasat-check" for="[^"]+consent"><input id="[^"]+consent" type="checkbox" name="consent_given"/',
		'membership' => '/<label class="pasat-check" for="[^"]+membership"><input id="[^"]+membership" type="checkbox" name="membership_opt_in"/',
		'warning'    => '/<label class="pasat-check[^"]*" for="[^"]+warning"[^>]*data-pasat-warning-check[^>]*>\\s*<input id="[^"]+warning" type="checkbox" name="warning_acknowledged"/',
		'age-note'   => '/<input id="[^"]+age" name="age"[^>]+aria-describedby="[^"]+age-note"/',
		'summary'    => '/data-pasat-signup-summary[^>]+role="status"[^>]+aria-live="polite"[^>]+aria-atomic="true"/',
		'activity-summary-control' => '/<select id="[^"]+activity" name="activity_id" required aria-controls="[^"]+summary"/',
	) as $name => $pattern
) {
	if ( ! preg_match( $pattern, $signup_html ) ) {
		fwrite( STDERR, "Signup accessibility markup missing explicit {$name} association.\n" );
		exit( 1 );
	}
}

do_action( 'rest_api_init' );
$request  = new WP_REST_Request( 'GET', '/pasat/v1/venues' );
$response = rest_do_request( $request );
if ( $response->is_error() ) {
	fwrite( STDERR, "Public venues REST endpoint returned an error.\n" );
	exit( 1 );
}

$data = rest_get_server()->response_to_data( $response, false );
if ( ! is_array( $data ) || count( $data ) < 2 ) {
	fwrite( STDERR, 'Expected public venue REST data, got ' . wp_json_encode( $data ) . "\n" );
	exit( 1 );
}

foreach ( $data as $venue ) {
	if ( array_key_exists( 'geocoding_error', $venue ) ) {
		fwrite( STDERR, "Public venue REST exposed geocoding_error.\n" );
		exit( 1 );
	}
}

add_filter(
	'pre_http_request',
	static function () {
		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( array( array( 'lat' => '61.5001000', 'lon' => '6.1002000' ) ) ),
			'response' => array( 'code' => 200, 'message' => 'OK' ),
			'cookies'  => array(),
		);
	},
	10,
	3
);

$geocoded = ( new Geocoder() )->geocode_venue( $address_venue_id );
if ( is_wp_error( $geocoded ) ) {
	fwrite( STDERR, 'Geocoder returned an error: ' . $geocoded->get_error_message() . "\n" );
	exit( 1 );
}

$venue = $venues->get( $address_venue_id );
if ( 'geocoded' !== ( $venue['geocoding_status'] ?? '' ) || '61.5001000' !== (string) $venue['latitude'] || '6.1002000' !== (string) $venue['longitude'] ) {
	fwrite( STDERR, 'Geocoded venue was not persisted as expected: ' . wp_json_encode( $venue ) . "\n" );
	exit( 1 );
}

wp_set_current_user( 0 );
$unauthorized = new WP_REST_Request( 'POST', '/pasat/v1/admin/venues/' . $address_venue_id . '/geocode' );
$denied       = rest_do_request( $unauthorized );
if ( ! $denied->is_error() ) {
	fwrite( STDERR, "Unauthenticated geocode REST request was not denied.\n" );
	exit( 1 );
}

echo "venue-map:ok\n";
PHP
}

run_wp_cli() {
	local version="$1"
	local db_version="$2"
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
			\$WP core install --url=http://pasat-map-smoke.test --title='PASAT Map Smoke' --admin_user=admin --admin_password='pasat-smoke-pass' --admin_email=admin@example.test --skip-email --allow-root
			\$WP plugin install '${archive}' --activate --allow-root
			test \"\$(\$WP option get pasat_db_version --allow-root)\" = '${db_version}'
			\$WP eval-file pasat-venue-map-smoke.php --allow-root
		"
}

require_command docker
trap cleanup EXIT

VERSION="$(parse_version)"
if [[ -z "${VERSION}" ]]; then
	echo "Could not determine PASAT version from pasat/pasat.php." >&2
	exit 1
fi

DB_VERSION="$(parse_db_version)"
if [[ -z "${DB_VERSION}" ]]; then
	echo "Could not determine PASAT DB version from pasat/pasat.php." >&2
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
run_wp_cli "${VERSION}" "${DB_VERSION}"

echo "PASAT Venue Map smoke test passed for pasat-${VERSION}.zip"
