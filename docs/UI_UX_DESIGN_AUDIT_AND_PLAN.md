# PASAT UI/UX Design Audit and Modernization Plan

Date: 2026-06-12
Plugin version audited: 0.1.10
Scope: public shortcodes, activity board, venue map, signup flow, participant lookup, membership/badges, poster PDFs, and PASAT wp-admin screens.

## Executive Summary

PASAT is functionally strong for a WordPress-native activity signup plugin. The major workflows exist: activity listing, signup, capacity/waitlist, cancellation, venue maps, activity board, membership opt-in, badges, poster PDFs, privacy tooling, and admin management.

The UI/UX has moved beyond raw MVP in recent public-facing steps:

- `0.1.3` added scoped public design tokens, surfaces, status pills, better buttons/forms/notices, and responsive spacing.
- `0.1.4` redesigned activity cards with date blocks, chips, capacity summaries, selected states, and client-side filters.
- `0.1.5` redesigned the public signup form with guided sections, selected-activity context, and accessible AJAX notices.
- `0.1.6` redesigned venue map cards and marker/card interactions.
- `0.1.7` improved printable activity poster PDFs and QR hierarchy.
- `0.1.8` redesigned verified My Signups membership, badges, and participation history.
- `0.1.9` improved activity board list/grid/kiosk presentation and refresh status behavior.
- `0.1.10` started public accessibility hardening for signup validation and lookup notices.

However, the plugin is not yet consistently modern across all surfaces. The strongest current areas are the public activity list, signup form, venue cards, activity board, poster PDFs, and verified participant profile. The weakest remaining areas are admin information architecture and full cross-theme accessibility QA.

The modernization target should be a calm, trustworthy, operational product UI:

- fast to scan,
- easy to complete on mobile,
- clear about capacity and waitlist state,
- visually consistent inside arbitrary WordPress themes,
- accessible by keyboard and screen reader,
- print-ready for venue QR posters,
- admin-friendly for repeated operational use.

## Current UI/UX Maturity Scorecard

| Area | Current Maturity | What Works | Main Gap |
| --- | ---: | --- | --- |
| Public activity list | 7/10 | Modern cards, filters, status pills, date blocks | Needs richer sort/date filters and visual QA across themes |
| Public signup form | 7/10 | Guided sections, selected activity summary, dynamic warning/age context, accessible AJAX feedback | Needs visual QA, first-invalid-field focus, and broader theme testing |
| Venue map and venue cards | 6/10 | Compact public cards, hidden raw coordinates, next activity context, marker-card interaction | Needs visual QA, selected-venue polish, and cross-theme testing |
| My signups | 7/10 | Private profile, membership card, badge gallery, responsive history | Needs visual QA with real participant data and broader theme testing |
| Activity board | 7/10 | Polling, QR option, list/grid/kiosk modes, stable refresh status | Needs visual QA on real displays and offline-state testing |
| Poster PDFs | 6/10 | Fixed print zones, bounded wrapping, stronger QR hierarchy, short-link card | Needs visual QA, optional poster settings, and ZIP manifest polish |
| Admin dashboard | 5/10 | Metrics, upcoming activities, recent signups | Needs workflow-focused dashboard, quick actions, richer status cards |
| Admin CRUD/list screens | 4/10 | CRUD works and uses WP conventions | Dense tables, cramped inline actions, limited hierarchy |
| Accessibility | 6/10 | Labels, focused notices, invalid-field styling, key live regions | Needs full keyboard, contrast, screen reader, and theme pass |
| Theme compatibility | 6/10 | Scoped `.pasat-` CSS improved | Needs cross-theme visual testing and conflict hardening |

## What PASAT Already Does Well

### WordPress-Native UX

PASAT uses expected WordPress patterns:

- shortcodes for public display,
- wp-admin menu pages,
- WordPress roles/capabilities,
- nonce-protected admin forms,
- REST endpoints for public/AJAX behavior,
- WordPress settings for labels, organization name, map options, consent text, poster logo, and email templates.

This makes the plugin feel installable and maintainable for a normal WordPress site owner.

### Public Activity Discovery

Current files:

- `pasat/templates/public/activity-list.php`
- `pasat/assets/css/public.css`
- `pasat/assets/js/public.js`

Strengths after `0.1.4`:

- activity cards are more structured,
- date blocks improve scanability,
- activity type chips help categorize activities,
- venue/date/capacity information is visible,
- selected activity state exists via `pasat_activity_id`,
- search/type/venue filters help with longer programs,
- capacity/waitlist status is visually surfaced.

