# Feature Research

**Domain:** Brownfield modernization of a multi-tenant utility billing and property management SaaS
**Researched:** 2026-03-19
**Confidence:** MEDIUM

## Feature Landscape

### Table Stakes (Milestone 1 Must Include These)

Features the market treats as baseline for a credible property-management and utility-billing platform, and that Tenanto specifically needs in order for modernization to count as a success.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Unified operating model and cleanup consolidation | AppFolio, Rent Manager, and Entrata all sell the idea of one platform, one workflow, and a consistent operator experience. In a brownfield app, modernization fails if teams still navigate duplicate panels, duplicate actions, or conflicting navigation and scope rules. | HIGH | Milestone 1 should standardize one authoritative panel/provider, one navigation source, one request/action pattern, and one canonical workspace query path. Remove dead builders, stale routes, and duplicate billing/admin surfaces rather than preserving them. |
| Canonical tenant/org scoping plus role-bound security hardening | Tenant-safe access control is non-negotiable in any SaaS handling resident, billing, and financial data. OWASP ASVS 5.0 also reinforces that secure development baselines are a standard expectation, not an add-on. | HIGH | Collapse elevated access onto one source of truth, make workspace-aware builders mandatory, tighten policy coverage, harden public write endpoints such as CSP reporting, and remove security exceptions like permissive inline execution where feasible. This is a cleanup feature, not a later enhancement. |
| Audit-ready governance and approval traceability | Property-management operators and owners expect secure document access, real-time reporting, and the ability to see who changed financial records or approved invoices. Governance is table stakes once money, utilities, and multiple roles are involved. | MEDIUM | Milestone 1 should normalize audit logs, actor/context metadata, invoice status transitions, approval trails, and retention rules. It should also add release governance basics: CI checks, migration discipline, and restore-tested backup procedures. |
| Billing correctness, transparency, and lifecycle consistency | Utility billing vendors emphasize accurate billing, regulatory compliance, direct ledger posting, validated reads, and clear resident bill breakdowns. If invoices age incorrectly, allocations are opaque, or preview/finalize behavior drifts, the modernization effort has missed the core domain. | HIGH | Refactor the current oversized billing orchestration into smaller collaborators, fix overdue aging to use due dates first, standardize preview/finalize behavior, validate reads consistently, and make the resident-facing bill explainable with PDFs, breakdowns, and stable allocation logic. |
| Standardized self-service for existing resident and operator billing flows | Competing systems treat 24/7 access to reports, documents, transaction history, and one-portal payment flows as baseline. Tenanto does not need a new resident product in Milestone 1, but it does need the existing tenant/admin billing workflows to feel coherent and trustworthy. | MEDIUM | Normalize invoice history, downloads, meter submission, payment instructions, and document access across current Livewire and Filament entry points. Keep the scope to existing flows; the win is consistency, not new portal breadth. |
| Operational reliability baseline for brownfield change | A modernization milestone that cannot be safely shipped, monitored, backed up, and regression-tested is incomplete. This is especially true when tenant isolation and billing correctness are explicit constraints. | HIGH | Replace config-only health checks with real connectivity probes, queue mail/notification work that currently blocks request flows, add billing/isolation regression coverage, and benchmark report and billing paths that currently scale poorly in memory. |

### Differentiators (High-Value, Usually Deferred Until Baseline Cleanup Lands)

