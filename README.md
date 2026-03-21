# Segmint

**Self-hosted, event-driven audience segmentation platform.**

Segmint lets you define audience segments based on real-time events, track how users match those segments over time, and visualize engagement through built-in analytics — all within your own infrastructure.

> Real-time visitor segmentation for modern web apps. Track events, define audience segments with flexible rules, and get instant insights into your audience composition.

## Features

- **Project-based organization** — Group segments, events, and access tokens under isolated projects
- **Rule-based segmentation** — Define segments with flexible rules (comparison, visit count, page view count, browser language) that automatically evaluate incoming events
- **Real-time event tracking** — Ingest events via API and watch segment matches update in real time
- **Built-in analytics** — Per-project dashboards with charts for event trends, segment distribution, top segments, and recent activity
- **Token-based API access** — Generate scoped access tokens for each project to integrate with your applications
- **JavaScript SDK** — Lightweight client-side SDK with namespaced API (`visitor.*` for tracking, `fetch.*` for project queries)
- **Two-factor authentication** — Secure your account with TOTP-based 2FA

## Tech Stack

| Layer       | Technology                          |
|-------------|-------------------------------------|
| Backend     | Laravel 13, PHP 8.5                |
| Frontend    | Svelte 5, Inertia.js v2            |
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
- **`Segmint.fetch.*`** — Query project-level data (e.g. list all available segments)

### Quick start

```html
<script src="https://your-segmint-host/js/segmint.min.js"></script>
<script>
  Segmint.init({ token: 'your-project-token', autoTrack: true })
    .then(function () {
      if (Segmint.visitor.hasSegment('high_intent')) {
        document.getElementById('cta').style.display = 'block';
      }
    });
</script>
```

### Track events

```js
// Track a custom event
await Segmint.visitor.event('add-to-cart', { product_id: 42, price: 29.99 });

// Fire-and-forget (page unload)
window.addEventListener('beforeunload', function () {
  Segmint.visitor.beacon('page-exit', { time_on_page: 45 });
});
```

### Read segments

```js
// Matched segments for the current visitor
Segmint.visitor.segments();           // [{ slug: 'high_intent', ... }]
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
| `visitor.event(type?, props?)` | `Promise<{status, segments}>` | Track event, update cached segments |
| `visitor.beacon(type?, props?)` | `void` | Fire-and-forget tracking (no response) |
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

- [Tracking SDK](docs/tracking-sdk.md) — Full SDK reference, payload format, and integration recipes
- [Segments API](docs/segments-api.md) — REST API for retrieving segments, CMS integration patterns

## License

[MIT](LICENSE.md)
