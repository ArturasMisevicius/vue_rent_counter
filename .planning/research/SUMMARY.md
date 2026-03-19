# Project Research Summary

**Project:** Tenanto
**Domain:** Brownfield multi-tenant utility billing and property management SaaS modernization
**Researched:** 2026-03-19
**Confidence:** MEDIUM

## Executive Summary

Tenanto is not a greenfield product build. It is a brownfield Laravel monolith that already handles platform administration, organization operations, billing, metering, and tenant self-service, and Milestone 1 should be judged on whether those existing capabilities become safer, more consistent, and easier to evolve. The research is clear that experts would not start this milestone with a rewrite, microservices split, or SPA migration. They would keep the app PHP-first on Laravel 12, Filament 5, Livewire 4, Blade, Tailwind 4, and Eloquent, then standardize the boundaries inside the monolith.

The recommended approach is a boundary-first modular monolith with one authoritative workspace context, one canonical panel/navigation contract, boundary-aware query builders for every read path, and action-driven write flows for every mutation. Milestone 1 should prioritize six outcomes: unified operating model, canonical tenant and organization scoping, audit-ready governance, billing correctness, coherent existing self-service flows, and operational reliability. That is the shortest path to a roadmap that improves the product without expanding scope.

The main risk is accidental breakage disguised as cleanup. Tenanto is most exposed when tenant isolation remains optional, when billing behavior changes during refactors, when route and panel contracts churn without compatibility planning, or when the team trusts happy-path tests that do not reflect production-like runtime behavior. The mitigation is to phase the work around invariants first, then tenant boundaries, then read/write standardization, then billing extraction, and only then deeper migration and operational hardening.

## Key Findings

### Recommended Stack

The stack recommendation is conservative on purpose: keep one Laravel monolith and reduce variance instead of introducing new architecture. The strongest fit is Laravel 12, Filament 5, Livewire 4, Blade SSR, Tailwind 4, Redis-backed queues and cache, Pest 4, and aggressive static analysis. This matches Tenanto's current shape and the research consensus that Milestone 1 should standardize the existing delivery model rather than replace it.

The important version constraint is not Laravel alone but the combined toolchain. PHP 8.4 is the safest team default, with PHP 8.5 validated separately. Pest 4 effectively raises the meaningful test runtime floor to PHP 8.3+, and Filament 5 expects Tailwind 4.1+ and modern Laravel. For Milestone 1 planning, database-engine migration should be treated as optional: prefer PostgreSQL long term if the choice is still open, but do not make an engine switch part of the cleanup milestone if MySQL is already stable.

**Core technologies:**
- PHP 8.4 default, validate 8.5 separately: runtime baseline that fits current Laravel and testing tooling without forcing the newest branch everywhere.
- Laravel 12: framework base for modernization with minimal breaking-change pressure.
- Filament 5: primary admin and operator UI layer for CRUD-heavy platform and workspace flows.
- Livewire 4 + Blade: PHP-first interactive flows for tenant, auth, and shared workspace surfaces without a JS rewrite.
- Tailwind 4 + Vite: modern styling and asset pipeline aligned with Filament 5.
- Redis + Horizon: cache, queues, locks, and worker visibility for notifications, exports, reminders, and background work.
- Pest 4 + PHPUnit 12: standard current Laravel testing stack, including browser coverage where needed.
- Sanctum, Spatie Permission, Brick Money, Pennant, Scout: first-party and targeted supporting packages for auth, RBAC, money correctness, feature flags, and pragmatic search.

### Expected Features

Milestone 1 is not feature expansion. It is baseline repair for a production-shaped SaaS. The must-have feature set is cross-cutting because the repo already contains the major product surfaces; what is missing is consistent operation, security, governance, and billing correctness across them. The research strongly supports treating cleanup itself as the product deliverable for this milestone.

**Must have (table stakes):**
- Unified operating model and cleanup consolidation: one authoritative panel/provider, navigation source, and canonical workspace and billing path.
- Canonical tenant and organization scoping plus role-bound security hardening: one source of truth for elevated access, mandatory scoped builders, and policy-first enforcement.
- Audit-ready governance and approval traceability: reliable audit logs, actor/context metadata, approval trails, and release governance basics.
- Billing correctness, transparency, and lifecycle consistency: correct aging logic, stable allocation rules, explainable invoices, and predictable preview/finalize behavior.
- Standardized self-service for existing resident and operator billing flows: coherent invoice history, downloads, meter submission, and document access across current entry points.
- Operational reliability baseline: real health checks, queued side effects, regression coverage, restore-tested backups, and performance visibility.

