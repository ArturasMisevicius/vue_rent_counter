# Phase 1: Safety Freeze and Guardrails - Research

**Researched:** 2026-03-19
**Domain:** Laravel 12 public-surface hardening, regression guardrails, and merge-time CI enforcement
**Confidence:** MEDIUM

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
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
- Gate a curated guard bundle rather than the entire test suite. The bundle should cover security, architecture inventory, and core billing invariants already identified in discussion.
- Use an explicit file-list command for the guard bundle instead of introducing new Pest grouping conventions in this phase.
- Add both a dedicated local entrypoint and a CI workflow that reuse the same guard command.

### Claude's Discretion
- Exact command names, workflow file names, throttle values, and retention windows may be chosen during planning as long as they enforce the policy decisions above.

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| SEC-05 | Public debug, test, or diagnostic entrypoints are removed or made unavailable outside explicitly approved development or testing contexts. | Recommends deleting the live `routes/testing.php` inclusion, relying on test-bootstrap route registration already present in `tests/Pest.php`, extending public-surface regression tests beyond 404 checks, and removing the PWA package/config/assets rather than hiding them. |
| GOV-03 | Maintainers have merge-time safety gates for formatting, static analysis, and regression tests so high-risk modernization changes are blocked before release. | Recommends one shared local/CI guard command, `vendor/bin/pint --test` for formatting, executable architecture/inventory guard tests as the lean Phase 1 static-check layer, and a required GitHub Actions workflow that reuses the same guard command. |
| OPS-04 | Regression coverage exists for tenant isolation, role-bound access, and core billing invariants before modernization changes ship. | Reuses existing tenant-isolation, role-bound access, architecture inventory, and billing invariant tests in a verified explicit file-list guard bundle that ran green locally in 4.57s. |
</phase_requirements>

## Summary

Phase 1 can stay lean if it is planned as four bounded workstreams: remove public test/debug exposure, harden the public CSP intake path, remove the half-enabled PWA surface completely, and add one shared merge gate that runs formatting plus a curated regression bundle. The repository already contains most of the regression assets needed for that guard bundle, and the current test bootstrap already knows how to register helper routes without relying on a live public `routes/testing.php` include.

The biggest planning trap is mistaking "returns 404" for "is no longer public". Today the application still imports `routes/testing.php` from [`routes/web.php`](/Users/andrejprus/Herd/tenanto/routes/web.php), and `php artisan route:list --path=__test --json` in the normal local app shows the `__test/*` routes are present in the live route graph. That violates the locked decision even though individual requests may 404 outside `runningUnitTests()`. The planner should therefore treat public-surface removal as both a runtime behavior change and an architecture/inventory proof problem.

The other non-obvious risk is the PWA package. Local Blade files do not visibly render manifest or service-worker markup, but the package injects Blade directives and published assets from auto-discovery. That means Phase 1 removal must cover Composer/package auto-discovery, published config, and public assets together. Deleting `public/sw.js` alone is not enough.

