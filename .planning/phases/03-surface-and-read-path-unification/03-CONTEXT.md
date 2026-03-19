# Phase 3: Surface and Read Path Unification - Context

**Gathered:** 2026-03-19
**Status:** Ready for planning

<domain>
## Phase Boundary

Phase 3 converges how users enter the product, how navigation is defined, and how read-heavy workspace data is assembled across admin, manager, superadmin, and tenant surfaces. This phase is about read-path and UX contract unification, not mutation governance or billing-rule extraction.

</domain>

<decisions>
## Implementation Decisions

### Canonical entry-path policy
- Each role should have one authoritative product entry path after authentication.
- Legacy or secondary entrypoints may remain temporarily as redirects, but they should not behave as independent product surfaces.

### Navigation policy
- Navigation, dashboard destinations, and workspace switching should come from one canonical source of truth.
- Phase 3 should remove or delegate any duplicate navigation builder or config surface that is no longer authoritative.

### Read-model policy
- Read-heavy tables, reports, search providers, and tenant views should prefer shared workspace-aware support builders over duplicated collection shaping in UI classes.
- Equivalent records should be read through the same core query or presenter contracts wherever practical.

### Invoice and document read experience
- Tenant and staff invoice history, detail, and document access should converge on one coherent read contract.
- Phase 3 should unify the supported read experience; mutation-side invoice behavior is deferred to later phases.

### Claude's Discretion
- Exact choice of authoritative navigation source, redirect locations, and read-builder extraction boundaries may be chosen during implementation as long as duplicate active read surfaces are eliminated.

</decisions>

<canonical_refs>
## Canonical References

- `.planning/ROADMAP.md`
- `.planning/REQUIREMENTS.md`
- `.planning/STATE.md`
- `.planning/codebase/ARCHITECTURE.md`
- `.planning/codebase/CONCERNS.md`
- `app/Filament/Support/Auth/LoginRedirector.php`
- `app/Filament/Support/Shell/DashboardUrlResolver.php`
- `app/Providers/Filament/AppPanelProvider.php`
- `app/Filament/Support/Shell/Navigation/NavigationBuilder.php`
- `config/tenanto.php`
- `app/Filament/Support/Shell/Search/*`
- `app/Filament/Support/Admin/Reports/*`
- `app/Filament/Support/Tenant/Portal/TenantInvoiceIndexQuery.php`
- `app/Filament/Resources/Invoices/*`
- `app/Http/Controllers/TenantPortalRouteController.php`
- `tests/Feature/Auth/LoginFlowTest.php`
- `tests/Feature/Shell/GlobalSearchTest.php`
- `tests/Feature/Tenant/TenantInvoiceHistoryTest.php`
- `tests/Feature/Billing/ReportsTest.php`

</canonical_refs>

<code_context>
## Existing Code Insights

- Entry-path and navigation logic are currently split between `LoginRedirector`, `DashboardUrlResolver`, `AppPanelProvider`, `config/tenanto.php`, and a custom navigation builder.
- Report builders and tenant invoice presenters already exist, but equivalent read behavior is still assembled across several separate support and UI classes.
- The codebase concerns audit already flags panel navigation as having multiple sources of truth, which makes Phase 3 a direct cleanup of a known drift area.

</code_context>

<deferred>
## Deferred Ideas

- Mutation-pipeline standardization and audit capture belong to Phase 4.
- Billing-rule extraction and money semantics belong to Phase 5.

</deferred>

---

*Phase: 03-surface-and-read-path-unification*
*Context gathered: 2026-03-19*