This is the closest surface to the target modern feel.

### Public Signup Logic

Current files:

- `pasat/templates/public/signup-form.php`
- `pasat/includes/REST/PublicSignupController.php`
- `pasat/assets/js/public.js`

Strengths:

- server-side validation is robust,
- consent and warning acknowledgement exist,
- membership opt-in exists,
- duplicate prevention, waitlist, capacity, and cancellation logic work,
- JavaScript submission exists but graceful form POST still works.

The main problem is presentation, not functionality.

### Activity Board

Current files:

- `pasat/templates/public/activity-list.php`
- `pasat/assets/js/public.js`
- `pasat/includes/Frontend/Shortcodes.php`

Strengths:

- board is polling-based, which is appropriate for normal WordPress hosting,
- status and count refreshes work,
- QR display option exists,
- kiosk mode exists,
- board smoke test passed for `0.1.4`.

The board now shares the newer activity card structure, but it still needs a purpose-built display-board visual language.

### Admin Capability and Workflow Foundation

Current files:

- `pasat/includes/Admin/AdminMenu.php`
- `pasat/includes/Admin/ActivitiesPage.php`
- `pasat/includes/Admin/SignupsPage.php`
- `pasat/includes/Admin/ParticipantsPage.php`
- `pasat/includes/Admin/VenuesPage.php`
- `pasat/includes/Admin/SettingsPage.php`
- `pasat/assets/css/admin.css`

Strengths:

- menu structure is complete,
- activity CRUD is functional,
- signups and participant management exist,
- hosts, privacy, settings, and poster downloads exist,
- admin screens use WordPress tables/forms, which site owners understand.

The admin experience needs refinement, not reinvention.

## Main Design Problems

### 1. Signup Form Does Not Yet Feel Guided

The signup form works, but it does not yet guide the user through a confident decision.

Problems:

- selected activity is not summarized strongly above the form,
- activity selection and participant data feel like one long flat form,
- optional fields are not visually explained,
- consent, membership, and warning checkboxes look similar despite very different meanings,
- success/error messages are functional but not emotionally clear,
- JavaScript submit lacks a strong loading/disabled state,
- server-side validation errors do not preserve all entered values.

Modern target:

- "You are signing up for..." summary card,
- grouped sections,
- clear required/optional labels,
- reassuring privacy copy,
- status badge for confirmed vs waitlisted,
- duplicate-submit protection.

### 2. Venue Map Is Useful but Too Heavy

Current files:

- `pasat/templates/public/venue-map.php`
- `pasat/assets/js/public.js`
- `pasat/assets/css/public.css`

Problems:

- map can dominate the first viewport,
- venue fallback cards still expose raw coordinates,
- cards list every activity in a basic list,
- marker/card interaction is missing,
- duplicate maps can appear when `[pasat_activity_signup show_map="1"]` and `[pasat_venue_map]` are both used,
- map action says "Open Map" but does not clearly communicate directions or venue context.

Modern target:

- compact embedded map in signup context,
- larger standalone venue map only when explicitly used,
- venue cards as compact location cards,
- hide coordinates by default,
- marker-card highlight/focus interaction,
- "View directions" and "Activities here" actions.

### 3. My Signups, Membership, and Badges Feel Like Plain Data

Current file:

- `pasat/templates/public/my-signups.php`

Problems:

- membership is a text line,
- badges are plain list items,
- participation history is a plain list,
- verified/private lookup messaging is not visually distinct,
- the experience does not feel rewarding.

Modern target:

- verified profile summary card,
- badge chips/cards with year and placement styles,
- responsive participation history cards,
- privacy-conscious lookup messaging,
- no public data exposure before verification.

### 4. Poster PDFs Are Not Print-Ready

Current files:

- `pasat/includes/Poster/ActivityPosterPdf.php`
- `pasat/includes/Admin/PosterDownloads.php`

Problems observed from generated PDF screenshot:

- long titles overflow into borders,
- metadata block is cramped,
- QR code is large but visually detached,
- direct signup URL wraps poorly,
- logo/header/footer lack polish,
- fixed layout does not handle long venue names, long addresses, or custom logos gracefully.

Modern target:

- print-safe grid,
- dynamic title fitting,
- resilient URL wrapping,
- QR quiet-zone protection,
- cleaner header/logo area,
- strong "Scan to sign up" instruction,
- ZIP manifest and safer per-file handling.

