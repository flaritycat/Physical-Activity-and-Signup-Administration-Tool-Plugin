# Improvement 2: Membership, Participation Logs, Placements, And Badges

## Goal

Extend PASAT so public signups can optionally express interest in becoming a member, administrators and hosts can record detailed participation/results for activities, and participants can receive badges for each year they participated plus placement badges for 1st, 2nd, and 3rd place.

This should stay WordPress-native, privacy-conscious, and generic. Membership should not assume a specific club, festival, country, payment provider, or legal membership model.

## Current State

- Participants are stored separately from signups.
- Signups track confirmed, waitlisted, and cancelled status.
- Activities have season/program year support.
- Hosts can be scoped to assigned activities.
- Audit logging exists for important admin actions.
- Privacy exporter/eraser and retention cleanup exist.
- No membership intent/status fields exist yet.
- No dedicated participation/result log exists yet.
- No badge model exists yet.

## Product Concepts

### Membership

Public signup forms should include an optional membership choice when enabled:

```text
[ ] I would like to become a member
```

The choice should be stored as membership intent, not automatic active membership. Many organizations need manual approval, payment, guardianship checks, or separate onboarding before someone is a real member.

Suggested membership states:

- `none`
- `interested`
- `pending`
- `active`
- `declined`
- `expired`

Initial MVP behavior:

- public signup can mark a participant as `interested`
- admins can update membership status manually
- participant export/erasure includes membership fields
- membership status is not exposed publicly

### Participation Logs

PASAT should distinguish signup status from actual participation. A confirmed signup is not always an attended/completed activity.

Create a detailed per-participant activity log that can record:

- attended/check-in state
- completion state
- placement/result
- score/time/result text where relevant
- private admin notes
- who recorded the entry
- timestamps

Suggested attendance states:

- `unknown`
- `attended`
- `completed`
- `no_show`
- `excused`
- `disqualified`

### Placements

Some activities may be non-competitive, while others may have placements. Placement should therefore be optional per log entry.

Rules:

- placement is an integer, normally `1`, `2`, `3`, etc.
- 1st, 2nd, and 3rd place trigger placement badges
- placement can be recorded by admins and assigned hosts
- only confirmed/attended/completed participants should normally receive placements
- placement changes should recalculate badges idempotently
- ties should be supported later through a `placement_label` or `result_label`, but MVP can allow duplicate placement numbers if admins need ties

### Badges

Badges should reward participation history, not expose private participant data publicly.

Required badge types:

- yearly participation badge for every season/program year where the participant completed or attended at least one activity
- 1st place badge
- 2nd place badge
- 3rd place badge

Suggested badge examples:

- `2026 Participant`
- `2027 Participant`
- `1st Place`
- `2nd Place`
- `3rd Place`

Badges should be participant-linked and auditable. They can initially be shown only in wp-admin and in verified `[pasat_my_signups]` views. Public badge galleries can be deferred.

## Proposed Database Changes

Use dbDelta migrations and increment `pasat_db_version`.

### Update `pasat_participants`

Add:

- `membership_status VARCHAR(30) not null default 'none'`
- `membership_opted_in TINYINT(1) not null default 0`
- `membership_opted_in_at DATETIME nullable`
- `membership_status_updated_at DATETIME nullable`
- `membership_number VARCHAR(100) nullable`
- `membership_notes TEXT nullable`

Indexes:

- `membership_status`
- `membership_number`

### New `pasat_participation_logs`

Fields:

- `id BIGINT unsigned auto_increment primary key`
- `signup_id BIGINT unsigned nullable`
- `activity_id BIGINT unsigned not null`
- `participant_id BIGINT unsigned not null`
- `attendance_status VARCHAR(30) not null default 'unknown'`
- `checked_in_at DATETIME nullable`
- `completed_at DATETIME nullable`
- `placement INT unsigned nullable`
- `placement_label VARCHAR(100) nullable`
- `result_value VARCHAR(120) nullable`
- `result_unit VARCHAR(50) nullable`
- `result_notes TEXT nullable`
- `private_notes TEXT nullable`
- `recorded_by BIGINT unsigned nullable`
- `created_at DATETIME not null`
- `updated_at DATETIME not null`

Indexes:

- `activity_id`
- `participant_id`
- `signup_id`
- `attendance_status`
- `placement`

Uniqueness:

- one active/logical participation result per participant/activity should be enforced by repository logic
- database-level unique `(activity_id, participant_id)` is attractive, but repository-level enforcement is safer if future re-runs/heats are added

### New `pasat_participant_badges`

Fields:

- `id BIGINT unsigned auto_increment primary key`
- `participant_id BIGINT unsigned not null`
- `badge_type VARCHAR(50) not null`
- `badge_key VARCHAR(120) not null`
- `label VARCHAR(190) not null`
- `season_year SMALLINT unsigned nullable`
- `activity_id BIGINT unsigned nullable`
- `participation_log_id BIGINT unsigned nullable`
- `placement INT unsigned nullable`
- `metadata LONGTEXT nullable`
- `awarded_by BIGINT unsigned nullable`
- `awarded_at DATETIME not null`
- `revoked_at DATETIME nullable`
- `created_at DATETIME not null`
- `updated_at DATETIME not null`

