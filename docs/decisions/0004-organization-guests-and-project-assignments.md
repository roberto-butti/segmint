# Organization Guests and Project Assignments

## Decision

Segmint has one organization owner and supports `admin`, `member`, and `guest`
membership roles.

- Owners and admins manage organization membership.
- Members can access and manage every project in the organization.
- Guests belong to the organization but can only view explicitly assigned projects.
- Project assignments are stored in `project_memberships`.
- Owners can manage admins, members, and guests.
- Admins can manage members and guests, but cannot manage the owner or other admins.

Organization invitations are durable records associated with an email address. They are
sent by email and shown in Segmint when the invited email belongs to a logged-in user.
Guest invitations may include initial project assignments.

Existing `viewer` memberships are migrated to `guest` and initially assigned to every
project they could previously access. This preserves access while allowing owners and
admins to narrow those assignments later.

## Context

Organization membership is useful for administration and discovery, but granting every
organization member access to every project is too broad for clients, contractors, and
other limited collaborators. A guest role keeps those users visible to organization
administrators while making project access explicit.

## Consequences

- Project authorization must consider both organization role and guest assignments.
- Guests can open a limited organization dashboard showing their organization context
  and assigned projects. Organization-wide analytics and unassigned projects remain
  hidden.
- Changing a guest to member or admin removes explicit project assignments because the
  broader role already grants access to every project.
- Future project-specific permission levels can extend `project_memberships` without
  changing the organization role model.
