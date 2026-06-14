# Segmint

**An open-source platform for event tracking and real-time audience segmentation.**

Segmint collects events from web applications, evaluates visitors against configurable
segment rules, and exposes the resulting segment data through an API and JavaScript SDK.
It can be used to analyse audience behaviour or adapt application content based on the
segments matched by a visitor.

## Why Segmint

Segmint provides a central place to inspect tracked activity, define audience segments,
and use those segments in other applications.

The project is open source and designed to run on infrastructure you control. This makes
the implementation inspectable and adaptable, keeps event data within your chosen
environment, and avoids dependencies on a hosted provider's pricing model or product
roadmap. Operating a self-hosted instance also means that your team is responsible for
deployment, monitoring, security, backups, and capacity planning.

### Common uses

- **Audience analysis** — Inspect event trends, segment distribution, and visitor activity
  derived from tracked data.
- **Content personalisation** — Use matched segments to select content, calls to action,
  or application flows.
- **Campaign analysis** — Define segments based on UTM parameters, referrers, and other
  tracked properties.
- **CMS integration** — Retrieve segments through the API and associate them with content
  in a CMS or headless application.

### Key features

- **Data-based segment suggestions** — Suggests segments from tracked UTM sources,
  campaigns, referrer domains, returning visitors, and frequently visited pages.
- **Real-time matching** — Re-evaluates a visitor's segments when an event is received.
- **JavaScript SDK** — Tracks browser events and provides access to the current visitor's
  matched segments.
- **Configurable rules** — Supports comparisons, visit counts, page views, and browser
  language rules.
- **Rule templates** — Provides reusable rule presets scoped to each project.
- **Organizations and teams** — Supports multiple organizations and role-based access
  using owner, admin, member, and guest roles. Guests only access explicitly assigned
  projects.
- **Member invitations** — Sends organization invitations by email and shows pending
  invitations inside Segmint.
- **Access-token lifecycle** — Create, revoke, reactivate, and rotate project tokens.
  Newly created or rotated token values are displayed once.
- **Analytics** — Provides per-project event trends, segment distribution, and recent
  activity.
- **Playground** — Includes an HTML page for testing segment visibility using project
  data.

## How it works

1. **Track events** — Add the SDK to a web application. It captures page views, UTM parameters, referrers, and custom events automatically.
2. **Define segments** — Create audience segments with rules, or let Segmint suggest them from your data.
3. **Use segment results** — Read the visitor's matched segments through the SDK and use
   them in application or content-selection logic.

```html
<script src="https://your-segmint-host/js/segmint.min.js"></script>
<script>
  await Segmint.init({ token: 'your-token', autoTrack: true });

  if (Segmint.visitor.hasSegment('high_intent')) {
    showSpecialOffer();
  }
</script>
```

## Tech Stack

| Layer       | Technology                          |
|-------------|-------------------------------------|
| Backend     | Laravel 13, PHP 8.5                |
| Frontend    | Svelte 5, Inertia.js v3            |
| Styling     | Tailwind CSS 4                      |
| Charts      | Chart.js                            |
| Auth        | Laravel Fortify (headless)          |
| Routing     | Laravel Wayfinder (type-safe TS)    |
| Testing     | PHPUnit 12                          |
| Database    | PostgreSQL (production), SQLite (test) |
| Build       | Vite 8, Bun                         |

## Requirements

- PHP 8.5+
- Composer
- Bun
- Node.js 22 LTS
- PostgreSQL

## Installation

```bash
# Clone the repository
git clone https://github.com/your-username/segmint.git
cd segmint

# Install dependencies
composer install
bun install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations and seed demo data
php artisan migrate
php artisan db:seed

# Build frontend assets
bun run build
```

## Development

```bash
# Start all dev services (Laravel server, queue worker, log viewer, Vite)
composer run dev
```

Inertia application pages are client-rendered. Public content pages use Laravel Blade;
see [Client-Rendered Inertia and Blade Public Pages](docs/decisions/0004-client-rendered-inertia-and-blade-public-pages.md)
for the rationale.

Or run services individually:

```bash
php artisan serve
bun run dev
```

## Testing

```bash
# Run the full test suite
php artisan test

# Run a specific test file
php artisan test tests/Feature/ProjectShowTest.php

# Filter by test name
php artisan test --filter=testName
```

## API

Segmint exposes a token-authenticated API for event ingestion and segment retrieval.

### Retrieve segments

```bash
curl https://your-domain.test/api/segments?token=your-project-token
```

### Track an event

```bash
curl -X POST https://your-domain.test/api/event-log/track \
  -H "Content-Type: application/json" \
  -d '{"token": "your-project-token", "type": "page-view", "visitor_id": "abc123"}'
```

