# Segmint Tracking SDK

The Segmint JavaScript SDK (`segmint.js`) is a lightweight client-side library for tracking user events and retrieving matched audience segments in real time.

## Installation

Include the script on any page you want to track. The SDK auto-detects the API endpoint from its own `src` URL — no extra configuration needed.

### Script tag

```html
<script src="https://your-segmint-host/js/segmint.min.js"></script>
```

Both `segmint.js` (readable) and `segmint.min.js` (minified) are served from `public/js/`
as classic browser scripts and expose `window.Segmint`. The current SDK is not published
as an npm package and does not provide an ES-module export.

For an implementation contract designed for Codex, Claude Code, and other coding agents,
see [Frontend Integration Guide for Coding Agents](frontend-integration-for-coding-agents.md).

## Quick start

```html
<script src="https://your-segmint-host/js/segmint.min.js"></script>
<script>
  Segmint.init({ token: 'your-project-token', autoTrack: true })
    .then(function () {
      if (Segmint.visitor.hasSegment('returning-buyer')) {
        document.getElementById('hero').innerHTML = 'Welcome back!';
      }
    });
</script>
```

With `autoTrack: true`, `init()` immediately sends a `page-view` event and returns a Promise that resolves once the server responds with matched segments. This lets you personalise content on first paint.

## Choosing how to send an event

| Use case | Recommended method | Stored? | Returns matched segments? |
|---|---|---|---|
| Track the initial page view automatically | `Segmint.init({ token, autoTrack: true })` | Yes | Yes, through the returned Promise |
| Initialise without automatically tracking a page view | `Segmint.init({ token, autoTrack: false })` | No automatic event | No |
| Record a page view, interaction, or component impression | `Segmint.visitor.event(type, properties)` | Yes | Yes |
| Record an event without blocking the current UI flow | Call `visitor.event(type, properties)` without `await`, and handle rejection | Yes | Yes, processed asynchronously |
| Test which segments a hypothetical event would match | `Segmint.visitor.event(type, properties, { dryRun: true })` | No | Yes |
| Record an event while the page is unloading | `Segmint.visitor.beacon(type, properties)` | Yes | No |
| Prevent a specific beacon event from being stored | Do not call `visitor.beacon()` | No | No |

Use a normal `visitor.event()` when an observable action should become part of the
visitor's history and future analytics. For example, record that a component was
actually displayed:

```js
await Segmint.visitor.event('component-viewed', {
  component: 'pricing-hero',
  variant: 'enterprise',
});
```

When the event should be stored but the current UI flow does not need its response, call
`visitor.event()` without `await` and handle a possible rejection:

```js
Segmint.visitor
  .event('component-viewed', {
    component: 'pricing-hero',
  })
  .catch(() => {
    // Tracking failure must not interrupt the UI flow.
  });
```

The request remains asynchronous, stores the event, and updates the SDK's cached
segments when its response arrives. Prefer this over `beacon()` during normal page use,
because `event()` can process the response and report failures.

Use `{ dryRun: true }` only when the event is hypothetical and must not affect analytics.
Use `beacon()` only when the page may unload before a normal `event()` request completes
and the client does not need the resulting segment list.

## SDK structure

The SDK is organised into two namespaces plus lifecycle methods:

```
Segmint
├── init(options)          Lifecycle — initialise the SDK
├── onReady(callback)      Lifecycle — fire when segments are available
├── isReady()              Lifecycle — check if first event completed
│
├── visitor                About the current visitor's session
│   ├── event()            Send a tracking event
│   ├── beacon()           Fire-and-forget tracking (page unload)
│   ├── segments()         Cached matched segments
│   ├── hasSegment(slug)   Check segment membership
│   ├── id()               Get visitor ID
│   └── reset()            Clear visitor identity and cache
│
└── fetch                  About the project (read-only)
    └── segments()         Retrieve all active segments
```

## API reference

### Lifecycle

#### `Segmint.init(options)`

Initialise the SDK. Must be called before any other method.

| Option | Type | Default | Description |
|---|---|---|---|
| `token` | `string` | — | **Required.** Your project access token. |
| `endpoint` | `string` | auto-detected | Full URL of the tracking API (`/api/event-log/track`). Auto-detected from the script `src` attribute. |
| `autoTrack` | `boolean` | `false` | Send a `page-view` event immediately on init. |
| `debug` | `boolean` | `false` | Log SDK activity to the browser console. |
| `visitorIdKey` | `string` | `"segmint_vid"` | localStorage key used to persist the visitor ID. |

**Returns:** `Promise<Object>` when `autoTrack` is `true`, `void` otherwise.

```js
// Minimal
Segmint.init({ token: 'abc123' });

// With auto-tracking and debug
await Segmint.init({ token: 'abc123', autoTrack: true, debug: true });
```

#### `Segmint.onReady(callback)`

Register a callback that fires once segments are available (after the first successful `visitor.event()` call). If segments are already cached, the callback fires immediately.

```js
Segmint.onReady(function (segments) {
  console.log('Segments loaded:', segments);
});
```

#### `Segmint.isReady()`

