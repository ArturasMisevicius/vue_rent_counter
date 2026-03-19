# Pitfalls Research

**Domain:** Brownfield multi-tenant Laravel billing and property management modernization
**Researched:** 2026-03-19
**Confidence:** MEDIUM

This pitfall set is intentionally specific to Tenanto's current cleanup mode: aggressive standardization of an existing Laravel 12 + Filament 5.3 + Livewire 4 multi-tenant billing/property application. The strongest conclusions come from the live repo context in `.planning/codebase/` plus current Laravel and Filament documentation. Several "common mistakes" below are informed inferences from that evidence, not direct vendor warnings, so they are written conservatively.

## Critical Pitfalls

### Pitfall 1: Treating tenant isolation as a convention instead of a hard contract

**What goes wrong:**
Teams refactor resources, Livewire pages, presenters, or actions into "cleaner" shared abstractions and accidentally remove the last tenant boundary on one code path. A single unscoped `findOrFail()`, `withoutGlobalScopes()`, early middleware query, or reused admin query builder can expose another organization's invoices, tenants, meter readings, or property data.

**Why it happens:**
Tenanto currently enforces workspace boundaries query-by-query across model scopes, Filament `getEloquentQuery()` overrides, policies, and page/action logic instead of through one mandatory boundary layer. Filament's tenancy docs explicitly warn that queries outside the tenant-aware panel are not automatically scoped, and disabling all global scopes disables tenancy scopes too.

**How to avoid:**
- Create one canonical workspace/tenant boundary layer for tenant-facing reads and writes.
- Require tenant/admin surfaces to start from approved scoped builders such as `forOrganizationWorkspace()`, `forTenantWorkspace()`, and `withTenantWorkspaceSummary()`.
- Ban naked model lookups for tenant data in code review unless the query begins from a scoped builder.
- Add an isolation regression suite that proves cross-organization and cross-tenant access returns `403` or `404` for every high-risk surface.
- Make tenant middleware persistent on Livewire AJAX requests when tenancy depends on middleware ordering.
- Treat any use of `withoutGlobalScopes()` or pre-tenant-resolution queries as security-reviewed exceptions.

**Warning signs:**
- New `findOrFail()` calls added in tenant/admin pages without a preceding workspace scope.
- `withoutGlobalScopes()` appears in a billing, tenant, or property query.
- A refactor moves queries into service providers, early middleware, or shared presenters before tenant context is resolved.
- Tests cover only same-tenant happy paths and do not attempt cross-organization access.
- A resource/page works in Filament but the same model is queried elsewhere without equivalent scoping.

**Phase to address:**
Phase 2: Tenant Boundary Consolidation

---

### Pitfall 2: Changing billing behavior while "just standardizing" services

**What goes wrong:**
An invoice refactor preserves method names and green tests but changes real financial meaning: due dates are interpreted differently, shared-service distribution drifts, rounding changes by a cent, finalized invoices mutate differently, or report aging logic changes. The result is silent trust loss, finance disputes, and painful manual reconciliation.

**Why it happens:**
Tenanto's billing behavior is concentrated in large orchestration services, and at least one existing report bug already shows how easy it is to age invoices from the wrong field. Brownfield billing logic usually contains historical business decisions that look duplicative until you compare real invoice outputs.

**How to avoid:**
- Freeze billing invariants before extraction: invoice total composition, rounding scale, aging rules, allocation math, finalization mutability, snapshot fields, and reminder triggers.
- Build characterization tests from real domain examples before simplifying calculators or invoice assemblers.
- Add fixture-based comparison tests for preview, finalization, overdue reporting, and shared-service allocation.
- Separate data fetching, math, document assembly, and persistence into smaller collaborators only after invariant coverage exists.
- Require business-signoff for any output diff in invoice totals, overdue counts, or generated line items.

