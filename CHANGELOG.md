# Changelog

All notable PASAT repository-level changes are documented here. The WordPress.org-style plugin changelog is also maintained in `pasat/readme.txt`.

## 0.1.10 - 2026-06-12

Public accessibility hardening, first pass.

### Added

- Client-side signup validation announcements in the PASAT notice region when required fields are missing.
- Visible invalid-field styling for signup inputs, selects, textareas, and checkbox rows.
- Smoke coverage for explicit signup checkbox labels, age guidance association, and live notice markup.

### Changed

- Signup AJAX notices now receive focus after success or failure so keyboard and screen-reader users are taken to the result.
- Confirmation checkboxes now use explicit `id`/`for` label associations.
- Signup age guidance now uses `aria-describedby`.
- My Signups lookup notices now include `role="status"` or `role="alert"` and `aria-atomic`.
- My Signups lookup e-mail field now has an explicit label association.
- Bumped plugin asset/runtime version to `0.1.10`.
- Kept database schema version at `0.1.2` because this release does not alter custom tables.

## 0.1.9 - 2026-06-12

Activity board display and kiosk UX polish.

### Added

- Activity board toolbar with stable refresh status, `role="status"`, and polite live-region updates.
- Dedicated board item container so AJAX refreshes update activity cards without replacing the board status/header region.
- Explicit `[pasat_activity_board mode="list|grid|kiosk"]` handling.
- Grid-mode smoke coverage for the activity board shortcode.

### Changed

- Reworked board card styling to separate display-board layouts from normal public activity cards.
- Improved kiosk readability with larger title/status/date treatment and reduced detail density.
- Improved QR display composition with a grouped QR/sign-up action area on first render and after polling refreshes.
- Bumped plugin asset/runtime version to `0.1.9`.
- Kept database schema version at `0.1.2` because this release does not alter custom tables.

## 0.1.8 - 2026-06-12

My Signups, membership, and badge UX redesign.

### Added

- Verified participant profile header for `[pasat_my_signups]` with private-view messaging and summary counts.
- Membership card with status, opt-in date, and membership number when membership features are enabled.
- Visual badge gallery for yearly participation and placement badges.
- Responsive mobile cards for signups and participation history.
- Privacy-conscious lookup card with `aria-live` feedback for private lookup notices.

### Changed

- Reworked the My Signups template from raw table/list output into a participant portal-style view.
- Updated the membership/badges smoke test so UI-only releases validate the declared PASAT DB schema version separately from the plugin package version.
- Bumped plugin asset/runtime version to `0.1.8`.
- Kept database schema version at `0.1.2` because this release does not alter custom tables.

## 0.1.7 - 2026-06-12

Printable poster PDF redesign.

### Added

- Fixed poster layout zones for the header, activity details, QR block, short link, and footer.
- Bounded text wrapping for long activity titles, venue names, addresses, organization names, and signup links.
- PDF drawing helpers for filled and stroked layout regions plus centered text.

### Changed

- Reworked activity posters to make the QR code more visually dominant and print-safe.
- Replaced the crowded dark direct-link block with a cleaner short-link card using the activity QR redirect URL.
- Capacity copy now reads as a capacity label rather than implying all spots remain available.
- Bumped plugin asset/runtime version to `0.1.7`.
- Kept database schema version at `0.1.2` because this release does not alter custom tables.

## 0.1.6 - 2026-06-12

Venue map and venue card UX redesign.

### Added

- Compact public venue cards with venue type/capacity/activity chips, address, description excerpt, next activity, and action buttons.
- Card-to-marker and marker-to-card highlighting for interactive Leaflet maps.
- Directions links inside map popups.
- Activity-scoped map styling for compact signup-page maps.

### Changed

- Raw latitude/longitude values are no longer shown in public venue cards by default.
- Venue cards now show participant-friendly map status and actions instead of implementation details.
- Bumped plugin asset/runtime version to `0.1.6`.
- Kept database schema version at `0.1.2` because this release does not alter custom tables.