See [docs/segments-api.md](docs/segments-api.md) for full API documentation.

## JavaScript SDK

Segmint ships with a lightweight SDK (`public/js/segmint.js`) organised into two namespaces:

- **`Segmint.visitor.*`** — Track events and read matched segments for the current visitor
- **`Segmint.fetch.*`** — Query project-level data (e.g. list all available segments for CMS integration)

### Track events

```js
// Store a custom event and receive matched segments
await Segmint.visitor.event('add-to-cart', { product_id: 42, price: 29.99 });

// Store an event without blocking the current UI flow
Segmint.visitor
  .event('component-viewed', { component: 'pricing-hero' })
  .catch(() => {});

// Evaluate matching without storing the event
await Segmint.visitor.event('page-view', { test_case: 'pricing' }, { dryRun: true });

// Store an event during page unload; no response is available
window.addEventListener('beforeunload', function () {
  Segmint.visitor.beacon('page-exit', { time_on_page: 45 });
});
```

Use `autoTrack: true` for an automatic stored initial page view, a normal
`visitor.event()` for events that should contribute to analytics, and `{ dryRun: true }`
only for rule testing or diagnostics. `visitor.beacon()` sends a normal stored event but
does not return segment results; reserve it for page unload and skip the call when it
must not be stored. During normal page use, call `visitor.event()` without `await` when
the UI does not need to wait for its response.

### Read segments

```js
// Matched segments for the current visitor
Segmint.visitor.segments();                // [{ slug: 'high_intent', ... }]
Segmint.visitor.hasSegment('high_intent'); // true

// All active segments defined in the project (for CMS integration)
const allSegments = await Segmint.fetch.segments();
```

### Configuration options

| Option         | Type    | Default          | Description                                     |
|----------------|---------|------------------|-------------------------------------------------|
| `token`        | string  | —                | Project access token (required)                 |
| `endpoint`     | string  | auto-detected    | Full URL of the tracking API                    |
| `autoTrack`    | boolean | `false`          | Send a `page-view` event on init                |
| `debug`        | boolean | `false`          | Log events and responses to the console         |
| `visitorIdKey` | string  | `'segmint_vid'`  | localStorage key used for the visitor ID        |

### Methods reference

| Method | Returns | Description |
|--------|---------|-------------|
| `init(options)` | `Promise` (if autoTrack) | Initialise the SDK |
| `visitor.event(type?, props?, options?)` | `Promise<{status, segments}>` | Store an event and update cached segments, or evaluate without storing with `options.dryRun` |
| `visitor.beacon(type?, props?)` | `void` | Store an event using fire-and-forget delivery; no response or dry-run |
| `visitor.segments()` | `Object[]` | Get cached matched segments |
| `visitor.hasSegment(slug)` | `boolean` | Check if visitor matches a segment |
| `visitor.id()` | `string` | Get the persistent visitor ID |
| `visitor.reset()` | `void` | Clear visitor ID and cached segments |
| `fetch.segments()` | `Promise<Object[]>` | Retrieve all active project segments |
| `onReady(callback)` | `void` | Run callback when segments are first available |
| `isReady()` | `boolean` | Whether the first event has completed |

### Playground

A live playground is included at `public/playground.html` for testing segment visibility:

```
https://your-segmint-host/playground.html?token=your-project-token
```

See [docs/tracking-sdk.md](docs/tracking-sdk.md) for full SDK documentation and recipes.

## Segment rule types

| Type | Description |
|------|-------------|
| Comparison | Match event fields against a value (equals, contains, greater than, etc.) |
| Visit count (all pages) | Match visitors with N+ total page views |
| Page view count (same page) | Match visitors with N+ views of the current page |
| Browser language | Match visitors by their browser language preference |

## Code style

PHP code follows [Laravel Pint](https://laravel.com/docs/pint) conventions:

```bash
vendor/bin/pint
```

## Documentation

- [Frontend Integration for Coding Agents](docs/frontend-integration-for-coding-agents.md) — Implementation contract and prompt template for integrating Segmint with Codex or Claude Code
- [Tracking SDK](docs/tracking-sdk.md) — Full SDK reference, payload format, and integration recipes
- [Segments API](docs/segments-api.md) — REST API for retrieving segments, CMS integration patterns
- [Data Model](docs/data-model.md) — Users, Organizations, and Projects architecture

## License

[AGPL-3.0](LICENSE.md) with an SDK exception:

- **Server-side code** (PHP, Svelte, etc.) — AGPL-3.0. Anyone who modifies and deploys it as a service must open-source their changes.
- **JavaScript SDK** (`segmint.js` / `segmint.min.js`) — MIT. It can be embedded in
  client-side applications without applying the AGPL-3.0 license to that application.
