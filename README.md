# Physical Activity Signup and Administration Tool

**Physical Activity Signup and Administration Tool** is a WordPress-native plugin for managing public signup and administration for physical activities, classes, sessions, workshops, and events.

Short name: **PASAT**

Plugin slug: `pasat`

Text domain: `pasat`

Main plugin file: `pasat/pasat.php`

## What PASAT Does

PASAT lets a WordPress site owner:

- publish a public activity list
- collect participant signups
- enforce capacity limits
- place overflow signups on a waitlist
- send confirmation and cancellation e-mails through WordPress
- provide secure cancellation links
- promote waitlisted participants when a confirmed spot opens
- manage activities, venues, participants, signups, and hosts in wp-admin
- integrate with WordPress privacy export, erasure, and retention tools

The plugin was designed after reviewing the previous standalone `HSF` application, but PASAT is a native WordPress rewrite. It does not require Python, FastAPI, Uvicorn, Docker, PostgreSQL, SQLAlchemy, JWT authentication, or any external application server at runtime.

## Requirements

- WordPress 6.0 or newer
- PHP 8.1 or newer
- A working WordPress mail setup for signup e-mails
- Administrator access for activation and setup

PASAT does not bundle SMTP settings. Use an established WordPress SMTP plugin if your site needs authenticated SMTP delivery.

## Server And Hosting Prerequisites

PASAT is not a demanding plugin for normal community programs, classes, workshops, or small-to-medium activity schedules. It is a server-rendered WordPress plugin with small frontend assets, custom database tables, indexed lookup columns, and no long-running background worker.

### Minimum Practical Hosting

- Standard Linux WordPress hosting, shared hosting, VPS, or managed WordPress hosting
- WordPress-compatible MySQL or MariaDB database
- PHP 8.1 or newer with normal WordPress extensions enabled
- HTTPS enabled, especially because participant contact data is submitted publicly
- WordPress permalinks and REST API available
- `wp_mail()` working, or an SMTP plugin configured
- WP-Cron enabled, or a real server cron triggering `wp-cron.php`

### Recommended Production Setup

- PHP memory limit of at least 128 MB; 256 MB is more comfortable for admin exports and larger sites
- Database backups enabled before plugin activation and before large imports
- A transactional mail setup using a reputable SMTP or mail delivery plugin
- A real cron job for reliable retention cleanup on low-traffic sites
- Object/page caching is fine, but exclude pages containing signup forms from full-page cache when nonce or form behavior is affected
- Web application firewall or host-level rate limiting for public sites with high exposure

### Resource Expectations

PASAT should be light for ordinary use. The plugin does not run Python, Docker, realtime sockets, queues, or a separate application server. Most work happens during normal WordPress requests: listing activities, submitting signups, sending mail, and rendering admin pages.

Resource usage grows mainly with:

- number of activities and signups
- CSV export size
- public signup bursts when registration opens
- mail delivery latency from the WordPress mail transport
- retention cleanup volume

For high-volume signup openings, use reliable hosting, object caching where appropriate, a real cron job, and a transactional mail provider. The database schema includes useful indexes, but very large programs should still monitor database performance and retention settings.

### Not Required

PASAT does not require:

- Python, FastAPI, or Uvicorn
- Docker or Docker Compose
- PostgreSQL
- SQLAlchemy
- JWT authentication
- Node.js build tooling
- Composer for normal installation

## Installation

1. Copy the `pasat` folder into your WordPress plugin directory:

   ```text
   wp-content/plugins/pasat
   ```

2. In wp-admin, go to **Plugins**.
3. Activate **Physical Activity Signup and Administration Tool**.
4. On activation, PASAT creates its custom database tables, default settings, roles, capabilities, and retention cron event.

After installation, this file should exist:

```text
wp-content/plugins/pasat/pasat.php
```

## First-Time Setup

1. Go to **Pages > Add New**.
2. Create a public page for the signup tool, for example **Activities**.
3. Add these shortcodes to the page:

   ```text
   [pasat_activity_list]
   [pasat_activity_signup]
   ```

4. Publish the page.
5. Go to **PASAT > Settings**.
6. Select the page under **Public Page**.
7. Set the organization name, labels, default capacity, consent text, retention period, and e-mail templates.
8. Go to **PASAT > Venues** and create at least one venue/location.
9. Go to **PASAT > Activities** and create a published activity with signup dates and capacity.
10. Visit the public page and submit a test signup.
11. Confirm the signup appears under **PASAT > Signups**.

