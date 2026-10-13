This is the todo list, collected while i was testing the platform ./ analyzing the source code. I set some prefixes like [CODE] for code suggestions (refactor, bug fix etc), [UI] for the UI functionalities (adding CTA in the admin panel etc), [LOGIC] suggestion/feedback/request for the implementation logic / functionalities

[DONE] cast organization membership roles to `OrganizationRole` through a
custom Eloquent pivot while retaining a portable string database column.

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

[DONE] add contextual empty states with organization/project context and a
relevant next action where one exists. Management actions are only shown when
the current organization role permits them.

[UI][LOGIC] Later diagnostic enhancement: show regression deltas when re-running
a saved diagnostic scenario. Compare the previous `last_result` with the new
result before overwriting it, then highlight segments that changed from matched
to not matched or not matched to matched. Keep this out of the initial
diagnostic release unless users need explicit regression reporting.

## Public SaaS and tracking scalability roadmap

The current architecture is appropriate for development and a controlled beta.
Complete the launch-blocker items before enabling unrestricted public signup or
accepting substantial production tracking traffic. Validate capacity with load
tests rather than relying on an estimated requests-per-second target.

### Phase 1: public launch blockers

- [ ] [LOGIC] Add a transactional onboarding flow after registration that
  creates the user's organization, first project, and publishable tracking key.
- [ ] [LOGIC] Require email verification before project creation or event
  ingestion is enabled, and add registration abuse protection.
- [ ] [CODE][SECURITY] Replace the controller's partial validation with a
  dedicated tracking request that validates every field, nested property, URL,
  identifier, and maximum payload size.
- [ ] [CODE][SECURITY] Add tracking-specific rate limits by project key and IP,
  plus per-project event quotas with clear `429` and quota-exceeded responses.
- [ ] [CODE][SECURITY] Add configurable allowed origins per project. Treat keys
  embedded in browser code as publishable identifiers, not secrets.
- [ ] [CODE][SECURITY] Separate publishable ingestion keys from read/server
  credentials, give credentials explicit scopes, store verifiable hashes where
  possible, and stop sending read credentials in query strings.
- [ ] [CODE] Accept a client-generated event UUID/ULID and enforce idempotency
  per project so SDK retries cannot create duplicate events.
- [ ] [LOGIC] Define plan limits and usage metering before offering paid or
  unrestricted accounts.
- [ ] [PRIVACY] Define data retention, visitor deletion/export, consent
  guidance, and safeguards against collecting PII in URLs and event properties.

### Phase 2: scalable ingestion

- [ ] [CODE] Create a fast ingestion path: validate, authenticate, throttle,
  enqueue, and return `202 Accepted` for events that do not require an immediate
  segment response.
- [ ] [CODE] Process stored events in idempotent queue jobs on a managed queue
  rather than using the database queue. Keep immediate segment evaluation as a
  separate, deliberately synchronous path.
- [ ] [CODE] Avoid updating `access_tokens.last_used_at` on every event. Cache or
  debounce the timestamp and persist it periodically to remove the hot-row write.
- [ ] [CODE] Cache active segment definitions and rules per project, invalidating
  the cache whenever a segment or rule changes.
- [ ] [CODE] Add visitor aggregate state such as event counters, page counters,
  and last-seen timestamps. Evaluate count rules from this bounded state instead
  of repeatedly counting the complete `event_logs` history.
- [ ] [CODE][LOGIC] Replace per-event true/false `segment_matches` rows with a
  current `visitor_segments` membership table and, if needed, a compact
  membership-change history.
- [ ] [SDK] Add event batching, event IDs, bounded retries with exponential
  backoff and jitter, `429 Retry-After` support, and reliable page-exit flushing.
- [ ] [TEST] Add load tests for ordinary ingestion, batched ingestion, and
  synchronous evaluation. Track latency percentiles, error rate, and database
  queries/writes per event.

### Phase 3: storage and analytics growth

- [ ] [CODE] Add scheduled retention and pruning per plan, with optional export
  to object storage before deletion.
- [ ] [CODE] Build hourly and daily analytics rollups so dashboards do not scan
  or count the raw event table for every request.
- [ ] [CODE] Use cursor pagination for large event streams and cache or maintain
  filter dimensions such as event types and UTM sources.
- [ ] [DATABASE] Introduce time-based PostgreSQL partitioning when measured event
  volume and maintenance costs justify it.
- [ ] [DATABASE] Review indexes using production-shaped `EXPLAIN ANALYZE` results;
  do not keep adding overlapping indexes because each one increases ingestion
  write cost.
- [ ] [LOGIC] Evaluate a dedicated analytics store only after PostgreSQL,
  aggregation, retention, and partitioning have been measured and exhausted.

### Laravel Cloud production setup

- [ ] Configure a transactional email provider for production, verify the
  sending domain (SPF/DKIM/DMARC), set the production mail environment
  variables, and test verification, password-reset, and invitation delivery.
- [ ] Provision PostgreSQL, a Valkey/Redis-compatible cache, and a separate
  autoscaling queue/worker cluster in the same region as the app.
- [ ] Keep the production tracking endpoint always on; reserve hibernation for
  development, preview, or staging environments.
- [ ] Enable the Laravel scheduler for retention, rollups, quota maintenance,
  and cleanup tasks.
- [ ] Configure database backups and point-in-time recovery, then test a restore.
- [ ] Add observability for accepted/rejected events, p50/p95/p99 ingestion
  latency, queue depth and age, worker failures, database load/storage, quota
  usage, and segment-evaluation duration.
- [ ] Define alerts and an operational response for queue backlog, elevated
  error rates, ingestion latency, database capacity, and failed scheduled jobs.
- [ ] Consider Octane only after the ingestion/database work is measured; it
  improves PHP request throughput but does not remove query amplification.
