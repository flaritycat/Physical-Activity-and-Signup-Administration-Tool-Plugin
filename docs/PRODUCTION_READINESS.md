# PASAT Production Readiness Checklist

Use this checklist before putting **Physical Activity Signup and Administration Tool** into production. The plugin can be installed and used without Docker, Node.js, Composer, Python, or a separate application server; the Docker items below are only for release maintainers who want disposable smoke tests.

## Release Package

- Run `tools/check-release.sh`.
- Run `tools/smoke-zip-install.sh` when Docker is available.
- Confirm the ZIP root is `pasat/`, not a parent project folder.
- Confirm `dist/pasat-0.1.0.zip.sha256` matches the release ZIP.
- Confirm the plugin header in `pasat/pasat.php` shows the intended version.
- Confirm `pasat/readme.txt` stable tag matches the plugin version.

## GitHub Publishing

- Confirm `git status --short --branch` is clean before publishing.
- Confirm `gh auth status` is authenticated or SSH/HTTPS credentials are configured.
- Push the local branch to `flaritycat/Physical-Activity-and-Signup-Administration-Tool-Plugin`.
- If direct push is not available, run `tools/export-publish-handoff.sh` and publish from an authenticated machine using `docs/GITHUB_PUBLISHING.md`.
- Confirm GitHub Actions completes successfully after the push.
- Keep the release ZIP/checksum from CI artifacts or a local trusted build.

## Staging Install

- Install the ZIP through **Plugins > Add New > Upload Plugin** on a staging copy of the site.
- Activate **Physical Activity Signup and Administration Tool**.
- Confirm all PASAT admin pages load without PHP notices.
- Confirm PASAT tables are created with the site database prefix.
- Create one venue.
- Create one published activity with capacity `1` and waitlist enabled.
- Add `[pasat_activity_list]`, `[pasat_activity_signup]`, `[pasat_my_signups]`, `[pasat_venue_map]`, and `[pasat_activity_board]` to a staging page as needed.
- Submit one confirmed signup.
- Submit a second signup and confirm it is waitlisted.
- Cancel the confirmed signup and confirm waitlist promotion.

## Mail Delivery

- Configure the production SMTP or transactional mail plugin before public launch.
- Use **PASAT > Settings > Mail Delivery Test** and confirm the message is received.
- Submit a public signup and confirm the signup confirmation e-mail is received.
- Cancel a signup and confirm the cancellation e-mail is received.
- Promote a waitlisted signup and confirm the waitlist promotion e-mail is received.
- Request `[pasat_my_signups]` lookup and confirm the private lookup link is received.
- Review sender name, subject lines, organization name, and template placeholders.
- Decide whether strict e-mail delivery should be enabled for launch.
- Confirm SPF, DKIM, and DMARC are acceptable for the sending domain.

## Theme And Browser Review

- Test the public signup page in the active production theme.
- Test desktop, tablet, and mobile viewports.
- Confirm activity cards, form fields, notices, board rows, and venue cards do not overlap.
- Confirm signup buttons and cancellation notices remain readable.
- Confirm full-page caching does not cache nonce-bearing signup forms incorrectly.
- Confirm JavaScript signup works.
- Confirm the no-JavaScript fallback works or is acceptable for the audience.
- Confirm external map links open correctly when venue coordinates are configured.

## Privacy And Legal

- Review and adapt the PASAT Privacy Policy Guide text in WordPress.
- Set the organization name.
- Set consent text and consent version.
- Confirm whether age collection is needed.
- Confirm the retention period and erasure mode.
- Run a personal data export for a test participant.
- Run a personal data erasure/anonymization request for a test participant.
- Confirm retention cleanup behavior in **PASAT > Privacy**.
- Confirm internal policy for minors and avoid public participant disclosure.
- Confirm imported legacy data has a lawful basis before import.
- Do not import legacy passwords or standalone authentication records.

## Operations

- Confirm database backups are enabled before activation and before imports.
- Use a real server cron for `wp-cron.php` on low-traffic sites.
- Exclude signup pages from full-page caching if it interferes with nonces or form submission.
- Monitor mail queue/provider logs during public signup openings.
- Monitor database and PHP error logs during the first live registration period.
- Confirm administrator and host accounts have appropriate PASAT capabilities only.

## Rollback

- Keep a database backup from before activation or import.
- Keep a copy of the exact release ZIP and checksum.
- Know how to deactivate PASAT from wp-admin or WP-CLI.
- Document whether uninstall should remove PASAT data before running uninstall in production.

## Go/No-Go Signoff

Use `docs/RELEASE_SIGNOFF_TEMPLATE.md` to capture the final release decision.