Features that create real product leverage, but are usually better after cleanup, governance, and billing correctness are already stable.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| Centralized multi-property operations cockpit | Entrata markets centralization across accounting, utilities, maintenance, and communication because it materially reduces operator overhead at scale. This can differentiate Tenanto for managers running many organizations or properties. | HIGH | Good Milestone 2 work. It depends on canonical scopes, bulk-safe actions, audit trails, and a stable billing/reporting core first. |
| Integration-first ecosystem with SSO and durable data contracts | Entrata's app-store model shows that one-login and push/pull integrations are valuable once the core platform is stable. This is especially relevant for utility vendors, payment providers, identity, and document systems. | HIGH | Defer until role cleanup, auditability, and model boundaries are standardized. Otherwise integrations freeze current inconsistency into long-lived contracts. |
| Utility optimization and anomaly intelligence | Utility vendors increasingly sell vacancy recovery, utility analysis, and exception detection because margin improvement matters once billing is reliable. | HIGH | Strong differentiator after Milestone 1. Build on correct invoices, validated reads, and performant reporting first; otherwise analytics will merely automate bad assumptions. |
| Advanced owner and portfolio governance dashboards | Basic owner visibility is table stakes, but cross-property approval routing, exception dashboards, and portfolio-level finance insights are higher-value operator tools. | MEDIUM | A good follow-on once audit trails, approval metadata, and report correctness are already in place. |
| AI-assisted operator workflows | Competitors now market AI and advanced automation heavily, but AI is most useful when data, permissions, and workflow boundaries are already clean. | HIGH | Defer. Good candidates later include billing exception triage, message drafting, search, and collections prompts, but only after Tenanto has canonical data paths and trustworthy auditability. |

### Anti-Features (Do Not Put These in Milestone 1)

Features that sound attractive during modernization, but usually derail scope, increase risk, or preserve the wrong abstractions.

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| Net-new product-surface expansion beyond current billing/property workflows | Brownfield teams often want to "catch up" with larger suites by adding leasing CRM, maintenance marketplace, smart-home, or resident-lifestyle features immediately. | This turns a cleanup milestone into a product-expansion program and hides whether the existing platform was actually standardized. | Stabilize and standardize the flows Tenanto already owns: billing, invoices, meter reads, org management, tenant self-service, and governance. |
| Multi-panel or microservice rewrite in Milestone 1 | A rewrite can feel like the fastest route to clean architecture. | In this repo, it would duplicate auth, scoping, navigation, and billing behavior while increasing brownfield migration risk. | Keep the Laravel monolith and unified panel model, but aggressively extract canonical actions, support services, and scoped query builders. |
| Edge-case billing rule explosion | Existing customers often ask for every legacy allocation variant, fee quirk, or one-off billing exception to survive modernization unchanged. | Supporting every bespoke rule before standardization locks current complexity in place and makes billing regressions harder to catch. | Define a supported billing policy set for Milestone 1, instrument exceptions, and schedule truly new billing modes after the core lifecycle is simplified. |
| AI-first automation on top of inconsistent data | AI is currently fashionable and easy to over-prioritize. | If permissions, report accuracy, and billing logic are still in flux, AI will amplify confusion and create trust issues. | Ship clean workflows, canonical data paths, and audit trails first; add AI as a thin layer on top later. |
| Full BI warehouse or predictive analytics stack before report correctness | Executive stakeholders often want cross-portfolio analytics early. | Warehouse work on top of inaccurate aging logic, PHP-memory reports, and unnormalized governance data produces polished but unreliable outputs. | First fix report correctness, query shape, and approval metadata in the application; then decide what belongs in a warehouse. |

## Feature Dependencies

```text
Unified operating model and cleanup consolidation
    -> Canonical tenant/org scoping plus role-bound security hardening
        -> Audit-ready governance and approval traceability
            -> Billing correctness, transparency, and lifecycle consistency
                -> Standardized self-service for existing resident and operator billing flows
                    -> Centralized multi-property operations cockpit
                    -> Integration-first ecosystem with SSO and durable data contracts
                    -> Utility optimization and anomaly intelligence
                    -> AI-assisted operator workflows

Operational reliability baseline for brownfield change
    -> supports every feature above

Edge-case billing rule explosion
    -> conflicts with billing correctness, transparency, and lifecycle consistency

Multi-panel or microservice rewrite in Milestone 1
    -> conflicts with unified operating model and cleanup consolidation
```

### Dependency Notes