**Should have (competitive, but usually after baseline cleanup):**
- Centralized multi-property operations cockpit: bulk-safe cross-property workflows once scoping and auditability are stable.
- Integration-first ecosystem with SSO and durable data contracts: only after permissions and model boundaries are canonical.
- Advanced owner and portfolio governance dashboards: higher-value reporting after audit and report correctness are fixed.
- Utility optimization and anomaly intelligence: valuable follow-on once billing outputs are trustworthy.

**Defer (v2+):**
- AI-assisted operator workflows: useful later, but risky before data, permissions, and workflow boundaries are clean.
- Net-new product-surface expansion: leasing CRM, maintenance marketplaces, resident lifestyle features, or other adjacent products.
- Full BI warehouse or predictive analytics stack: wait until application-level reports and billing semantics are correct.
- Multi-panel or microservice rewrite: high risk and misaligned with the core Milestone 1 job.

### Architecture Approach

The safest architecture target is a boundary-first modular monolith. Tenanto should keep one Laravel runtime and one relational model, but every request should pass through a predictable chain: route or panel, middleware, workspace context, policy, query or action, Eloquent, then presenter or view. That pattern matters more than directory cleanup because it is what turns tenant safety, billing correctness, and UI consistency into enforceable contracts.

**Major components:**
1. Workspace context: resolves actor, organization, tenant assignment, subscription state, and current security boundary for every request and job.
2. Delivery boundaries: separates public/auth, control plane, organization workspace, and tenant portal entry points without splitting the app into services.
3. Boundary-aware query builders and read models: powers every table, page, report, and export from approved scoped entry points.
4. Action pipeline for writes: routes every mutation through validation, policy, transaction, and event or job boundaries.
5. Billing domain services: decomposes billing into candidate selection, calculators, finalization, and payments instead of one oversized orchestration layer.
6. Reporting and projections: moves heavy report logic to dedicated builders, database-side aggregation, cache, and scheduled refresh jobs.
7. Shared UI composition: centralizes navigation, panel middleware, search, and workspace shell behavior in one authoritative support layer.

### Critical Pitfalls

1. **Treating tenant isolation as a convention** — require one canonical workspace boundary, forbid naked tenant data lookups, and add hostile cross-organization access tests for every high-risk surface.
2. **Changing billing behavior while "just cleaning up"** — freeze invoice, aging, allocation, and rounding invariants first, then use fixture-based characterization tests before extracting services.
3. **Renaming routes, panels, and navigation contracts together** — inventory route usage, consolidate navigation first, and ship aliases or redirects before removing legacy names.
4. **Leaving public test, debug, or support surfaces exposed** — audit the public route and asset graph early, register testing-only routes only in testing, and harden anonymous write endpoints such as CSP reporting.
5. **Trusting the existing test suite too much** — add a modernization-specific regression harness, CI gates, query budgets, and at least one production-like runtime lane for queue, cache, and database behavior.
6. **Using destructive schema cleanup instead of expand/contract** — separate structural additions, backfills, cutovers, and removals so brownfield releases stay reversible.

## Implications for Roadmap

Based on the research, Milestone 1 should be planned as six dependent phases inside one modernization milestone, not as parallel workstreams with loose coupling.

### Phase 1: Safety Freeze and Baseline Verification
**Rationale:** Tenanto needs a safe starting point before any structural cleanup. Public exposure, route churn, and regression blind spots are immediate release risks.
**Delivers:** Public route and asset inventory, test-only surface cleanup, route snapshots, billing and tenant characterization baselines, query-budget smoke checks, and CI gates for format, static analysis, and tests.
**Addresses:** Operational reliability baseline, audit-ready governance, and Milestone 1 brownfield safety requirements.
**Avoids:** Public surface exposure, false confidence from the existing suite, and accidental route or contract breakage during later phases.