### 5. Admin Screens Are Functional but Dense

Current admin CSS is minimal. Tables and forms work, but admin UX is still closer to a technical MVP than a polished operational tool.

Problems:

- dashboard metrics are simple boxes with limited workflow guidance,
- activity form is a long flat `form-table`,
- activity rows have many inline actions,
- participant table has many columns and dense nested details,
- membership editing is hidden inside row-level details,
- signups table lacks status chips and activity context,
- empty states are basic table rows,
- CSV/export/poster actions are present but not visually prioritized.

Modern target:

- dashboard cards with quick actions,
- segmented activity form sections,
- badges/status pills in admin tables,
- row action groups,
- clearer destructive action zones,
- "next best action" empty states,
- admin responsive behavior for smaller screens.

### 6. Accessibility Is Partially Addressed but Not Audited

Existing strengths:

- many form controls have labels,
- focus states were added in public CSS,
- reduced motion is partially respected,
- public/private data exposure is thoughtfully constrained.

Remaining concerns:

- AJAX notice containers need `aria-live`,
- board refresh status should be announced appropriately,
- filter controls need clear reset behavior and no-results announcements,
- color contrast should be checked across all status pills and notices,
- map marker/card interaction must be keyboard accessible,
- table-heavy admin screens need better screen-reader context.

### 7. Visual QA Is Blocked Until Browser Dependencies Are Installed

Playwright is available, but Chromium cannot launch on the dev host because `libatk-1.0.so.0` is missing. This blocks screenshot-based visual regression checks.

This should be fixed before declaring UI/UX work "done".

## Modern Design Principles for PASAT

PASAT should not feel like a marketing site. It should feel like a dependable operational tool for signups and activity administration.

Principles:

1. **Activity-first:** users should quickly answer "What can I join, when, where, and is there space?"
2. **Trust before conversion:** signup forms should explain what data is collected and why.
3. **Status always visible:** open, full, waitlist, closed, cancelled, confirmed, and waitlisted states should be visually distinct.
4. **Mobile as primary:** public signup at a venue will often happen on a phone from a QR code.
5. **No raw implementation details:** coordinates, hashes, internal IDs, and long technical URLs should not be prominent.
6. **Theme-friendly:** PASAT should look good inside WordPress themes without taking over global styles.
7. **Accessible by default:** keyboard, screen reader, contrast, and reduced-motion behavior are not optional.
8. **Print matters:** QR posters are a first-touch UI and should receive the same design attention as screens.
9. **Admin screens should reduce cognitive load:** repeated operational work should be fast and predictable.

## Detailed Improvement Roadmap

### Phase 3: Signup Form Redesign

Status after `0.1.5`: Mostly implemented. PASAT now has a selected activity summary, grouped form sections, optional/helper labels, dynamic warning and age context, accessible AJAX notices, disabled submit state, and non-JavaScript POST fallback preservation of the selected activity. Remaining items are visual QA, first-invalid-field focus, and cross-theme testing.

Priority: High
Primary files:

- `pasat/templates/public/signup-form.php`
- `pasat/assets/css/public.css`
- `pasat/assets/js/public.js`
- `pasat/includes/Frontend/Shortcodes.php`

Tasks:

- Add selected activity summary above the form:
  - title,
  - date/time,
  - venue,
  - capacity/status,
  - age limits,
  - warning requirement.
- Split form into sections:
  - activity,
  - participant,
  - contact,
  - consent and acknowledgements.
- Add helper text for optional fields such as nickname, phone, and age.
- Make consent, membership, and warning checkboxes visually distinct:
  - consent as privacy/legal block,
  - membership as optional benefit block,
  - warning as attention block.
- Add AJAX loading state:
  - disable submit,
  - show "Submitting...",
  - prevent duplicate submissions,
  - restore button on error.
- Improve success state:
  - show confirmed/waitlisted badge,
  - say cancellation link is in email,
  - avoid frightening users when email is unavailable in dev.
- Improve error state:
  - `aria-live`,
  - focus first invalid field when possible,
  - preserve field values after server validation failure.

Acceptance criteria:

- User can understand the selected activity before entering personal data.
- Form looks complete on mobile and desktop.
- Confirmed/waitlisted results are visually obvious.
- Duplicate submissions are prevented.
- Server and AJAX error messages are accessible.

### Phase 4: Venue Map and Venue Cards

