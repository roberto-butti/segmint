# Data Model: Users, Organizations & Projects

This document describes how users, organizations, and projects relate to each other in Segmint.

## Overview

```
User
├── owns 0 or 1 Organization (via users.owned_organization_id)
├── belongs to many Organizations (via organization_memberships)
│    └── each with a role: admin, member, or guest
├── receives guest Project assignments (via project_memberships)
└── favorites many Projects (via favorite_projects)

Organization
├── has 1 owner (the User whose owned_organization_id points here)
├── has many members (via organization_memberships)
├── has many Projects
└── has many Invitations

Project
├── belongs to 1 Organization
├── has many assigned guest users
├── has many Segments
├── has many Access Tokens
├── has many Rule Templates
└── has many Event Logs
```

## Users

A user is anyone with an account in Segmint. Users authenticate via email/password with optional two-factor authentication.

A user can:
- **Own** at most one organization (0 or 1)
- **Belong to** many organizations with different roles
- **Favorite** accessible projects for personal quick access

Project favorites are personal to each user. Favoriting a project does not change its
state or affect how other organization members see it. The project collection displays
the current user's favorites separately from their other accessible projects.

### Ownership

Ownership is tracked on the `users` table via `owned_organization_id` — a nullable foreign key with a unique constraint. This guarantees:
- A user can own at most **one** organization
- An organization can have at most **one** owner

The owner always has `admin` role in the `organization_memberships` pivot table. Ownership is a separate concept from the role — the role determines permissions, ownership determines who "owns" the org.

## Organizations

An organization is a workspace that groups projects and team members.

| Field | Type | Description |
|---|---|---|
| `id` | integer | Primary key |
| `public_id` | string | Immutable, globally unique public route identifier |
| `name` | string | Display name |
| `slug` | string | URL-safe identifier (unique) |

### Members

Members are linked via the `organization_memberships` pivot table:

| Field | Type | Description |
|---|---|---|
| `organization_id` | FK | The organization |
| `user_id` | FK | The user |
| `role` | string | `admin`, `member`, or `guest` |

A user can only appear once per organization (unique compound index on `organization_id + user_id`).

### Roles

| Role | Manage projects | Manage segments & rules | Project access | Manage org settings |
|---|---|---|---|---|
| **Admin** | Yes | Yes | Yes | Yes |
| **Member** | Yes | Yes | Yes | No |
| **Guest** | No | No | Assigned projects only | No |

The organization **owner** has the `admin` role in the pivot. Ownership is determined by `users.owned_organization_id`, not by the role value.

### Invitations

Owners and admins can invite users by email. Invitations expire after seven days and
can be accepted or declined from the user's internal invitations page. Existing users
also receive a database notification; users without an account receive the email and
see the pending invitation after registering with the invited address.

Owners can assign any role. Admins can manage members and guests, but cannot invite,
change, or remove another admin or the owner. Guest invitations may include initial
project assignments.

### Who is the owner?

```
Owner = the User where users.owned_organization_id = organization.id
```

This is a one-to-one relationship:
- `User::ownedOrganization()` — belongsTo, returns the org the user owns (or null)
- `User::isOwnerOf($organization)` — checks if `owned_organization_id === $organization->id`

## Projects

A project belongs to one organization. It groups segments, access tokens, rule templates, and event logs.

| Field | Type | Description |
|---|---|---|
| `id` | integer | Primary key |
| `organization_id` | FK | The organization this project belongs to |
| `name` | string | Display name |
| `public_id` | string | Immutable, globally unique public route identifier |
| `description` | text (nullable) | Optional description |
| `active` | boolean | Whether the project is receiving events |

### Auto-provisioning

When a project is created, the following are automatically generated:
- **12 default rule templates** (UTM matching, visit counts, language detection, etc.)
- **1 default access token** (64-character random string, named "Default")

The default token value is shown once after project creation. Project managers can create
additional tokens, revoke or reactivate existing tokens, and rotate token values.

Stored token values are not returned by the access-token management page. Creation and
rotation reveal the new value through one-request flash data so it can be copied into the
client configuration. After leaving or refreshing the page, the value cannot be
recovered from the UI and must be rotated if it was not saved.

