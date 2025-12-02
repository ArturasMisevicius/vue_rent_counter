# Hook Response Templates (for less-used hooks)

Use this when a .kiro hook triggers and no feature-specific doc exists. Fill in the placeholders with project specifics when the feature is implemented.

## GraphQL API Builder
- Scope: schema design, auth, validation, errors, pagination.
- Template:
  - **Schema:** types, queries, mutations, relationships, pagination strategy.
  - **Auth:** guards, abilities/permissions, tenant scoping.
  - **Validation:** input types, rules, error shapes.
  - **Errors:** standard error format, codes, retry guidance.
  - **Examples:** query/mutation samples with variables.
  - **Versioning:** how breaking changes are handled.

## Content Workflows (content-orchestrator, recommendation-engine, newsletter, it-news-curator, headline-optimizer, seo-content-optimizer, landing-content-sync)
- Scope: ingestion, moderation, scheduling, rendering, analytics.
- Template:
  - **Sources:** allowed feeds/APIs, polling cadence, dedupe rules.
  - **Moderation:** approval steps, PII/NSFW filters, roles.
  - **Personalization:** ranking signals, cold-start handling.
  - **Publishing:** channels (email/web/app), schedules, A/B tests.
  - **Metrics:** CTR, open rate, dwell time; logging/BI sinks.

## Database Schema Designer / Zero-Downtime Migration
- Scope: migration strategy, backfills, rollout/rollback.
- Template:
  - **Plan:** phase 1 add columns/tables (nullable/backfilled), phase 2 dual writes, phase 3 cutover, phase 4 cleanup.
  - **Safety:** feature flags, chunked backfills, lock avoidance, indexes.
  - **Rollback:** how to revert schema/data safely.
  - **Monitoring:** query performance, error budgets, slow logs.

## Repository Pattern / Service Layer Implementation
- Scope: layering, contracts, testing seams.
- Template:
  - **Interfaces:** repository contracts (methods, DTOs), service contracts.
  - **Responsibilities:** what lives in repo vs service vs controller.
  - **Tenancy:** how tenant_id flows through repositories/services.
  - **Testing:** fakes/mocks, contract tests, integration coverage.

## Docs Relocator / MD File Organizer
- Scope: doc structure and routing.
- Template:
  - **Structure:** target folders, naming conventions.
  - **Links:** update cross-links, sidebar/nav if applicable.
  - **Redirects:** note if legacy paths need redirects.
  - **Ownership:** who maintains the doc map.

## Misc (tech-fact-checker, tech-news-writer, content-moderation-specialist)
- Provide a short SOP when first used:
  - **Fact checker:** sources allowed, citation format, confidence levels.
  - **News writer:** tone, length, sources, embargo rules.
  - **Moderation:** policy (PII, abuse, NSFW), escalation path.

Keep this file updated: when a pending hook becomes active, create a dedicated guide under `docs/` and link it from [HOOKS_DOCUMENTATION_MAP.md](HOOKS_DOCUMENTATION_MAP.md); remove or trim the template once a full guide exists.
