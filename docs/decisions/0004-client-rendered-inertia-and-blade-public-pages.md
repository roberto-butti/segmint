# Client-Rendered Inertia and Blade Public Pages

## Status

Accepted on 2026-06-13.

## Context

Segmint's application interface is primarily an authenticated dashboard. These pages do
not need search-engine indexing or server-rendered HTML, while enabling Inertia SSR adds
an additional production process and requires every Svelte component and dependency to
support server rendering and hydration.

The public website is expected to remain small, consisting of pages such as the homepage
and documentation. These pages benefit from directly rendered HTML but do not require
the application interface or client-side navigation.

## Decision

Inertia SSR is disabled in both the Laravel adapter and the Inertia Vite plugin.
Authenticated application pages use client-rendered Svelte and Inertia.

Public content pages use Laravel Blade rather than Inertia SSR. This keeps their HTML
server-rendered without introducing an SSR runtime for the application. Public pages use
a CSS-only Vite entry and may use progressively enhanced JavaScript where needed, but
they must not depend on the Inertia application shell.

## Consequences

- Production deployments do not build an Inertia SSR bundle or run an Inertia SSR
  process.
- Application pages require JavaScript for their initial render and navigation.
- Public pages can provide server-rendered metadata and content through Blade.
- The public homepage does not load the Inertia or Svelte application runtime.
- Shared visual styles or components between Blade and Svelte may require separate
  implementations.
- New public pages should use Blade unless this decision is explicitly revisited.