## 0.1.5 - 2026-06-12

Public signup form redesign.

### Added

- Selected activity summary panel above the public signup form with status, date, venue, capacity, activity type, age limits, and short description.
- Grouped public signup sections for activity, participant, contact, and confirmations.
- Dynamic activity-specific warning acknowledgement and age-limit context when the selected activity changes.
- Accessible AJAX notice region with success/error roles and a disabled submitting state.

### Changed

- Public signup POST fallback now preserves the submitted activity after validation errors, including warning acknowledgement retries.
- Bumped plugin asset/runtime version to `0.1.5`.
- Kept database schema version at `0.1.2` because this release does not alter custom tables.

## 0.1.4 - 2026-06-12

Public activity list redesign.

### Added

- Activity list filter controls for search, activity type, and venue when enough activities are rendered.
- Activity card date blocks, activity type chips, selected activity highlighting, compact metadata rows, and signup/capacity count summaries.
- Matching upgraded card structure for auto-refreshed activity board cards.

### Changed

- Bumped plugin asset/runtime version to `0.1.4`.
- Normal public activity cards now respect activity signup windows when displaying status.
- Kept database schema version at `0.1.2` because this release does not alter custom tables.

## 0.1.3 - 2026-06-12

Public UI/UX foundation.

### Added

- Scoped `.pasat-public` wrappers for public activity list, signup, venue map, and my-signups templates.
- Public CSS design tokens for PASAT colors, surfaces, borders, spacing, shadows, status states, and focus states.
- Polished default public styling for activity cards, venue cards, signup forms, notices, buttons, status pills, tables, and map containers.
- Responsive public layout rules for mobile, tablet, and desktop widths.

### Changed

- Bumped plugin asset/runtime version to `0.1.3`.
- Kept database schema version at `0.1.2` because this release does not alter custom tables.

### Deferred

- Full activity-card redesign with filters, selected-activity summary, map/card interaction, my-signups badge redesign, and admin UI cleanup remain in `improvement3-uiux.md`.

## 0.1.2 - 2026-06-10

Membership, participation logs, placements, and badges.

### Added

- Optional membership opt-in on public signup forms, controlled by PASAT settings.
- Participant membership fields for status, opt-in timestamp, membership number, and private membership notes.
- Admin membership management on the Participants screen with audit logging.
- Participation/result logs per participant and activity, including attendance status, placement, result value/unit, notes, and recorder metadata.
- Activity **Results** workflow for administrators and assigned hosts with nonce-protected saves, badge recalculation, and CSV export.
- Participant badges for yearly participation and 1st/2nd/3rd placements, recalculated idempotently from participation logs.
- Verified `[pasat_my_signups]` badge, membership, and participation-history display.
- Admin REST endpoints for participant badges/history/membership and activity participation/badge recalculation.
- Privacy export, erasure, retention, and Privacy Policy Guide coverage for membership, participation, and badge data.
- Docker membership/badges smoke test covering opt-in, admin membership update, badge award/revoke, privacy export/erase, public REST non-exposure, and host scoping.

### Changed

- Bumped plugin and database schema version to `0.1.2`.
- PASAT Activity Managers and Activity Hosts receive the new scoped participation capabilities on upgrade.

### Deferred

- Membership payments, renewals, public badge galleries, leaderboards, badge artwork, participant certificates, team placements, heats/rounds, and badge notification e-mails.

## 0.1.1 - 2026-06-10

Open-source venue map improvement.

### Added

- Embedded Leaflet venue maps for `[pasat_venue_map]` with OpenStreetMap-compatible tile settings.
- `[pasat_activity_signup show_map="1"]` support for showing the venue map above the public signup form.
- Public `GET /pasat/v1/venues` endpoint with public venue/activity map data only.
- Venue geocoding schema fields for cached coordinates, status, provider, timestamp, and error details.
- Admin **Geocode Address** and **Open Map** venue row actions with capability checks and nonce protection.
- Map settings for enabling maps, showing maps on signup pages by default, tile URL, attribution, default height/zoom, geocoding endpoint, and throttle.
- `PASAT\Map\Geocoder` and `PASAT\Map\VenueMapData` services.
- Docker venue-map smoke test covering shortcode rendering, signup map rendering, REST output, mocked geocoding, and unauthorized geocoding rejection.