## Admin Workflow

### Venues

Use **PASAT > Venues** to create reusable locations with name, description, address, type, capacity, latitude, and longitude. Venues can be deleted only when they are not used by an activity.

### Activities

Use **PASAT > Activities** to create and manage public activities. Activities support title, description, type, season year, schedule, venue, capacity, waitlist, signup windows, status, visibility, age limits, warning acknowledgement text, and host assignment when the current administrator has host-management permissions.

Only published public activities with an open signup window appear in public signup flows.

### Signups

Use **PASAT > Signups** to filter signups, search by participant or activity, cancel signups manually, confirm waitlisted signups, and export filtered CSV data. CSV exports guard against spreadsheet formula injection and preserve host activity scope.

### Participants

Use **PASAT > Participants** to search participant records, view related signups, export filtered data when permitted, and anonymize or delete records according to policy.

### Hosts

Use **PASAT > Hosts** to assign and remove WordPress users as activity hosts, instructors, or organizers. Administrators can manage all PASAT data. Hosts are scoped to assigned activities unless they also have broader PASAT capabilities.

## Public Shortcodes

```text
[pasat_activity_list]
```

Displays public, published, upcoming activities with time, venue, capacity status, and a signup link.

```text
[pasat_activity_signup]
```

Displays a public signup form for available activities.

```text
[pasat_activity_signup activity_id="123"]
```

Locks the signup form to one activity.

```text
[pasat_my_signups]
```

Lets participants request a private, time-limited e-mail lookup link before viewing their own signups.

```text
[pasat_venue_map]
```

Displays venues/locations that have latitude and longitude, including address, capacity/type metadata, and an external map link. The markup also includes machine-readable venue data for theme or script integration.

```text
[pasat_activity_board]
```

Displays a simple read-only board of upcoming activities and refreshes public capacity status through PASAT's REST API when JavaScript is available.

## Public Signup Behavior

The public signup form collects first name, last name, optional nickname, e-mail, optional phone, optional age, consent, warning acknowledgement, and selected activity.

Server-side validation checks required fields, e-mail validity, activity status, signup window, age restrictions, consent, warning acknowledgement, duplicate active signups, capacity, and waitlist settings.

After a successful signup, PASAT creates or updates the participant by e-mail, creates a confirmed or waitlisted signup, generates a secure cancellation token, stores only the token hash, and sends a confirmation e-mail. The duplicate and capacity checks run under an activity-level database advisory lock to reduce oversubscription during signup bursts.

When a confirmed signup is cancelled, PASAT promotes the earliest waitlisted signup if capacity allows.

## E-mail

PASAT uses `wp_mail()`.

Configurable templates are available for signup confirmation, cancellation confirmation, waitlist promotion, and activity cancellation notices.

Supported placeholders:

- `{organization_name}`
- `{activity_title}`
- `{activity_date}`
- `{activity_time}`
- `{venue_name}`
- `{participant_name}`
- `{signup_status}`
- `{cancellation_url}`
- `{site_name}`
- `{site_url}`

If strict e-mail delivery is enabled and `wp_mail()` fails, PASAT rejects the signup.

## REST API

PASAT registers REST routes under `pasat/v1`.

Public endpoints:

- `GET /pasat/v1/activities`
- `GET /pasat/v1/activities/{id}`
- `POST /pasat/v1/signups`
- `POST /pasat/v1/signups/cancel`

Admin endpoints:

- `GET /pasat/v1/admin/activities`
- `POST /pasat/v1/admin/activities`
- `GET /pasat/v1/admin/activities/{id}`
- `PUT/PATCH /pasat/v1/admin/activities/{id}`
- `DELETE /pasat/v1/admin/activities/{id}`
- `GET /pasat/v1/admin/venues`
- `POST /pasat/v1/admin/venues`
- `GET /pasat/v1/admin/venues/{id}`
- `PUT/PATCH /pasat/v1/admin/venues/{id}`
- `DELETE /pasat/v1/admin/venues/{id}`
- `GET /pasat/v1/admin/signups`
- `PUT/PATCH /pasat/v1/admin/signups/{id}`
- `POST /pasat/v1/admin/signups/{id}/cancel`

