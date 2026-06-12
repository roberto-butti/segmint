This is the todo list, collected while i was testing the platform ./ analyzing the source code. I set some prefixes like [CODE] for code suggestions (refactor, bug fix etc), [UI] for the UI functionalities (adding CTA in the admin panel etc), [LOGIC] suggestion/feedback/request for the implementation logic / functionalities

[CODE] define the roles not as string but more typed like:
in the organization_memberships: $table->string('role')->default('member');

[DONE] replace project slugs with globally unique immutable public IDs. Project routes use `/projects/<public-id>`; see `docs/decisions/0001-project-public-identifiers.md`.
[DONE] namespace project collection and creation routes by organization public ID; see `docs/decisions/0002-organization-scoped-project-collections.md`.
[DONE] separate the global organization overview from organization-scoped dashboards; see `docs/decisions/0003-global-and-organization-dashboards.md`.
So the projects page should be https://segmint.test/projects/3/ instead of https://segmint.test/projects?organization_id=3

[DONE] include the organization context in project breadcrumbs:
`Organizations / Acme / Projects / Acme Website / Segments`.

[DONE] remove the organization switcher from the organization-scoped project
collection. Organization switching belongs in global navigation rather than on a
URL-scoped collection page.

[DONE] make the sidebar contextual with organization and project switchers,
organization navigation, and project-section navigation.

[UI] when some screen is empty because no data , add the CTA for creating a new data directly in the message . For exaple if i don't have any segment in a project , at the moment is shown:
No segments yet. Create your first segment to get started.
Maybe we can: provide more context (org and project where the user is), and the CTA for creating a new segment (even though the CTA is alrready present in the right top side).
