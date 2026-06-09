=== Physical Activity Signup and Administration Tool ===
Contributors: project-contributors
Tags: activities, signup, events, waitlist, administration
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A WordPress plugin for managing public signups and administration for physical activities, sessions, classes, and events.

== Description ==

Physical Activity Signup and Administration Tool, short name PASAT, lets a WordPress site owner create activities, manage venues, collect public signups, enforce capacity limits, handle waitlists, send e-mail notifications, and administer participant records from wp-admin.

PASAT is WordPress-native. It uses WordPress users, roles, capabilities, nonces, REST routes, WP-Cron, custom database tables, and wp_mail().

== Installation ==

1. Upload the `pasat` folder to `/wp-content/plugins/`.
2. Activate the plugin through the WordPress Plugins screen.
3. Create a page containing `[pasat_activity_list]` and `[pasat_activity_signup]`.
4. Select that page under PASAT > Settings.
5. Create venues and activities under the PASAT admin menu.

== Frequently Asked Questions ==

= Does PASAT include SMTP settings? =

No. PASAT sends through `wp_mail()`. Site owners can use any normal WordPress SMTP plugin.

= Does PASAT expose participant data publicly? =

No. Public endpoints return public activity data only. Participant data is restricted to users with PASAT capabilities.

= Can hosts manage only their own activities? =

Yes. Users with the PASAT Activity Host role can manage assigned activities when assigned under PASAT > Hosts.

= Can I import legacy data? =

The MVP includes an importer placeholder for future JSON or CSV migration. Passwords and external authentication records should be mapped to WordPress users instead of imported directly.

== Screenshots ==

1. PASAT admin dashboard.
2. Public activity list and signup form.

== Changelog ==

= 0.1.0 =

Initial WordPress-native MVP with custom tables, admin pages, shortcodes, public signup, waitlist handling, cancellation links, REST endpoints, e-mail notifications, and privacy hooks.