### Changed

- Bumped plugin and database schema version to `0.1.1`.
- Venue map fallback cards now include venues without coordinates, so address-only venues still appear for users without JavaScript or before geocoding.
- Public venue map data omits admin-only geocoding error details.

### Deferred

- Bulk geocoding queues, marker clustering, drag-and-drop marker placement, route planning, and bundled/self-hosted Leaflet assets.

## 0.1.0 - 2026-06-09

Initial WordPress-native release candidate for **Physical Activity Signup and Administration Tool**.

### Added

- Standard WordPress plugin bootstrap at `pasat/pasat.php`.
- Activation, deactivation, uninstall cleanup, schema installation, settings defaults, roles, and capabilities.
- Custom database tables for activities, venues, participants, signups, activity hosts, and audit logs.
- Admin pages for Dashboard, Activities, Venues, Signups, Participants, Hosts, Settings, Privacy, and Legacy Import.
- Activity and venue management with capacity, waitlist, signup window, status, visibility, age-limit, warning acknowledgement, and host assignment support.
- Public shortcodes for activity listing, signup, verified my-signups lookup, venue map/listing, and read-only activity board.
- Activity Board kiosk mode, visible refresh/connection state, improved status labels, change highlights, optional local QR signup codes, and venue/type/host/refresh/limit filters.
- Printable activity poster PDFs with unique QR signup codes, configurable poster logo, per-activity downloads, and bulk ZIP export for administrators and assigned hosts.
- Public signup validation, duplicate prevention, advisory locking around capacity checks, waitlist placement, secure cancellation links, and waitlist promotion.
- E-mail notifications through `wp_mail()` for signup confirmation, cancellation, waitlist promotion, lookup links, activity cancellation notices, and mail-delivery testing.
- REST API routes under `pasat/v1` with public activity endpoints and capability-protected admin endpoints.
- Privacy exporter, eraser, retention cleanup, Privacy Policy Guide text, consent capture, hashed request metadata, and retention settings.
- Structured JSON/CSV importer for venues, activities, participants, signups, and host assignments.
- Minimal theme-friendly public/admin CSS and JavaScript.
- Release packaging, preflight, ZIP-install smoke, GitHub Actions CI, production readiness, publishing handoff, and security policy documentation.

### Validated

- PHP syntax on PHP 8.1 and PHP 8.3.
- JavaScript syntax checks.
- WordPress activation, ZIP install, and uninstall flows.
- Public signup, duplicate rejection, waitlist placement, cancellation, and waitlist promotion.
- Concurrent signup behavior for capacity-one activities.
- Role and capability scoping for managers and assigned hosts.
- Public shortcode rendering, HTTP no-JavaScript signup, enhanced activity board, printable poster PDFs, and venue map.
- Mail-test generation through WordPress mail hooks.
- WordPress privacy export/erase integration and retention cleanup.
- WordPress Plugin Check with no reported errors.
- Release ZIP integrity, SHA-256 checksums, and offline GitHub handoff bundle verification.

### Deferred

- Realtime display board service; the board remains polling-based for normal WordPress hosting compatibility.
- Group/team signup.
- Winner/history administration.
- Bundled map provider or geocoding.
- Account-based participant portal.
- Organization-specific cleanup for messy legacy exports.

### Production Notes

- Production launch still requires real SMTP receipt testing, review in the target WordPress theme, final privacy/legal signoff, and authenticated GitHub publishing.
- PASAT does not require Python, Docker, PostgreSQL, Node.js, Composer, queues, realtime sockets, or a separate application server at runtime.
- Bulk activity poster ZIP downloads require PHP ZipArchive; single poster PDF downloads remain available without ZipArchive.