**Warning signs:**
- PRs describe billing changes as "cleanup only" but modify calculators, assemblers, or report builders.
- A refactor removes "duplicate" branching without sample invoices proving equivalence.
- Invoice previews and finalized invoices are not diffed against known-good fixtures.
- A change touches `BillingService`, `InvoiceService`, and reporting in one sweep.
- No one can state the rounding, aging, or allocation rules in writing.

**Phase to address:**
Phase 3: Billing Characterization and Domain Extraction

---

### Pitfall 3: Renaming routes, panels, and navigation contracts all at once

**What goes wrong:**
Panel path changes, route name churn, resource renames, or tenant URL restructuring break sidebars, global search, deep links, emails, bookmarks, authorization assumptions, and tests. The app looks cleaner in code but becomes fragile in production because multiple surfaces still depend on old route strings and panel assumptions.

**Why it happens:**
Tenanto already has panel/navigation drift: imperative navigation in `AppPanelProvider.php`, role navigation config in `config/tenanto.php`, and a leftover custom navigation builder. The codebase also contains many direct `filament.admin.*` route checks. Filament's tenancy docs additionally warn that some tenant URL strategies can create route-parameter conflicts across the application.

**How to avoid:**
- Inventory all public and internal route names before changing panel IDs, panel paths, tenant route prefixes, or resource slugs.
- Collapse navigation to one authoritative source before renaming anything.
- Introduce route helpers or route-contract wrappers for shared navigation/search code instead of raw string duplication.
- Stage compatibility aliases and redirects before removing legacy names.
- Snapshot route lists and key navigation maps in tests so churn is intentional and reviewed.
- Treat `tenantDomain('{tenant:domain}')` or tenant-parameter changes as an architectural migration, not a cosmetic cleanup.

**Warning signs:**
- `Route::has()` and literal `filament.admin.*` strings appear across pages, search providers, topbar/sidebar components, and tests.
- A cleanup proposal changes panel ID, panel path, and tenant URL structure in the same phase.
- Navigation is updated in more than one file for the same change.
- Search providers or notifications build links from route names that are being renamed.
- The plan says "we'll just update broken links later."

**Phase to address:**
Phase 4: Routing and Panel Contract Unification

---

### Pitfall 4: Leaving public test/debug/support surfaces in production because they "aren't used"

**What goes wrong:**
Unlinked or low-traffic endpoints remain reachable in production and become attack, noise, or confusion surfaces. Even when they return `404` outside tests, they still bloat the public route graph and normalize unsafe patterns. CSRF-exempt write endpoints can also become amplification or log-pollution vectors.

**Why it happens:**
Cleanup efforts often focus on private architecture, not public exposure. In Tenanto, `routes/testing.php` is required from `routes/web.php`, `/csp/report` is CSRF-exempt, and public PWA assets still ship despite partial/incomplete product ownership. Laravel also warns that `APP_DEBUG=true` in production exposes sensitive configuration details.

**How to avoid:**
- Add a public-surface audit phase before structural refactors begin.
- Register test routes only in the testing environment, not from the normal web route graph.
- Rate-limit, size-limit, and retention-limit anonymous reporting endpoints such as `/csp/report`.
- Remove unfinished public assets or assign an owner and production criteria for them.
- Add deployment assertions that `APP_DEBUG=false` in production and that only config files call `env()`.
- Maintain a denylist for `/__test`, `/debug`, ad hoc health probes, and stray public executables/assets.

**Warning signs:**
- `routes/web.php` includes test-only route files.
- Public endpoints accept unauthenticated POSTs without throttling.
- Files such as `public/sw.js`, `public/manifest.json`, or other public artifacts have no active owner or rollout plan.
- Reviewers say "it's fine because nobody knows the URL."
- Production safety depends on runtime `abort_unless(app()->runningUnitTests(), 404)` instead of not registering the route.

**Phase to address:**
Phase 1: Surface Inventory and Safety Freeze

---

### Pitfall 5: Using destructive schema cleanup instead of expand/contract migration strategy

