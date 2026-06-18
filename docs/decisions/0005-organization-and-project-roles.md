# Organization and Project Roles

## Decision

Segmint separates organization-level responsibility from project-level access.

Organization roles:

| Role | Purpose |
|---|---|
| Owner | Owns the organization, manages every organization capability, and implicitly administers every project |
| Admin | Manages organization membership and projects, and implicitly administers every project |
| Member | Belongs to the organization and accesses explicitly assigned projects |
| Guest | Belongs to the organization and accesses explicitly assigned projects |

Project roles for explicitly assigned members and guests:

| Role | Capabilities |
|---|---|
| Admin | View and edit project content; manage project settings, access tokens, and assignments |
| Editor | View analytics and events; edit segments, rules, and rule templates |
| Viewer | View the project, analytics, events, segments, and rule templates |

Organization owners and admins do not need explicit project assignments. Their project
role resolves to project admin for every project in their organization.

Organization members and guests require a `project_memberships` record. Its `role`
column stores `admin`, `editor`, or `viewer`.

## Why

Organization roles answer who can administer the workspace. Project roles answer what a
collaborator can do in a specific project. Keeping those concerns separate avoids
granting organization-wide access merely because someone needs to edit or inspect one
project.

`Member` and `Guest` currently use the same explicit-assignment rule. They remain
separate organization roles so future organization-level capabilities can distinguish
regular team members from external collaborators without changing project permissions.

## Migration

Before this decision, organization members could access every project and guest project
assignments had no role.

To preserve existing effective access:

- Existing organization members receive a project-admin assignment for every existing
  project in their organization.
- Existing guest assignments become project-viewer assignments.
- Existing pending invitation assignments become project-viewer assignments.

New projects are not automatically assigned to organization members or guests.

## Authorization Rules

- Project `view`: any resolved project role.
- Project `update`: project admin or editor.
- Project `manage`: project admin only.
- Project deletion: organization owner or admin.
- Organization membership and project creation: organization owner or admin.

Project roles never grant organization-management capabilities.

## Consequences

- Owners and organization admins have implicit access to every project.
- Members and guests receive least-privilege access per project.
- Invitations and assignment screens show both the organization role and each project
  role.
- Permission checks use project policies instead of inferring project permissions
  directly from organization membership.
