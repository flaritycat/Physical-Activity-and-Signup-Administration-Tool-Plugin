# Security Policy

## Supported Versions

PASAT is currently in the `0.1.x` pre-release line. Security fixes should target the latest committed `main` branch until the first stable release process is established.

## Reporting A Vulnerability

Do not open a public issue containing participant data, cancellation tokens, database dumps, server logs with personal data, or exploit details.

Use one of these private reporting paths:

- Contact the repository owner/maintainer directly through GitHub.
- Use GitHub private vulnerability reporting if it is enabled for the repository.
- If the plugin is deployed for an organization, contact that organization's designated site administrator or data protection contact.

Include:

- PASAT version and source commit when known.
- WordPress version and PHP version.
- A short description of the issue and affected workflow.
- Clear reproduction steps using test data only.
- Whether participant data, e-mail delivery, cancellation links, or host/admin access may be affected.

## Security Scope

Security-sensitive areas include:

- public signup and cancellation flows
- cancellation token generation and verification
- participant data export, erasure, anonymization, and retention
- host/admin capability checks
- CSV exports
- REST API permission callbacks
- mail templates that include private links
- importer handling of structured legacy data

PASAT relies on WordPress for authentication, sessions, password storage, roles, cookies, nonces, and mail transport. Security issues in WordPress core, themes, SMTP plugins, hosting, or mail providers should also be reported to those projects or vendors.

## Operational Expectations

Production site owners should:

- keep WordPress, PHP, themes, and plugins updated
- use HTTPS
- use reliable database backups
- configure a trusted SMTP or transactional mail provider
- limit PASAT capabilities to users who need them
- review retention, consent, and privacy settings before launch
- exclude nonce-bearing signup pages from full-page cache when needed
- avoid importing legacy passwords or standalone authentication records

See `docs/PRODUCTION_READINESS.md` for the full launch checklist.