**Primary recommendation:** Keep Phase 1 dependency-light: remove live public test/PWA surfaces, harden `/csp/report` with a named limiter plus prunable tagged telemetry, and enforce one shared Pint + explicit Pest guard bundle through GitHub Actions.

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| `laravel/framework` | `12.54.1` | Named route limiters, route middleware, Eloquent pruning, scheduler | First-party primitives already installed; no new dependency needed for throttling, cleanup, or routing guardrails. |
| `pestphp/pest` | `4.4.2` | Curated explicit-file regression bundle | Already installed, already used across the repo, and supports the fast file-list guard pattern locked for Phase 1. |
| `phpunit/phpunit` | `12.5.12` | Underlying runner for Pest | Existing test foundation; no new setup cost. |
| `laravel/pint` | `1.29.0` | Merge-time formatting gate | Already installed and officially supported by Laravel for CI via `--test`. |
| GitHub Actions | workflow syntax current as of 2026-03-19 | Required merge gate orchestration | Repo-native CI layer; lets the team block unsafe merges with one required check. |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `actions/checkout` | `v6` | Checkout repository in CI | Use in the Phase 1 guard workflow; official releases are already on `v6`. |
| `shivammathur/setup-php` | `v2` | Provision PHP 8.5 and Composer tooling in CI | Use for the guard workflow's PHP runtime. |
| `actions/cache` | `v5` | Cache Composer downloads in CI | Use to keep the guard workflow lean without changing its behavior. |
| `erag/laravel-pwa` | `2.0.0` | Removal target, not a recommended dependency | Keep only long enough to remove it cleanly with tests proving the surface is gone. |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Test-bootstrap route registration | Public `routes/testing.php` with `abort_unless(app()->runningUnitTests(), 404)` | Rejected: still leaks test routes into the live route graph. |
| `MassPrunable` cleanup for CSP telemetry | `Prunable` cleanup | Use `Prunable` only if delete-time side effects are required; otherwise `MassPrunable` is simpler and faster. |
| Explicit file-list guard bundle | Full `php artisan test` on every Phase 1 merge gate | Full suite is safer but slower; the user explicitly locked Phase 1 to a lean curated bundle. |
| Executable architecture/inventory guard tests plus Pint | PHPStan/Larastan rollout in Phase 1 | Rejected by locked decision; Phase 1 should not expand into a full static-analysis bootstrap. |

**Installation:**
```bash
# No new Composer packages are recommended for Phase 1.
composer install
```

**Version verification:** Local versions were verified on 2026-03-19 with `php artisan about` and `composer show`: Laravel `12.54.1` (released 2026-03-10), Pint `1.29.0` (released 2026-03-12), Pest `4.4.2` (released 2026-03-10), PHPUnit `12.5.12` (released 2026-02-16), and `erag/laravel-pwa` `2.0.0` (released 2026-03-18). CI action majors were verified from official docs/release pages on 2026-03-19: `actions/checkout@v6`, `actions/cache@v5`, and `shivammathur/setup-php@v2`.

## Architecture Patterns

### Recommended Project Structure
```text
app/
├── Providers/AppServiceProvider.php      # Named rate limiter registration
├── Models/SecurityViolation.php          # Tagged telemetry retention via pruning
├── Http/Controllers/CspViolationReportController.php
routes/
├── web.php                               # Public routes only
└── console.php                           # model:prune schedule
tests/
├── Pest.php                              # Shared test-only route registration
├── Feature/Security/                     # Public-surface and CSP regression
├── Feature/Architecture/                 # Route/public-surface inventory guards
├── Feature/Filament/                     # Role-bound access guard
└── Feature/Admin/                        # Billing invariants and inventory guards
.github/workflows/
└── phase-1-guardrails.yml                # Required PR gate using shared command
```

### Pattern 1: Test-Only Routes Live In Test Bootstrap
**What:** Do not register `__test/*` endpoints from the normal public route graph. Register them from `tests/Pest.php` or file-local test helpers only.
**When to use:** Any helper route that exists solely to support feature tests.
**Example:**
```php
// Source: project pattern in tests/Pest.php and Laravel testing docs
if (! Route::has('test.intended')) {
    Route::middleware(['web', 'auth'])
        ->get('/__test/intended', fn () => 'intended')
        ->name('test.intended');
}
```

### Pattern 2: Public CSP Intake Uses A Dedicated Named Limiter
**What:** Keep `/csp/report` public, but attach a dedicated named limiter and keep the controller/service flow thin.
**When to use:** Public-but-writeable telemetry endpoints such as CSP reports.
**Example:**
```php
// Source: https://laravel.com/docs/12.x/routing
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('security-csp-report', function (Request $request): Limit {
    return Limit::perMinute(10)->by('csp-report|'.$request->ip());
});
```

```php
Route::post('/csp/report', CspViolationReportController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->middleware('throttle:security-csp-report')
    ->name('security.csp.report');
```

