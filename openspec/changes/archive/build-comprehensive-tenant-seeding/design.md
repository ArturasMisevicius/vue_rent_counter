## Context
The project has many seeders with overlapping concerns and uneven factory coverage. The user requested one realistic tenant-centric dataset that exercises the full application model graph (core + auxiliary) and aligns with existing role/tenant logic.

A quick inventory shows many models currently lack matching factories, which blocks consistent factory-driven generation for full-project datasets.

## Goals / Non-Goals
- Goals:
  - Provide one comprehensive, realistic synthetic dataset for `tenant_id = 1`.
  - Cover both core and auxiliary models used by the application.
  - Use factories as the primary mechanism for record generation.
  - Ensure deterministic and idempotent behavior so seeding can be re-run safely.
  - Keep data logically consistent with project tenant hierarchy and domain rules.
- Non-Goals:
  - Seeding multiple independent tenant universes in this change.
  - Production data migration or import from real customer records.
  - Re-architecting domain models beyond what is needed for factory/seeder support.

## Decisions
- Decision: Introduce one dedicated orchestrator seeder for full tenant data.
  - Why: Central ownership for dataset graph and easier maintenance.
- Decision: Use `tenant_id = 1` as the canonical seeded tenant.
  - Why: Matches existing project assumptions and seeded references.
- Decision: Build missing factories for models without factory support.
  - Why: User requested full model coverage via factories.
- Decision: Seed in ordered phases to preserve relationships.
  - Why: Avoid foreign key/orchestration errors and keep data coherent.
- Decision: Use deterministic generation strategy (stable keys and seeded randomness where needed).
  - Why: Reproducible test/dev behavior.

## Seeding Phases
1. Foundation: locales/currencies/providers/tariffs/utility templates and baseline config.
2. Tenant identity: organization, subscription, admin/manager/tenant users and role links.
3. Asset graph: buildings, properties, leases, service configurations, meters.
4. Billing graph: readings, invoices, invoice items, billing records, renewals.
5. Auxiliary graph: audit/activity/system/security/notifications/tasks/comments/tags/translations and other supporting models.

## Risks / Trade-offs
- Risk: Large dataset slows seed runs.
  - Mitigation: Balanced default record counts and targeted batching.
- Risk: Inconsistent tenant scoping across auxiliary models.
  - Mitigation: Explicit tenant alignment assertions in tests.
- Risk: Existing test assumptions break due to richer baseline data.
  - Mitigation: Add focused seeding verification tests and keep deterministic keys.

## Migration Plan
1. Add missing factories with relationship-safe defaults.
2. Implement comprehensive tenant orchestrator seeder.
3. Route `DatabaseSeeder` to call the orchestrator.
4. Add feature tests to assert full graph presence and tenant consistency.
5. Run formatting and relevant test suite.

## Open Questions
- None (scope confirmed by user selections).
