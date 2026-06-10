# Improvement 1: Better PASAT Activity Board

## Execution Status

Implemented. The board now supports kiosk mode, visible refresh and connection state, clearer status labels, change highlights, optional local QR signup links, shortcode filters, public REST filters, and a reusable Docker smoke test at `tools/smoke-activity-board.sh`.

## Goal

Improve `[pasat_activity_board]` from a simple auto-refreshing list into a more useful public display board for participants, hosts, and venue screens, while keeping it WordPress-friendly and avoiding fragile realtime infrastructure.

## Current State

- The board renders upcoming public activities server-side.
- JavaScript refreshes the board from the public PASAT REST activities endpoint.
- Default refresh interval is 60 seconds.
- Minimum refresh interval is 15 seconds.
- The board is polling-based, not WebSocket/SSE realtime.
- It is intentionally read-only and does not expose participant data.

## Proposed Improvements

### 1. Kiosk Mode

Add a display-friendly mode for TVs, tablets, and venue screens.

Example:

```text
[pasat_activity_board mode="kiosk"]
```

Kiosk mode should:

- use larger text and stronger spacing
- optimize for distance viewing
- hide theme-dependent clutter where possible
- show activity status clearly
- preserve accessibility and responsive behavior

### 2. Visible Refresh And Connection Status

Show users when the board was last updated and whether refresh is working.

Examples:

- `Updated just now`
- `Updated 42 seconds ago`
- `Refreshing...`
- `Connection lost. Showing last saved board.`

Behavior:

- update the timestamp after every successful refresh
- show a non-alarming warning after repeated failed refreshes
- keep the last successfully rendered board visible if refresh fails
- avoid clearing server-rendered content on failed requests

### 3. Status Improvements

Add clearer activity status labels.

Suggested labels:

- `Open`
- `Few spots left`
- `Full`
- `Waitlist open`
- `Signup closed`
- `Starting soon`
- `Cancelled`

Rules:

- `Few spots left` should be configurable or use a sensible threshold, such as 3 remaining spots.
- Cancelled activities should be visually distinct if they are shown.
- Status labels should be translatable.

### 4. Change Highlights

Highlight meaningful changes after a refresh.

Examples:

- spots remaining changed
- activity became full
- waitlist opened
- activity starts soon
- activity was cancelled

Implementation notes:

- compare the previous rendered activity state with the new REST response
- apply a short-lived `.pasat-board-changed` class
- use CSS transitions that are noticeable but not distracting
- respect `prefers-reduced-motion`

### 5. QR Signup Links

Add optional QR codes linking to the signup page for each activity.

Example:

```text
[pasat_activity_board show_qr="1"]
```

QR behavior:

- link directly to the public signup page with `pasat_activity_id`
- do not include private participant data
- work without external tracking
- use a lightweight local QR implementation or a server-generated markup approach
- include plain text fallback link for accessibility

### 6. Shortcode Filters

Allow boards to be scoped for specific screens or contexts.

Examples:

```text
[pasat_activity_board venue_id="3"]
[pasat_activity_board activity_type="yoga"]
[pasat_activity_board host_id="12"]
[pasat_activity_board refresh="15000"]
[pasat_activity_board limit="10"]
```

Filter behavior:

- `venue_id` limits the board to one venue/location
- `activity_type` limits by activity type
- `host_id` limits to activities assigned to a WordPress user
- `refresh` sets polling interval in milliseconds, with a minimum of 15 seconds
- `limit` controls number of activities shown

## Suggested Implementation Order

1. Extend `Shortcodes::activity_board()` to parse shortcode attributes.
2. Add repository filtering support for `venue_id`, `activity_type`, and `host_id` if missing.
3. Pass board options into `templates/public/activity-list.php`.
4. Add board data attributes for mode, refresh interval, filters, QR setting, and status thresholds.
5. Extend the public REST activities endpoint to accept safe public filters.
6. Update `assets/js/public.js` to preserve last rendered content, track refresh state, show last-updated text, and highlight changes.
7. Add CSS for kiosk mode, refresh status, change highlights, QR layout, and reduced-motion behavior.
8. Add QR generation support.
9. Update README shortcode documentation.
10. Add validation notes to `IMPLEMENTATION_REPORT.md`.

## Files Likely To Change

- `pasat/includes/Frontend/Shortcodes.php`
- `pasat/includes/Database/ActivitiesRepository.php`
- `pasat/includes/REST/PublicActivitiesController.php`
- `pasat/templates/public/activity-list.php`
- `pasat/assets/js/public.js`
- `pasat/assets/css/public.css`
- `README.md`
- `CHANGELOG.md`
- `IMPLEMENTATION_REPORT.md`

## Acceptance Criteria

- `[pasat_activity_board]` continues to work exactly as before.
- `[pasat_activity_board mode="kiosk"]` renders a display-friendly board.
- The board shows a last-updated indicator.
- Failed refreshes do not blank the board.
- Repeated failed refreshes show a connection warning.
- Status labels are clearer and translation-ready.
- Meaningful changes are highlighted briefly.
- QR signup links can be enabled with `show_qr="1"`.
- Venue, activity type, host, refresh, and limit filters work.
- Public REST responses still expose only public activity data.
- Participant/private data is never shown on the board.
- All output is escaped.
- CSS remains `.pasat-` scoped.
- `prefers-reduced-motion` is respected.

## Testing Plan

Run:

```text
tools/check-release.sh
tools/smoke-activity-board.sh
```

Manual smoke:

1. Create two venues.
2. Create several published activities across venues and activity types.
3. Add `[pasat_activity_board]` to a page and confirm default behavior.
4. Add `[pasat_activity_board mode="kiosk" show_qr="1"]` and confirm kiosk layout and QR links.
5. Add `[pasat_activity_board venue_id="..."]` and confirm only that venue appears.
6. Add `[pasat_activity_board activity_type="..."]` and confirm filtering.
7. Add `[pasat_activity_board refresh="15000"]` and confirm polling interval.
8. Fill an activity and confirm status changes to `Full` or `Waitlist open`.
9. Cancel or close an activity and confirm the board reflects the status.
10. Simulate REST failure and confirm the board keeps the last content with a connection warning.

Browser review:

- desktop
- tablet
- mobile
- kiosk/full-screen display

## Deferred Ideas

- Optional SSE/WebSocket transport for sites that can support it.
- Admin preview mode for board configuration.
- Theme-independent fullscreen route.
- Per-board saved presets.
- QR styling controls.
- Sound or visual alerts for venue staff.
