# Requirements: Tenanto

**Defined:** 2026-03-19
**Core Value:** Tenanto must deliver tenant-safe utility billing and property management workflows on a clean, consistent application foundation that the team can evolve confidently.

## v1 Requirements

Requirements for the first modernization milestone. These define what "foundation cleanup" must actually deliver.

### Architecture Foundation

- [ ] **ARCH-01**: Authorized users can reach each supported product surface through one authoritative entry path per role without duplicate legacy panel or workspace flows remaining active by default.
- [ ] **ARCH-02**: Navigation, workspace switching, and primary billing/report entry points resolve from one canonical source of truth across the application.
- [ ] **ARCH-03**: Read-heavy screens, tables, and reports use standardized workspace-aware query paths so equivalent data is consistent across admin, operator, and tenant surfaces.
- [ ] **ARCH-04**: Write flows execute through standardized validated pipelines so equivalent mutations follow one predictable request or action path instead of duplicated controller, resource, or component logic.

### Security & Access Boundaries

- [ ] **SEC-01**: Every organization-scoped or tenant-scoped request resolves an explicit workspace context before accessing protected data.
- [ ] **SEC-02**: `SUPERADMIN` can perform platform-wide actions without tenant assignment, while non-superadmin roles cannot cross organization boundaries.
- [ ] **SEC-03**: `ADMIN` retains organization billing-management authority while `MANAGER` remains blocked from billing settings and equivalent privileged finance controls.
- [ ] **SEC-04**: `TENANT` can access only property-scoped self-service capabilities such as meter readings, invoice history, invoice documents, and related resident-facing records tied to that tenant.
- [ ] **SEC-05**: Public debug, test, or diagnostic entrypoints are removed or made unavailable outside explicitly approved development or testing contexts.

### Governance & Auditability

- [ ] **GOV-01**: Authorized operators can trace who changed invoice, approval, or other high-risk financial records, when the change occurred, and which workspace it affected.
- [ ] **GOV-02**: Invoice approvals, status transitions, and comparable governance actions retain consistent actor, timestamp, and before/after context across all supported workflows.
- [ ] **GOV-03**: Maintainers have merge-time safety gates for formatting, an approved static-check layer, and regression tests so high-risk modernization changes are blocked before release. For Phase 1, the accepted static-check layer is the curated executable architecture and inventory guard bundle rather than a repo-wide PHPStan or Larastan rollout.

### Billing Lifecycle Consistency

- [ ] **BILL-01**: Invoice aging and overdue status use one canonical due-date-first policy across dashboards, reports, exports, and resident-visible views.
- [ ] **BILL-02**: Billing preview and billing finalization produce consistent totals, statuses, and side effects for the same underlying billing data.
- [ ] **BILL-03**: Money calculations use one canonical rounding and allocation policy across invoice generation, payment handling, and reporting outputs.
- [ ] **BILL-04**: Meter-reading validation, billing candidate selection, and downstream invoice generation use consistent rules across all current entry points.
- [ ] **BILL-05**: Resident- and operator-facing invoice views expose clear bill breakdowns and stable downloadable artifacts so the same invoice is explainable everywhere it appears.

### Existing Self-Service & Operator Flow Coherence

- [ ] **PORT-01**: Tenants can view one coherent invoice history and detail experience regardless of which supported tenant entry point they use.
- [ ] **PORT-02**: Tenants can submit meter readings through one validated workflow with consistent success, validation-error, and out-of-scope behavior.
- [ ] **PORT-03**: Authorized staff can access the same billing-related documents, invoice details, and supporting records across the supported admin and workspace surfaces without conflicting behavior.

### Operational Reliability

- [ ] **OPS-01**: Health and readiness checks verify real application dependencies and runtime behavior rather than configuration presence alone.
- [ ] **OPS-02**: Notifications, reminders, exports, and similar slow side effects can run through an asynchronous queue-backed path instead of blocking critical request flows.
- [ ] **OPS-03**: Backup and restore procedures for the modernized application are documented, runnable, and validated well enough to support release confidence.
- [ ] **OPS-04**: Regression coverage exists for tenant isolation, role-bound access, and core billing invariants before modernization changes ship.

## v2 Requirements

Deferred until the foundation-cleanup milestone is complete and validated.

### Multi-Property Operations

- **MOPS-01**: Operators can manage multiple properties through centralized cross-property workflows and bulk-safe actions.

### Integrations & Identity

- **INTG-01**: The platform exposes durable SSO and external-integration contracts after permissions and model boundaries are standardized.

### Analytics & Governance Expansion

- **ANLY-01**: Operators can use anomaly detection and utility optimization insights built on trustworthy billing outputs.
- **GOVX-01**: Owners and platform staff can access advanced cross-property governance dashboards and exception-routing views.

### AI Assistance

- **AI-01**: Staff can use AI-assisted operational workflows only after data paths, permissions, and auditability are stable.

### Billing Expansion

- **BILX-01**: Additional or bespoke billing rule families can be introduced after the canonical lifecycle and money policies are simplified and verified.

## Out of Scope

Explicitly excluded from the first modernization milestone.

| Feature | Reason |
|---------|--------|
| Net-new product surfaces beyond current billing, property, and tenant-service workflows | Milestone 1 is foundation cleanup, not feature expansion. |
| Microservice split or React/Inertia rewrite | Research strongly favors a standardized Laravel monolith for this cleanup phase. |
| Full BI warehouse or predictive analytics stack | Reporting correctness and governance must be fixed in-app first. |
| AI-first automation on top of inconsistent data | AI is deferred until permissions, billing semantics, and audit trails are trustworthy. |
| Full legacy compatibility for every route, panel, and duplicated workflow | Aggressive standardization is allowed when tenant and billing correctness are preserved. |

## Traceability

Which phases cover which requirements. This will be updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| ARCH-01 | Phase 3 | Pending |
| ARCH-02 | Phase 3 | Pending |
| ARCH-03 | Phase 3 | Pending |
| ARCH-04 | Phase 4 | Pending |
| SEC-01 | Phase 2 | Pending |
| SEC-02 | Phase 2 | Pending |
| SEC-03 | Phase 2 | Pending |
| SEC-04 | Phase 2 | Pending |
| SEC-05 | Phase 1 | Pending |
| GOV-01 | Phase 4 | Pending |
| GOV-02 | Phase 4 | Pending |
| GOV-03 | Phase 1 | Pending |
| BILL-01 | Phase 5 | Pending |
| BILL-02 | Phase 5 | Pending |
| BILL-03 | Phase 5 | Pending |
| BILL-04 | Phase 5 | Pending |
| BILL-05 | Phase 5 | Pending |
| PORT-01 | Phase 3 | Pending |
| PORT-02 | Phase 4 | Pending |
| PORT-03 | Phase 3 | Pending |
| OPS-01 | Phase 6 | Pending |
| OPS-02 | Phase 4 | Pending |
| OPS-03 | Phase 6 | Pending |
| OPS-04 | Phase 1 | Pending |

**Coverage:**
- v1 requirements: 24 total
- Mapped to phases: 24
- Unmapped: 0 ✓

---
*Requirements defined: 2026-03-19*
*Last updated: 2026-03-19 after roadmap creation*
