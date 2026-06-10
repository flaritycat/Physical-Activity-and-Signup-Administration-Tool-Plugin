#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB_IMAGE="${PASAT_SMOKE_DB_IMAGE:-mariadb:10.11}"
WP_CLI_IMAGE="${PASAT_SMOKE_WP_CLI_IMAGE:-wordpress:cli-php8.1}"
SUFFIX="${PASAT_SMOKE_ID:-$(date +%s)-$$}"
NETWORK_NAME="pasat-membership-smoke-${SUFFIX}"
DB_CONTAINER="pasat-membership-smoke-db-${SUFFIX}"
WORK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/pasat-membership-smoke.XXXXXX")"

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
	cat > "${WORK_DIR}/pasat-membership-badges-smoke.php" <<'PHP'
<?php
use PASAT\Badges\Awarder;
use PASAT\Capabilities;
use PASAT\Database\ActivitiesRepository;
use PASAT\Database\AuditLogRepository;
use PASAT\Database\BadgesRepository;
use PASAT\Database\HostsRepository;
use PASAT\Database\ParticipantsRepository;
use PASAT\Database\ParticipationLogsRepository;
use PASAT\Database\SignupsRepository;
use PASAT\Helpers;
use PASAT\Privacy\Eraser;
use PASAT\Privacy\Exporter;
use PASAT\REST\PublicSignupController;

global $wpdb;

$admin_id = get_user_by( 'login', 'admin' )->ID;
wp_set_current_user( $admin_id );

$settings                                 = Helpers::settings();
$settings['membership_enabled']            = 1;
$settings['membership_default_status']     = 'interested';
$settings['membership_opt_in_text']        = 'I would like to become a member';
$settings['badges_enabled']                = 1;
$settings['badges_show_in_my_signups']     = 1;
$settings['hosts_can_record_placements']   = 1;
$settings['pasat_strict_email_delivery']   = 0;
update_option( 'pasat_settings', $settings );

foreach ( array( 'membership_status', 'membership_number' ) as $column ) {
	$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW COLUMNS FROM ' . Helpers::table( 'participants' ) . ' LIKE %s', $column ) );
	if ( ! $exists ) {
		fwrite( STDERR, "Missing participant column: {$column}\n" );
		exit( 1 );
	}
}

foreach ( array( Helpers::table( 'participation_logs' ), Helpers::table( 'participant_badges' ) ) as $table ) {
	$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	if ( $exists !== $table ) {
		fwrite( STDERR, "Missing PASAT table: {$table}\n" );
		exit( 1 );
	}
}

$activities = new ActivitiesRepository();
$activity_id = $activities->save(
	array(
		'title'             => 'Badge Sprint',
		'activity_type'     => 'race',
		'season_year'       => 2026,
		'starts_at'         => gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ),
		'capacity'          => 10,
		'waitlist_enabled'  => 1,
		'status'            => 'published',
		'public_visibility' => 'public',
	)
);

$signup = PublicSignupController::process_signup(
	array(
		'activity_id'        => $activity_id,
		'first_name'         => 'Member',
		'last_name'          => 'Runner',
		'email'              => 'member-runner@example.test',
		'consent_given'      => 1,
		'membership_opt_in'  => 1,
	)
);

if ( is_wp_error( $signup ) ) {
	fwrite( STDERR, 'Signup failed: ' . $signup->get_error_message() . "\n" );
	exit( 1 );
}

$participants = new ParticipantsRepository();
$participant  = $participants->find_by_email( 'member-runner@example.test' );
if ( ! $participant || 'interested' !== ( $participant['membership_status'] ?? '' ) || empty( $participant['membership_opted_in'] ) ) {
	fwrite( STDERR, 'Membership opt-in was not stored correctly: ' . wp_json_encode( $participant ) . "\n" );
	exit( 1 );
}

$participants->update_membership(
	(int) $participant['id'],
	array(
		'membership_status' => 'active',
		'membership_number' => 'M-100',
		'membership_notes'  => 'Approved in smoke test',
	)
);
( new AuditLogRepository() )->log( 'participant.membership_update', 'participant', (int) $participant['id'], 'Smoke membership update' );
$participant = $participants->get( (int) $participant['id'] );
if ( 'active' !== ( $participant['membership_status'] ?? '' ) || 'M-100' !== ( $participant['membership_number'] ?? '' ) ) {
	fwrite( STDERR, 'Admin membership update was not stored correctly.' . "\n" );
	exit( 1 );
}

$audit_count = (int) $wpdb->get_var(
	$wpdb->prepare(
		'SELECT COUNT(*) FROM ' . Helpers::table( 'audit_log' ) . ' WHERE action = %s AND object_id = %d',
		'participant.membership_update',
		(int) $participant['id']
	)
);
if ( $audit_count < 1 ) {
	fwrite( STDERR, "Membership update did not create an audit log entry.\n" );
	exit( 1 );
}

$signup_details = ( new SignupsRepository() )->get_with_details( (int) $signup['signup_id'] );
$logs           = new ParticipationLogsRepository();
$badges         = new BadgesRepository();
$awarder        = new Awarder();
$log_id         = $logs->save(
	array(
		'signup_id'          => (int) $signup_details['id'],
		'activity_id'        => $activity_id,
		'participant_id'     => (int) $participant['id'],
		'attendance_status'  => 'completed',
		'placement'          => 1,
		'result_value'       => '12.4',
		'result_unit'        => 'seconds',
		'private_notes'      => 'Strong finish',
	)
);
$awarder->recalculate_log( $log_id );

