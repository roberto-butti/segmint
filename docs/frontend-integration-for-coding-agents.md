# Frontend Integration Guide for Coding Agents

Use this document as the implementation contract when asking Codex, Claude Code, or
another coding agent to integrate Segmint into a frontend application.

For the complete SDK API, payloads, and additional recipes, see
[Tracking SDK](tracking-sdk.md).

## How to use this guide

Copy this file into the target frontend repository, attach it to the coding-agent
session, or give the agent this URL:

```text
https://github.com/roberto-butti/segmint/blob/main/docs/frontend-integration-for-coding-agents.md
```

Include the required inputs below in the task. The agent should stop and request any
missing required input instead of guessing production configuration or segment behavior.

## Required inputs

Give the coding agent these values before implementation:

| Input | Example | Purpose |
|---|---|---|
| Segmint host | `https://segmint.example.com` | Hosts the SDK and tracking API |
| Project access token | `project-token` | Associates events with a Segmint project |
| Segment slugs | `high_intent`, `returning_visitor` | Select content for matched visitors |
| Tracking consent rule | `required before tracking` | Determines when the SDK may initialise |

The project access token is sent by the browser and must be treated as a client-visible
identifier. Never expose Segmint user credentials or other server-side secrets.

## Agent implementation contract

When integrating Segmint, follow these rules:

1. Inspect the frontend framework, routing, environment-variable conventions, and
   existing analytics abstractions before editing.
2. Load `https://<segmint-host>/js/segmint.min.js` once as a classic browser script.
   The current SDK exposes `window.Segmint`; do not invent an npm package or ES-module
   import.
3. Store the Segmint host and project token in the frontend application's existing
   environment configuration. Do not hard-code production values in source files.
4. Initialise Segmint only in the browser and only after any required tracking consent.
5. Track exactly one initial page view. For a multi-page application, use
   `autoTrack: true`. For an SPA, either use `autoTrack: true` and track only subsequent
   route changes, or use `autoTrack: false` and explicitly track every route including
   the initial route.
6. Centralise SDK access in one integration module or service. UI components should use
   that abstraction instead of repeatedly initialising Segmint.
7. Wait for the initial tracking response before evaluating segments. Segment checks
   made before `init({ autoTrack: true })` resolves will use an empty cache.
8. Fail open when tracking is unavailable: render the default content and do not block
   page usage.
9. Use segmentation only for presentation and personalisation. Never use a client-side
   segment check as authorization or to protect sensitive data.
10. Reset the Segmint visitor identity on logout when the application's identity and
    privacy model requires the next user to start a separate visitor history.

## Recommended integration shape

Create one frontend integration module with a small API:

```js
// segmint-client.js
const host = FRONTEND_SEGMINT_HOST;
const token = FRONTEND_SEGMINT_TOKEN;

let readyPromise;

export function initialiseSegmint() {
  if (readyPromise) {
    return readyPromise;
  }

  readyPromise = loadScript(`${host}/js/segmint.min.js`)
    .then(() => window.Segmint.init({ token, autoTrack: true }))
    .then(() => window.Segmint)
    .catch((error) => {
      console.warn('Segmint is unavailable; using default content.', error);
      return null;
    });

  return readyPromise;
}

export async function hasSegment(slug) {
  const segmint = await initialiseSegmint();

  return segmint?.visitor.hasSegment(slug) ?? false;
}

export async function trackEvent(type, properties = {}) {
  const segmint = await initialiseSegmint();

  return segmint?.visitor.event(type, properties);
}

function loadScript(src) {
  if (window.Segmint) {
    return Promise.resolve();
  }

  return new Promise((resolve, reject) => {
    const existing = document.querySelector(`script[src="${src}"]`);

    if (existing) {
      existing.addEventListener('load', resolve, { once: true });
      existing.addEventListener('error', reject, { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = src;
    script.async = true;
    script.addEventListener('load', resolve, { once: true });
    script.addEventListener('error', reject, { once: true });
    document.head.append(script);
  });
}
```

Adapt the environment-variable names and module syntax to the target frontend. If the
application already loads the SDK in its HTML template, omit `loadScript()` and keep the
same centralised initialisation and error-handling behavior.

## Personalising content

Render stable default content while Segmint initialises, then replace or enhance it when
the visitor matches a segment:

```js
const showHighIntentContent = await hasSegment('high_intent');

renderHero(showHighIntentContent ? highIntentHero : defaultHero);
```

For component frameworks, keep three explicit states:

```text
loading -> render stable default or reserved placeholder
matched -> render personalised component
not matched or error -> render default component
```

Avoid briefly rendering sensitive or strongly personalised content before the segment
result is known. Reserve layout space where necessary to avoid content shifts.

When multiple components depend on segmentation, initialise once and share the resolved
segment set or `hasSegment()` service. Do not send a separate page-view event from every
component.

## Tracking pages and events

### Multi-page application

Initialise once on each full page load:

```js
await window.Segmint.init({
  token: FRONTEND_SEGMINT_TOKEN,
  autoTrack: true,
});
```

### Single-page application

After the initial page view, track each completed client-side route change:

```js
router.afterEach(() => {
  window.Segmint.visitor.event('page-view').catch(() => {
    // Tracking failure must not break navigation.
  });
});
```

Register the route hook after the initial tracked page view, or explicitly skip its first
invocation, to avoid duplicate initial events.

### Meaningful interactions

Track product-relevant events with stable event names and small structured properties:

```js
await trackEvent('add-to-cart', {
  product_id: product.id,
  category: product.category,
});
```

Do not send passwords, authentication tokens, payment details, or unnecessary personal
data in event properties.

## Verification checklist

The coding agent must verify all applicable items:

- The SDK script is loaded once.
- The project token comes from the frontend environment configuration.
- Tracking waits for required consent.
- Exactly one initial `page-view` event is sent.
- SPA navigation sends one additional `page-view` per completed route change.
- `hasSegment()` is evaluated only after initialisation resolves.
- Matched visitors see personalised content.
- Unmatched visitors and tracking failures see default content.
- Tracking failures do not break rendering, navigation, or form submission.
- Logout resets the visitor only when required by the application's identity policy.
- No sensitive data is included in event properties.
- Automated tests cover matched, unmatched, loading, and failure behavior where the
  frontend test setup supports them.

## Prompt template

Use this prompt when assigning the integration:

```text
Integrate Segmint into this frontend using
docs/frontend-integration-for-coding-agents.md as the implementation contract.

Segmint host: <host>
Project access token environment variable: <variable name>
Tracking consent requirement: <requirement>
Segments and intended content:
- <segment-slug>: <content/component behavior>

Inspect the existing framework and conventions first. Implement centralised
initialisation, initial and SPA page-view tracking where applicable, resilient default
content, relevant tests, and documentation of the chosen integration point. Do not use
client-side segments for authorization.
```
