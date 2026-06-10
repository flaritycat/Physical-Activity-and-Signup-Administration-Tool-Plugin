# Improvement 2: Open-Source Venue Map On Signup Pages

## Execution Status

Implemented for PASAT `0.1.1`.

Delivered:

- embedded Leaflet venue maps for `[pasat_venue_map]`
- `[pasat_activity_signup show_map="1"]`
- fallback venue cards for address-only and no-JavaScript users
- public `GET /pasat/v1/venues` endpoint
- venue geocoding status fields and schema upgrade
- admin **Geocode Address** and **Open Map** venue row actions
- map/geocoding settings under **PASAT > Settings**
- mocked geocoding and public map smoke coverage in `tools/smoke-venue-map.sh`

Deferred:

- bulk geocoding queue
- marker clustering
- route planning/directions
- drag-and-drop marker placement
- bundled/self-hosted Leaflet assets

## Goal

Add a proper open-source venue map to PASAT so public signup pages can show all relevant venues on an embedded map, using venue addresses and/or coordinates.

Before this improvement, the plugin had a basic `[pasat_venue_map]` shortcode, but it only rendered coordinate-enabled venue cards and external OpenStreetMap links. It did not geocode addresses, did not show an embedded interactive map, and did not automatically appear alongside the signup flow.

## Current State

- Venues store:
  - name
  - description
  - address
  - latitude
  - longitude
  - venue type
  - capacity
- `[pasat_venue_map]` exists.
- `[pasat_venue_map]` filters out venues without latitude/longitude.
- Each venue card links to `openstreetmap.org`.
- The signup page does not automatically show a map.
- No bundled map library exists.
- No address geocoding exists.
- No geocoding cache/status exists.

## Desired User Experience

### Public Signup Page

When a visitor views the signup page, they should be able to see where activities take place.

Expected behavior:

- show an embedded OpenStreetMap-based map near the activity list/signup form
- show markers for venues connected to public upcoming activities
- allow clicking a marker to see venue name, address, and related activities
- allow clicking from the map popup to the relevant activity signup
- degrade gracefully to venue cards and external OpenStreetMap links when JavaScript is unavailable

### Venue Map Shortcode

Enhance `[pasat_venue_map]` so it can render:

```text
[pasat_venue_map]
[pasat_venue_map source="upcoming"]
[pasat_venue_map source="all"]
[pasat_venue_map activity_id="123"]
[pasat_venue_map height="420"]
[pasat_venue_map show_cards="1"]
```

Suggested defaults:

- `source="upcoming"`
- `show_cards="1"`
- `height="420"`

### Activity Signup Shortcode

Add an option to include the map directly with the signup UI:

```text
[pasat_activity_signup show_map="1"]
[pasat_activity_signup activity_id="123" show_map="1"]
```

If `activity_id` is provided, the map should focus on that activity's venue.

## Map Technology

Use open-source tools:

- Leaflet.js for the interactive map
- OpenStreetMap tile layer by default
- no proprietary Google Maps dependency
- no API key required for the default map display

Important tile policy note:

- OpenStreetMap public tiles are fine for low/ordinary traffic.
- Larger public sites should configure a responsible tile provider or self-host tiles.
- Add a setting for tile URL and attribution.

Default tile layer:

```text
https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png
```

Default attribution:

```text
© OpenStreetMap contributors
```

## Address Geocoding

PASAT should support venues based on addresses, but geocoding must be implemented carefully.

### MVP Approach

1. Keep latitude/longitude as the authoritative map coordinates.
2. Add an admin geocoding action that converts venue address to coordinates.
3. Cache geocoding results in venue fields.
4. Never geocode on every public page load.
5. Allow manual coordinate entry/editing to override geocoding.

### Geocoding Provider

Use OpenStreetMap Nominatim-compatible geocoding by default, but make it configurable.

Default endpoint:

```text
https://nominatim.openstreetmap.org/search
```

Important:

- Respect Nominatim usage policy.
- Send a meaningful User-Agent or Referer through WordPress HTTP API.
- Rate limit geocoding requests.
- Do not bulk-geocode aggressively.
- Provide settings for endpoint URL and throttle.

### Geocoding Status

Add fields or metadata for:

- `geocoded_at`
- `geocoding_status`
- `geocoding_error`
- `geocoding_provider`

Possible statuses:

- `not_geocoded`
- `geocoded`
- `failed`
- `manual`

## Proposed Database Changes

Use dbDelta migration and increment `pasat_db_version`.

### Update `pasat_venues`

Add:

- `geocoded_at DATETIME nullable`
- `geocoding_status VARCHAR(30) not null default 'not_geocoded'`
- `geocoding_error TEXT nullable`
- `geocoding_provider VARCHAR(100) nullable`

Indexes:

- `geocoding_status`

No new table is required for MVP.

## Settings

Add a **Map Settings** section under **PASAT > Settings**:

- enable venue maps
- show map on public signup page by default
- tile URL
- tile attribution
- default map height
- default zoom
- geocoding enabled
- geocoding provider endpoint
- geocoding throttle seconds
- geocoding country/language bias, optional
- whether admins can geocode addresses from the venue list

Suggested defaults:

