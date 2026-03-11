# Segmint

**Self-hosted, event-driven audience segmentation platform.**

Segmint lets you define audience segments based on real-time events, track how users match those segments over time, and visualize engagement through built-in analytics — all within your own infrastructure.

## Features

- **Project-based organization** — Group segments, events, and access tokens under isolated projects
- **Rule-based segmentation** — Define segments with flexible rules that automatically evaluate incoming events
- **Real-time event tracking** — Ingest events via API and watch segment matches update in real time
- **Built-in analytics** — Per-project dashboards with charts for event trends, segment distribution, top segments, and recent activity
- **Token-based API access** — Generate scoped access tokens for each project to integrate with your applications
- **Two-factor authentication** — Secure your account with TOTP-based 2FA

## Tech Stack

| Layer       | Technology                          |
|-------------|-------------------------------------|
| Backend     | Laravel 12, PHP 8.2+               |
| Frontend    | Svelte 5, Inertia.js v2            |
| Styling     | Tailwind CSS 4                      |
| Charts      | Chart.js                            |
| Auth        | Laravel Fortify (headless)          |
| Routing     | Laravel Wayfinder (type-safe TS)    |
| Database    | PostgreSQL (production), SQLite (dev/test) |
| Build       | Vite, Bun                           |

## Requirements

- PHP 8.2+
- Composer
- Bun (or Node.js 18+)
- PostgreSQL (recommended) or SQLite

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

# Run migrations
php artisan migrate

# Build frontend assets
bun run build
```

## Development

```bash
# Start all dev services (Laravel server, queue worker, log viewer, Vite)
composer dev
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

Segmint exposes a token-authenticated API for event ingestion and segment evaluation. Each project has its own access tokens that can be managed from the project dashboard.

```bash
# Example: query segments via API
curl https://your-domain.test/api/segments?token=your-project-token
```

## Code Style

PHP code follows [Laravel Pint](https://laravel.com/docs/pint) conventions:

```bash
composer lint
```

## License

[MIT](LICENSE.md)