### Pattern 3: Retention Uses Eloquent Pruning, Not A Custom Cleanup Command
**What:** Add a targeted pruning rule to `SecurityViolation` and schedule `model:prune` from `routes/console.php`.
**When to use:** Short-lived CSP telemetry records that do not need delete-time events.
**Example:**
```php
// Source: https://laravel.com/docs/12.x/eloquent#pruning-models
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;

class SecurityViolation extends Model
{
    use MassPrunable;

    public function prunable(): Builder
    {
        return static::query()
            ->where('type', SecurityViolationType::DATA_ACCESS)
            ->where('metadata->source', 'csp-report') // project-specific inference
            ->where('occurred_at', '<=', now()->subDays(14));
    }
}
```

```php
// Source: https://laravel.com/docs/12.x/eloquent#pruning-models
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', [
    '--model' => [SecurityViolation::class],
])->daily();
```

### Pattern 4: One Guard Command, Two Callers
**What:** Create one local entrypoint and make the GitHub Actions workflow call the exact same command.
**When to use:** Any required merge gate in this repository.
**Example:**
```yaml
# Source: GitHub Actions workflow syntax docs plus official action release pages
name: Phase 1 Guardrails

on:
  pull_request:
    branches: [main]
  push:
    branches: [main]

jobs:
  phase1-guard:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v6

      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.5'

      - name: Composer cache dir
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> "$GITHUB_OUTPUT"

      - uses: actions/cache@v5
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - run: composer install --no-interaction --prefer-dist --no-progress
      - run: composer guard:phase1
```

### Anti-Patterns to Avoid
- **Public 404 guards for test routes:** A route that is still listed by `route:list` is still part of the live surface, even if it aborts.
- **Partial PWA removal:** Deleting `public/sw.js` without removing the package/config leaves invisible injected hooks behind.
- **Inline CI commands drifting from local commands:** The workflow and local developer command must call the same entrypoint.
- **Broad pruning on `security_violations`:** Retention should target CSP-sourced rows explicitly, not every security record forever.
- **Phase 1 PHPStan expansion:** The locked decision is to keep this phase lean; do not let GOV-03 balloon into a repo-wide type-fixing project here.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Public endpoint throttling | Manual cache counters in the controller | Laravel named limiters + `throttle` middleware | First-party, declarative, and already used in this repo for login/password reset. |
| Telemetry retention | Custom delete command or ad hoc job | `MassPrunable` / `model:prune` | First-party cleanup path with scheduler support and low maintenance cost. |
| Test-only route safety | Public runtime route plus `abort_unless()` | Test-bootstrap route registration in `tests/Pest.php` | Matches the locked policy and the repo already uses this pattern. |
| Merge-gate orchestration | Duplicate shell logic in README and workflow YAML | One composer/script entrypoint called locally and in CI | Prevents drift and keeps the phase easy to operate. |
| PWA shutdown | Manual Blade flags or leaving package auto-discovery installed | Remove package/config/assets and invert regression tests | The package injects hooks via service provider/directives, so partial removal is unreliable. |

**Key insight:** Phase 1 does not need new infrastructure so much as it needs correct wiring. Laravel already provides the throttle, pruning, scheduler, and testing primitives. The repository already has the guard tests. Planning should focus on removing unsafe exposure and making the existing protection reusable and mandatory.

## Common Pitfalls

### Pitfall 1: 404 Is Not The Same As Not Public
**What goes wrong:** Test routes still exist in the live route graph even when they return 404 outside tests.
**Why it happens:** [`routes/web.php`](/Users/andrejprus/Herd/tenanto/routes/web.php) currently unconditionally requires [`routes/testing.php`](/Users/andrejprus/Herd/tenanto/routes/testing.php).
**How to avoid:** Remove the live include and let tests register helper routes explicitly from `tests/Pest.php` or file-local setup.
**Warning signs:** `php artisan route:list --path=__test --json` returns rows in the normal local app.

### Pitfall 2: CSP Retention Without A Source Marker Becomes Unsafe Later
**What goes wrong:** A future prune query may delete unrelated `SecurityViolation` records because CSP reports are not explicitly tagged.
**Why it happens:** The accepted-report flow currently records generic `SecurityViolation` rows and only the CSP controller calls `recordViolation()` today.
**How to avoid:** Add a stable marker such as `metadata['source'] = 'csp-report'` when ingesting the report, then prune only those rows.
**Warning signs:** A prune query only filters by `type`, `created_at`, or `occurred_at`.

