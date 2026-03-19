# Roadmap: Tenanto Milestone 1 Foundation Cleanup

## Overview

Milestone 1 modernizes the existing Laravel monolith without a rewrite. The work starts by freezing unsafe public exposure and regression risk, then consolidates workspace and role boundaries, standardizes read and write contracts across current surfaces, extracts billing behavior into canonical rules, and finishes with operational hardening so tenant isolation and billing correctness stay safe throughout aggressive standardization.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [ ] **Phase 1: Safety Freeze and Guardrails** - Remove unsafe public surfaces and establish merge-time modernization safety checks.
- [ ] **Phase 2: Workspace Boundary and Role Contracts** - Make workspace resolution and role authority explicit before protected data access.
- [ ] **Phase 3: Surface and Read Path Unification** - Converge navigation, entry paths, and workspace-aware read models across supported surfaces.
- [ ] **Phase 4: Mutation Governance and Async Pipelines** - Standardize validated write paths, auditability, and queued side effects.
- [ ] **Phase 5: Billing Lifecycle Canonicalization** - Make billing rules, invoice behavior, and money semantics consistent everywhere.
- [ ] **Phase 6: Operational Hardening and Recovery** - Prove the cleaned-up application can be monitored, recovered, and shipped safely.

## Phase Details

### Phase 1: Safety Freeze and Guardrails
**Goal**: Maintainers can modernize the live product without exposing unsafe public surfaces or shipping blind regressions.
**Depends on**: Nothing (first phase)
**Requirements**: SEC-05, GOV-03, OPS-04
**Success Criteria** (what must be TRUE):
  1. Public debug, test, and diagnostic entrypoints are unavailable outside explicitly approved development or testing contexts.
  2. Merge-time gates block modernization changes when formatting, the approved Phase 1 static-check layer, or regression checks fail.
  3. Maintainers can run regression coverage for tenant isolation, role-bound access, and core billing invariants before Milestone 1 changes ship.
**Plans**: 5 plans

Plans:
- [x] `01-01-PLAN.md` — Remove live test-route exposure and add public-surface inventory proof.
- [x] `01-02-PLAN.md` — Remove the PWA dependency, config, and public assets with executable removal proof.
- [x] `01-03-PLAN.md` — Harden `/csp/report` with a named throttle, source tagging, and pruning retention.
- [x] `01-04-PLAN.md` — Add the shared Phase 1 guard command and GitHub Actions merge gate.
- [ ] `01-05-PLAN.md` — Require the remote `main` branch to enforce `Phase 1 Guardrails` before merge.

### Phase 2: Workspace Boundary and Role Contracts
**Goal**: Every protected request resolves the same tenant-safe workspace and authority contract before data access.
**Depends on**: Phase 1
**Requirements**: SEC-01, SEC-02, SEC-03, SEC-04
**Success Criteria** (what must be TRUE):
  1. Every organization-scoped or tenant-scoped request resolves an explicit workspace context before protected data is read or mutated.
  2. `SUPERADMIN` can perform platform-wide actions without tenant assignment, while non-superadmin users cannot cross organization boundaries.
  3. `ADMIN` users retain billing-management authority and equivalent finance controls that `MANAGER` users cannot access.
  4. `TENANT` users can access only property-scoped self-service records and actions tied to their tenant.
**Plans**: 1 plan

Plans:
- [ ] `02-01-PLAN.md` — Resolve shared workspace context, normalize role authority, harden tenant property boundaries, and add boundary inventory proof.

### Phase 3: Surface and Read Path Unification
**Goal**: Users reach and read billing and workspace data through one coherent, canonical operating model.
**Depends on**: Phase 2
**Requirements**: ARCH-01, ARCH-02, ARCH-03, PORT-01, PORT-03
**Success Criteria** (what must be TRUE):
  1. Each role reaches its supported product surface through one authoritative entry path, with duplicate legacy panel or workspace flows no longer active by default.
  2. Navigation, workspace switching, and primary billing or reporting entry points resolve from one canonical source of truth across the application.
  3. Read-heavy screens, tables, and reports show consistent workspace-aware data across admin, operator, and tenant surfaces for the same underlying records.
  4. Tenants and authorized staff see one coherent invoice history, invoice detail, document, and supporting-record experience across supported entry points.
**Plans**: 1 plan

Plans:
- [ ] `03-01-PLAN.md` — Canonicalize entry paths, collapse navigation to one source, standardize read builders, and unify invoice read experience.

### Phase 4: Mutation Governance and Async Pipelines
**Goal**: Writes and governance actions follow one validated, auditable, queue-aware pipeline across current workflows.
**Depends on**: Phase 3
**Requirements**: ARCH-04, GOV-01, GOV-02, PORT-02, OPS-02
**Success Criteria** (what must be TRUE):
  1. Equivalent mutations across controllers, Filament resources, and Livewire flows follow one standardized validated request or action path.
  2. Authorized operators can trace who changed high-risk financial records, when the change happened, and which workspace it affected.
  3. Invoice approvals, status transitions, and comparable governance actions preserve consistent actor, timestamp, and before or after context across supported workflows.
  4. Tenants can submit meter readings through one validated workflow with consistent success, validation-error, and out-of-scope behavior.
  5. Notifications, reminders, exports, and similar slow side effects run through an asynchronous queue-backed path instead of blocking critical request flows.
**Plans**: 1 plan

Plans:
- [ ] `04-01-PLAN.md` — Standardize mutation pipelines, attach governance capture, unify tenant reading writes, and queue slow side effects.

### Phase 5: Billing Lifecycle Canonicalization
**Goal**: Billing outcomes become canonical, explainable, and internally consistent before deeper expansion work.
**Depends on**: Phase 4
**Requirements**: BILL-01, BILL-02, BILL-03, BILL-04, BILL-05
**Success Criteria** (what must be TRUE):
  1. Invoice aging and overdue status follow one due-date-first policy across dashboards, reports, exports, and resident-visible views.
  2. Billing preview and billing finalization produce the same totals, statuses, and side effects for the same underlying billing data.
  3. Money calculations use one canonical rounding and allocation policy across invoice generation, payment handling, and reporting outputs.
  4. Meter-reading validation, billing candidate selection, and downstream invoice generation follow the same rules across all current entry points.
  5. Resident-facing and operator-facing invoice views expose clear bill breakdowns and stable downloadable artifacts for the same invoice.
**Plans**: 1 plan

Plans:
- [ ] `05-01-PLAN.md` — Canonicalize overdue semantics, billing parity, money policy, eligibility rules, and invoice explainability.

### Phase 6: Operational Hardening and Recovery
**Goal**: The modernized application can be monitored, recovered, and released with trustworthy operational evidence.
**Depends on**: Phase 5
**Requirements**: OPS-01, OPS-03
**Success Criteria** (what must be TRUE):
  1. Health and readiness checks reflect real dependency connectivity and runtime behavior instead of configuration presence alone.
  2. Backup and restore procedures for the modernized application are documented, runnable, and validated enough to support release confidence.
**Plans**: 1 plan

Plans:
- [ ] `06-01-PLAN.md` — Replace false-positive health probes, add backup/restore readiness, and publish release evidence.

## Progress

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Safety Freeze and Guardrails | 4/5 | In Progress | - |
| 2. Workspace Boundary and Role Contracts | 0/1 | Planned | - |
| 3. Surface and Read Path Unification | 0/1 | Planned | - |
| 4. Mutation Governance and Async Pipelines | 0/1 | Planned | - |
| 5. Billing Lifecycle Canonicalization | 0/1 | Planned | - |
| 6. Operational Hardening and Recovery | 0/1 | Planned | - |