### Phase 2: Workspace Boundary and Role Contract Consolidation
**Rationale:** Tenant safety is the first true application-level dependency. Every later phase gets riskier if scoping and elevated access are still inconsistent.
**Delivers:** One authoritative `WorkspaceContext`, standardized middleware chains, canonical scoped query entry points, unified superadmin authority, and a role capability matrix backed by policy tests.
**Uses:** Laravel middleware, policies, Eloquent scopes, and Filament support classes.
**Implements:** The core architecture boundary layer for actor, organization, and tenant resolution.
**Addresses:** Canonical tenant/org scoping plus role-bound security hardening.
**Avoids:** Tenant leaks, role flattening, and security drift during cleanup.

### Phase 3: Read Path, Navigation, and Panel Contract Unification
**Rationale:** Once the boundary is trustworthy, Tenanto can safely converge on one operating model for how users discover and read data.
**Delivers:** One authoritative navigation source, boundary-aware resource queries, tenant-safe read models, route helper cleanup, report query extraction, and compatibility handling for legacy deep links where needed.
**Uses:** Filament 5 resources and pages, Livewire 4, Blade, query builders, and cached projections.
**Implements:** Delivery boundaries plus shared UI composition and reporting read models.
**Addresses:** Unified operating model and standardized self-service for existing flows.
**Avoids:** Route or panel churn, N+1 regression, and duplicated query logic across resources and tenant pages.

### Phase 4: Mutation Pipeline, Governance, and Side-Effect Standardization
**Rationale:** After reads are standardized, writes need the same consistency so approvals, auditability, and side effects stop depending on UI-specific code paths.
**Delivers:** Form Request and action-driven write flows, explicit transactions, audit metadata, approval tracing, event/job boundaries, and queued notifications or slow side effects where appropriate.
**Uses:** Laravel requests, actions, events, jobs, queue infrastructure, and audit support services.
**Implements:** The write-side action pipeline and governance layer.
**Addresses:** Audit-ready governance, approval traceability, operational reliability, and coherence of existing admin and tenant mutations.
**Avoids:** Hidden write behavior in Filament or Livewire, missing audit trails, and runtime drift between sync and queued execution.

### Phase 5: Billing Characterization and Domain Extraction
**Rationale:** Billing is the highest-blast-radius domain and should only be simplified after invariants, boundaries, and mutation patterns are already in place.
**Delivers:** Written billing invariants, fixture-based comparison coverage, smaller billing collaborators, corrected overdue logic, consistent preview/finalize semantics, stable money calculations, and clearer invoice outputs.
**Uses:** PHP 8.4+, Laravel 12 services and transactions, Brick Money where needed, and queue-backed reminders or exports.
**Implements:** Billing candidates, calculators, finalization, and payment collaborators inside the modular monolith.
**Addresses:** Billing correctness, transparency, lifecycle consistency, and the trustworthiness of resident and operator billing surfaces.
**Avoids:** Silent invoice drift, rounding regressions, and risky "cleanup only" billing rewrites.

### Phase 6: Migration, Operations, and Release Hardening
**Rationale:** Milestone 1 is not complete until the refactored system can be shipped and operated safely under realistic runtime conditions.
**Delivers:** Expand/contract migration plans, rehearsed backfills, real connectivity-based health checks, backup and restore validation, queue and worker verification, projection refresh jobs, and performance benchmarks for billing and reporting paths.
**Uses:** Redis, Horizon, Pulse, queue drivers, migration rehearsal, and production-like smoke validation.
**Implements:** Infrastructure and operational layers that support the standardized monolith.
**Addresses:** Operational reliability baseline and release governance expectations.
**Avoids:** Destructive migrations, false health signals, and production-only failures in async flows.

### Phase Ordering Rationale

- Phase 1 comes first because Tenanto needs proof of current behavior before aggressive standardization starts.
- Phase 2 comes before any major refactor because tenant isolation and role authority are the system's primary safety boundary.
- Phases 3 and 4 are split between reads and writes because the architecture research shows that query standardization and action standardization solve different classes of risk.
- Phase 5 is intentionally late because billing depends on trustworthy boundaries, tests, and write semantics more than any other domain.
- Phase 6 closes the milestone because operational hardening validates that the refactor can survive production conditions, not just local development.
- This ordering follows the research dependency chain: unified operating model -> canonical scoping -> governance -> billing correctness -> self-service coherence, with operational reliability supporting every phase.

