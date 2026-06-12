#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB_IMAGE="${PASAT_SMOKE_DB_IMAGE:-mariadb:10.11}"
WP_CLI_IMAGE="${PASAT_SMOKE_WP_CLI_IMAGE:-wordpress:cli-php8.1}"
SUFFIX="${PASAT_SMOKE_ID:-$(date +%s)-$$}"
NETWORK_NAME="pasat-board-smoke-${SUFFIX}"
DB_CONTAINER="pasat-board-smoke-db-${SUFFIX}"
WORK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/pasat-board-smoke.XXXXXX")"

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
	cat > "${WORK_DIR}/pasat-board-smoke.php" <<'PHP'
<?php
use PASAT\Database\ActivitiesRepository;
use PASAT\Database\VenuesRepository;

$venues     = new VenuesRepository();
$activities = new ActivitiesRepository();
$now        = time();

$venue_a = $venues->save(
	array(
		'name'       => 'Studio A',
		'address'    => 'One Example Street',
		'venue_type' => 'studio',
		'capacity'   => 20,
	)
);

$venue_b = $venues->save(
	array(
		'name'       => 'Studio B',
		'address'    => 'Two Example Street',
		'venue_type' => 'hall',
		'capacity'   => 20,
	)
);

$activity_a = $activities->save(
	array(
		'title'             => 'Morning Yoga',
		'description'       => 'A calm public test activity.',
		'activity_type'     => 'yoga',
		'starts_at'         => gmdate( 'Y-m-d H:i:s', $now + 1800 ),
		'venue_id'          => $venue_a,
		'capacity'          => 8,
		'waitlist_enabled'  => 1,
		'status'            => 'published',
		'public_visibility' => 'public',
	)
);

$activities->save(
	array(
		'title'             => 'Evening Dance',
		'description'       => 'Should be filtered out of this board.',
		'activity_type'     => 'dance',
		'starts_at'         => gmdate( 'Y-m-d H:i:s', $now + 7200 ),
		'venue_id'          => $venue_b,
		'capacity'          => 8,
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

$settings                   = PASAT\Helpers::settings();
$settings['public_page_id'] = $page_id;
update_option( 'pasat_settings', $settings );

$html = do_shortcode(
	sprintf(
		'[pasat_activity_board mode="kiosk" show_qr="1" venue_id="%d" activity_type="yoga" refresh="15000" limit="5"]',
		$venue_a
	)
);

$expectations = array(
	'pasat-activity-board--kiosk',
	'data-pasat-mode="kiosk"',
	'data-pasat-show-qr="1"',
	'pasat-board-toolbar',
	'pasat-board-items',
	'role="status"',
	'data-pasat-qr-value=',
	'pasat-board-qr-wrap',
	'Morning Yoga',
	'Starting soon',
	'Updated just now',
	'pasat_activity_id=' . $activity_a,
);

foreach ( $expectations as $expected ) {
	if ( false === strpos( $html, $expected ) ) {
		fwrite( STDERR, "Missing board markup: {$expected}\n" );
		exit( 1 );
	}
}

$grid_html = do_shortcode(
	sprintf(
		'[pasat_activity_board mode="grid" show_qr="0" venue_id="%d" activity_type="yoga" refresh="15000" limit="5"]',
		$venue_a
	)
);

foreach ( array( 'pasat-activity-board--grid', 'data-pasat-mode="grid"', 'pasat-board-items', 'Morning Yoga' ) as $expected ) {
	if ( false === strpos( $grid_html, $expected ) ) {
		fwrite( STDERR, "Missing grid board markup: {$expected}\n" );
		exit( 1 );
	}
}

if ( false !== strpos( $html, 'Evening Dance' ) ) {
	fwrite( STDERR, "Venue/activity-type filters did not exclude Evening Dance.\n" );
	exit( 1 );
}

do_action( 'rest_api_init' );

$request = new WP_REST_Request( 'GET', '/pasat/v1/activities' );
$request->set_param( 'venue_id', $venue_a );
$request->set_param( 'activity_type', 'yoga' );
$request->set_param( 'limit', 5 );

$response = rest_do_request( $request );
if ( $response->is_error() ) {
	fwrite( STDERR, "Public activities REST endpoint returned an error.\n" );
	exit( 1 );
}

$server = rest_get_server();
$data   = $server->response_to_data( $response, false );

if ( ! is_array( $data ) || 1 !== count( $data ) ) {
	fwrite( STDERR, 'Expected exactly one filtered REST activity, got ' . wp_json_encode( $data ) . "\n" );
	exit( 1 );
}

$activity = reset( $data );

if ( 'Morning Yoga' !== ( $activity['title'] ?? '' ) || empty( $activity['signup_url'] ) || empty( $activity['qr_url'] ) ) {
	fwrite( STDERR, 'Filtered REST activity did not include expected public fields: ' . wp_json_encode( $activity ) . "\n" );
	exit( 1 );
}

if ( false === strpos( $activity['qr_url'], '?psa=' . $activity_a ) ) {
	fwrite( STDERR, 'Filtered REST activity did not include the expected short QR URL: ' . wp_json_encode( $activity ) . "\n" );
	exit( 1 );
}

foreach ( array( 'participant', 'email', 'phone', 'cancellation_token' ) as $private_key ) {
	if ( array_key_exists( $private_key, $activity ) ) {
		fwrite( STDERR, "Public REST activity exposed private key: {$private_key}\n" );
		exit( 1 );
	}
}

echo "activity-board:ok\n";
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
			\$WP core install --url=http://pasat-board-smoke.test --title='PASAT Board Smoke' --admin_user=admin --admin_password='pasat-smoke-pass' --admin_email=admin@example.test --skip-email --allow-root
			\$WP plugin install '${archive}' --activate --allow-root
			\$WP eval-file pasat-board-smoke.php --allow-root
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

echo "PASAT Activity Board smoke test passed for pasat-${VERSION}.zip"
