# Global and Organization Dashboards

## Decision

`/dashboard` is the authenticated user's organization overview. It lists only
organizations the user can access and shows enough context to choose one:
membership level, project counts, and member count.

Organization operational data belongs at:

```text
/organizations/{organization_public_id}/dashboard
```

The organization dashboard contains aggregate metrics and project summaries
scoped to that organization. Individual project dashboards continue to use
globally unique project public IDs.

## Rationale

A global dashboard cannot present project and event metrics without losing the
organization context in which permissions and projects are managed. Requiring
an organization selection before showing operational metrics keeps the URL,
authorization boundary, navigation, and displayed data aligned.

The organization dashboard reports only metrics represented by the current data
model. Storage usage and quotas are intentionally omitted until they are
measured and persisted.

## Consequences

- Users with multiple memberships can clearly distinguish ownership and roles.
- Organization aggregate queries must always be scoped through that
  organization's projects.
- Visiting an organization dashboard updates the remembered organization used
  by the `/projects` redirect.
