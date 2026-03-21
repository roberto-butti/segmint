# Segments API

The Segments API lets you retrieve the list of active segments defined for a project. This is useful for integrating Segmint with your CMS or content platform, so content editors can associate pieces of content (banners, hero sections, product recommendations, etc.) with specific audience segments.

## Why you need this

When building personalised experiences, the content side and the segmentation side need a shared vocabulary. A content editor creating a "Welcome back" banner in the CMS needs to know which segments exist and pick the right one. The Segments API provides that bridge:

1. **CMS integration** — Fetch the segment list to populate a dropdown in your CMS, so editors can tag content with a target segment (e.g. "show this banner to `high_intent` visitors").
2. **External tools** — Feed the segment list into A/B testing platforms, email tools, or ad systems that need to know your audience definitions.

> To render personalised content on the frontend based on visitor segments, see the [Tracking SDK documentation](tracking-sdk.md).

## Endpoint

```
GET /api/segments?token={your-project-token}
```

### Authentication

The endpoint is authenticated via the project access token, passed as a query parameter. This is the same token used by the tracking SDK.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `token` | `string` | Yes | The project access token. |

### Response

Returns a JSON array of all **active** segments for the project.

```json
[
  {
    "id": 1,
    "project_id": 1,
    "name": "Google Visitors",
    "slug": "google_visitors",
    "description": "Users who arrive from Google UTM source",
    "active": true,
    "value": "google_visitors",
    "created_at": "2026-03-21T10:00:00.000000Z",
    "updated_at": "2026-03-21T10:00:00.000000Z"
  },
  {
    "id": 2,
    "project_id": 1,
    "name": "High Intent",
    "slug": "high_intent",
    "description": "Visitors with more than 3 pageviews",
    "active": true,
    "value": "high_intent",
    "created_at": "2026-03-21T10:00:00.000000Z",
    "updated_at": "2026-03-21T10:00:00.000000Z"
  }
]
```

#### Segment fields

| Field | Type | Description |
|---|---|---|
| `id` | `integer` | Unique segment identifier. |
| `project_id` | `integer` | The project this segment belongs to. |
| `name` | `string` | Human-readable segment name (e.g. "Google Visitors"). |
| `slug` | `string` | URL-safe identifier used for matching (e.g. `google_visitors`). This is the value returned by the tracking SDK. |
| `description` | `string\|null` | Optional description of the segment's purpose. |
| `active` | `boolean` | Always `true` in the response (inactive segments are filtered out). |
| `value` | `string` | Alias for `slug`. Provided for convenience when binding to frontend components. |
| `created_at` | `string` | ISO 8601 timestamp. |
| `updated_at` | `string` | ISO 8601 timestamp. |

### Error responses

| Status | Condition | Body |
|---|---|---|
| `404` | `token` parameter is missing | `token_mandatory` |
| `404` | Token does not match any active project | `token_not_valid` |

## Retrieving segments

### Using the Segmint SDK

If you already have the Segmint SDK loaded, use `Segmint.fetch.segments()`:

```js
const allSegments = await Segmint.fetch.segments();
console.log(allSegments);
// [{ id: 1, name: "Google Visitors", slug: "google_visitors", ... }, ...]
```

### Using the REST API directly

If your CMS or integration environment doesn't allow loading external JavaScript libraries, you can call the API directly.

**cURL:**

```bash
curl "https://your-segmint-host/api/segments?token=your-project-token"
```

**JavaScript (fetch):**

```js
const response = await fetch('https://your-segmint-host/api/segments?token=your-project-token');
const segments = await response.json();
```

**PHP:**

```php
$response = Http::get('https://your-segmint-host/api/segments', [
    'token' => 'your-project-token',
]);

$segments = $response->json();
```

The response is identical regardless of how you call it — the SDK method is simply a convenience wrapper around this endpoint.

## CMS integration patterns

### Populating a segment picker

Fetch the segment list and render it as a dropdown or multi-select in your CMS editor:

```js
// Using the SDK
async function loadSegmentOptions() {
  const segments = await Segmint.fetch.segments();

  return segments.map(segment => ({
    label: segment.name,
    value: segment.slug,
    description: segment.description,
  }));
}
```

If the SDK is not available in your CMS environment, use a direct API call instead:

```js
async function loadSegmentOptions() {
  const response = await fetch('https://your-segmint-host/api/segments?token=your-project-token');
  const segments = await response.json();

  return segments.map(segment => ({
    label: segment.name,
    value: segment.slug,
    description: segment.description,
  }));
}
```

This gives content editors a list like:

| Label | Slug |
|---|---|
| Google Visitors | `google_visitors` |
| Italy Traffic | `italy_traffic` |
| High Intent | `high_intent` |
| Campaign A Visitors | `campaign_visitors` |

The editor picks a segment, and the CMS stores the `slug` alongside the content block. At render time, the frontend uses the [Tracking SDK](tracking-sdk.md) to check if the current visitor matches that segment and decides what to display.

## Relationship to the tracking SDK

The Segments API and the tracking SDK serve different purposes:

| | `Segmint.fetch.segments()` | `Segmint.visitor.segments()` |
|---|---|---|
| **Who uses it** | CMS, backend services, content editors | End-user browsers |
| **When** | At build/edit time | At runtime (page load) |
| **What it returns** | All active segments for the project | Only segments the current visitor matches |
| **HTTP method** | `GET /api/segments` | Result of `POST /api/event-log/track` |
| **Use case** | "What segments exist?" | "Does this visitor belong to segment X?" |

Together they form the complete personalisation loop: `fetch.segments()` tells your CMS *what audiences you can target*, and `visitor.hasSegment()` tells your frontend *which audience the current visitor belongs to*.