**What goes wrong:**
Schema "standardization" bundles renames, drops, type changes, and data backfills into one deploy. Large tenant/billing tables lock, queues/sessions/cache tables are disturbed, old code cannot roll forward/back cleanly, and partial migrations leave billing or tenancy data in inconsistent states.

**Why it happens:**
Brownfield teams treat schema cleanup like a greenfield reformat instead of an operational change. Laravel's migration docs encourage previewing SQL with `--pretend`, isolating deployment-time migrations with `--isolated`, and keeping explicit reversible `up()` / `down()` logic. Tenanto also lacks CI workflow enforcement and uses simplified test drivers, which lowers the chance of catching operational migration problems early.

**How to avoid:**
- Use expand/contract migrations for tenant and billing tables: add new structure first, dual-write or backfill second, cut over third, remove legacy columns last.
- Run `php artisan migrate --pretend` and `migrate:status` in review for risky changes.
- Use `php artisan migrate --isolated` in multi-server deployment flows.
- Rehearse backfills against a production-like copy and make them resumable.
- Keep queue/session/cache table changes isolated from unrelated billing refactors.
- Never couple destructive schema changes with route/panel churn in the same release.

**Warning signs:**
- A migration both renames/drops columns and updates all consuming code in one step.
- Backfill logic is embedded in an HTTP request or a non-resumable one-off script.
- Rollback strategy is "restore from backup if needed."
- Index additions are postponed even though filters/orderings already depend on them.
- The plan assumes SQLite test success proves production safety.

**Phase to address:**
Phase 5: Migration Safety and Data Backfill Rehearsal

---

### Pitfall 6: Believing the existing test suite is enough for aggressive refactors

**What goes wrong:**
Teams see many passing tests and assume cleanup is safe, but regressions slip through because the suite is optimized for happy-path integration on in-memory SQLite, sync queues, and array cache/session. Billing edge cases, queue timing, route churn, real database semantics, and Livewire/Filament state regressions survive until manual QA or production.

**Why it happens:**
Tenanto has a healthy amount of tests, but no CI workflow files, opt-in performance tests, no browser suite, and simplified test drivers by default. Laravel's testing docs make it clear that the testing environment swaps cache/session behavior, and parallel/database setup still needs explicit care.

**How to avoid:**
- Build a dedicated regression harness for modernization, not just feature correctness.
- Add characterization tests for the top-risk flows: tenant invoice history, meter submission, invoice finalization, reports, organization access, role redirects, and route inventory.
- Add production-like test lanes for database queue/cache/session behavior and at least one non-SQLite smoke path before high-risk releases.
- Add CI gates for Pest, static analysis, and formatting before broad cleanup branches merge.
- Require before/after route snapshots and billing fixture diffs for major consolidation PRs.

**Warning signs:**
- Review justification is "all tests pass" without any new risk-targeted tests.
- Billing/routing/authorization refactors land without fixture additions.
- Performance tests are not run for reporting or invoice generation changes.
- CI is still effectively local-only discipline.
- SQLite happy-path tests are being used to approve transactional or locking behavior changes.

**Phase to address:**
Phase 6: Regression Harness and CI Gate

---

### Pitfall 7: Flattening role differences into one generic "admin-like" standard

**What goes wrong:**
Standardization merges `SUPERADMIN`, `ADMIN`, `MANAGER`, and `TENANT` behavior into broad shared flows. A user gets the wrong navigation, an action becomes visible without authority, or legacy boolean/enum privilege signals drift apart and expose platform-level capabilities to the wrong actor.

**Why it happens:**
Tenanto already has mixed role abstractions (`isAdminLike()`, tenant-specific pages, and a known elevated-access dual-source issue from `role` plus legacy `is_super_admin`). Broad deduplication is tempting because many surfaces look similar until one policy edge case matters.

