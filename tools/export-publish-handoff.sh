#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE_REF="${1:-origin/main}"
HEAD_REF="${2:-HEAD}"
DIST_DIR="${ROOT_DIR}/dist"

require_command() {
	if ! command -v "$1" >/dev/null 2>&1; then
		echo "The '$1' command is required for publishing handoff export." >&2
		exit 1
	fi
}

require_command git

(
	cd "${ROOT_DIR}"

	if ! git rev-parse --verify --quiet "${BASE_REF}^{commit}" >/dev/null; then
		echo "Base ref '${BASE_REF}' was not found." >&2
		exit 1
	fi

	if ! git rev-parse --verify --quiet "${HEAD_REF}^{commit}" >/dev/null; then
		echo "Head ref '${HEAD_REF}' was not found." >&2
		exit 1
	fi

	COMMIT_COUNT="$(git rev-list --count "${BASE_REF}..${HEAD_REF}")"
	if [[ "${COMMIT_COUNT}" = "0" ]]; then
		echo "No commits to export from ${BASE_REF}..${HEAD_REF}." >&2
		exit 1
	fi

	HEAD_SHA="$(git rev-parse --short=12 "${HEAD_REF}")"
	BASE_SHA="$(git rev-parse --short=12 "${BASE_REF}")"
	HANDOFF_DIR="${DIST_DIR}/publish-handoff-${HEAD_SHA}"
	PATCH_DIR="${HANDOFF_DIR}/patches"
	BUNDLE_PATH="${HANDOFF_DIR}/pasat-${BASE_SHA}-to-${HEAD_SHA}.bundle"
	MANIFEST_PATH="${HANDOFF_DIR}/MANIFEST.md"
	CHECKSUM_PATH="${HANDOFF_DIR}/SHA256SUMS"

	rm -rf "${HANDOFF_DIR}"
	mkdir -p "${PATCH_DIR}"

	git bundle create "${BUNDLE_PATH}" "${HEAD_REF}" "^${BASE_REF}" >/dev/null
	git format-patch --quiet --output-directory "${PATCH_DIR}" "${BASE_REF}..${HEAD_REF}"

	{
		echo "# PASAT GitHub Publishing Handoff"
		echo
		echo "- Repository: flaritycat/Physical-Activity-and-Signup-Administration-Tool-Plugin"
		echo "- Base ref: \`${BASE_REF}\` (${BASE_SHA})"
		echo "- Head ref: \`${HEAD_REF}\` (${HEAD_SHA})"
		echo "- Commit count: ${COMMIT_COUNT}"
		echo "- Bundle: \`$(basename "${BUNDLE_PATH}")\`"
		echo "- Patch directory: \`patches/\`"
		echo "- Checksum file: \`SHA256SUMS\`"
		echo
		echo "## Commit List"
		echo
		git log --oneline --reverse "${BASE_REF}..${HEAD_REF}" | sed 's/^/- /'
		echo
		echo "## Apply With Git Bundle"
		echo
		echo "\`\`\`text"
		echo "git clone https://github.com/flaritycat/Physical-Activity-and-Signup-Administration-Tool-Plugin.git pasat-publish"
		echo "cd pasat-publish"
		echo "git fetch /path/to/$(basename "${BUNDLE_PATH}") HEAD:pasat-handoff-${HEAD_SHA}"
		echo "git checkout pasat-handoff-${HEAD_SHA}"
		echo "tools/check-release.sh"
		echo "git push origin pasat-handoff-${HEAD_SHA}:main"
		echo "\`\`\`"
		echo
		echo "## Apply As Patch Series"
		echo
		echo "\`\`\`text"
		echo "git clone https://github.com/flaritycat/Physical-Activity-and-Signup-Administration-Tool-Plugin.git pasat-publish"
		echo "cd pasat-publish"
		echo "git am /path/to/patches/*.patch"
		echo "tools/check-release.sh"
		echo "git push origin main"
		echo "\`\`\`"
		echo
		echo "## Verify Handoff Integrity"
		echo
		echo "\`\`\`text"
		echo "cd /path/to/publish-handoff-${HEAD_SHA}"
		echo "sha256sum -c SHA256SUMS || shasum -a 256 -c SHA256SUMS"
		echo "git bundle verify $(basename "${BUNDLE_PATH}")"
		echo "\`\`\`"
	} > "${MANIFEST_PATH}"

	(
		cd "${HANDOFF_DIR}"
		if command -v sha256sum >/dev/null 2>&1; then
			{
				sha256sum "$(basename "${BUNDLE_PATH}")" MANIFEST.md
				find patches -type f -name '*.patch' -print0 | sort -z | xargs -0 sha256sum
			} > "${CHECKSUM_PATH}"
		elif command -v shasum >/dev/null 2>&1; then
			{
				shasum -a 256 "$(basename "${BUNDLE_PATH}")" MANIFEST.md
				find patches -type f -name '*.patch' -print0 | sort -z | xargs -0 shasum -a 256
			} > "${CHECKSUM_PATH}"
		else
			echo "Install sha256sum or shasum to generate handoff checksums." >&2
			exit 1
		fi
	)

	echo "Created ${HANDOFF_DIR}"
	echo "Bundle: ${BUNDLE_PATH}"
	echo "Patches: ${PATCH_DIR}"
	echo "Manifest: ${MANIFEST_PATH}"
	echo "Checksums: ${CHECKSUM_PATH}"
)
