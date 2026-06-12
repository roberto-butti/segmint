# Project Public Identifiers

## Status

Accepted on 2026-06-12.

## Context

Projects belong to organizations, but project routes previously used a slug derived from
the project name:

```text
/projects/{project-slug}
```

Project slugs were not globally unique. Projects in different organizations could
therefore have the same route value, making route-model binding ambiguous. Scoping the
slug to an organization would require longer routes and would still introduce rename,
validation, collision, and redirect-history concerns.

## Decision

Each project has an immutable, randomly generated, globally unique `public_id`.

Project routes use the public ID directly:

```text
/projects/{project-public-id}
/projects/{project-public-id}/segments
```

The organization is not included in project routes because the project public ID already
identifies one project globally. Authorization continues to be determined through the
project's organization membership.

Internal numeric IDs remain the primary and foreign keys. Project names remain editable
display values and are not used for routing.

Public IDs:

- are generated automatically when a project is created;
- contain 12 case-sensitive alphanumeric characters (`a-z`, `A-Z`, and `0-9`);
- have a database-level global unique constraint;
- are immutable and are not editable in the UI;
- identify resources but do not grant access.

## Consequences

- Project names can change without changing project URLs.
- Projects in any organizations can have the same name.
- Route-model binding is unambiguous without organization-scoped project routes.
- URLs do not communicate the project or organization name, so the application UI must
  provide that context through navigation and breadcrumbs.
- Existing project slug URLs stop working after the migration. Redirect history is not
  retained because project slugs were not reliable unique identifiers.