**How to avoid:**
- Write a role capability matrix before consolidating navigation, policies, or shared actions.
- Collapse elevated access onto one authoritative field and reconcile legacy data during migration.
- Keep policy checks close to the surface and add role-specific contract tests for every destructive/high-value action.
- Treat UI visibility and authorization as separate concerns; hiding a button is not enforcement.
- Prefer extracting shared mechanics beneath role-specific entry points instead of merging the entry points themselves.

**Warning signs:**
- New helpers or presenters use `isAdminLike()` to bypass more specific role checks.
- A cleanup PR removes policy coverage because "navigation already hides it."
- Role enum and boolean privilege fields can disagree.
- Manager and admin flows are combined without an explicit permission audit.
- Tenant portal behavior starts reusing admin resources directly.

**Phase to address:**
Phase 7: Authorization and Role Contract Cleanup

---

### Pitfall 8: Standardizing async and operational flows without proving real runtime behavior

**What goes wrong:**
The codebase looks more modern, but notifications, exports, health probes, and background work still behave only in local/sync mode. Production failures arrive when workers lag, jobs read uncommitted data, or "healthy" integration status pages are only checking config presence.

**Why it happens:**
Tenanto defaults to database-backed queues in app config, but tests run with `QUEUE_CONNECTION=sync`, notifications use `Queueable` without becoming true queued jobs, and current health checks are already documented as potentially false-positive. Laravel's queue docs warn that jobs may need `afterCommit()` behavior when dispatched around open transactions.

**How to avoid:**
- Decide explicitly which flows stay synchronous and which become queued during modernization.
- If a flow is queue-backed, test it with real queue workers and production-like drivers.
- Use `afterCommit()` or connection-level `after_commit` behavior for jobs triggered by invoice/finalization transactions.
- Replace config-only health probes with connectivity or end-to-end smoke probes.
- Add failed-job monitoring and operator runbooks before moving core billing notifications off sync execution.

**Warning signs:**
- "Queued later" is the answer for notification or report work with no worker test plan.
- Health checks only read config values or table presence.
- Notifications use `Queueable` but no `ShouldQueue` implementation or worker verification exists.
- New jobs dispatch from inside transactions without commit semantics being discussed.
- Runtime confidence comes from local `php artisan serve` behavior only.

**Phase to address:**
Phase 8: Operational Hardening

## Technical Debt Patterns

Shortcuts that seem reasonable but create long-term problems.

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Keep tenant scoping in each resource/page "for now" | Fast local refactor with minimal surface change | One missed query becomes a data-leak incident | Only as a short-lived transition inside Phase 2 with explicit audit list |
| Rename panel IDs/routes/resources in one cleanup pass | Fewer temporary compatibility layers | Broken deep links, navigation drift, brittle tests, support confusion | Never for core panel contracts |
| Replace billing branches before characterization tests exist | Smaller service classes quickly | Silent invoice/report drift and reconciliation work | Never for invoice totals, aging, or allocation logic |
| Ship test/support routes but hide them behind runtime guards | Avoids test bootstrap work | Public route clutter and normalization of unsafe exposure | Never outside the testing environment |
| Combine schema changes and data backfills in one migration | One deploy, fewer files | Long-running locks, failed rollbacks, tenant data inconsistency | Only for tiny, low-risk tables with verified runtime cost |
| Trust SQLite + sync queue green tests for production safety | Fast feedback | Missed locking, queue timing, and driver-specific regressions | Never as the only gate for risky modernization |

## Integration Gotchas

Common mistakes when connecting to external services.

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| Email notifications | Refactoring mail/invoice flows without verifying links, queue mode, and organization/tenant context | Add feature tests for reminder/invitation payloads, and verify queue semantics before backgrounding delivery |
| Broadcasting | Assuming log-based broadcasting behavior proves private-channel correctness | Test organization channel authorization explicitly and treat real broker rollout as a separate operational change |
| CSP violation intake | Treating unauthenticated report collection as harmless because payloads are "just logs" | Add throttling, payload limits, storage retention, and operator ownership |
| PWA/public assets | Leaving `manifest.json`, `sw.js`, and offline pages in place during cleanup because they are "not hurting anything" | Either complete tenant-safe PWA ownership or remove the public surface until intentionally scheduled |
| Local MCP/tooling assumptions | Baking local Herd-centric behavior into roadmap assumptions | Keep developer tooling separate from runtime architecture and verify any MCP requirement against the live repo |