Public endpoints expose activity data only. Participant data is restricted to users with PASAT capabilities.

REST routes declare basic argument validation and sanitization, and privileged endpoints enforce WordPress capabilities plus assigned-activity scope where relevant.

## Privacy And Data Protection

PASAT stores participant names, e-mail addresses, optional phone numbers, optional ages, consent state, signup state, and hashed request metadata. It does not store raw IP addresses or raw user-agent strings.

The plugin integrates with WordPress privacy exporters and erasers, and registers the WP-Cron event `pasat_daily_retention_cleanup`.

PASAT also adds suggested text to the WordPress Privacy Policy Guide so site owners can adapt public privacy disclosures for activity signups, e-mail notifications, retention, and erasure behavior.

Retention cleanup anonymizes or deletes participant data according to the configured retention period and erasure mode.

## Database Tables

PASAT creates custom tables using the active WordPress database prefix:

- `{prefix}pasat_activities`
- `{prefix}pasat_venues`
- `{prefix}pasat_participants`
- `{prefix}pasat_signups`
- `{prefix}pasat_activity_hosts`
- `{prefix}pasat_audit_log`

The schema version is stored in `pasat_db_version`.

## Security Notes

PASAT uses WordPress users, roles, capabilities, admin nonces, REST permission callbacks, server-side validation, escaped output, prepared SQL for user-controlled values, hashed cancellation tokens, hashed request metadata, and basic public rate limiting.

PASAT does not implement a parallel authentication system.

## My Signups Lookup

`[pasat_my_signups]` avoids exposing participant data directly. A participant enters an e-mail address, PASAT sends a private lookup link if mail delivery is available, and the link displays only signups for that verified e-mail address. The public message does not reveal whether the address exists in the database.

## Migration Notes

PASAT includes the importer class `PASAT\Migration\HsfImporter` and a **Legacy Import** form under **PASAT > Settings**. It can import structured JSON or CSV files for:

- venues
- activities
- participants
- signups
- host assignments

Do not migrate legacy passwords or external authentication records directly. Map hosts and administrators to WordPress users and PASAT capabilities.

CSV files must include a header row. JSON files may be an array of rows or an object containing the selected import type, such as `{ "venues": [...] }`. Signups can reference activities by `activity_id` or activity title, and participants by `participant_id` or e-mail address. Host imports map to existing WordPress users by `user_id`, e-mail, or login.

## Development

No Composer install is required.

Useful local checks:

```text
php -l pasat/pasat.php
git diff --check
find pasat -name "*.php" -print0 | xargs -0 -n1 php -l
```

## Manual Test Checklist

1. Activate the plugin.
2. Confirm PASAT tables are created.
3. Confirm the PASAT admin menu appears.
4. Create a venue.
5. Create a published activity with capacity `1` and waitlist enabled.
6. Add the public shortcodes to a page.
7. Submit the first signup and confirm it is `confirmed`.
8. Submit a second signup and confirm it is `waitlisted`.
9. Cancel the first signup using its cancellation link.
10. Confirm the waitlisted signup is promoted.
11. Export signups CSV from wp-admin.
12. Run a WordPress personal data export for a participant e-mail.
13. Run retention cleanup from **PASAT > Privacy**.
14. Test the public page in the production theme on desktop and mobile viewports.
15. Use **PASAT > Settings > Mail Delivery Test** to send a test e-mail through the production SMTP/mail setup.
16. Test confirmation, cancellation, waitlist promotion, and lookup e-mails through the production SMTP/mail setup.
17. Confirm GitHub publishing credentials are configured and push the local commits.

## Known Limitations

- The activity board is a simple read-only polling display, not a realtime service.
- The venue map shortcode displays coordinate-enabled venues and links to an external map, but does not bundle a full map provider.
- Group/team signup is not implemented in the MVP.
- Winner/history administration is deferred.
- The importer handles structured JSON/CSV rows but may still need organization-specific field mapping for messy legacy exports.
- Full production readiness still requires browser testing in the target WordPress theme, real mail receipt testing, authenticated GitHub publishing, and organization-specific privacy/legal review.

## License

The plugin header declares `GPL-2.0-or-later`.