Returns `true` if the SDK has completed at least one `visitor.event()` call and segments are cached.

```js
if (Segmint.isReady()) {
  personaliseContent();
}
```

### `Segmint.visitor` — current visitor's session

#### `Segmint.visitor.event(eventType?, eventProperties?, options?)`

Send a tracking event to the Segmint API and update the internal segment cache with the
response. By default, the event is stored and becomes available for analytics and future
segment evaluation.

| Parameter | Type | Default | Description |
|---|---|---|---|
| `eventType` | `string` | `"page-view"` | The type of event (e.g. `page-view`, `add-to-cart`, `signup`). |
| `eventProperties` | `object` | `{}` | Arbitrary key-value pairs attached to the event. |
| `options.dryRun` | `boolean` | `false` | Evaluate segment matches without storing the event or match records. |

**Returns:** `Promise<Object>` — the API response containing `status`, `session`, and `segments`.

```js
// Track a page view (default)
await Segmint.visitor.event();

// Track a custom event
await Segmint.visitor.event('add-to-cart', {
  product_id: 42,
  price: 29.99,
  currency: 'EUR',
});

// Evaluate rules without storing the event
const result = await Segmint.visitor.event(
  'page-view',
  { test_case: 'pricing-page' },
  { dryRun: true },
);

console.log(result.dry_run); // true
```

Dry-run uses the same project token and matching logic as a real event. Visit-count and
page-view-count rules include the candidate event during evaluation, but the event does
not change stored counts. Use dry-run for rule testing, QA, and diagnostics only. A
client-side segment result, including a dry-run result, must never be used for
authorization. Like every `visitor.event()` response, a dry-run response updates the
SDK's browser-side matched-segment cache.

For a diagnostics or rule-testing flow, the recommended setup is
`Segmint.init({ token, autoTrack: false })`. This prevents `init()` from storing its
automatic page-view event. You can then surgically evaluate an individual event without
storing it by calling `visitor.event(type, properties, { dryRun: true })`.

`autoTrack: false` affects only the automatic page-view sent by `init()`; later calls to
`visitor.event()` and `visitor.beacon()` still store events unless an individual
`visitor.event()` uses `dryRun: true`. To prevent a beacon event from being stored, do
not call `visitor.beacon()`.

#### `Segmint.visitor.beacon(eventType?, eventProperties?)`

Fire-and-forget tracking using `navigator.sendBeacon()`. Ideal for events sent during page unload (e.g. exit tracking), where a regular `fetch` might be cancelled by the browser.

Falls back to `fetch` with `keepalive: true` if `sendBeacon` is not available.

> **Note:** Because beacon requests don't return a response, calling `beacon` does **not**
> update the internal segment cache or support dry-run. Any additional options passed to
> `beacon()` are ignored. To prevent a beacon event from being stored, skip the
> `beacon()` call. A beacon event is otherwise stored normally and can contribute to
> future analytics and segment evaluation.

```js
window.addEventListener('beforeunload', function () {
  Segmint.visitor.beacon('page-exit', { time_on_page: 45 });
});
```

#### `Segmint.visitor.segments()`

Returns a copy of the cached segment list (array of segment objects) from the most recent `visitor.event()` response.

```js
const segments = Segmint.visitor.segments();
console.log(segments); // [{ id: 1, slug: 'google_visitors', ... }, ...]
```

#### `Segmint.visitor.hasSegment(slug)`

Check whether the current visitor matches a specific segment by its slug.

| Parameter | Type | Description |
|---|---|---|
| `slug` | `string` | The segment slug to check. |

**Returns:** `boolean`

```js
if (Segmint.visitor.hasSegment('high_intent')) {
  showSpecialOffer();
}
```

#### `Segmint.visitor.id()`

Returns the current persistent visitor ID (stored in `localStorage` under the `visitorIdKey`).

```js
console.log(Segmint.visitor.id()); // "a1b2c3d4-..."
```

#### `Segmint.visitor.reset()`

Clear the visitor ID from localStorage and reset the internal segment cache. Useful when a user logs out and you want the next session to start fresh.

```js
logoutButton.addEventListener('click', function () {
  Segmint.visitor.reset();
});
```

### `Segmint.fetch` — project-level queries

#### `Segmint.fetch.segments()`

Retrieve all active segments defined for the project. This is a read-only `GET` request to `/api/segments` — it does not affect the visitor's matched segment cache.

**Returns:** `Promise<Object[]>` — array of segment objects.

```js
const allSegments = await Segmint.fetch.segments();
console.log(allSegments);
// [
//   { id: 1, name: "Google Visitors", slug: "google_visitors", ... },
//   { id: 2, name: "High Intent", slug: "high_intent", ... },
// ]
```

See [Segments API](segments-api.md) for the full response format and CMS integration patterns.

## What the SDK sends

Each `visitor.event()` call sends a `POST` request to `/api/event-log/track` with this payload:

```json
{
  "token": "your-project-token",
  "visitor_id": "a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d",
  "type": "page-view",
  "url": "https://example.com/products/shoes",
  "path": "/products/shoes",
  "referrer": "https://google.com",
  "title": "Shoes - Example Store",
  "utms": {
    "utm_source": "google",
    "utm_medium": "cpc",
    "utm_campaign": "summer-2025",
    "utm_content": null,
    "utm_term": "running shoes"
  },
  "event_properties": {},
  "dry_run": true,
  "metadata": {
    "path": "/products/shoes",
    "url": "https://example.com/products/shoes",
    "query": {},
    "title": "Shoes - Example Store",
    "referrer": "https://google.com",
    "screen": { "width": 1920, "height": 1080 },
    "viewport": { "width": 1440, "height": 900 },
    "device": {
      "language": "en-US",
      "timezone": "Europe/Rome",
      "platform": "MacIntel",
      "user_agent": "Mozilla/5.0 ...",
      "touch": false
    },
    "connection": {
      "effective_type": "4g",
      "downlink": 10,
      "rtt": 50
    },
    "timestamp": "2026-03-21T14:30:00.000Z"
  }
}
```

`dry_run` is included only when `visitor.event(..., { dryRun: true })` is used.

### What the API returns

```json
{
  "status": "OK",
  "session": "abc123sessionid",
  "dry_run": true,
  "segments": [
    { "id": 1, "name": "Google Visitors", "slug": "google_visitors", "value": "google_visitors" },
    { "id": 3, "name": "High Intent", "slug": "high_intent", "value": "high_intent" }
  ]
}
```

`dry_run` is included only in dry-run responses. Normal responses remain unchanged.

### Direct API dry-run

Set `dry_run` to the JSON boolean `true` when calling the tracking endpoint directly:

```bash
curl -X POST https://your-segmint-host/api/event-log/track \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "token": "your-project-token",
    "visitor_id": "diagnostic-visitor",
    "type": "page-view",
    "path": "/pricing",
    "dry_run": true
  }'
```

The API rejects non-boolean `dry_run` values with HTTP `422`. When omitted or set to
`false`, the event and its segment-match records are stored normally.

## Data collected automatically

The SDK collects the following data from the browser on every `visitor.event()` call:

| Data | Source | Purpose |
|---|---|---|
| Visitor ID | `localStorage` (persistent UUID) | Identify returning visitors across sessions |
| Page URL & path | `window.location` | Page-level analytics and segment rule matching |
| Referrer | `document.referrer` | Traffic source attribution |
| Page title | `document.title` | Page identification |
| UTM parameters | URL query string | Campaign attribution and segment rules |
| Screen & viewport size | `window.screen`, `window.innerWidth/Height` | Device categorisation |
| Language & timezone | `navigator.language`, `Intl` | Locale-based segmentation |
| Platform & user agent | `navigator` | Device/browser detection |
| Touch support | `navigator.maxTouchPoints` | Mobile vs desktop detection |
| Connection info | `navigator.connection` (if available) | Network quality context |
| Timestamp | `new Date().toISOString()` | Event timing |

## Recipes

### Personalise on page load

```html
<script src="https://your-segmint-host/js/segmint.min.js"></script>
<script>
  Segmint.init({ token: 'your-token', autoTrack: true }).then(function () {
    if (Segmint.visitor.hasSegment('italy_traffic')) {
      document.getElementById('banner').textContent = 'Benvenuto!';
    }

    if (Segmint.visitor.hasSegment('high_intent')) {
      document.getElementById('cta').style.display = 'block';
    }
  });
</script>
```

### Track a button click

```js
document.getElementById('signup-btn').addEventListener('click', function () {
  Segmint.visitor.event('signup-click', { plan: 'pro' });
});
```

### Track a displayed component

Use a normal stored event when the component was actually displayed and that fact should
be available for future analytics or segmentation:

```js
await Segmint.visitor.event('component-viewed', {
  component: 'pricing-hero',
  variant: 'enterprise',
});
```

Send the event after the component is displayed. Avoid recording an impression merely
because its module was loaded or its render function was considered.

### Track form submission

```js
document.getElementById('contact-form').addEventListener('submit', function (e) {
  Segmint.visitor.event('form-submit', {
    form: 'contact',
    source: document.referrer,
  });
});
```

### SPA navigation tracking

For single-page apps that change routes without full page reloads:

```js
// Call after each route change
function onRouteChange() {
  Segmint.visitor.event('page-view', {
    path: window.location.pathname,
  });
}
```

### Exit tracking with beacon

```js
window.addEventListener('beforeunload', function () {
  Segmint.visitor.beacon('page-exit', {
    time_on_page: Math.round((Date.now() - pageLoadTime) / 1000),
  });
});
```

### Populate a CMS segment picker

```js
const allSegments = await Segmint.fetch.segments();

const options = allSegments.map(s => ({
  label: s.name,
  value: s.slug,
  description: s.description,
}));
// Feed `options` into your CMS dropdown
```

### Reset on logout

```js
function logout() {
  Segmint.visitor.reset();
  window.location.href = '/login';
}
```

### Debug mode

Enable `debug: true` to see all SDK activity in the browser console:

```js
Segmint.init({ token: 'your-token', autoTrack: true, debug: true });
```

Console output will be prefixed with `[Segmint]`.
