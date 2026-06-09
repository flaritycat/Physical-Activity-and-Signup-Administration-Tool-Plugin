#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB_IMAGE="${PASAT_SMOKE_DB_IMAGE:-mariadb:10.11}"
WP_CLI_IMAGE="${PASAT_SMOKE_WP_CLI_IMAGE:-wordpress:cli-php8.1}"
SUFFIX="${PASAT_SMOKE_ID:-$(date +%s)-$$}"
NETWORK_NAME="pasat-smoke-${SUFFIX}"
DB_CONTAINER="pasat-smoke-db-${SUFFIX}"
WORK_DIR="$(mktemp -d "${TMPDIR:-/tmp}/pasat-smoke.XXXXXX")"
chmod 0777 "${WORK_DIR}"

cleanup() {
	docker rm -f "${DB_CONTAINER}" >/dev/null 2>&1 || true
	docker network rm "${NETWORK_NAME}" >/dev/null 2>&1 || true
	rm -rf "${WORK_DIR}"
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
			\$WP core install --url=http://pasat-smoke.test --title='PASAT Smoke' --admin_user=admin --admin_password='pasat-smoke-pass' --admin_email=admin@example.test --skip-email --allow-root
			\$WP plugin install '${archive}' --activate --allow-root
			\$WP plugin is-active pasat --allow-root
			test \"\$(\$WP option get pasat_db_version --allow-root)\" = '${version}'
			test \"\$(\$WP db query 'SHOW TABLES;' --skip-column-names --allow-root | grep -c '^wp_pasat_')\" = '6'
			\$WP eval 'foreach ( array( \"pasat_activity_list\", \"pasat_activity_signup\", \"pasat_my_signups\" ) as \$shortcode ) { if ( ! shortcode_exists( \$shortcode ) ) { fwrite( STDERR, \"Missing shortcode: {\$shortcode}\n\" ); exit( 1 ); } } echo \"shortcodes:ok\n\";' --allow-root
		"
}

require_command docker
trap cleanup EXIT

VERSION="$(parse_version)"
if [[ -z "${VERSION}" ]]; then
	echo "Could not determine PASAT version from pasat/pasat.php." >&2
	exit 1
fi

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

echo "PASAT ZIP install smoke test passed for pasat-${VERSION}.zip"