### Research Flags

Phases likely needing deeper research during planning:
- **Phase 5:** Billing characterization and domain extraction — Tenanto-specific invoice, aging, rounding, and allocation rules need repo and stakeholder validation before implementation plans are locked.
- **Phase 6:** Migration, operations, and release hardening — actual deployment topology, backup process, queue runtime, and health-probe expectations need environment-specific verification.
- **Conditional follow-up after Phase 3:** If roadmap scope includes tenant-domain changes, multi-panel separation, or durable external contracts, run a targeted research phase first because those moves are architectural migrations, not cleanup details.

Phases with standard patterns (usually skip research-phase):
- **Phase 1:** Safety freeze and CI gate — well-understood Laravel, Pest, PHPStan, and GitHub Actions patterns.
- **Phase 2:** Workspace boundary and authorization consolidation — repo exploration is required, but external patterns are already well documented by Laravel and Filament.
- **Phase 3:** Read path, navigation, and panel contract unification — standard Filament and Laravel patterns unless tenant URL strategy changes materially.
- **Phase 4:** Mutation pipeline and governance — established Laravel requests, actions, policies, events, and jobs patterns.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Strongly grounded in official Laravel, Filament, Livewire, Tailwind, Pest, and package documentation plus current repo direction. |
| Features | MEDIUM | Table stakes are credible and repo-aligned, but differentiator guidance depends partly on competitor marketing material and informed prioritization. |
| Architecture | MEDIUM | The modular monolith recommendation fits the codebase and official framework patterns, but the exact internal module seams are still an implementation inference. |
| Pitfalls | MEDIUM | High-value and repo-specific, but several warnings are conservative conclusions drawn from current risks rather than explicit upstream documentation. |

**Overall confidence:** MEDIUM

### Gaps to Address

- **Database standardization path:** Confirm whether Milestone 1 should remain database-engine neutral or whether PostgreSQL migration is a later roadmap item; do not let this ambiguity slow cleanup planning.
- **Billing rule inventory:** Write down approved overdue, allocation, rounding, finalization, and reminder invariants before Phase 5 plans are finalized.
- **Route and deep-link dependency map:** Confirm which legacy route names, notification links, and saved bookmarks require compatibility handling during panel and navigation consolidation.
- **Runtime operations baseline:** Verify real queue workers, mail delivery behavior, backup restore process, and health-check expectations against the live environment rather than only app config.
- **Role authority migration:** Confirm the data migration path for collapsing elevated access onto one authoritative field if legacy boolean and enum signals still coexist.

## Sources

### Primary (HIGH confidence)
- `.planning/PROJECT.md` — milestone scope, constraints, and product definition
- `.planning/research/STACK.md` — stack recommendation synthesized from official framework and package docs
- Laravel 12 docs — releases, routing, middleware, configuration, deployment, authorization, queues, migrations, testing, Sanctum, Horizon, Telescope, Pulse, Pennant, Scout, Pint
- Filament 5 docs — installation, panels, resources, tenancy
- Livewire 4 docs — installation, security, actions, testing
- Tailwind CSS v4 docs — Vite integration and stack alignment
- Pest support policy — runtime compatibility

### Secondary (MEDIUM confidence)
- `.planning/research/FEATURES.md` — table stakes, differentiators, and anti-features for Milestone 1
- `.planning/research/ARCHITECTURE.md` — boundary-first modular monolith recommendation
- `.planning/research/PITFALLS.md` — repo-specific modernization risks and sequencing guidance
- AppFolio, Buildium, Entrata, UtilityPro — market expectations for platform consolidation, billing clarity, governance, and operations
- OWASP ASVS 5.0 — security baseline framing for tenant-safe product requirements

### Tertiary (LOW confidence)
- No roadmap-critical conclusion in this summary depends on a single low-confidence source. Any future roadmap item involving AI features, broad integrations, tenant-domain changes, or warehouse analytics should be re-researched before planning.

---
*Research completed: 2026-03-19*
*Ready for roadmap: yes*
