# Feature Architecture Template

**Hook coverage:** `feature-architecture-generator`.  
Use this outline when documenting a new feature or major refactor.

## 1) Goal & Scope
- Problem statement, target users, success metrics.
- In/Out of scope items.

## 2) Domain Model & Data
- Entities, relationships, tenant ownership (`tenant_id`), pivots/history.
- Validation rules, enums/states, indexes.
- Migrations/backfills plan (additive → backfill → dual-write → cutover → cleanup).

## 3) Flows & UX
- Primary flows (create/update/read/delete), edge cases, error handling.
- API/route list (methods, paths, auth/policy, request/response shapes).
- Filament/admin surfaces: pages, relation managers, widgets; tenant scoping.

## 4) Services & Repositories
- Service responsibilities, transactions, events.
- Repository/query patterns (projections, filters, pagination).

## 5) Performance & Reliability
- N+1 avoidance, eager loading, counts/aggregates.
- Caching considerations, background jobs, rate limits/throttles.
- Observability: logs (include tenant_id/user_id), metrics to watch.

## 6) Security & Compliance
- Policies/guards, tenant isolation, PII handling.
- Input validation, file upload/storage rules.
- Audit trails/history as needed.

## 7) Testing Plan
- Unit/service tests, feature/API tests, tenancy/policy checks.
- Seed/data setup (factories with tenant helpers), fixtures.

## 8) Rollout & Migration
- Feature flags/toggles, phased rollout steps, rollback plan.
- Data migration/backfill steps with validation.

## 9) Open Questions / Risks
- List unknowns, trade-offs, and mitigation ideas.

Include diagrams or sequence charts when helpful. Link this doc from [HOOKS_DOCUMENTATION_MAP.md](../reference/HOOKS_DOCUMENTATION_MAP.md) when used.*** End Patch