- **Unified operating model requires consolidation before deeper feature work:** Until Tenanto has one authoritative navigation, query, and action model, later features will keep reproducing legacy variance.
- **Security hardening depends on canonical scoping:** You cannot reliably prove tenant isolation or role boundaries while every resource and page scopes data differently.
- **Governance depends on security cleanup:** Approval trails and audit logs are only trustworthy if actor identity and authorization are already normalized.
- **Billing hardening depends on governance:** Once invoice state changes and approval paths are observable, billing refactors become safer and easier to verify.
- **Self-service cleanup depends on billing correctness:** Tenant and admin portals should expose one stable invoice truth, not multiple inconsistent code paths.
- **Centralization, integrations, analytics, and AI all depend on Milestone 1 fundamentals:** Each one becomes more expensive if Tenanto first exports or automates today’s inconsistency.
- **Operational reliability is a cross-cutting prerequisite:** CI, backups, restore drills, real health probes, and regression coverage are part of the milestone, not postscript work.

## MVP Definition

### Launch With (Milestone 1)

- [ ] Unified operating model and cleanup consolidation — one authoritative panel/provider, navigation source, and canonical workspace/billing path.
- [ ] Canonical tenant/org scoping plus role-bound security hardening — normalize superadmin authority, harden public write surfaces, and make policy/scoping coverage non-optional.
- [ ] Audit-ready governance and approval traceability — reliable audit logs, approval metadata, CI gates, and restore-tested backup procedures.
- [ ] Billing correctness, transparency, and lifecycle consistency — correct overdue logic, smaller billing collaborators, validated reads, and explainable invoice outputs.
- [ ] Standardized self-service for existing resident and operator billing flows — coherent invoice, document, and meter-reading UX across current entry points.
- [ ] Operational reliability baseline for brownfield change — real health checks, queued side effects, performance benchmarks, and billing/isolation regression coverage.

### Add After Validation (Milestone 1.x / 2)

- [ ] Centralized multi-property operations cockpit — add bulk-safe cross-property workflows once scope and audit foundations are stable.
- [ ] Integration-first ecosystem with SSO and durable data contracts — expose partner-grade integrations only after model boundaries and permissions are consistent.
- [ ] Advanced owner and portfolio governance dashboards — layer exception views and cross-property approvals on top of corrected reports and audit trails.
- [ ] Utility optimization and anomaly intelligence — add NOI and usage insights once billing outputs are trustworthy.

### Future Consideration (Milestone 2+)

- [ ] AI-assisted operator workflows — only after canonical data, permissions, and governance are in place.
- [ ] Net-new product-surface expansion — leasing, maintenance marketplaces, resident-experience extras, or smart-building adjacencies should wait until the modernization outcome is proven.
- [ ] Full BI warehouse and predictive analytics — pursue only when application-level reporting and governance metadata are stable enough to justify a parallel analytics platform.

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| Unified operating model and cleanup consolidation | HIGH | HIGH | P1 |
| Canonical tenant/org scoping plus role-bound security hardening | HIGH | HIGH | P1 |
| Billing correctness, transparency, and lifecycle consistency | HIGH | HIGH | P1 |
| Audit-ready governance and approval traceability | HIGH | MEDIUM | P1 |
| Operational reliability baseline for brownfield change | HIGH | HIGH | P1 |
| Standardized self-service for existing resident and operator billing flows | HIGH | MEDIUM | P1 |
| Centralized multi-property operations cockpit | HIGH | HIGH | P2 |
| Integration-first ecosystem with SSO and durable data contracts | MEDIUM | HIGH | P2 |
| Utility optimization and anomaly intelligence | MEDIUM | HIGH | P2 |
| Advanced owner and portfolio governance dashboards | MEDIUM | MEDIUM | P2 |
| AI-assisted operator workflows | MEDIUM | HIGH | P3 |
| Net-new product-surface expansion | LOW | HIGH | P3 |

**Priority key:**
- P1: Must have for Milestone 1
- P2: Valuable after Milestone 1 stabilization
- P3: Explicitly defer until the platform is standardized and validated