Status after `0.1.6`: Mostly implemented. PASAT now hides raw coordinates by default, renders compact participant-friendly venue cards, shows venue chips and next activity context, adds directions actions, uses compact activity-scoped map styling, and connects cards with Leaflet markers. Remaining items are visual QA, optional shortcode attributes for alternate map modes, and deeper selected-venue polish.

Priority: High
Primary files:

- `pasat/templates/public/venue-map.php`
- `pasat/assets/css/public.css`
- `pasat/assets/js/public.js`
- `pasat/includes/Map/VenueMapData.php`

Tasks:

- Hide coordinates by default.
- Replace "Open Map" with clearer action language:
  - "View directions",
  - "Open in map",
  - "Activities at this venue".
- Redesign venue cards:
  - compact header,
  - address row,
  - venue type/capacity chips,
  - next activity preview,
  - activity count.
- Add marker/card interaction:
  - click card focuses marker,
  - click marker highlights card,
  - keyboard focus on card can trigger marker highlight.
- Add signup-context map behavior:
  - compact map above form,
  - selected activity focuses its venue,
  - avoid duplicate map rendering on pages with both signup and standalone map shortcodes.
- Add shortcode attributes:
  - `show_coordinates="0|1"`,
  - `compact="1|0"`,
  - `selected_activity_id`,
  - possibly `show_cards="compact|full|0"`.

Acceptance criteria:

- Map no longer dominates signup pages.
- Venue cards are concise and action-oriented.
- Coordinates are hidden unless explicitly enabled.
- Marker/card interaction works with mouse and keyboard.

### Phase 5: My Signups, Membership, and Badges UX

Status after `0.1.8`: Implemented for the public verified lookup experience. PASAT now renders a participant profile header, membership card, visual badge gallery, responsive signup/history cards, private lookup messaging, and `aria-live` notice feedback. Remaining items are visual QA with richer badge data and cross-theme/mobile checks.

Priority: Medium-High
Primary files:

- `pasat/templates/public/my-signups.php`
- `pasat/assets/css/public.css`

Tasks:

- Redesign lookup intro as a privacy-focused card.
- Add `aria-live` notice for lookup request feedback.
- After verification, show:
  - participant/profile summary,
  - membership status card,
  - badge gallery,
  - signup list,
  - participation history.
- Render badge types distinctly:
  - year participation badges,
  - 1st/2nd/3rd placement badges,
  - inactive/revoked badges hidden or separated.
- Improve mobile layout:
  - cards on mobile,
  - table on desktop if useful.
- Add empty states:
  - no badges yet,
  - no participation yet,
  - no active signups.

Acceptance criteria:

- Verified user area feels like a participant portal, not a database dump.
- Badges are visually rewarding.
- Lookup flow remains privacy-conscious.

### Phase 6: Activity Board Display UX

Status after `0.1.9`: Implemented for the public board experience. PASAT now supports explicit list/grid/kiosk modes, a stable board toolbar, `aria-live` refresh status, dedicated refresh item container, grouped QR actions, and stronger kiosk readability. Remaining items are real-device visual QA and longer offline/refresh testing.

Priority: Medium
Primary files:

- `pasat/templates/public/activity-list.php`
- `pasat/assets/js/public.js`
- `pasat/assets/css/public.css`

Tasks:

- Separate board visual mode more clearly from regular activity cards.
- Add large-room readability rules:
  - larger time,
  - bigger status labels,
  - fewer details,
  - high contrast.
- Add board layout options:
  - list,
  - grid,
  - kiosk.
- Make QR mode visually balanced:
  - QR to the side,
  - title/date/status readable at distance.
- Improve refresh feedback:
  - unobtrusive last-updated,
  - visible offline state,
  - `aria-live` polite announcements.

Acceptance criteria:

- Board works as a display board from several meters away.
- Polling changes do not cause jarring layout shifts.
- Offline/refresh state is clear.

### Phase 7: Poster PDF and QR Print UX

Status after `0.1.7`: Mostly implemented. PASAT now uses fixed poster zones, bounded title/link wrapping, safer logo placement, a stronger centered QR block, print-oriented helper copy, and a cleaner short-link card. Remaining items are visual QA on generated PDFs, optional poster settings, admin preview polish, and ZIP manifest handling.

Priority: High
Primary files:

- `pasat/includes/Poster/ActivityPosterPdf.php`
- `pasat/includes/Admin/PosterDownloads.php`
- `pasat/includes/Admin/SettingsPage.php`

