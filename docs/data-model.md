# Data Model: Users, Organizations & Projects

This document describes how users, organizations, and projects relate to each other in Segmint.

## Overview

```
User
├── owns 0 or 1 Organization (via users.owned_organization_id)
└── belongs to many Organizations (via organization_memberships)
     └── each with a role: admin, member, or viewer

Organization
├── has 1 owner (the User whose owned_organization_id points here)
├── has many members (via organization_memberships)
└── has many Projects

Project
├── belongs to 1 Organization
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
| `name` | string | Display name |
| `slug` | string | URL-safe identifier (unique) |

### Members

Members are linked via the `organization_memberships` pivot table:

| Field | Type | Description |
|---|---|---|
| `organization_id` | FK | The organization |
| `user_id` | FK | The user |
| `role` | string | `admin`, `member`, or `viewer` |

A user can only appear once per organization (unique compound index on `organization_id + user_id`).

### Roles

| Role | Manage projects | Manage segments & rules | View projects & data | Manage org settings |
|---|---|---|---|---|
| **Admin** | Yes | Yes | Yes | Yes |
| **Member** | Yes | Yes | Yes | No |
| **Viewer** | No | No | Yes | No |

The organization **owner** has the `admin` role in the pivot. Ownership is determined by `users.owned_organization_id`, not by the role value.

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
| `slug` | string | URL-safe identifier (used in URLs) |
| `description` | text (nullable) | Optional description |
| `active` | boolean | Whether the project is receiving events |

### Auto-provisioning

When a project is created, the following are automatically generated:
- **12 default rule templates** (UTM matching, visit counts, language detection, etc.)
- **1 default access token** (64-character random string, named "Default")

### Access control

Project access is determined by organization membership:
- If a user belongs to the project's organization → they can access the project
- Their permissions depend on their role in that organization (see roles table above)

There is no per-project access control. If you're in the org, you see all its projects.

## How it all connects

### User creates a project

1. User selects which organization to create the project in (must have `admin` or `member` role)
2. Project is created with `organization_id` pointing to the chosen org
3. Default rule templates and access token are auto-created
4. All members of that organization can now see the project

### User views projects

The `/projects` page shows all projects the user can access, filtered by organization:
- A dropdown lists all organizations the user belongs to
- The user's owned organization appears first
- Selecting an org filters the project list
- The selection is remembered in the session

### Authorization flow

```
Request to /projects/{slug}/segments
  → Route resolves Project by slug
  → ProjectPolicy::view(User, Project)
    → Does User belong to Project's Organization?
      → Yes: allow (any role can view)
      → No: 403 Forbidden
```

For mutations (create/update segments, manage templates):
```
ProjectPolicy::update(User, Project)
  → User's role in Project's Organization
    → admin or member: allow
    → viewer: 403 Forbidden
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
  ├── name
  └── slug (unique)

organization_memberships
  ├── id
  ├── organization_id (FK → organizations)
  ├── user_id (FK → users)
  ├── role (admin | member | viewer)
  └── unique(organization_id, user_id)

projects
  ├── id
  ├── organization_id (FK → organizations)
  ├── name
  ├── slug
  ├── description
  └── active
```

## Examples

### User who owns an org and is invited to others

```
Alice (owned_organization_id: 1)
  ├── Organization "Alice's Startup" (id: 1) → role: admin (owner)
  │   ├── Project "Marketing Site"
  │   └── Project "Mobile App"
  ├── Organization "Acme Corp" (id: 2) → role: admin (invited)
  │   └── Project "Acme Website"
  └── Organization "Agency Pro" (id: 3) → role: viewer (invited)
      └── Project "Client Campaign"
```

Alice can:
- Create/edit/delete projects in "Alice's Startup" (admin + owner)
- Create/edit projects in "Acme Corp" (admin)
- Only view projects in "Agency Pro" (viewer)

### User with no owned org

```
Bob (owned_organization_id: null)
  ├── Organization "Acme Corp" → role: member
  └── Organization "Agency Pro" → role: viewer
```

Bob doesn't own any organization. He can create projects in "Acme Corp" (member) but can only view in "Agency Pro" (viewer). The dashboard prompts him to create his own organization.
