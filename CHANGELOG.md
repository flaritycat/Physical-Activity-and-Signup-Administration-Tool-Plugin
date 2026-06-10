# Changelog

All notable PASAT repository-level changes are documented here. The WordPress.org-style plugin changelog is also maintained in `pasat/readme.txt`.

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