## Performance Traps

Patterns that work at small scale but fail as usage grows.

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Billing/report queries refactored without production-scale fixtures | Acceptable local timings, slow invoice generation or reports for large organizations | Add repeatable large-fixture performance checks for invoices, meter readings, and reports | Usually once organizations have many meters/readings or monthly invoice batches grow materially |
| Widget/report logic doing runtime aggregation on every request | Slow admin dashboards and report pages, rising query counts | Precompute or cache aggregates and verify query counts around reporting changes | Breaks well before "massive" scale because billing/report data is relation-heavy |
| N+1 introduced during Blade/Filament cleanup | Pages remain correct but query counts spike sharply | Enable lazy-loading prevention in non-production and add query-budget tests for core pages | Breaks as soon as the first medium-sized organization loads relational tables |
| Backfills performed synchronously | Long deploys, timeouts, blocked requests | Use resumable background backfills with checkpoints | Breaks immediately on non-trivial tenant data volumes |

## Security Mistakes

Domain-specific security issues beyond general web security.

| Mistake | Risk | Prevention |
|---------|------|------------|
| Assuming Filament tenancy secures non-panel queries automatically | Cross-tenant data leakage | Scope all non-panel queries explicitly and test hostile cross-tenant access |
| Leaving `APP_DEBUG=true` or equivalent debug visibility in production | Sensitive config and error exposure | Enforce production env assertions and deployment checks |
| Using UI visibility as authorization | Privilege escalation through direct routes/actions | Put policies/middleware on every sensitive route, action, and page |
| Keeping test/support routes in the production route graph | Accidental exposure and operational confusion | Register them only in testing |
| Allowing anonymous writable support endpoints without throttling | Storage/log amplification and abuse | Add rate limits, payload limits, and retention rules |

## UX Pitfalls

Common user experience mistakes in this domain.

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| Billing output changes without explanation | Tenants and staff lose trust in invoice totals, overdue status, or reminders | Preserve existing semantics until a deliberate domain change is approved and communicated |
| Route/panel consolidation breaks bookmarks and emailed links | Users feel the product is unstable even if data is intact | Provide redirects, staged aliases, and migration notes for changed entry points |
| Role cleanup hides or misroutes valid workflows | Managers and tenants cannot reach expected tasks | Validate navigation and destination rules per role before removing old paths |
| False "healthy" admin status | Operators trust broken mail/queue/integration surfaces | Show real degraded states from actual connectivity and worker checks |

## "Looks Done But Isn't" Checklist

Things that appear complete but are missing critical pieces.

- [ ] **Tenant isolation cleanup:** Often missing hostile cross-tenant tests — verify every invoice, tenant, meter, property, and report surface rejects cross-organization access.
- [ ] **Billing refactor:** Often missing output characterization — verify preview, finalization, aging, and allocation fixtures match approved before/after expectations.
- [ ] **Panel/route consolidation:** Often missing route inventory protection — verify named-route snapshots, redirects, and navigation/search links before removing legacy names.
- [ ] **Migration cleanup:** Often missing rollout rehearsal — verify `--pretend`, rollback plan, backfill checkpoints, and prod-like rehearsal results.
- [ ] **Public-surface hardening:** Often missing route/asset audit — verify no `/__test` or debug routes are registered outside testing and every public asset/endpoint has an owner.
- [ ] **Regression safety:** Often missing CI enforcement — verify risky cleanup cannot merge without automated tests and static checks.
- [ ] **Operational modernization:** Often missing real worker/probe validation — verify queued and health-check flows against production-like runtime, not only sync/test defaults.

