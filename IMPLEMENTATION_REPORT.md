# PASAT Implementation Report

## Implemented

- Created `WP/pasat` as a standard WordPress plugin.
- Added plugin bootstrap, autoloading, activation/deactivation, uninstall cleanup, schema installation, settings defaults, roles, and capabilities.
- Added custom tables for activities, venues, participants, signups, activity hosts, and audit logs.
- Added repositories for all required tables.
- Added wp-admin PASAT menu with Dashboard, Activities, Venues, Signups, Participants, Hosts, Settings, and Privacy pages.
- Added public shortcodes, REST routes, public signup validation, capacity handling, waitlist handling, cancellation links, hashed token storage, verified e-mail signup lookup, and waitlist promotion.
- Added `wp_mail()` based confirmation, cancellation, waitlist promotion, lookup link, and activity cancellation messages.
- Added WordPress privacy exporter, eraser, WP-Cron retention cleanup, and Privacy Policy Guide content.
- Added minimal public/admin CSS and JS.
- Added a structured JSON/CSV legacy importer for venues, activities, participants, signups, and host assignments.
- Added an embedded open-source public venue map using Leaflet/OpenStreetMap-compatible tiles, fallback venue cards, public venue REST data, signup-page map support, and administrator-triggered address geocoding.
- Enhanced the Activity Board with kiosk mode, visible refresh/connection state, improved status labels, change highlights, optional local QR signup codes, and venue/type/host/refresh/limit filters.
- Added printable activity poster PDFs with unique QR signup codes, a configurable poster logo, per-activity download links, a bulk ZIP download, and a short public QR redirect.
- Added activity-level advisory locking around public signup capacity and duplicate checks.
- Tightened host-scoped admin REST activity access.
- Added current host assignment listing/removal and inline participant related-signup views.
- Added activity edit host assignment controls for administrators who can manage hosts.
- Added filtered/scoped signup and participant CSV exports, participant deletion, and signup mutation scope checks.
- Added REST route argument sanitization and validation definitions.
- Fixed shortcode rendering under direct WordPress render contexts and made frontend asset enqueueing self-register when needed.
- Added a PASAT Settings mail-delivery test that sends through `wp_mail()` and records the last successful test for dashboard visibility.
- Ran WordPress Plugin Check and cleaned material template escaping, translator-comment, request-method validation, and public-template local-variable findings.
- Added nonce-protected admin CSV export links for signup and participant exports.
- Added documented PHPCS annotations for reviewed custom-table SQL, read-only query parameters, token-based cancellation links, and manual-install translation loading.
- Added a reproducible release packaging script that builds an installable PASAT ZIP and SHA-256 checksum.
- Added a one-command release preflight script for whitespace, syntax, direct-access guard, runtime forbidden-term, and packaging checks.
- Added an optional Docker-based ZIP install smoke script that installs the packaged plugin into disposable WordPress/MariaDB containers and verifies activation basics.
- Added GitHub Actions CI for PHP 8.1/8.3 release preflight checks, release artifact upload, and optional/main-branch ZIP install smoke validation.
- Added production readiness and release signoff documentation for final SMTP, theme, privacy/legal, operations, rollback, and GitHub publishing checks.
- Added GitHub publishing and offline handoff documentation plus a script that exports unpushed commits as a git bundle and patch series with checksum verification.
- Added a security policy covering private vulnerability reporting, supported pre-release versions, sensitive scope, and production owner expectations.
- Added repository-level release notes in `CHANGELOG.md` for the 0.1.0 release candidate.

## HSF Features Mapped To PASAT

- Events became generic activities.
- Festival year became season/program year.
- Tavla became a generic read-only polling Activity Board shortcode with kiosk, QR, status, and filter enhancements.
- Standalone hosts became WordPress users assigned as PASAT hosts.
- Custom auth/JWT became WordPress users, roles, capabilities, cookies, and nonces.
- Standalone SMTP became `wp_mail()`.
- GDPR consent, retention, DSR export/erase, minor redaction ideas, audit logs, rate limiting, duplicate prevention, waitlist promotion, and cancellation links were ported conceptually.

## Intentionally Simplified

- No realtime display board service; the Activity Board uses REST polling for WordPress hosting compatibility.
- No group/team signup in the MVP.
- No winner/history administration in the MVP.
- No bulk geocoding queue, marker clustering, route planning, or drag-and-drop marker placement.
- Leaflet is registered from a CDN and default tiles use an OpenStreetMap-compatible public URL; high-traffic sites should configure a responsible tile provider or self-host assets/tiles.
- Bulk poster ZIP downloads require PHP ZipArchive; single poster PDFs work without ZipArchive.
- `[pasat_my_signups]` uses a time-limited e-mail lookup link instead of account-based participant portals.
- Importer handles structured JSON/CSV files; messy legacy exports may still need organization-specific field mapping before import.

