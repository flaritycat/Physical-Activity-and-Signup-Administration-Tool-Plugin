=== Physical Activity Signup and Administration Tool ===
Contributors: project-contributors
Tags: activities, signup, events, waitlist, administration
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin for managing public signups and administration for physical activities, sessions, classes, and events.

== Description ==

Physical Activity Signup and Administration Tool, short name PASAT, lets a WordPress site owner create activities, manage venues, collect public signups, enforce capacity limits, handle waitlists, send e-mail notifications, and administer participant records from wp-admin.

PASAT is WordPress-native. It uses WordPress users, roles, capabilities, nonces, REST routes, WP-Cron, custom database tables, and wp_mail().

== Server Requirements ==

PASAT is not demanding for normal activity programs. It runs as a native WordPress plugin and does not require Python, Docker, PostgreSQL, Node.js, Composer, queues, realtime sockets, or a separate app server.

Minimum practical hosting: WordPress 6.0+, PHP 8.1+, MySQL/MariaDB, HTTPS, working REST API, working wp_mail() or an SMTP plugin, and WP-Cron or a real cron job.

Recommended production hosting: PHP memory limit of at least 128 MB, regular database backups, reliable SMTP/transactional mail, and a real cron job for retention cleanup on low-traffic sites. For high-volume signup openings, use solid WordPress hosting and monitor database/mail performance.

== Installation ==

1. Upload the `pasat` folder to `/wp-content/plugins/pasat/`.
2. Activate **Physical Activity Signup and Administration Tool** through the WordPress Plugins screen.
3. Create a WordPress page for public signups.
4. Add `[pasat_activity_list]` and `[pasat_activity_signup]` to that page.
5. Select that page under PASAT > Settings.
6. Configure organization name, labels, consent text, retention, and e-mail templates.
7. Create at least one venue under PASAT > Venues.
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

== Screenshots ==

1. PASAT admin dashboard.
2. Public activity list and signup form.

== Changelog ==

= 0.1.0 =

Initial WordPress-native MVP with custom tables, admin pages, shortcodes, public signup, waitlist handling, cancellation links, REST endpoints, e-mail notifications, a polling activity board, and privacy hooks.

Includes verified e-mail lookup for participant signups, activity cancellation notices, host assignment management, participant related-signup views, filtered/scoped CSV exports, participant deletion, REST argument validation, and activity-level signup locking for capacity checks.

Validated with disposable WordPress activation, signup/waitlist/cancellation promotion, privacy export, real-user role/capability checks, shortcode rendering, HTTP rendering, mail-test generation, package integrity, and concurrent capacity tests.
