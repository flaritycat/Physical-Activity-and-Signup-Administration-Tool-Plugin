# PASAT Assistive Technology QA

Date: 2026-06-12

This checklist is for the manual accessibility pass that complements PASAT's automated public contrast and accessibility-hook checks. Record the pass in `docs/ASSISTIVE_TECH_QA_RESULTS.md`.

## Recommended Matrix

- Screen readers: NVDA with Firefox or Chrome on Windows, VoiceOver with Safari on macOS or iOS.
- Keyboard: desktop keyboard only, no mouse.
- Viewports: mobile width, tablet width, and desktop width.
- Themes: one block theme and one classic theme.

## Public Signup

1. Tab to the activity selector.
2. Change the selected activity.
3. Confirm the selected activity summary is announced after the change.
4. Confirm age guidance, warning acknowledgement, capacity status, date, and venue are understandable.
5. Submit an empty form.
6. Confirm the error notice is announced and focus moves to the first invalid field.
7. Complete a valid signup.
8. Confirm the success notice is announced and focus lands on the result notice.

Expected result: a participant can select an activity, understand its requirements, fix validation errors, and submit without using a mouse.

## My Signups

1. Tab to the private lookup form.
2. Submit an invalid e-mail, then a valid e-mail.
3. Confirm notices are announced without exposing whether an e-mail exists.
4. Open a verified lookup link.
5. Confirm profile, membership, badges, current signups, and history are understandable in reading order.

Expected result: private lookup feedback is clear and privacy-preserving.

## Activity List Filters

1. Tab through search, type, venue, and reset controls.
2. Change each filter.
3. Confirm the live result count announces the number of visible activities.
4. Confirm reset is unavailable until a filter is active.
5. Confirm no-result state is announced when filters hide all activities.

Expected result: filtering is understandable without visual-only feedback.

## Venue Map

1. Tab through venue cards and map action buttons.
2. Activate "Show on map" for multiple venues.
3. Confirm the active venue is announced.
4. Confirm the active button state changes.
5. Open map popup links and confirm "Directions" has a venue-specific accessible name.

Expected result: venue/map relationships work even when the visual map is not usable.

## Activity Board

1. Tab to board signup links.
2. Wait for at least one auto-refresh interval.
3. Confirm focus remains on the same signup link after refresh.
4. Confirm refresh status updates are announced without repeating excessive unrelated content.
5. Confirm QR/signup controls have activity-specific names.

Expected result: the polling board does not disorient keyboard or screen-reader users.

## Pass Criteria

- No keyboard trap.
- Visible focus remains obvious.
- Dynamic changes are announced once, clearly, and in context.
- Repeated links/buttons have unique accessible names.
- Public flows do not expose private participant data.
- Mobile and desktop reading order match the visual task order.

Record defects in the UI/UX plan with the affected shortcode, theme, viewport, assistive technology, expected behavior, and observed behavior.