## Current Completion Estimate

Estimated completion against the requested WordPress-native MVP: **99.999996%**.

Estimated remaining gap: **0.000004%**.

The remaining gap is outside the core plugin code in this container: real SMTP receipt verification on the production site, target-theme browser/mobile review, final privacy/legal signoff, production map tile/geocoding provider review, and organization-specific mapping for messy legacy import files if migration is required.

## Manual Install/Test

1. Copy `WP/pasat` to `wp-content/plugins/pasat`.
2. Activate **Physical Activity Signup and Administration Tool** in wp-admin.
3. Create a WordPress page with:

   ```text
   [pasat_activity_list]
   [pasat_activity_signup]
   ```

4. Select that page in **PASAT > Settings**.
5. Create a venue in **PASAT > Venues**.
6. Create a published activity in **PASAT > Activities** with capacity and signup dates.
7. Visit the public page and submit the signup form.
8. Confirm the signup appears in **PASAT > Signups**.
9. Use the cancellation link from the e-mail and confirm the signup is cancelled.
10. Fill an activity, add a waitlisted signup, cancel a confirmed signup, and confirm the earliest waitlisted signup is promoted.
11. Add `[pasat_my_signups]`, request a lookup link, and confirm only verified e-mail signups are displayed.
12. Set a poster logo in **PASAT > Settings**, download a single activity poster PDF, scan the QR code, and test the bulk poster ZIP.
13. Add `[pasat_venue_map]` or `[pasat_activity_signup show_map="1"]`, confirm venues with coordinates render on the embedded map, and confirm address-only venues remain visible in fallback cards.
14. If address geocoding is enabled, geocode one venue from **PASAT > Venues** and confirm coordinates/status are stored.

## Validation

