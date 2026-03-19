# Phase 1: Safety Freeze and Guardrails - Context

**Gathered:** 2026-03-19
**Status:** Ready for planning

<domain>
## Phase Boundary

Phase 1 removes unsafe public and test-facing exposure from the live application and establishes the minimum enforced regression gates maintainers need before broader modernization work continues. This phase does not redesign workspace, billing, or mutation architecture; it freezes risky surfaces and makes regression checks unavoidable.

</domain>

<decisions>
## Implementation Decisions

### Test and debug surface policy
- Test-only routes must not be registered from the normal public route graph. They should only exist in explicitly approved testing contexts.
- `public/index.php` must remain the only public PHP entrypoint, with zero exceptions.
- Tests and documentation should be updated immediately to the safe pattern. Do not ship temporary shims or transitional allowances for removed public debug surfaces.
- Phase 1 must include targeted regression proof for removed public entrypoints and protected test routes rather than relying on manual review alone.

### CSP report endpoint policy
- Keep `/csp/report` publicly reachable.
- Harden the endpoint with an aggressive dedicated throttle rather than leaving it as an effectively unbounded public sink.
- Preserve the current accepted-report flow of recording a `SecurityViolation` and dispatching the `SecurityViolationDetected` event.
- Apply short retention with explicit cleanup for accepted CSP report records.

### PWA surface treatment
- Remove the current PWA surface entirely during Phase 1.
- Update tests immediately so they prove removal instead of preserving manifest or service-worker behavior.
- Remove package and configuration traces, not just the public assets.
- Phase 1 proof must cover full removal: public assets absent, rendered pages no longer emit PWA hooks, and package/config traces are gone.

### Merge gate strictness
- Add a real merge-time gate in Phase 1, but keep it lean rather than turning this phase into a general tooling expansion.
- Defer PHPStan bootstrap to a later phase; Phase 1 should not add static-analysis rollout work.
- For Phase 1, the accepted static-check layer is the curated executable architecture and inventory guard bundle rather than a repo-wide PHPStan or Larastan rollout.
- Gate a curated guard bundle rather than the entire test suite. The bundle should cover security, architecture inventory, and core billing invariants already identified in discussion.
- Use an explicit file-list command for the guard bundle instead of introducing new Pest grouping conventions in this phase.
- Add both a dedicated local entrypoint and a CI workflow that reuse the same guard command.

### Claude's Discretion
- Exact command names, workflow file names, throttle values, and retention windows may be chosen during planning as long as they enforce the policy decisions above.

</decisions>

<specifics>
## Specific Ideas

- "No temporary shims" for removed public debug or test exposure.
- Keep the CSP ingestion path's audit-record plus event-dispatch behavior after hardening.
- Prefer a fast, explicit regression gate over broad suite expansion during this phase.

</specifics>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Phase scope and milestone rules
- `.planning/ROADMAP.md` — Defines the fixed Phase 1 boundary, requirements mapping, and success criteria for Safety Freeze and Guardrails.
- `.planning/REQUIREMENTS.md` — Defines the linked requirement set for this phase, especially `SEC-05`, `GOV-03`, and `OPS-04`.
- `.planning/PROJECT.md` — Captures the project-wide non-negotiables: tenant safety, billing correctness, and aggressive cleanup without a rewrite.
- `.planning/STATE.md` — Records the current project position and confirms Phase 1 is the active focus.

### Codebase reference docs
- `.planning/codebase/CONCERNS.md` — Summarizes public-surface risk, legacy testing exposure, and other cleanup concerns already observed in the repository.
- `.planning/codebase/TESTING.md` — Summarizes current test organization and existing regression assets that can be reused in Phase 1.
- `.planning/codebase/STRUCTURE.md` — Provides the verified live structure of routes, public assets, panels, and support files that Phase 1 touches.

