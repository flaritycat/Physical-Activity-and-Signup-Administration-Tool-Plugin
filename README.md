# Physical Activity Signup and Administration Tool

Physical Activity Signup and Administration Tool, short name PASAT, is a WordPress-native plugin for managing public signup and administration for physical activities, classes, sessions, workshops, and similar scheduled activities.

This repository was created by inspecting the existing standalone `HSF` application and porting the relevant domain workflows into a normal WordPress plugin. PASAT does not wrap or run the old Python/FastAPI application. It uses WordPress users, roles, capabilities, nonces, REST routes, WP-Cron, custom database tables, and `wp_mail()`.

## Installation

1. Copy `pasat/` into `wp-content/plugins/pasat`.
2. In wp-admin, activate **Physical Activity Signup and Administration Tool**.
3. Create a public WordPress page and add:

   ```text
   [pasat_activity_list]
   [pasat_activity_signup]
   ```

4. Go to **PASAT > Settings** and select that page as the public PASAT page.
5. Create venues and activities under the PASAT admin menu.

## Shortcodes

- `[pasat_activity_list]` displays published upcoming activities.
- `[pasat_activity_signup]` displays the signup form.
- `[pasat_activity_signup activity_id="123"]` locks the form to one activity.
- `[pasat_my_signups]` provides a privacy-preserving placeholder for a future verified lookup flow.
- `[pasat_venue_map]` outputs venue coordinate data for theme/script integration.
- `[pasat_activity_board]` displays a simple read-only activity board.

## Admin Menus

PASAT adds a wp-admin menu with:

- Dashboard
- Activities
- Venues
- Signups
- Participants
- Hosts
- Settings
- Privacy

Administrators receive all PASAT capabilities on activation. The plugin also creates `pasat_activity_manager` and `pasat_activity_host` roles.

## Core Features

- Activity CRUD with status, time window, capacity, waitlist, venue, age limits, and warning acknowledgement.
- Venue/location CRUD with address, type, capacity, and coordinates.
- Public signup form with server-side validation.
- Duplicate active signup prevention by normalized e-mail per activity.
- Capacity and waitlist handling.
- Secure cancellation links using random tokens with only hashes stored in the database.
- Waitlist promotion when a confirmed participant cancels.
- WordPress e-mail delivery through `wp_mail()`.
- Admin signup filters, cancellation, waitlist confirmation, and CSV export.
- Participant search, CSV export, and anonymization.
- Host assignment using WordPress users.
- REST namespace `pasat/v1`.

## Privacy Behavior

PASAT stores participant names, e-mail addresses, optional phone numbers, optional ages, consent state, signup state, and hashed request metadata. It does not store raw IP addresses or raw user-agent strings.

The plugin integrates with:

- `wp_privacy_personal_data_exporters`
- `wp_privacy_personal_data_erasers`
- WP-Cron event `pasat_daily_retention_cleanup`

Retention cleanup anonymizes or deletes participant data according to the configured mode once the retention period has elapsed and no active future signup still needs the participant data.

Public REST endpoints never expose private participant fields. Minor participant names are not intended for public display.

## Database Tables

PASAT creates custom tables using the WordPress table prefix:

- `pasat_activities`
- `pasat_venues`
- `pasat_participants`
- `pasat_signups`
- `pasat_activity_hosts`
- `pasat_audit_log`

The schema version is stored in `pasat_db_version`.

## Migration Notes

The old HSF app used a standalone stack with Python, FastAPI, SQLAlchemy, PostgreSQL, Docker, JWT auth, and custom SMTP configuration. PASAT intentionally replaces those runtime dependencies with WordPress-native equivalents.

The placeholder importer class `PASAT\Migration\HsfImporter` is ready to be extended for structured JSON or CSV exports for venues, activities/events, participants/people, signups, hosts, and optional winner/history data. Old passwords and external auth records should not be migrated directly. Hosts and admins should be mapped to WordPress users and PASAT capabilities.

## Known Limitations

- The first pass focuses on a stable MVP rather than every advanced standalone-app feature.
- The display board is a simple read-only listing, not a realtime service.
- Venue maps expose coordinate data but do not bundle a map provider.
- Group/team signup and winner history are deferred.
- `[pasat_my_signups]` avoids exposing private data until a verified e-mail lookup flow is implemented.
- PHP syntax checks could not be run in the current development container because `php` is not installed.

## Development Notes

- Target WordPress: 6.0+
- Target PHP: 8.1+
- Text domain: `pasat`
- Namespace: `PASAT`
- Main plugin file: `pasat/pasat.php`
- No Composer dependency is required.