### Pitfall 3: PWA Hooks Look Invisible In The App Code But Still Render
**What goes wrong:** Planner assumes there are no PWA hooks because local Blade grep is clean, yet rendered pages still emit manifest/service-worker markup.
**Why it happens:** The package is auto-discovered and injects Blade directives/service-worker script support from the package layer, not from visible app templates.
**How to avoid:** Remove `erag/laravel-pwa` from Composer, remove `config/pwa.php`, remove published public assets, and invert the existing PWA integration tests.
**Warning signs:** `bootstrap/providers.php` looks clean but pages still contain `rel="manifest"` or `serviceWorker.register`.

### Pitfall 4: Local And CI Guard Commands Drift
**What goes wrong:** A PR is green in CI but developers are not actually running the same checks locally, or vice versa.
**Why it happens:** The workflow hardcodes commands instead of calling one shared entrypoint.
**How to avoid:** Put the curated guard bundle behind one named local command and reuse it from the workflow.
**Warning signs:** README, workflow YAML, and planning docs all list slightly different test files or flags.

### Pitfall 5: GOV-03 Can Balloon Into A Full Tooling Rollout
**What goes wrong:** Phase 1 grows into PHPStan config, baselines, and repo-wide type cleanup work.
**Why it happens:** The requirement text says "static analysis", but the locked phase decision explicitly defers PHPStan bootstrap.
**How to avoid:** Treat Phase 1's static-check layer as Pint plus executable architecture/inventory guard tests, and record full PHPStan/Larastan rollout as later work.
**Warning signs:** The plan starts adding `phpstan.neon`, baselines, or broad annotation sweeps.

### Pitfall 6: Planning Assumes Boost MCP Commands That This Repo Does Not Expose
**What goes wrong:** The plan blocks on MCP startup steps that are not actually available in this repository.
**Why it happens:** `docs/SESSION-BOOTSTRAP.md` confirms `boost:mcp` and `mcp:start tenanto` are not registered here.
**How to avoid:** Base Phase 1 verification on artisan commands, direct file inspection, Pint, and Pest.
**Warning signs:** The plan treats Boost MCP as a mandatory prerequisite for implementation or CI.

## Code Examples

Verified patterns from official sources and repo evidence:

### Attach A Named Limiter To The Public CSP Route
```php
// Source: https://laravel.com/docs/12.x/routing
RateLimiter::for('security-csp-report', fn (Request $request) =>
    Limit::perMinute(10)->by('csp-report|'.$request->ip())
);

Route::post('/csp/report', CspViolationReportController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->middleware('throttle:security-csp-report');
```

### Schedule Targeted Model Pruning
```php
// Source: https://laravel.com/docs/12.x/eloquent#pruning-models
Schedule::command('model:prune', [
    '--model' => [SecurityViolation::class],
])->daily();
```

### CI-Friendly Formatting Gate
```bash
# Source: https://laravel.com/docs/12.x/pint
./vendor/bin/pint --test
```

