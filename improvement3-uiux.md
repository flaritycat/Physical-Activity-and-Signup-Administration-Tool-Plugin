# Improvement 3: Public UI/UX Upgrade Plan

## Goal

Make the PASAT public signup experience feel polished, trustworthy, and easy to use on a normal WordPress page. The current implementation is functional, but the smoke-test page shows that the default theme styling leaks through too much: the map dominates the page, venue cards feel oversized, raw coordinates add noise, and form controls look unfinished.

This plan focuses first on the public activity listing, venue map, signup form, and activity board. Admin UI cleanup is included as a secondary pass after the public flow is improved.

## Current Issues Observed

- The map is visually useful but too dominant at the top of the signup flow.
- Venue cards expose implementation detail, especially raw latitude/longitude, instead of user-facing guidance.
- The venue card layout feels unbalanced: lots of whitespace, large text, detached button placement.
- Signup form fields inherit browser/theme defaults and look like an unfinished form.
- Activity cards do not provide enough scanning structure for 20+ activities.
- There is no strong page-level composition tying list, map, form, and board together.
- Repeated maps can appear on the same page when multiple shortcodes are used.
- Labels and status messages are technically correct but not optimized for quick decisions.
- Mobile layout is basic rather than intentionally designed.
- Printable activity poster PDFs are functional but not polished: long titles collide with card borders, the information block is cramped, QR placement feels detached, the direct URL wraps poorly, and the page does not yet feel print-ready for real venue signage.

## Design Direction

Create a calm, operational UI that works inside arbitrary WordPress themes:

- Activity-first, map-supporting layout.
- Stronger hierarchy for date, venue, capacity, and signup action.
- Compact cards that scan well with many activities.
- Form controls that feel native to PASAT, not raw browser defaults.
- Responsive layout that works from phone to desktop.
- Clear status pills for open, few spots left, full, waitlist, closed, and cancelled.
- Better empty/loading/success/error states.
- Print-ready signup posters with strong hierarchy, resilient text fitting, prominent QR scanning, and attractive logo/organization placement.
- No hardcoded organization, festival, or location branding.

## Phase 1: Public Layout Shell

Status: addressed in `0.1.3` with scoped public wrappers, CSS design tokens, polished base surfaces, controls, notices, status pills, focus states, and responsive spacing. Remaining refinements from this phase can be folded into the later component-specific phases.

Create a consistent public wrapper and layout vocabulary.

Tasks:

- Add a `.pasat-public` or `.pasat-surface` wrapper around public templates where practical.
- Introduce CSS custom properties under PASAT classes only:
  - colors
  - spacing
  - border radius
  - shadows
  - text sizes
  - status colors
- Add a responsive page layout for combined shortcode pages:
  - Desktop: activity/signup content and supporting map can sit in a balanced two-column rhythm where possible.
  - Tablet/mobile: single-column flow with tighter spacing and sticky-free controls.
- Ensure all PASAT components have `box-sizing: border-box`.
- Keep styles scoped with `.pasat-` prefixes to avoid theme pollution.

Acceptance criteria:

- Public page no longer looks like raw theme defaults.
- PASAT components have consistent spacing, font sizing, buttons, borders, and focus states.
- Layout remains readable at 360px, 768px, and desktop widths.

## Phase 2: Activity Listing Redesign

Status: addressed in `0.1.4` with redesigned card structure, date blocks, activity type chips, compact metadata, capacity/count summaries, selected activity highlighting, and lightweight client-side search/type/venue filters.

Make activity cards easier to scan when 20+ activities exist.

Tasks:

- Redesign `.pasat-card` into a compact activity card:
  - date/time block
  - title
  - venue/location row
  - activity type chip
  - capacity/status pill
  - primary signup button
- Shorten long descriptions visually:
  - show a concise excerpt in cards
  - keep full description available on the selected signup view or detail context
- Add optional filter/sort controls above the list:
  - search
  - activity type
  - venue
  - date range or upcoming/today toggle
- Add a `featured/selected` visual state when `pasat_activity_id` is present.
- Improve board mode separately so the display board remains readable at distance.

Acceptance criteria:

- 20 activities can be scanned without the page feeling like a wall of text.
- Status and signup action are visible without hunting.
- Cards do not jump or resize awkwardly when counts refresh.

## Phase 3: Signup Form Redesign

Status: addressed in `0.1.5` with a selected activity summary, sectioned signup form, styled optional/helper labels, activity-specific age and warning context, accessible AJAX notices, disabled submit state, and POST fallback preservation of the chosen activity after validation errors. Remaining refinements from this phase are now covered by Phase 6 visual/accessibility QA.

Turn the form into a guided signup panel.

Tasks:

- Add a selected activity summary above the form:
  - title
  - date/time
  - venue
  - spots/waitlist status
  - warning/age restriction note when relevant
- Style form controls:
  - consistent height
  - padding
  - border/focus ring
  - readable labels
  - helper text for optional fields
- Group fields into clear sections:
  - activity
  - participant
  - contact
  - consent and acknowledgements