### Public and test-facing route surface
- `routes/web.php` — Shows the live public route graph, including the public CSP report endpoint and the current inclusion of `routes/testing.php`.
- `routes/testing.php` — Shows the existing test-only routes and their current `app()->runningUnitTests()` guards.
- `tests/Feature/Security/NoPublicDebugFilesTest.php` — Defines the current expectation that removed public debug entrypoints and `/sw.js` stay unavailable.

### CSP reporting and security behavior
- `app/Http/Controllers/CspViolationReportController.php` — Defines the current HTTP entrypoint for CSP reports.
- `app/Services/Security/SecurityMonitor.php` — Defines the current persistence and event-dispatch flow for accepted security violations.
- `tests/Feature/Security/SecurityHeadersTest.php` — Verifies current security headers, CSP reporting acceptance, and downstream security-violation behavior.

### PWA removal surface
- `composer.json` — Shows the current `erag/laravel-pwa` dependency and existing script surface.
- `config/pwa.php` — Shows current PWA configuration that Phase 1 should remove.
- `public/manifest.json` — Current public PWA asset to remove.
- `public/offline.html` — Current public PWA asset to remove.
- `public/sw.js` — Current public PWA asset to remove.
- `tests/Feature/Public/PwaIntegrationTest.php` — Current regression coverage that still expects manifest and service-worker behavior and must be inverted to removal proof.

### Regression guard assets
- `tests/Feature/Security/TenantIsolationTest.php` — Existing tenant-boundary regression coverage available for the curated guard bundle.
- `tests/Feature/Security/TenantPortalIsolationTest.php` — Existing tenant portal isolation coverage available for the curated guard bundle.
- `tests/Feature/Architecture/FilamentFoundationPlacementTest.php` — Existing architecture guard that protects the intended request/action/support foundation layout.
- `tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php` — Existing regression inventory guard for Filament resource coverage.
- `tests/Feature/Admin/InvoiceImmutabilityTest.php` — Existing billing invariant coverage relevant to the curated Phase 1 guard bundle.
- `tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php` — Existing billing invariant coverage relevant to the curated Phase 1 guard bundle.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `tests/Feature/Security/NoPublicDebugFilesTest.php`: Already proves removed public entrypoints stay unavailable and that `index.php` is the only allowed public PHP file.
- `tests/Feature/Security/SecurityHeadersTest.php`: Already exercises the CSP report endpoint, security headers, and downstream `SecurityViolation` behavior that Phase 1 intends to keep.
- `tests/Feature/Architecture/FilamentFoundationPlacementTest.php`: Already acts as a merge-time architecture guard without needing new framework tooling.
- `tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php`: Already acts as a coverage inventory guard for Filament resources.
- Existing targeted billing and tenant-isolation feature tests can be reused directly for the curated guard bundle instead of inventing new broad coverage.

### Established Patterns
- Test-only HTTP helpers currently live in `routes/testing.php`, but they are still loaded from `routes/web.php`; Phase 1 should preserve the testing-only intent while removing them from the normal route graph.
- Public CSP reporting is already separated into a controller plus `SecurityMonitor` service, which gives Phase 1 a clear hardening seam without changing the accepted-report domain flow.
- The repository currently favors explicit file-based Pest coverage over centralized grouping conventions for this kind of guard behavior.
- There is no real `.github/workflows` CI pipeline yet, so Phase 1 is establishing the first enforced merge gate rather than extending an existing one.

### Integration Points
- Route registration changes will connect primarily through `routes/web.php` and `routes/testing.php`.
- CSP hardening work will connect through `app/Http/Controllers/CspViolationReportController.php`, `app/Services/Security/SecurityMonitor.php`, and any supporting middleware, requests, or cleanup mechanisms added during planning.
- PWA removal will touch `composer.json`, `config/pwa.php`, `public/manifest.json`, `public/offline.html`, `public/sw.js`, and rendered-page regression tests.
- Merge gating will connect through a new local quality command plus a new `.github/workflows` entry that reuses the same guard command.

</code_context>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 01-safety-freeze-and-guardrails*
*Context gathered: 2026-03-19*
