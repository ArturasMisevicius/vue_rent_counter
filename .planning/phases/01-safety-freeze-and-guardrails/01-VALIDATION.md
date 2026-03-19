---
phase: 1
slug: safety-freeze-and-guardrails
status: draft
nyquist_compliant: true
wave_0_complete: false
created: 2026-03-19
---

# Phase 1 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 4.4.2 on PHPUnit 12.5.12 |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test tests/Feature/Security/NoPublicDebugFilesTest.php tests/Feature/Security/SecurityHeadersTest.php tests/Feature/Security/TenantIsolationTest.php tests/Feature/Security/TenantPortalIsolationTest.php tests/Feature/Filament/SuperadminResourcesTest.php tests/Feature/Architecture/FilamentFoundationPlacementTest.php tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php tests/Feature/Admin/InvoiceImmutabilityTest.php tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php --compact` |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~5 seconds quick run, ~120 seconds full suite |

---

## Sampling Rate

- **After every task commit:** Run `vendor/bin/pint --test && composer guard:phase1`
- **After every plan wave:** Run `php artisan test --compact`
- **Before `$gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 120 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 01-01-01 | 01 | 1 | SEC-05 | feature + architecture | `php artisan test tests/Feature/Security/NoPublicDebugFilesTest.php tests/Feature/Architecture/Phase1PublicSurfaceInventoryTest.php --compact` | ❌ W0 | ⬜ pending |
| 01-02-01 | 02 | 1 | SEC-05 | feature | `php artisan test tests/Feature/Public/PwaIntegrationTest.php tests/Feature/Security/NoPublicDebugFilesTest.php --compact` | ❌ W0 | ⬜ pending |
| 01-03-01 | 03 | 1 | GOV-03 | formatting + integration | `vendor/bin/pint --test && composer guard:phase1` | ❌ W0 | ⬜ pending |
| 01-04-01 | 04 | 1 | OPS-04 | feature | `php artisan test tests/Feature/Security/TenantIsolationTest.php tests/Feature/Security/TenantPortalIsolationTest.php tests/Feature/Filament/SuperadminResourcesTest.php tests/Feature/Admin/InvoiceImmutabilityTest.php tests/Feature/Admin/TenantUnassignmentInvoiceRetentionTest.php --compact` | ✅ | ⬜ pending |
| 01-05-01 | 05 | 1 | SEC-05 | feature | `php artisan test tests/Feature/Security/SecurityHeadersTest.php tests/Feature/Security/CspReportRateLimitTest.php --compact` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/Architecture/Phase1PublicSurfaceInventoryTest.php` — prove `routes/web.php` no longer imports `routes/testing.php` and the live route graph does not expose `__test/*`
- [ ] `tests/Feature/Public/PwaIntegrationTest.php` — invert current expectations to prove manifest and service-worker hooks are absent
- [ ] `tests/Feature/Security/CspReportRateLimitTest.php` — prove the public CSP endpoint is throttled while accepted reports still persist and dispatch events
- [ ] `composer.json` — add a shared `guard:phase1` entrypoint used by both local development and CI
- [ ] `.github/workflows/phase-1-guardrails.yml` — add the first required repository CI workflow for the shared guard command

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Required-check branch protection is enabled for the new workflow | GOV-03 | GitHub branch protection is external to the repo and cannot be enforced by Pest alone | After the workflow is merged, confirm the Phase 1 guard workflow is marked required on the default branch in repository settings |

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all missing references
- [x] No watch-mode flags
- [x] Feedback latency < 120s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