Revoking a token prevents it from resolving a project. Rotating a token immediately
invalidates its previous value and preserves its current active or revoked status.
Successful stored-event tracking and active-segment API requests update the token's
last-used timestamp. Dry-run evaluation does not update it because dry-run is
side-effect free.

### Access control

Project access is determined by organization membership and guest assignments:
- Owners, admins, and members can access every project in the organization.
- Guests can only access projects explicitly linked through `project_memberships`.

## How it all connects

### User creates a project

1. User opens the create page within an organization's project collection URL (must have `admin` or `member` role)
2. Project is created with `organization_id` pointing to the chosen org
3. Default rule templates and access token are auto-created
4. Owners, admins, and members can see the project; guests require an explicit assignment

### User views projects

The `/organizations/{organization_public_id}/projects` page shows projects belonging to
the organization identified in the URL:
- A dropdown lists all organizations the user belongs to
- Selecting an organization navigates to its project collection URL
- `/projects` redirects to the remembered, owned, or first accessible organization

### Authorization flow

```
Request to /projects/{public_id}/segments
  → Route resolves Project by its globally unique public ID
  → ProjectPolicy::view(User, Project)
    → admin or member: allow
    → assigned guest: allow
    → otherwise: 403 Forbidden
```

For mutations (create/update segments, manage templates):
```
ProjectPolicy::update(User, Project)
  → User's role in Project's Organization
    → admin or member: allow
    → guest: 403 Forbidden
    → not a member: 403 Forbidden
```

## Database schema

```
users
  ├── id
  ├── name
  ├── email
  ├── owned_organization_id (nullable, unique FK → organizations)
  └── ...

organizations
  ├── id
  ├── public_id (unique)
  ├── name
  └── slug (unique)

organization_memberships
  ├── id
  ├── organization_id (FK → organizations)
  ├── user_id (FK → users)
  ├── role (admin | member | guest)
  └── unique(organization_id, user_id)

projects
  ├── id
  ├── public_id (unique)
  ├── organization_id (FK → organizations)
  ├── name
  ├── description
  └── active

favorite_projects
  ├── user_id (FK → users)
  ├── project_id (FK → projects)
  └── unique(user_id, project_id)

project_memberships
  ├── user_id (FK → users)
  ├── project_id (FK → projects)
  └── unique(user_id, project_id)

organization_invitations
  ├── organization_id (FK → organizations)
  ├── invited_by_id (FK → users)
  ├── email
  ├── role
  ├── expires_at
  └── accepted_at | declined_at | revoked_at
```

Project routes use immutable public IDs rather than names or slugs. See
[Project Public Identifiers](decisions/0001-project-public-identifiers.md) for the
decision and its tradeoffs.

Project collection routes use organization public IDs. See
[Organization-Scoped Project Collections](decisions/0002-organization-scoped-project-collections.md).

The global dashboard lists the user's accessible organizations. Operational
metrics are shown on organization-scoped dashboards. See
[Global and Organization Dashboards](decisions/0003-global-and-organization-dashboards.md).

Guest access and project assignments are described in
[Organization Guests and Project Assignments](decisions/0004-organization-guests-and-project-assignments.md).

## Examples

### User who owns an org and is invited to others

```
Alice (owned_organization_id: 1)
  ├── Organization "Alice's Startup" (id: 1) → role: admin (owner)
  │   ├── Project "Marketing Site"
  │   └── Project "Mobile App"
  ├── Organization "Acme Corp" (id: 2) → role: admin (invited)
  │   └── Project "Acme Website"
  └── Organization "Agency Pro" (id: 3) → role: guest (invited)
      └── assigned Project "Client Campaign"
```

Alice can:
- Create/edit/delete projects in "Alice's Startup" (admin + owner)
- Create/edit projects in "Acme Corp" (admin)
- Only view explicitly assigned projects in "Agency Pro" (guest)

### User with no owned org

```
Bob (owned_organization_id: null)
  ├── Organization "Acme Corp" → role: member
  └── Organization "Agency Pro" → role: guest
```

Bob doesn't own any organization. He can create projects in "Acme Corp" (member) but
can only view explicitly assigned projects in "Agency Pro" (guest). The global dashboard
lists both organizations with his role in each.