$labels = wp_list_pluck( $badges->active_for_participant( (int) $participant['id'] ), 'label' );
if ( ! in_array( '2026 Participant', $labels, true ) || ! in_array( '1st Place', $labels, true ) ) {
	fwrite( STDERR, 'Expected year and first-place badges, got ' . wp_json_encode( $labels ) . "\n" );
	exit( 1 );
}

$logs->save(
	array(
		'attendance_status' => 'completed',
		'placement'         => 2,
		'result_value'      => '12.4',
		'result_unit'       => 'seconds',
		'private_notes'     => 'Adjusted placement',
	),
	$log_id
);
$awarder->recalculate_log( $log_id );
$labels = wp_list_pluck( $badges->active_for_participant( (int) $participant['id'] ), 'label' );
if ( in_array( '1st Place', $labels, true ) || ! in_array( '2nd Place', $labels, true ) ) {
	fwrite( STDERR, 'Placement badge did not move from first to second: ' . wp_json_encode( $labels ) . "\n" );
	exit( 1 );
}

$logs->save(
	array(
		'attendance_status' => 'completed',
		'placement'         => '',
		'private_notes'     => 'Placement cleared',
	),
	$log_id
);
$awarder->recalculate_log( $log_id );
$labels = wp_list_pluck( $badges->active_for_participant( (int) $participant['id'] ), 'label' );
if ( in_array( '1st Place', $labels, true ) || in_array( '2nd Place', $labels, true ) || in_array( '3rd Place', $labels, true ) ) {
	fwrite( STDERR, 'Placement badge remained active after clearing placement: ' . wp_json_encode( $labels ) . "\n" );
	exit( 1 );
}

$logs->save(
	array(
		'attendance_status' => 'no_show',
		'placement'         => '',
		'private_notes'     => 'No show',
	),
	$log_id
);
$awarder->recalculate_log( $log_id );
$labels = wp_list_pluck( $badges->active_for_participant( (int) $participant['id'] ), 'label' );
if ( in_array( '2026 Participant', $labels, true ) ) {
	fwrite( STDERR, 'Year badge remained active after no-show: ' . wp_json_encode( $labels ) . "\n" );
	exit( 1 );
}

$logs->save(
	array(
		'attendance_status' => 'completed',
		'placement'         => 3,
		'private_notes'     => 'Final result',
	),
	$log_id
);
$awarder->recalculate_log( $log_id );
$labels = wp_list_pluck( $badges->active_for_participant( (int) $participant['id'] ), 'label' );
if ( ! in_array( '2026 Participant', $labels, true ) || ! in_array( '3rd Place', $labels, true ) ) {
	fwrite( STDERR, 'Expected year and third-place badges after final result: ' . wp_json_encode( $labels ) . "\n" );
	exit( 1 );
}

$export = Exporter::export( 'member-runner@example.test' );
$export_json = wp_json_encode( $export['data'] );
foreach ( array( 'Membership status', 'PASAT Participation', 'Badge label', '3rd Place' ) as $expected ) {
	if ( false === strpos( $export_json, $expected ) ) {
		fwrite( STDERR, "Privacy export missing {$expected}: {$export_json}\n" );
		exit( 1 );
	}
}

$host_id = wp_create_user( 'pasat-host', 'password', 'pasat-host@example.test' );
$host    = get_user_by( 'id', $host_id );
$host->set_role( 'pasat_activity_host' );
( new HostsRepository() )->assign( $activity_id, $host_id );
$other_activity_id = $activities->save(
	array(
		'title'             => 'Unassigned Result Activity',
		'starts_at'         => gmdate( 'Y-m-d H:i:s', time() + 2 * HOUR_IN_SECONDS ),
		'status'            => 'published',
		'public_visibility' => 'public',
	)
);
wp_set_current_user( $host_id );
if ( ! Capabilities::can_manage_participation( $activity_id ) || Capabilities::can_manage_participation( $other_activity_id ) ) {
	fwrite( STDERR, "Host participation scope check failed.\n" );
	exit( 1 );
}

wp_set_current_user( $admin_id );
$settings = Helpers::settings();
$settings['erasure_mode'] = 'anonymize';
update_option( 'pasat_settings', $settings );
$erase = Eraser::erase( 'member-runner@example.test' );
if ( empty( $erase['items_retained'] ) ) {
	fwrite( STDERR, 'Expected anonymize erasure mode to retain anonymized records.' . "\n" );
	exit( 1 );
}

$participant = $participants->get( (int) $participant['id'] );
$log         = $logs->get( $log_id );
if ( 'none' !== ( $participant['membership_status'] ?? '' ) || '' !== (string) ( $log['private_notes'] ?? '' ) ) {
	fwrite( STDERR, 'Privacy anonymization did not clear membership/log sensitive data.' . "\n" );
	exit( 1 );
}

do_action( 'rest_api_init' );
$request = new WP_REST_Request( 'GET', '/pasat/v1/activities' );
$public_response = rest_do_request( $request );
$public_json = wp_json_encode( rest_get_server()->response_to_data( $public_response, false ) );
if ( false !== strpos( $public_json, 'membership_status' ) || false !== strpos( $public_json, '3rd Place' ) ) {
	fwrite( STDERR, "Public activity REST leaked membership or badge data.\n" );
	exit( 1 );
}

echo "membership-badges:ok\n";
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
			\$WP core install --url=http://pasat-membership-smoke.test --title='PASAT Membership Smoke' --admin_user=admin --admin_password='pasat-smoke-pass' --admin_email=admin@example.test --skip-email --allow-root
			\$WP plugin install '${archive}' --activate --allow-root
			test \"\$(\$WP option get pasat_db_version --allow-root)\" = '${version}'
			\$WP eval-file pasat-membership-badges-smoke.php --allow-root
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

echo "PASAT Membership/Badges smoke test passed for pasat-${VERSION}.zip"