Tasks:

- Redesign PDF layout around print-safe areas:
  - A4 portrait,
  - consistent margins,
  - optional Letter support if feasible.
- Add title fitting:
  - estimate text width,
  - reduce font size for long titles,
  - wrap to bounded title area,
  - never overlap card borders.
- Improve QR block:
  - clear quiet zone,
  - strong "Scan to sign up" call-to-action,
  - short helper copy.
- Improve direct link block:
  - wrap at path/query boundaries,
  - optionally show short QR redirect URL,
  - keep readable contrast.
- Improve logo handling:
  - preserve aspect ratio,
  - avoid overlarge logo,
  - better no-logo fallback.
- Add optional poster settings:
  - accent color,
  - headline text,
  - show/hide direct link,
  - paper size.
- Improve ZIP:
  - manifest file,
  - skip bad poster with manifest warning instead of failing all,
  - date/title filenames.
- Add admin preview:
  - open inline PDF in new tab,
  - keep download action.

Acceptance criteria:

- Long smoke-test titles fit.
- QR scans reliably.
- PDF looks good printed and viewed on screen.
- Single and ZIP downloads still pass smoke tests.

### Phase 8: Admin UI Cleanup

Priority: Medium-High
Primary files:

- `pasat/includes/Admin/AdminMenu.php`
- `pasat/includes/Admin/ActivitiesPage.php`
- `pasat/includes/Admin/SignupsPage.php`
- `pasat/includes/Admin/ParticipantsPage.php`
- `pasat/includes/Admin/VenuesPage.php`
- `pasat/includes/Admin/SettingsPage.php`
- `pasat/assets/css/admin.css`

Tasks:

- Add a shared admin header component:
  - page title,
  - subtitle,
  - primary actions,
  - secondary actions.
- Dashboard:
  - metric cards with icons/status,
  - quick actions,
  - public page status,
  - email test status,
  - upcoming high-risk items such as full activities.
- Activities:
  - filter/search above table,
  - status chips,
  - grouped row actions,
  - poster preview/download action group,
  - multi-section edit form.
- Signups:
  - status chips,
  - activity context panel,
  - grouped filters,
  - clearer waitlist promotion action.
- Participants:
  - reduce table density,
  - profile drawer/detail page,
  - badge chips,
  - safer anonymize/delete zone.
- Venues:
  - geocoding status chips,
  - map preview,
  - coordinates shown as admin metadata, not primary content.
- Settings:
  - tabs/sections,
  - better explanation of mail, privacy, membership, maps, posters,
  - validation hints.

Acceptance criteria:

- Admin users can perform common workflows faster.
- Tables are less visually dense.
- Destructive actions are separated and confirmed.
- Settings are easier to understand.

### Phase 9: Accessibility and Theme Compatibility Pass

Priority: High before release signoff
Primary files:

- public templates,
- admin templates/pages,
- `public.css`,
- `admin.css`,
- `public.js`.

Tasks:

- Add `aria-live` to dynamic notices and board status.
- Verify all form controls have labels.
- Verify focus order on:
  - activity filters,
  - signup form,
  - map cards,
  - lookup form.
- Check contrast for:
  - buttons,
  - status pills,
  - warning/error/success notices,
  - muted text.
- Ensure no required state relies on color alone.
- Respect reduced-motion everywhere.
- Test in at least:
  - Twenty Twenty-Four or newer block theme,
  - one classic theme,
  - a narrow mobile viewport.
- Fix Playwright host dependencies or use containerized screenshot testing.

Acceptance criteria:

- Keyboard-only signup works.
- Screen reader users receive meaningful status changes.
- Visual regressions are checked with screenshots.

### Phase 10: Documentation and Release Polish

Priority: Medium
Primary files:

- `README.md`,
- `pasat/readme.txt`,
- `IMPLEMENTATION_REPORT.md`,
- `docs/PRODUCTION_READINESS.md`,
- smoke scripts.

Tasks:

- Add recommended shortcode layouts:
  - simple page,
  - activity list + signup,
  - map page,
  - display board page.
- Add UI screenshots after Playwright/browser dependencies are fixed.
- Document poster logo recommendations.
- Document map provider/attribution expectations.
- Add "modern UI checklist" to production readiness.
- Update smoke scripts to check:
  - signup form selected summary,
  - map card behavior,
  - PDF title fitting,
  - badge rendering,
  - admin dashboard key elements.

Acceptance criteria:

