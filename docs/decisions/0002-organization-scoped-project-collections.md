# Organization-Scoped Project Collections

## Status

Accepted on 2026-06-12.

## Context

The project list was previously available at `/projects`, with the selected organization
stored in a query parameter or session. The page content could therefore change without
the URL identifying which organization's projects were displayed.

Individual projects already have globally unique public IDs, so they do not require an
organization namespace.

## Decision

Organizations receive immutable, randomly generated, globally unique `public_id` values.
Like project public IDs, they contain 12 case-sensitive alphanumeric characters.
Organization-scoped project collection operations use these routes:

```text
/organizations/{organization-public-id}/projects
/organizations/{organization-public-id}/projects/create
```

Creating a project is scoped to the organization in the route. The organization is not
accepted as a submitted form field.

`/projects` remains as a global entry point and redirects to the user's remembered,
owned, or first accessible organization.

Individual project resource routes remain globally addressed:

```text
/projects/{project-public-id}
/projects/{project-public-id}/segments
```

## Consequences

- Project collection URLs identify the organization whose data is displayed.
- Switching organizations changes the URL.
- Project creation cannot target a different organization than the route specifies.
- Organization and project names can change without changing URLs.
- Authorization is still required for every scoped route; public IDs do not grant access.