## Recovery Strategies

When pitfalls occur despite prevention, how to recover.

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Tenant isolation breach | HIGH | Freeze rollout, disable affected surface, audit access logs, patch scope entry point, add a failing hostile-access test, review all related query paths before re-enabling |
| Billing logic drift | HIGH | Stop automated billing/finalization for the affected flow, diff against known-good fixtures or sampled invoices, reconcile impacted invoices, ship a guarded fix with characterization coverage |
| Route/panel churn breakage | MEDIUM | Restore aliases/redirects, re-enable old route names temporarily, ship navigation/search fixes, then retry consolidation in smaller slices |
| Unsafe migration/backfill | HIGH | Halt rollout, stabilize schema at last safe state, restore from validated backup if data integrity is at risk, resume with resumable backfill and expand/contract plan |
| Public surface exposure | MEDIUM | Remove route/asset from production graph immediately, rotate secrets if debug data may have leaked, add production-route audit checks |
| False regression confidence | MEDIUM | Reproduce with production-like drivers/data, backfill missing characterization tests, and re-run release gating before resuming cleanup |

## Pitfall-to-Phase Mapping

How roadmap phases should address these pitfalls.

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Tenant isolation as convention | Phase 2: Tenant Boundary Consolidation | Cross-organization hostile-access suite passes for all high-risk surfaces |
| Billing behavior changed during cleanup | Phase 3: Billing Characterization and Domain Extraction | Approved fixture diffs show no unintended change in totals, aging, or allocation |
| Route/panel churn | Phase 4: Routing and Panel Contract Unification | Route snapshot, navigation/search tests, and redirect checks all pass |
| Public test/debug/support exposure | Phase 1: Surface Inventory and Safety Freeze | Production route/asset inventory excludes test/debug surfaces and writable endpoints are hardened |
| Destructive schema cleanup | Phase 5: Migration Safety and Data Backfill Rehearsal | `--pretend`, rehearsal, rollback plan, and resumable backfill evidence are documented |
| Weak regression confidence | Phase 6: Regression Harness and CI Gate | CI enforces risk-targeted tests and production-like smoke paths for critical changes |
| Role flattening and privilege drift | Phase 7: Authorization and Role Contract Cleanup | Role matrix and policy contract tests pass for every protected surface |
| Async/operational false confidence | Phase 8: Operational Hardening | Real queue/mail/health probes succeed in production-like conditions |

## Sources

- Internal project context:
  - `.planning/PROJECT.md`
  - `.planning/codebase/CONCERNS.md`
  - `.planning/codebase/TESTING.md`
  - `.planning/codebase/INTEGRATIONS.md`
- Live repository evidence:
  - `routes/web.php`
  - `routes/testing.php`
  - `app/Providers/Filament/AppPanelProvider.php`
  - `app/Filament/Support/Shell/Navigation/NavigationBuilder.php`
  - `config/queue.php`
  - `public/`
- Laravel 12 documentation:
  - Configuration: https://laravel.com/docs/12.x/configuration
  - Deployment: https://laravel.com/docs/12.x/deployment
  - Routing: https://laravel.com/docs/12.x/routing
  - Authorization: https://laravel.com/docs/12.x/authorization
  - Eloquent: https://laravel.com/docs/12.x/eloquent
  - Queues: https://laravel.com/docs/12.x/queues
  - Migrations: https://laravel.com/docs/12.x/migrations
  - Testing: https://laravel.com/docs/12.x/testing
- Filament 5 documentation:
  - Tenancy: https://filamentphp.com/docs/5.x/users/tenancy
  - Resources overview: https://filamentphp.com/docs/5.x/resources/overview
  - Panels overview: https://filamentphp.com/docs/5.x/panels/overview

---
*Pitfalls research for: Tenanto brownfield multi-tenant billing/property standardization*
*Researched: 2026-03-19*