## Competitor Feature Analysis

| Feature | AppFolio / Buildium | Entrata / Utility Vendors | Tenanto Milestone 1 Approach |
|---------|----------------------|---------------------------|------------------------------|
| Unified operating model | AppFolio stresses a clear, consistent user experience and "all in one" operations; Buildium and Rent Manager also position themselves as single platforms. | Entrata markets one operating system plus centralized workflows across accounting, utilities, and communication. | Match the market baseline by consolidating onto one authoritative panel, one navigation source, and one canonical action/query model. |
| Billing transparency and resident clarity | AppFolio + Livable highlight consolidated or utility-only statements plus clear bill breakdowns and allocation tables. | UtilityPro and Residence Billing emphasize validated reads, direct ledger posting, move-in/out sync, invoice auditing, vacancy recovery, and regulatory compliance. | Standardize the invoice lifecycle, fix billing bugs, and make resident/admin bill views coherent before adding new billing modes. |
| Governance and financial oversight | Buildium's owner portal emphasizes real-time reports, secure document sharing, invoice approvals, and customizable access controls. | Entrata accounting and budgeting surfaces emphasize single-source-of-truth finance workflows, automated reconciliation, and approval tracking. | Deliver trustworthy audit logs, approval metadata, report correctness, backups, and CI as part of the modernization baseline. |
| Integrations and one-login workflows | Larger suites increasingly expose open integrations and ecosystem stories. | Entrata explicitly offers single sign-on plus push/pull data access in its App Store model. | Defer broad integration work until Tenanto's permissions, data contracts, and domain boundaries are standardized. |
| AI and advanced automation | AppFolio and Buildium now market AI prominently. | The broader ecosystem is moving toward AI-enhanced operations and analytics. | Treat AI as a later multiplier, not a Milestone 1 foundation. The prerequisite is clean data, clean workflows, and auditability. |

## Sources

- Internal repo context (HIGH): `.planning/PROJECT.md`
- Current codebase architecture (HIGH): `.planning/codebase/ARCHITECTURE.md`
- Current codebase risk inventory (HIGH): `.planning/codebase/CONCERNS.md`
- Current codebase conventions (HIGH): `.planning/codebase/CONVENTIONS.md`
- AppFolio property management software overview (official, MEDIUM-HIGH for market expectations): https://www.appfolio.com/property-management-software
- AppFolio main platform messaging (official, MEDIUM for positioning): https://www.appfolio.com/
- Livable x AppFolio utility management integration (official, HIGH for billing-transparency expectations): https://www.appfolio.com/partners/livable
- Buildium owner portal (official, HIGH for portal/governance expectations): https://www.buildium.com/features/property-owner-portal/
- Buildium data security (official, HIGH for security-baseline expectations): https://www.buildium.com/resources/detail/data-security/
- Entrata multifamily platform (official, MEDIUM-HIGH for unified-ops expectations): https://www.entrata.com/multifamily/
- Entrata General Accounting (official, MEDIUM-HIGH for finance/governance expectations): https://www.entrata.com/products/general-accounting/
- Entrata App Store / SSO and data-access model (official, HIGH for integration expectations): https://docs.entrata.com/app-store
- Entrata centralization workflows (official, HIGH for post-baseline centralization patterns): https://centralization.entrata.com/
- UtilityPro official site (official vendor, MEDIUM-HIGH for utility-billing workflow expectations): https://www.utilityprobilling.com/
- OWASP Application Security Verification Standard 5.0 (official, HIGH for security-baseline framing): https://owasp.org/www-project-application-security-verification-standard/

**Confidence note:** Table-stakes calls are high confidence because they are consistent across the current Tenanto repo context and multiple official vendor surfaces. Differentiator and deferment calls are medium confidence because vendor pages are marketing materials; the recommendations here are intentionally opinionated for roadmap usefulness.

---
*Feature research for: Tenanto brownfield modernization*
*Researched: 2026-03-19*
