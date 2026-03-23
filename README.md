# Segmint

**Self-hosted, real-time audience segmentation for modern web apps.**

Segmint lets you define audience segments based on real-time events, track how visitors match those segments, and deliver personalised content — all within your own infrastructure.

## Why Segmint

**Know your audience. Personalise in real time. Own your data.**

Most personalisation tools either lock you into expensive SaaS platforms or require complex enterprise setups. Segmint gives you the same capabilities — self-hosted, open source, and developer-first.

### Business value

- **Higher conversions** — Show returning visitors a "Welcome back" offer, match campaign traffic with campaign-specific content, surface relevant CTAs based on engagement. Personalised experiences convert better.
- **Zero marginal cost** — No per-event pricing. No per-user fees. Runs on your existing infrastructure. At 100K monthly visitors, SaaS alternatives cost thousands — Segmint costs nothing.
- **Full data ownership** — Visitor data never leaves your servers. No third-party processors, no DPA negotiations, no GDPR complications with external vendors.
- **CMS-agnostic** — Works with any CMS or headless setup. Fetch segments via API, tag content with audiences, render the right content. Storyblok, Contentful, WordPress, or your own CMS.

### Key features

- **Smart segment suggestions** — Segmint analyzes your actual event data and suggests segments you should create — with pre-built rules, confidence levels, and one-click creation. It detects top UTM sources, campaigns, referrer domains, returning visitors, and frequent page visitors automatically. No other self-hosted tool does this.
- **Real-time matching** — Segments are evaluated on every event. A visitor's third page view immediately triggers a "high intent" segment. No batch processing, no overnight jobs.
- **Lightweight SDK** — Under 5KB. One script tag, 4 lines of code to personalise. Auto-detects the API endpoint from its own URL.
- **Flexible rules** — Comparison, visit count, page views, browser language — combine rules to define any audience.
- **Rule templates** — Per-project reusable presets. Default templates are created automatically when you start a project.
- **Organizations & teams** — Multi-org support with role-based access (admin, member, viewer). One user can own an org and be invited to others.
- **Built-in analytics** — Per-project dashboards with event trends, segment distribution, top audiences, and real-time activity.
- **Playground** — Built-in HTML playground to test segment visibility with your actual data, directly from the access tokens page.

## How it works

1. **Track events** — Drop the SDK on your site. It captures page views, UTM parameters, referrers, and custom events automatically.
2. **Define segments** — Create audience segments with rules, or let Segmint suggest them from your data.
3. **Personalise content** — The SDK tells you which segments the current visitor matches. Show different content to different audiences.

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
- **`Segmint.fetch.*`** — Query project-level data (e.g. list all available segments for CMS integration)

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
- [Data Model](docs/data-model.md) — Users, Organizations, and Projects architecture

## License

[AGPL-3.0](LICENSE.md) with an SDK exception:

- **Server-side code** (PHP, Svelte, etc.) — AGPL-3.0. Anyone who modifies and deploys it as a service must open-source their changes.
- **JavaScript SDK** (`segmint.js` / `segmint.min.js`) — MIT. Users can embed it in their sites without AGPL obligations. The SDK's permissive license ensures it doesn't contaminate your client-side code.