- Improve checkbox blocks so consent, membership, and warnings are visually distinct and touch-friendly.
- Add submit loading state and duplicate-submit protection in `public.js`.
- Improve success state:
  - show confirmed/waitlisted badge
  - explain email/cancellation link
  - avoid sounding broken if SMTP is unavailable in local testing
- Improve error state:
  - show concise top error
  - preserve entered fields when server-side validation fails
  - focus first invalid field when possible

Acceptance criteria:

- Form looks professional before any theme customization.
- Required actions are clear.
- Submission feedback feels intentional, not like raw system output.

## Phase 4: Venue Map and Venue Cards

Status: addressed in `0.1.6` with redesigned public venue cards, hidden raw coordinates by default, venue chips, next-activity context, participant-friendly actions, compact activity-scoped maps, popup directions links, and card-to-marker highlighting. Remaining refinements from this phase can be handled in Phase 6 visual/accessibility QA.

Make the map useful without letting it overpower signup.

Tasks:

- Change map default presentation:
  - fixed responsive aspect ratio
  - reasonable max height
  - compact map on signup pages
  - larger map only when using `[pasat_venue_map]` alone
- Hide raw latitude/longitude by default.
- Replace coordinate text with user-facing actions:
  - View directions
  - Open map
  - Activities at this venue
- Redesign venue cards:
  - compact header
  - venue type/capacity chips
  - address row
  - concise description
  - activity count and next activity
- Add map/list interaction:
  - clicking a venue card focuses marker
  - clicking a marker highlights card
  - selected activity can focus its venue
- Prevent duplicated map fatigue when multiple shortcodes are used on the same page:
  - document recommended shortcode combinations
  - consider a setting/attribute to suppress embedded signup map when standalone map shortcode is present

Acceptance criteria:

- Map supports the user’s decision instead of becoming the whole first viewport.
- Venue cards are compact and action-oriented.
- Coordinates are not displayed unless explicitly useful for debugging/admin.

## Phase 5: My Signups and Badges UX

Status: addressed in `0.1.8` with a verified participant profile header, membership status card, visual badge gallery, responsive signup/history cards, privacy-conscious lookup copy, and `aria-live` lookup notices. Remaining refinements from this phase are visual QA with larger real datasets and cross-theme checks.

Make membership and badges feel rewarding without exposing private data.

Tasks:

- Redesign membership summary as a profile card after verified lookup.
- Render badges as visual chips/cards instead of plain list items.
- Show year participation badges and placement badges with distinct styling.
- Improve participation history for mobile:
  - card layout on small screens
  - table layout on larger screens
- Add clearer private lookup messaging:
  - do not reveal whether an email exists before verification
  - explain that the lookup link is private

Acceptance criteria:

- Membership/badges feel like a feature, not debug output.
- The private lookup flow remains privacy-conscious.

## Phase 6: Accessibility and Theme Compatibility

Harden the UI for real users and arbitrary WordPress themes.

Tasks:

- Add visible focus states to all PASAT buttons, inputs, select boxes, map controls, and links.
- Confirm color contrast for:
  - buttons
  - status pills
  - notices
  - muted text
- Use `aria-live` for AJAX signup notices and board refresh status.
- Ensure form labels are explicit and not only visual.
- Avoid relying on color alone for activity status.
- Respect `prefers-reduced-motion`.
- Test with common WordPress block themes and classic themes.

Acceptance criteria:

- Keyboard-only users can complete signup.
- Screen reader users receive meaningful status changes.
- Styles do not break common WordPress themes.

## Phase 7: Poster PDF and QR Print UX

Status: addressed in `0.1.7` with fixed poster zones, bounded title/link wrapping, a stronger centered QR block, cleaner short-link card, safer logo placement, and print-oriented copy. Remaining refinements from this phase can be handled in Phase 6 visual/accessibility QA and final release smoke testing.

Make the downloadable activity poster PDFs good enough to print, place at venues, and trust as first-contact signup material.

Screenshot issues to address:

- Long activity titles overflow into the card border and crowd the metadata.
- The visual hierarchy does not clearly separate organization, activity, date, location, QR, and direct link.
- The QR code is large enough, but it feels mechanically pasted in rather than intentionally composed.
- The direct signup URL wraps in an unattractive, hard-to-type block.
- The footer and branding feel under-designed.
- The current fixed-position PDF layout has little resilience for long titles, long venue names, long addresses, and custom logos.

Tasks:

- Redesign `PASAT\Poster\ActivityPosterPdf` around a print-safe grid:
  - A4 portrait with consistent margins and print bleed safety.
  - Optional Letter-size support if practical.
  - Header band for logo/organization and a concise "Scan to sign up" message.
  - Activity information card that reserves enough room for 1-4 title lines without collision.
  - Dedicated date/time, venue, address, capacity/status, and optional warning areas.
  - Large centered QR block with clear scan instruction.
  - Short fallback URL block that wraps cleanly and remains readable.
- Add text fitting helpers for PDF rendering:
  - measure/estimate line lengths per font size,
  - reduce title font size for long titles,
  - clamp or gracefully wrap venue/address/link text,
  - avoid drawing text outside its bounding region.