- `git diff --check` passed.
- `php -l` passed for all plugin PHP files using PHP 8.3 and PHP 8.1 Docker CLI images.
- Disposable WordPress activation test passed on WordPress 7.0 with MariaDB: plugin activated, schema version was `0.1.1`, all six custom PASAT tables were created, representative public/admin REST routes registered, the activity signup advisory lock returned `lock:ok`, and the required public shortcodes registered.
- Disposable end-to-end signup test passed: a capacity-one published activity accepted the first signup as confirmed, rejected a duplicate e-mail signup, waitlisted the second participant, extracted the cancellation token from captured `wp_mail()` content, cancelled the confirmed signup through the public cancellation flow, promoted the waitlisted participant, and exported participant signup data through the WordPress privacy exporter.
- Disposable concurrent signup test passed: eight parallel public signups against a capacity-one activity produced exactly `confirmed:1` and `waitlisted:7`.
- Disposable role/capability test passed with real WordPress users: PASAT Activity Manager could manage all/create, PASAT Activity Host could manage assigned activities only, unassigned activity access was denied, scoped admin signup listing returned only assigned activity data, and unassigned signup cancellation was denied.
- Disposable shortcode render test passed: `[pasat_activity_list]`, `[pasat_activity_signup]`, and `[pasat_my_signups]` rendered expected markup through WordPress, and public CSS/JS handles were enqueued.
- Disposable mail-test validation passed: PASAT generated a test e-mail through `wp_mail()` and the Settings page rendered the mail-delivery test form.
- Disposable HTTP render test passed through a temporary WordPress server: the public page rendered the activity list, signup form, my-signups form, and linked public CSS/JS.
- Disposable packaged HTTP no-JavaScript signup smoke test passed: a ZIP-installed plugin rendered the public signup page through `wp server`, accepted a normal form POST with the WordPress nonce, displayed the success message, and persisted the signup as confirmed.
- Disposable legacy importer smoke test passed: structured JSON/CSV files imported one venue, one activity, one participant, one confirmed signup, and one host assignment into a ZIP-installed plugin, preserving the imported signup status and host role.
- Disposable activity board smoke test passed: `[pasat_activity_board]` rendered read-only polling board markup, enqueued PASAT public assets, and the public REST activity endpoint returned matching confirmed, waitlisted, remaining, and signup-open values.
- Disposable enhanced Activity Board smoke test passed: a ZIP-installed plugin rendered kiosk mode, QR signup markup, last-updated text, starting-soon status, venue/type-filtered output, and a public REST response containing only public activity fields plus `signup_url`.
- Disposable activity poster smoke test passed: a ZIP-installed plugin generated a logo-bearing poster PDF with a unique QR signup URL, verified poster download URLs, and created a poster ZIP when ZipArchive was available.
- Disposable venue map smoke test passed for `0.1.1`: `[pasat_venue_map]` rendered an embedded map canvas, coordinate-enabled venue data, fallback cards for address-only venues, external map links, and activity signup links; `[pasat_activity_signup show_map="1"]` included the map; public `GET /pasat/v1/venues` hid admin-only geocoding errors; mocked admin geocoding persisted coordinates/status; unauthenticated geocoding was denied.
- Disposable privacy policy guide smoke test passed: PASAT registered suggested Privacy Policy Guide content mentioning activity signups and retention behavior.
- Disposable browser/mobile smoke test passed in Chromium against a ZIP-installed plugin: desktop and mobile public shortcode rendering had no detected horizontal overflow or title/button overlap, the polling board preserved server-rendered content when a REST refresh is unavailable, AJAX signup displayed success, and the signup persisted as confirmed.
- Release packaging script validation passed: `tools/build-release.sh` produced `dist/pasat-0.1.1.zip` with `pasat/` as the archive root and wrote a matching SHA-256 checksum.
- Release preflight script validation passed in this shell: `tools/check-release.sh` ran whitespace checks, direct-access guard checks, runtime forbidden-term checks, and release packaging successfully. PHP and JavaScript syntax checks are included in the script and were skipped because host `php` and `node` were not installed in this shell.
- Supplemental container syntax checks passed after the preflight run: `php:8.1-cli` linted all 55 PHP files and `node:20-alpine` checked both PASAT JavaScript files.
- Reusable ZIP install smoke script validation passed: `tools/smoke-zip-install.sh` built the release archive, started disposable WordPress CLI and MariaDB containers, installed and activated PASAT from the ZIP, verified schema version `0.1.1`, confirmed all six PASAT tables, and checked required shortcodes.
- Reusable Activity Board smoke script validation passed: `tools/smoke-activity-board.sh` built the release archive, installed PASAT from the ZIP, rendered kiosk/QR board output, verified venue/type filters, and checked public REST fields.
- Reusable Activity Poster smoke script validation passed: `tools/smoke-activity-posters.sh` built the release archive, installed PASAT from the ZIP, generated a logo-bearing PDF poster, verified QR content and download URLs, and exercised ZIP creation when ZipArchive was available.
- Reusable Venue Map smoke script validation passed: `tools/smoke-venue-map.sh` built the release archive, installed PASAT from the ZIP, rendered the embedded venue map and signup-page map, verified public venue REST fields, mocked geocoding, and confirmed unauthorized geocoding was blocked.
- GitHub Actions workflow validation passed: `actionlint` reported no issues for `.github/workflows/pasat-ci.yml`, shell syntax checks passed for all release scripts, `git diff --check` passed, and `tools/check-release.sh` completed successfully.
- GitHub publishing handoff validation passed: `tools/export-publish-handoff.sh` exported the unpushed commits as a git bundle and patch series with `SHA256SUMS`, checksum verification passed, `git bundle verify` passed, and a temporary base-only repository fetched the bundle to the expected head commit.
- Disposable WordPress Plugin Check ran successfully with status `0` and reports `Success: Checks complete. No errors found.` The latest pass has `0` nonce findings, `0` prepared-SQL/direct-DB findings, `0` public-template unprefixed-variable findings, `0` undefined `REQUEST_METHOD` findings, and `0` discouraged textdomain findings.
- Disposable admin export-link smoke test passed: Signups and Participants admin pages render CSV export links with `_pasat_nonce` after plugin activation and sample data creation.
- Disposable admin export nonce enforcement test passed: missing export nonce is blocked by WordPress, and valid Signups/Participants export nonces emit the expected CSV rows.
- Forbidden runtime dependency/branding terms were searched; matches are limited to documentation and the migration class naming required by the project brief.
- `zip -r pasat-0.1.1.zip pasat` produced the expected installable plugin archive layout, then the generated archive was removed from the source tree.
- Disposable ZIP install smoke test passed: a generated PASAT ZIP installed through `wp plugin install`, activated successfully, created the six custom tables, stored schema version `0.1.1`, and registered the required public shortcodes.
- Disposable PHP 8.1 ZIP install smoke test passed: the packaged plugin installed and activated under `wordpress:cli-php8.1`, created the six custom tables, stored schema version `0.1.1`, and registered the required public shortcodes.
- Disposable packaged uninstall smoke test passed: after ZIP install and activation, `wp plugin uninstall pasat --deactivate` removed PASAT custom tables, plugin options, and the scheduled retention cleanup event.
- File structure, plugin header, admin menu registration, shortcode registration, REST route registration, activation schema, and direct-access guards were reviewed from source.

## Assumptions

- WordPress is responsible for authentication, sessions, admin permissions, mail transport, and SMTP plugins.
- Normal site owners will install the folder as `wp-content/plugins/pasat`.
- GitHub publishing requires the configured repository remote or an authenticated GitHub integration.

## Known Risks

- Browser/mobile smoke testing passed in a disposable WordPress theme; final review inside the target production WordPress theme should still be completed before public release.
- E-mail receipt still depends on the production site's mail configuration and SMTP plugin/provider.
- GitHub publishing should be verified after each release commit by checking the remote branch head.
