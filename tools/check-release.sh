#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RUNTIME_TERM_PATTERN='Hardanger|HSF|Spillfestival|FastAPI|Uvicorn|SQLAlchemy|JWT|PostgreSQL|Docker'

log() {
	printf '\n==> %s\n' "$1"
}

skip() {
	printf 'SKIP: %s\n' "$1"
}

log "Checking repository whitespace"
if command -v git >/dev/null 2>&1; then
	(
		cd "${ROOT_DIR}"
		git diff --check
	)
else
	skip "git is not available"
fi

log "Checking PHP syntax"
if command -v php >/dev/null 2>&1; then
	while IFS= read -r -d '' file; do
		php -l "${file}" >/dev/null
	done < <(find "${ROOT_DIR}/pasat" -name '*.php' -type f -print0 | sort -z)
else
	skip "php is not available"
fi

log "Checking public UI contrast"
if command -v php >/dev/null 2>&1; then
	php "${ROOT_DIR}/tools/check-public-contrast.php" >/dev/null
else
	skip "php is not available"
fi

log "Checking public accessibility hooks"
if command -v php >/dev/null 2>&1; then
	php "${ROOT_DIR}/tools/check-public-accessibility.php" >/dev/null
else
	skip "php is not available"
fi

log "Checking JavaScript syntax"
if command -v node >/dev/null 2>&1; then
	while IFS= read -r -d '' file; do
		node --check "${file}" >/dev/null
	done < <(find "${ROOT_DIR}/pasat/assets/js" -name '*.js' -type f -print0 | sort -z)
else
	skip "node is not available"
fi

log "Checking direct-access guards"
missing_guards=()
while IFS= read -r -d '' file; do
	if ! grep -Eq "defined\( *['\"]ABSPATH['\"] *\)" "${file}"; then
		missing_guards+=( "${file#${ROOT_DIR}/}" )
	fi
done < <(find "${ROOT_DIR}/pasat" -name '*.php' -type f -print0 | sort -z)

if (( ${#missing_guards[@]} > 0 )); then
	printf 'The following PHP files are missing an ABSPATH direct-access guard:\n' >&2
	printf ' - %s\n' "${missing_guards[@]}" >&2
	exit 1
fi

log "Checking runtime code for forbidden legacy/dependency terms"
runtime_paths=(
	"${ROOT_DIR}/pasat/assets"
	"${ROOT_DIR}/pasat/includes"
	"${ROOT_DIR}/pasat/templates"
	"${ROOT_DIR}/pasat/pasat.php"
	"${ROOT_DIR}/pasat/uninstall.php"
)

if command -v rg >/dev/null 2>&1; then
	if rg -n "${RUNTIME_TERM_PATTERN}" "${runtime_paths[@]}"; then
		printf 'Forbidden legacy/dependency terms were found in runtime plugin code.\n' >&2
		exit 1
	fi
else
	if grep -R -n -E "${RUNTIME_TERM_PATTERN}" "${runtime_paths[@]}"; then
		printf 'Forbidden legacy/dependency terms were found in runtime plugin code.\n' >&2
		exit 1
	fi
fi

log "Building release archive"
"${ROOT_DIR}/tools/build-release.sh"

log "PASAT release preflight passed"
