#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_DIR="${ROOT_DIR}/pasat"
DIST_DIR="${ROOT_DIR}/dist"

if [[ ! -f "${PLUGIN_DIR}/pasat.php" ]]; then
	echo "PASAT plugin bootstrap not found at ${PLUGIN_DIR}/pasat.php" >&2
	exit 1
fi

if ! command -v zip >/dev/null 2>&1; then
	echo "The 'zip' command is required to build a release archive." >&2
	exit 1
fi

VERSION="$(
	sed -nE 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*(.+)$/\1/p' "${PLUGIN_DIR}/pasat.php" \
		| head -n 1 \
		| tr -d '\r'
)"

if [[ -z "${VERSION}" ]]; then
	echo "Could not determine PASAT version from pasat/pasat.php." >&2
	exit 1
fi

mkdir -p "${DIST_DIR}"

ARCHIVE_BASENAME="pasat-${VERSION}.zip"
CHECKSUM_BASENAME="${ARCHIVE_BASENAME}.sha256"
ARCHIVE_PATH="${DIST_DIR}/${ARCHIVE_BASENAME}"
CHECKSUM_PATH="${DIST_DIR}/${CHECKSUM_BASENAME}"

rm -f "${ARCHIVE_PATH}" "${CHECKSUM_PATH}"

(
	cd "${ROOT_DIR}"
	zip -rq "${ARCHIVE_PATH}" pasat \
		-x 'pasat/.DS_Store' \
		-x 'pasat/**/.DS_Store' \
		-x 'pasat/__MACOSX/**' \
		-x 'pasat/**/__MACOSX/**'
)

if command -v sha256sum >/dev/null 2>&1; then
	(
		cd "${DIST_DIR}"
		sha256sum "${ARCHIVE_BASENAME}" > "${CHECKSUM_BASENAME}"
	)
elif command -v shasum >/dev/null 2>&1; then
	(
		cd "${DIST_DIR}"
		shasum -a 256 "${ARCHIVE_BASENAME}" > "${CHECKSUM_BASENAME}"
	)
else
	echo "Install sha256sum or shasum to generate a release checksum." >&2
	exit 1
fi

if command -v unzip >/dev/null 2>&1; then
	unzip -tq "${ARCHIVE_PATH}" >/dev/null
fi

echo "Built ${ARCHIVE_PATH}"
echo "Wrote ${CHECKSUM_PATH}"
cat "${CHECKSUM_PATH}"