- maps enabled: `1`
- show on signup page: `0`
- tile URL: OpenStreetMap public tile URL
- attribution: OpenStreetMap contributors
- geocoding enabled: `0` until admin explicitly enables it
- throttle: `1` request per second minimum

## Admin UI

### Venues

Enhance **PASAT > Venues**:

- show coordinates column
- show geocoding status column
- add row action: **Geocode Address**
- add row action: **Open Map**
- show last geocoded timestamp
- show geocoding errors in admin notices
- allow manual coordinates to mark status as `manual`

### Activity Form

When an activity has a venue:

- show a compact map preview if coordinates exist
- show warning if selected venue has address but no coordinates

### Settings

Add map/geocoding settings with explanatory copy:

- public map display uses open-source map tiles
- address geocoding may call an external geocoding service
- administrators should review provider terms and usage limits

## Public UI

### Embedded Map

Render:

- `div.pasat-venue-map__canvas`
- data attributes or localized JSON containing venues and activities
- fallback venue cards below the map

Marker popup should include:

- venue name
- address
- upcoming activities at the venue
- signup links

### No JavaScript Fallback

When JavaScript is unavailable:

- show venue cards
- show external OpenStreetMap links for venues with coordinates
- show address text for venues without coordinates

### Accessibility

- map container has an accessible label
- venue cards remain keyboard-accessible
- marker popup links are real links
- no critical signup behavior depends on the map

## REST API

Add public endpoint:

```text
GET /pasat/v1/venues
```

Public response should include only:

- id
- name
- address
- latitude
- longitude
- venue type
- related public upcoming activities summary, optional

Do not expose private/admin-only notes.

Admin endpoints:

```text
POST /pasat/v1/admin/venues/{id}/geocode
POST /pasat/v1/admin/venues/geocode-missing
```

Permissions:

- public venue endpoint is readable without auth
- geocoding endpoints require `pasat_manage_venues`

## Security And Privacy

Venue addresses are public operational data in most cases, but still treat them carefully:

- only public venues/activity-linked venues should be shown publicly
- escape all venue output
- sanitize map settings
- validate tile URLs and geocoding endpoint URLs
- use `wp_remote_get()` for geocoding
- use nonces for admin geocoding actions
- rate limit geocoding
- do not send participant data to geocoding providers
- do not geocode on public requests

## Implementation Order

1. Add map settings defaults.
2. Add venue geocoding fields to schema.
3. Add `MapSettings` or helper methods for tile URL/attribution.
4. Add a `Geocoder` service using WordPress HTTP API.
5. Extend `VenuesRepository` with geocoding status updates and coordinate filtering.
6. Add admin venue row actions for geocoding and opening maps.
7. Add public venue REST endpoint.
8. Add Leaflet assets:
   - vendor CSS/JS if bundled
   - or registered CDN with local fallback, depending project policy
9. Enhance `[pasat_venue_map]` rendering.
10. Add `show_map` option to `[pasat_activity_signup]`.
11. Add frontend map initialization JS.
12. Add responsive CSS.
13. Update README, readme.txt, implementation report, and POT.
14. Add smoke tests.

## Acceptance Criteria

- Signup page can show an embedded open-source map when enabled.
- `[pasat_venue_map]` renders an interactive map when coordinates exist.
- Venues can be shown based on stored coordinates.
- Admins can geocode venue addresses into coordinates.
- Geocoding is never performed on public page load.
- Venue cards remain available without JavaScript.
- Map markers link to relevant public signup flows.
- Tile URL and attribution are configurable.
- Admin geocoding actions are nonce-protected and capability-checked.
- Public endpoints expose only public venue/activity data.
- Documentation explains OpenStreetMap/Nominatim usage expectations.

## Testing Plan

Run:

```text
tools/check-release.sh
```

Add or extend Docker smoke tests:

1. Create venues with coordinates.
2. Create venues with addresses but no coordinates.
3. Render `[pasat_venue_map]` and confirm map container, venue JSON, and fallback cards.
4. Render `[pasat_activity_signup show_map="1"]` and confirm map output appears.
5. Confirm venues without coordinates show fallback address cards.
6. Confirm public venue REST endpoint hides private/admin-only data.
7. Mock geocoding response and confirm coordinates/status are saved.
8. Confirm geocoding endpoint rejects unauthorized users.
9. Confirm map assets enqueue only when needed.

Manual browser review:

- desktop signup page
- mobile signup page
- no-JavaScript fallback
- screen-reader labels
- marker popup links

## Deferred Ideas

- Route planning/directions from user's current location.
- Clustered markers for large programs.
- Saved map presets.
- Per-activity map focus in activity cards.
- Self-hosted tile server guidance.
- Bulk geocoding queue with WP-Cron.
- Map provider presets for OpenStreetMap-compatible commercial providers.
- Admin drag-and-drop marker placement.

## Open Questions

- Should maps be shown by default on signup pages, or only when `show_map="1"` is set?
- Should the plugin bundle Leaflet locally, or register it from a CDN with integrity/fallback?
- Which geocoding provider should be recommended for production sites with higher volume?
- Should venues without activity assignments be public in `[pasat_venue_map source="all"]`?
- Should organization-specific private venues be supported?