- Improve QR and link behavior:
  - keep the QR quiet zone visually clear,
  - include the short QR redirect URL when possible,
  - avoid broken URL wrapping by splitting at path/query boundaries,
  - optionally add "Open camera and scan" helper copy.
- Improve logo handling:
  - preserve logo aspect ratio,
  - avoid stretching or oversized logos,
  - support a clean no-logo fallback,
  - document recommended logo dimensions.
- Add poster style settings if lightweight:
  - poster accent color,
  - optional poster headline text,
  - A4 vs Letter default if feasible,
  - whether to show direct link.
- Improve bulk ZIP export:
  - filename should include date and sanitized activity title,
  - ZIP should skip/flag impossible posters rather than failing all when one activity has bad data,
  - add a small manifest text file to ZIP if practical.
- Add an admin preview/download workflow:
  - keep single PDF download per activity,
  - keep ZIP download for all activity PDFs,
  - consider a "Preview Poster" link opening inline PDF in a new tab.

Acceptance criteria:

- Long smoke-test activity titles do not overlap borders or metadata.
- QR code remains scannable at normal print sizes.
- Direct signup link is readable and does not overflow.
- Poster looks good with and without a configured logo.
- Poster works for long venue names and addresses.
- Single PDF download and ZIP download still work with capability checks.
- Generated PDFs pass smoke tests for content, logo embedding, QR content, and ZIP output.

## Phase 8: Admin UI Cleanup

After public UX is improved, bring wp-admin screens up to the same level.

Tasks:

- Add consistent PASAT admin page headers.
- Improve dashboard metric cards and recent signup table.
- Add quick links:
  - create activity
  - create venue
  - view public page
  - download poster ZIP
- Improve CSV/export button placement.
- Add clearer empty states for activities, venues, signups, and participants.
- Add admin notices for SMTP not configured or strict mail setting risks.

Acceptance criteria:

- Admin screens remain WordPress-native but feel organized and intentional.
- Common workflows are reachable within one click from the dashboard.

## Implementation Order

1. Update public CSS foundation and design tokens.
2. Redesign activity cards/list.
3. Redesign signup form and notices.
4. Redesign venue map/cards and map behavior.
5. Improve my-signups, membership, and badges presentation. Completed in `0.1.8`.
6. Add accessibility polish and responsive checks. Continued in `0.1.13`; contrast checks are automated and the first keyboard fixes are in place, remaining work is manual screen-reader, cross-theme, and follow-up keyboard checks.
7. Redesign poster PDFs and QR print workflow. Mostly completed in `0.1.7`; keep visual QA and ZIP manifest refinements.
8. Clean up admin dashboard and key admin pages.
9. Improve activity board display/kiosk readability and refresh feedback. Completed in `0.1.9`.
10. Rebuild release ZIP and smoke-test on `https://dev.raoul.no/wp/pasat-test-signup/`.

## Test Plan

Use the current WordPress smoke site and seeded data:

- 20 smoke-test activities.
- 5 mapped smoke-test venues.
- public page: `https://dev.raoul.no/wp/pasat-test-signup/`
- admin: `https://dev.raoul.no/wp/wp-admin/`

Manual checks:

- Desktop screenshot at 1440px.
- Tablet screenshot around 768px.
- Mobile screenshot around 390px.
- Submit a confirmed signup.
- Fill an activity to capacity and confirm waitlist state.
- Check cancellation link flow.
- Confirm map markers and venue cards interact.
- Confirm `[pasat_activity_board]` still auto-refreshes.
- Confirm `[pasat_my_signups]` does not expose private data without verification.
- Download several activity poster PDFs, including long-title smoke activities, and check for title/link overflow.
- Scan at least one generated poster QR code from screen and/or printed PDF.
- Download poster ZIP and confirm it contains all available PDFs with usable filenames.

Automated/light checks:

```bash
cd /home/project/PASAT-WP
./sync-plugin.sh
./wp-cli.sh plugin activate pasat
curl -I -L https://dev.raoul.no/wp/pasat-test-signup/
curl -sS https://dev.raoul.no/wp/wp-json/pasat/v1/activities
```

If Playwright is available, capture visual screenshots for public page, signup-selected state, and mobile layout.

## Deliverables

- Updated `pasat/assets/css/public.css`.
- Updated `pasat/assets/js/public.js` where interaction polish is needed.
- Updated public templates:
  - `activity-list.php`
  - `signup-form.php`
  - `venue-map.php`
  - `my-signups.php`
- Updated poster PDF renderer:
  - `pasat/includes/Poster/ActivityPosterPdf.php`
- Updated poster download workflow if needed:
  - `pasat/includes/Admin/PosterDownloads.php`
- Optional small template partials if duplication grows.
- Updated README screenshots/testing notes if the final UI materially changes setup or shortcode recommendations.
- Updated implementation report with UX improvements.

## Estimated Impact

This should move the public-facing experience from functional MVP to credible beta quality. The largest visible gains will come from:

- compact activity cards,
- styled form fields,
- less dominant map,
- cleaner venue cards,
- better status/feedback states.

Estimated effort: medium. Most work is template/CSS/JS polish, with limited PHP changes unless filters or better selected-activity summaries require additional view data.