### Local Guard Bundle Prototype Verified In This Repo
```bash
# Repo-verified on 2026-03-19; this passed in 4.57s.
php artisan test \
  tests/Feature/Security/NoPublicDebugFilesTest.php \
  tests/Feature/Security/SecurityHeadersTest.php \
  tests/Feature/Security/TenantIsolationTest.php \
  tests/Feature/Security/TenantPortalIsolationTest.php \
  tests/Feature/Filament/SuperadminResourcesTest.php \
  tests/Feature/Architecture/FilamentFoundationPlacementTest.php \
  tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php \
  tests/Feature/Admin/InvoiceImmutabilityTest.php \
  tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php \
  --compact
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Keep test-only routes in the live route graph and hide them behind runtime 404 guards | Register helper routes only inside the testing context | Current repo already has `tests/Pest.php` support for this; Phase 1 should finish the shift | Removes unsafe public route exposure instead of merely masking it |
| Write custom cleanup commands for short-lived rows | Use `Prunable` / `MassPrunable` plus scheduled `model:prune` | Current Laravel 12 docs | Smaller maintenance surface and easier retention testing |
| Treat formatting as a local developer habit | Use `pint --test` in a required workflow | Current Laravel Pint docs | Makes code-style regressions block merges instead of relying on discipline |
| Run broad CI commands inline in YAML | Reuse one shared local command from CI | Current GitHub Actions best practice | Prevents local/CI drift and keeps the phase easy to reason about |

**Deprecated/outdated:**
- Public `routes/testing.php` inclusion from the normal web graph: outdated for this phase because the repo already has safer test-bootstrap registration patterns.
- Partial PWA disablement while keeping `erag/laravel-pwa` installed: outdated because the package still injects hooks through auto-discovery.
- `actions/checkout@v5` in older examples: the official releases page is already on `v6`; pin a deliberate major and review periodically.

## Open Questions

1. **How strictly should GOV-03 interpret "static analysis" in Phase 1?**
   - What we know: there is no `phpstan.neon`, `psalm.xml`, or repo-local static-analysis config; the locked decision explicitly defers PHPStan bootstrap.
   - What's unclear: whether the planner should treat executable architecture/inventory guard tests as the Phase 1 static-check layer or record GOV-03 as only partially closed until later.
   - Recommendation: plan Pint + executable architecture/inventory guards + curated regression bundle now, and explicitly log full PHPStan/Larastan rollout as later work rather than forcing it into Phase 1.

2. **What retention window should Phase 1 choose for accepted CSP report records?**
   - What we know: the user wants an aggressive dedicated throttle and short explicit retention; exact values are left to planning.
   - What's unclear: whether the team wants a very short incident-response window or a slightly longer audit window.
   - Recommendation: default to 14 days unless maintainers have a strong operational reason to keep noisy CSP telemetry longer.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest `4.4.2` on PHPUnit `12.5.12` |
| Config file | [`phpunit.xml`](/Users/andrejprus/Herd/tenanto/phpunit.xml) |
| Quick run command | `php artisan test tests/Feature/Security/NoPublicDebugFilesTest.php tests/Feature/Security/SecurityHeadersTest.php tests/Feature/Security/TenantIsolationTest.php tests/Feature/Security/TenantPortalIsolationTest.php tests/Feature/Filament/SuperadminResourcesTest.php tests/Feature/Architecture/FilamentFoundationPlacementTest.php tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php tests/Feature/Admin/InvoiceImmutabilityTest.php tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php --compact` |
| Full suite command | `php artisan test --compact` |

### Phase Requirements -> Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| SEC-05 | Public debug PHP files stay gone, live route graph stops exposing `__test/*`, and PWA traces are fully removed | feature + architecture | `php artisan test tests/Feature/Security/NoPublicDebugFilesTest.php tests/Feature/Public/PwaIntegrationTest.php tests/Feature/Architecture/Phase1PublicSurfaceInventoryTest.php -x` | `❌ Wave 0` |
| GOV-03 | Formatting plus executable architecture/inventory checks plus curated regressions are run from one shared local/CI command | architecture + integration | `vendor/bin/pint --test && composer guard:phase1` | `❌ Wave 0` |
| OPS-04 | Tenant isolation, role-bound access, and billing invariants are all present in the guard bundle | feature | `php artisan test tests/Feature/Security/TenantIsolationTest.php tests/Feature/Security/TenantPortalIsolationTest.php tests/Feature/Filament/SuperadminResourcesTest.php tests/Feature/Admin/InvoiceImmutabilityTest.php tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php --compact -x` | `✅` |

### Sampling Rate
- **Per task commit:** `vendor/bin/pint --test && composer guard:phase1`
- **Per wave merge:** `php artisan test --compact`
- **Phase gate:** Full suite green plus required GitHub Actions guard workflow green before `/gsd:verify-work`

### Wave 0 Gaps
- [ ] [`tests/Feature/Architecture/Phase1PublicSurfaceInventoryTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Architecture/Phase1PublicSurfaceInventoryTest.php) — prove `routes/web.php` no longer imports `routes/testing.php` and that Phase 1 public-surface rules stay true
- [ ] [`tests/Feature/Public/PwaIntegrationTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Public/PwaIntegrationTest.php) — invert current expectations to absence checks and add full removal proof for manifest/service-worker hooks
- [ ] [`tests/Feature/Security/CspReportRateLimitTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Security/CspReportRateLimitTest.php) — prove the public CSP endpoint is throttled without breaking accepted-report persistence/event behavior
- [ ] [`composer.json`](/Users/andrejprus/Herd/tenanto/composer.json) — add one shared guard entrypoint such as `guard:phase1` so local and CI commands cannot drift
- [ ] [`.github/workflows/phase-1-guardrails.yml`](/Users/andrejprus/Herd/tenanto/.github/workflows/phase-1-guardrails.yml) — first required CI workflow for this repository

## Sources

### Primary (HIGH confidence)
- Local repo inspection on 2026-03-19:
  - [`routes/web.php`](/Users/andrejprus/Herd/tenanto/routes/web.php)
  - [`routes/testing.php`](/Users/andrejprus/Herd/tenanto/routes/testing.php)
  - [`tests/Pest.php`](/Users/andrejprus/Herd/tenanto/tests/Pest.php)
  - [`app/Providers/AppServiceProvider.php`](/Users/andrejprus/Herd/tenanto/app/Providers/AppServiceProvider.php)
  - [`app/Models/SecurityViolation.php`](/Users/andrejprus/Herd/tenanto/app/Models/SecurityViolation.php)
  - [`app/Http/Controllers/CspViolationReportController.php`](/Users/andrejprus/Herd/tenanto/app/Http/Controllers/CspViolationReportController.php)
  - [`tests/Feature/Security/NoPublicDebugFilesTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Security/NoPublicDebugFilesTest.php)
  - [`tests/Feature/Security/SecurityHeadersTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Security/SecurityHeadersTest.php)
  - [`tests/Feature/Security/TenantIsolationTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Security/TenantIsolationTest.php)
  - [`tests/Feature/Security/TenantPortalIsolationTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Security/TenantPortalIsolationTest.php)
  - [`tests/Feature/Filament/SuperadminResourcesTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Filament/SuperadminResourcesTest.php)
  - [`tests/Feature/Architecture/FilamentFoundationPlacementTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Architecture/FilamentFoundationPlacementTest.php)
  - [`tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php)
  - [`tests/Feature/Admin/InvoiceImmutabilityTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Admin/InvoiceImmutabilityTest.php)
  - [`tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php)
- Laravel official docs:
  - https://laravel.com/docs/12.x/routing
  - https://laravel.com/docs/12.x/eloquent#pruning-models
  - https://laravel.com/docs/12.x/pint
  - https://laravel.com/docs/12.x/testing
- GitHub official docs:
  - https://docs.github.com/en/actions/automating-your-workflow-with-github-actions/workflow-syntax-for-github-actions
  - https://docs.github.com/en/actions/concepts/workflows-and-actions/dependency-caching

### Secondary (MEDIUM confidence)
- Official GitHub release pages:
  - https://github.com/actions/checkout/releases
  - https://github.com/shivammathur/setup-php/releases
- Installed package source and docs for the removal target:
  - [`vendor/erag/laravel-pwa/src/EragLaravelPwaServiceProvider.php`](/Users/andrejprus/Herd/tenanto/vendor/erag/laravel-pwa/src/EragLaravelPwaServiceProvider.php)
  - [`vendor/erag/laravel-pwa/README.md`](/Users/andrejprus/Herd/tenanto/vendor/erag/laravel-pwa/README.md)

### Tertiary (LOW confidence)
- None

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - based on installed package versions, verified commands, and first-party Laravel/GitHub documentation
- Architecture: HIGH - driven primarily by direct repo inspection and existing passing guard tests
- Pitfalls: MEDIUM - most are directly observed, but GOV-03's exact "static analysis" interpretation is still a planning judgment call

**Research date:** 2026-03-19
**Valid until:** 2026-04-18
