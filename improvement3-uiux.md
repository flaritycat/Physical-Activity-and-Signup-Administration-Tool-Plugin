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

## Design Direction

Create a calm, operational UI that works inside arbitrary WordPress themes:

- Activity-first, map-supporting layout.
- Stronger hierarchy for date, venue, capacity, and signup action.
- Compact cards that scan well with many activities.
- Form controls that feel native to PASAT, not raw browser defaults.
- Responsive layout that works from phone to desktop.
- Clear status pills for open, few spots left, full, waitlist, closed, and cancelled.
- Better empty/loading/success/error states.
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

## Phase 7: Admin UI Cleanup

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
5. Improve my-signups, membership, and badges presentation.
6. Add accessibility polish and responsive checks.
7. Clean up admin dashboard and key admin pages.
8. Rebuild release ZIP and smoke-test on `https://dev.raoul.no/wp/pasat-test-signup/`.

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