Badge types:

- `year_participation`
- `placement`
- `manual` later

Indexes:

- `participant_id`
- `badge_type`
- `badge_key`
- `season_year`
- `activity_id`
- `placement`

Uniqueness:

- active year badge: participant + badge type + season year
- active placement badge: participant + badge type + activity id + placement

Because MySQL does not handle filtered unique indexes consistently across supported WordPress hosts, enforce uniqueness in repository code and use normal indexes for performance.

## Domain Rules

### Membership Opt-In

1. Setting `membership_enabled` controls whether the signup form shows membership opt-in.
2. Setting `membership_opt_in_text` controls the checkbox label.
3. If checked during signup:
   - set `participants.membership_opted_in = 1`
   - set `participants.membership_opted_in_at = now` if empty
   - set `participants.membership_status = interested` if current status is `none`
4. If a participant already has `active`, `pending`, or `expired`, do not downgrade them to `interested`.
5. Admin changes to membership status should write audit log entries.

### Participation Logs

1. A confirmed signup can be marked attended/completed/no-show/excused/disqualified.
2. Activity hosts can manage logs only for assigned activities unless they have global activity capabilities.
3. Logs can be created manually for participants if needed, but this should require admin/host capability and a clear audit log.
4. Updating a log should trigger badge recalculation for that participant/activity/year.

### Year Badges

Award a `year_participation` badge when:

- participant has at least one participation log in the year with `attendance_status` of `attended` or `completed`
- related activity is not archived/cancelled, unless admin explicitly allows historical results

Use activity `season_year` when available; otherwise derive the year from `starts_at`.

Recalculate behavior:

- if the participant no longer has any qualifying participation logs for that year, revoke the year badge
- if a qualifying log returns, restore or create the badge

### Placement Badges

Award a `placement` badge when:

- participation log has `placement` of `1`, `2`, or `3`
- attendance status is `attended` or `completed`
- activity is published/archived historical, but not cancelled unless explicitly allowed

Labels:

- `1st Place`
- `2nd Place`
- `3rd Place`

Recalculate behavior:

- changing placement from 1 to 2 revokes the 1st-place badge for that activity/log and awards 2nd place
- clearing placement revokes the placement badge
- changing attendance to no-show/disqualified revokes placement badge

## Admin UI

### Settings

Add a **Membership And Badges** section:

- enable membership opt-in
- membership opt-in label/text
- default membership status after opt-in, default `interested`
- enable badges
- year badge label template, for example `{year} Participant`
- placement badge labels for 1/2/3
- whether badges appear in verified participant self-service view
- whether hosts can record placements

### Participants

Enhance **PASAT > Participants**:

- show membership status in the table
- filter by membership status
- edit membership status, membership number, and notes
- show badge summary
- show participation history
- export membership and badge data in participant CSV/export

### Activities

Add a **Participation / Results** action for each activity:

- list confirmed signups
- mark attended/completed/no-show/excused/disqualified
- record placement
- record result value/unit
- save private notes
- bulk update attendance
- export results CSV
- recalculate badges for the activity

Assigned hosts should see only their assigned activities.

### Badges

MVP can show badges inside participant detail and verified my-signups views.

Optional later admin page:

- badge ledger
- filters by participant/year/type/activity
- manual award/revoke
- badge artwork controls

## Public UI

### Signup Form

If membership is enabled, show a membership opt-in checkbox:

```text
[ ] I would like to become a member of {organization_name}
```

Validation:

- optional by default
- if checked, store opt-in state and timestamp
- do not require public users to create WordPress accounts
- do not expose whether an e-mail is already a member

### My Signups

After verified e-mail lookup, optionally show:

- membership status, if enabled
- participation history
- earned badges

Important privacy rules:

- show only after verified e-mail lookup
- never expose participant badge data in unauthenticated public REST responses
- avoid public display of minors unless explicitly configured and legally reviewed

## REST API

Admin endpoints:

- `GET /pasat/v1/admin/participants/{id}/badges`
- `GET /pasat/v1/admin/participants/{id}/participation`
- `PUT /pasat/v1/admin/participants/{id}/membership`
- `GET /pasat/v1/admin/activities/{id}/participation`
- `POST /pasat/v1/admin/activities/{id}/participation`
- `PUT /pasat/v1/admin/participation/{id}`
- `POST /pasat/v1/admin/activities/{id}/badges/recalculate`

Public endpoints:

- no unauthenticated public badge participant endpoints in MVP
- verified lookup can remain server-rendered before adding REST

Permissions:

- admins with `pasat_view_participants` can view badges/history
- admins with `pasat_manage_signups` can edit participation logs
- assigned hosts can edit logs only for assigned activities if setting allows
- membership status edits require a new or existing higher capability, probably `pasat_view_participants` plus `pasat_manage_signups`, or a new `pasat_manage_memberships`