- Site owner knows which shortcodes to use and where.
- Release notes explain UI improvements.
- Smoke tests cover modernized UI surfaces.

## Recommended Implementation Sequence

1. Signup form redesign.
2. Venue map and venue cards.
3. Poster PDF redesign.
4. My signups, membership, and badges.
5. Activity board display polish.
6. Admin dashboard and admin tables/forms.
7. Accessibility and theme compatibility pass.
8. Documentation, screenshots, and release package.

Rationale:

- Signup and posters are highest-impact because they are first-touch user experiences.
- Map cleanup fixes the current screenshot-level visual concerns.
- My signups/badges improves participant retention and perceived value.
- Admin cleanup matters, but it should follow public workflow polish unless admin usability blocks operations.

## Definition of Done for "Modern Feel"

PASAT should not be considered UI/UX complete until:

- public signup page looks intentionally designed without theme customization,
- activity list remains scannable with 20+ activities,
- signup form clearly summarizes the selected activity,
- venue map supports the flow without dominating it,
- QR poster PDF can be printed and placed at a venue without visual embarrassment,
- badges/membership feel like participant-facing features,
- admin dashboard supports common workflows with quick actions,
- status states are consistent across public/admin/PDF,
- mobile layout is checked at roughly 390px width,
- desktop layout is checked at 1280px+ width,
- keyboard-only flow is tested,
- screenshot capture works in CI or a repeatable container,
- smoke scripts cover the main UI outputs.

## Validation Gaps

Current known validation gap:

- Playwright screenshots cannot run on the dev host because Chromium is missing `libatk-1.0.so.0`.

Recommended fix:

- Install the missing browser dependencies on the dev host, or run screenshot tests in a container image that includes Playwright dependencies.

Until this is fixed, visual QA depends on manual browser review.

## Suggested Milestones

### Milestone A: Public Signup Confidence

Includes:

- signup form redesign,
- selected activity summary,
- improved AJAX states,
- accessible notices.

Outcome:

- The main public conversion flow feels modern and trustworthy.

### Milestone B: Location and Venue Clarity

Includes:

- map/card redesign,
- hidden coordinates,
- marker/card interaction,
- duplicate-map prevention.

Outcome:

- Users can understand where activities are without the map overwhelming signup.

### Milestone C: Print and Venue Onboarding

Includes:

- poster PDF redesign,
- QR/link layout,
- logo and print settings,
- ZIP manifest.

Outcome:

- Administrators can print professional venue signage.

### Milestone D: Participant Value

Includes:

- my signups redesign,
- membership profile card,
- badge gallery,
- participation history.

Outcome:

- Participants see a reason to return, not just a signup receipt.

### Milestone E: Admin Operations

Includes:

- dashboard,
- activity/signups/participants/venues screens,
- settings structure,
- admin empty states.

Outcome:

- PASAT feels like a complete operational tool in wp-admin.

## Risk Notes

- Avoid over-styling global WordPress elements; keep styles scoped to `.pasat-`.
- Keep no-JavaScript fallbacks for signup and public information.
- Do not make public endpoints expose participant data while improving participant-facing UI.
- Do not add a heavy front-end framework unless a specific problem requires it.
- Keep map provider attribution visible and configurable.
- Poster PDF improvements should avoid external PDF libraries unless absolutely necessary for hosting compatibility.

## Accessibility Status

Status after `0.1.10`: Started. PASAT now announces signup validation errors in the public notice region, focuses AJAX signup results, marks invalid signup fields visually, adds explicit confirmation checkbox labels, associates age guidance with the age field, and improves My Signups notice roles/labeling. Remaining work is a full keyboard, screen-reader, contrast, and cross-theme pass.

## Immediate Next Recommended Work

The next implementation should continue Accessibility and Theme Compatibility QA.

Reason:

- The main public experiences now have modern structure, but they still need a pass across real themes, screen sizes, keyboard navigation, and contrast.
- This work reduces risk before deeper admin redesign.
- It can catch layout regressions introduced by the recent activity list, signup, venue, poster, My Signups, and board improvements.

First concrete tasks:

1. Keyboard-test activity list filters, venue map cards, My Signups lookup, and activity board links.
2. Verify contrast for buttons, status pills, notices, muted text, invalid states, and board refresh states.
3. Add or verify accessible names for map controls and QR/link actions.
4. Check mobile and desktop layouts in a block theme and a classic theme.
5. Fix text overflow, focus, and spacing issues found during the pass.
