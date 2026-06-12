=== Physical Activity Signup and Administration Tool ===
Contributors: project-contributors
Tags: activities, signup, events, waitlist, administration
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.13
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin for managing public signups and administration for physical activities, sessions, classes, and events.

== Description ==

Physical Activity Signup and Administration Tool, short name PASAT, lets a WordPress site owner create activities, manage venues, collect public signups, enforce capacity limits, handle waitlists, send e-mail notifications, and administer participant records from wp-admin.

PASAT is WordPress-native. It uses WordPress users, roles, capabilities, nonces, REST routes, WP-Cron, custom database tables, and wp_mail().

== Server Requirements ==

PASAT is not demanding for normal activity programs. It runs as a native WordPress plugin and does not require Python, Docker, PostgreSQL, Node.js, Composer, queues, realtime sockets, or a separate app server.

Minimum practical hosting: WordPress 6.0+, PHP 8.1+, MySQL/MariaDB, HTTPS, working REST API, working wp_mail() or an SMTP plugin, and WP-Cron or a real cron job. Bulk activity poster ZIP downloads require PHP ZipArchive; single poster PDF downloads do not. Outbound HTTPS is needed if administrators enable address geocoding or use the default external Leaflet/OpenStreetMap-compatible map assets.

Recommended production hosting: PHP memory limit of at least 128 MB, regular database backups, reliable SMTP/transactional mail, and a real cron job for retention cleanup on low-traffic sites. For high-volume signup openings, use solid WordPress hosting and monitor database/mail performance.

== Installation ==

1. Upload the `pasat` folder to `/wp-content/plugins/pasat/`.
2. Activate **Physical Activity Signup and Administration Tool** through the WordPress Plugins screen.
3. Create a WordPress page for public signups.
4. Add `[pasat_activity_list]` and `[pasat_activity_signup]` to that page.
5. Select that page under PASAT > Settings.
6. Configure organization name, poster logo, labels, consent text, membership/badge settings, retention, map settings, and e-mail templates.
7. Create at least one venue under PASAT > Venues. Add coordinates manually, or enable geocoding and run Geocode Address.
8. Create a published activity under PASAT > Activities.
9. Visit the public page and submit a test signup.

== Frequently Asked Questions ==

= Does PASAT include SMTP settings? =

No. PASAT sends through `wp_mail()`. Site owners can use any normal WordPress SMTP plugin.

= Does PASAT expose participant data publicly? =

No. Public endpoints return public activity data only. Participant data is restricted to users with PASAT capabilities, and the public "my signups" flow requires a private e-mail lookup link.

= Can hosts manage only their own activities? =

Yes. Users with the PASAT Activity Host role can manage assigned activities when assigned under PASAT > Hosts.

= Can I import legacy data? =

Yes. PASAT includes a Legacy Import form for structured JSON or CSV files covering venues, activities, participants, signups, and host assignments. Passwords and external authentication records should be mapped to WordPress users instead of imported directly.

= Does PASAT include venue maps? =

Yes. `[pasat_venue_map]` renders an embedded Leaflet map with OpenStreetMap-compatible tiles when venues have coordinates, plus accessible fallback cards. `[pasat_activity_signup show_map="1"]` can show the same map above the signup form. Address geocoding is optional, disabled by default, and only runs from administrator actions.

= Does membership opt-in make someone an active member? =

No. The public checkbox records membership interest. Administrators can manually move participants through statuses such as interested, pending, active, declined, or expired after any required review, payment, or onboarding.

== Screenshots ==

1. PASAT admin dashboard.
2. Public activity list and signup form.

== Changelog ==

= 0.1.13 =

Improves public keyboard accessibility with live filter result counts, active venue-map button states, map status announcements, and focus preservation when the polling activity board refreshes.

= 0.1.12 =

Adds public UI contrast audit automation and wires it into release preflight when PHP is available.

= 0.1.11 =

Adds contextual accessible labels for repeated signup, activity board QR/signup, and venue map action controls, plus smoke coverage for the new public action labels.

= 0.1.10 =

Adds a first public accessibility hardening pass with signup validation announcements, invalid-field styling, explicit checkbox labels, age guidance associations, focused AJAX notices, My Signups notice roles, and smoke coverage for key accessibility markup.

= 0.1.9 =

Improves the activity board with list/grid/kiosk modes, a stable refresh-status toolbar, larger kiosk readability, better QR composition, and smoke coverage for refreshed board markup.

= 0.1.8 =

Redesigns the verified My Signups view with a participant profile header, membership card, visual badge gallery, responsive signup/history cards, private lookup messaging, and accessible lookup notices.

= 0.1.7 =

Improves printable activity poster PDFs with a safer fixed-zone layout, bounded title/link wrapping, stronger QR hierarchy, and a cleaner short-link section.

= 0.1.6 =

Redesigns public venue cards, hides raw coordinates by default, adds next-activity context, and connects venue cards with map markers.

= 0.1.5 =

Redesigns the public signup form with a selected activity summary, grouped form sections, dynamic age and warning context, accessible AJAX notices, and a safer submitting state.

= 0.1.4 =

Improves public activity list scanability with date blocks, activity type chips, capacity/count details, selected activity highlighting, and client-side search/type/venue filters.

= 0.1.3 =

Adds a public UI foundation with scoped PASAT design tokens, public template wrappers, improved activity/form/venue surfaces, styled form controls, status pills, notices, focus states, and responsive spacing.

= 0.1.2 =

Adds optional membership opt-in, participant membership management, participation/result logs, yearly participation badges, 1st/2nd/3rd placement badges, verified participant badge/history display, admin REST endpoints, privacy coverage, and a Docker membership/badges smoke test.

= 0.1.1 =

Adds the open-source venue map improvement: embedded Leaflet venue maps, fallback venue cards, `[pasat_activity_signup show_map="1"]`, public venue REST data, map settings, venue geocoding status fields, administrator geocoding actions, and a Docker venue-map smoke test.

= 0.1.0 =

Initial WordPress-native MVP with custom tables, admin pages, shortcodes, public signup, waitlist handling, cancellation links, REST endpoints, e-mail notifications, an enhanced polling activity board, a coordinate-based venue listing, privacy hooks, and Privacy Policy Guide content.

Includes verified e-mail lookup for participant signups, activity cancellation notices, host assignment management, participant related-signup views, filtered/scoped CSV exports, participant deletion, REST argument validation, activity board kiosk/QR/filter options, printable activity poster PDFs with unique QR signup codes, and activity-level signup locking for capacity checks.

Validated with disposable WordPress activation, signup/waitlist/cancellation promotion, privacy export, real-user role/capability checks, shortcode rendering, HTTP rendering, printable poster generation, mail-test generation, package integrity, and concurrent capacity tests.