## Capabilities

Add optional new capabilities:

- `pasat_manage_memberships`
- `pasat_manage_participation_logs`
- `pasat_manage_badges`

Assign to administrators and PASAT Activity Managers.

PASAT Activity Hosts should get:

- `pasat_manage_participation_logs`

Host access must still be activity-scoped.

## Privacy And Compliance

Membership and badge data are personal data.

Required updates:

- privacy policy guide text
- personal data exporter includes membership fields, participation logs, and badges
- eraser/anonymizer handles membership notes, member number, participation logs, and badges
- retention cleanup can anonymize/delete old participation and badge data according to settings
- admin notices should explain that membership opt-in may not equal legally active membership
- audit membership status changes and badge awards/revocations

Sensitive fields:

- `membership_notes`
- `private_notes`
- result notes if they include health/injury context

Avoid public exposure by default.

## E-mail

MVP can skip automatic badge e-mails.

Optional e-mail templates later:

- membership interest received
- membership status changed
- badge awarded
- annual participation summary

If implemented, use `wp_mail()` and existing mail settings conventions.

## Import/Migration

Extend importer later for:

- membership status
- member number
- participation logs
- placements/results
- historical badges

Importer should prefer importing raw participation/result records, then recalculating badges, rather than trusting imported badge rows blindly.

## Suggested Implementation Order

1. Add settings defaults and Settings UI for membership and badges.
2. Add schema migration for participant membership fields.
3. Add `pasat_participation_logs` and `pasat_participant_badges` tables.
4. Add repositories:
   - `ParticipationLogsRepository`
   - `BadgesRepository`
   - optional `MembershipRepository` if participant repository becomes too large
5. Add domain service:
   - `Badges/Awarder.php`
   - recalculates year and placement badges idempotently
6. Extend public signup form and signup processing for membership opt-in.
7. Extend Participants admin page with membership fields, filters, badge summary, and participation history.
8. Add Activity Participation/Results admin screen.
9. Add host-scoped participation editing.
10. Add privacy exporter/eraser/retention coverage.
11. Add REST endpoints if needed by the admin UI.
12. Add CSV export for activity results.
13. Update docs, readme, implementation report, and language POT.
14. Add smoke tests for:
    - signup membership opt-in
    - admin membership update
    - participation log creation
    - year badge award/revoke
    - 1/2/3 placement badges
    - privacy export/erase coverage

## Acceptance Criteria

- Membership opt-in can be enabled/disabled in settings.
- Public signups can choose membership opt-in.
- Membership opt-in is stored with timestamp.
- Admins can manage membership status and membership number.
- Activity participation logs can be recorded per participant.
- Admins/assigned hosts can record attendance and placement.
- Year participation badges are awarded for each qualifying participation year.
- 1st, 2nd, and 3rd place badges are awarded from placements.
- Badge award/revoke logic is idempotent.
- Badge data is visible in admin participant views.
- Verified participants can optionally see their own badges.
- Public unauthenticated endpoints do not expose membership or badge data.
- Privacy export includes membership, participation logs, and badges.
- Privacy erasure/anonymization handles the new data.
- CSV exports guard against spreadsheet formula injection.
- All new admin forms use nonces and capability checks.
- All output is escaped and all inputs are sanitized.
- Documentation explains that membership opt-in is not necessarily active membership.

## Testing Plan

Run:

```text
tools/check-release.sh
```

Add or extend Docker smoke tests:

1. Activate plugin and confirm new tables/columns.
2. Enable membership opt-in.
3. Submit signup with membership opt-in checked.
4. Confirm participant has `membership_status = interested`.
5. Admin changes participant to `active`; audit log records it.
6. Create activity participation log marked `completed`; confirm year badge.
7. Set placement `1`; confirm 1st-place badge.
8. Change placement to `2`; confirm 1st badge revoked and 2nd badge awarded.
9. Clear placement; confirm placement badge revoked.
10. Run privacy export and confirm membership/log/badge data appears.
11. Run erasure/anonymization and confirm sensitive data is removed or anonymized.
12. Confirm assigned host cannot edit unrelated activity logs.

Manual review:

- admin participant list
- participant detail/history
- activity result entry workflow
- verified my-signups badge display
- mobile admin usability for check-in/result entry

## Deferred Ideas

- Public badge gallery or leaderboard.
- Printable participant certificates.
- Badge artwork upload and theming.
- Points/ranking system.
- Team/group placements.
- Multiple heats/rounds per activity.
- Automatic check-in QR codes per participant.
- Membership payments or external CRM integration.
- Annual membership renewal workflow.
- Badge notification e-mails.

## Open Questions

- Should membership mean legal organization membership, newsletter interest, or both?
- Should minors be allowed to opt into membership without guardian fields?
- Should placement badges be awarded for disqualified participants if an admin records historical results?
- Should hosts be allowed to assign placements by default, or only attendance?
- Should badges be visible in verified participant self-service by default?
- Should annual badges require `completed`, or is `attended` enough?
