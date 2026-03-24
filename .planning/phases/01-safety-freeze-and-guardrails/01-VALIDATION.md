---
phase: 1
slug: safety-freeze-and-guardrails
status: blocked
nyquist_compliant: true
wave_0_complete: true
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
| **Quick run command** | `Run the active task's <verify> command; after 01-04-01 completes, prefer composer guard:phase1` |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~5 seconds quick run, ~120 seconds full suite |

---

## Sampling Rate

- **After every task commit:** Run that task's `<verify>` command from its PLAN.md.
- **After 01-04-01 is complete:** Promote `composer guard:phase1` to the default quick gate for the remaining Phase 1 work.
- **After every plan wave:** Run `php artisan test --compact`
- **Before `$gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 120 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 01-01-01 | 01 | 1 | SEC-05 | feature + architecture | `php artisan test tests/Feature/Security/NoPublicDebugFilesTest.php tests/Feature/Architecture/Phase1PublicSurfaceInventoryTest.php --compact` | ✅ verified | ✅ complete |
| 01-02-01 | 02 | 1 | SEC-05 | feature + architecture | `php artisan test tests/Feature/Public/PwaIntegrationTest.php tests/Feature/Architecture/PwaSurfaceRemovalInventoryTest.php --compact` | ✅ verified | ✅ complete |
| 01-02-02 | 02 | 1 | SEC-05 | architecture | `php artisan test tests/Feature/Public/PwaIntegrationTest.php tests/Feature/Architecture/PwaSurfaceRemovalInventoryTest.php --compact` | ✅ verified | ✅ complete |
| 01-03-01 | 03 | 2 | SEC-05 | feature | `php artisan test tests/Feature/Security/SecurityHeadersTest.php tests/Feature/Security/CspReportRateLimitTest.php --compact` | ✅ verified | ✅ complete |
| 01-04-01 | 04 | 3 | GOV-03, OPS-04 | formatting + architecture/inventory | `vendor/bin/pint --test app config database routes tests resources/views bootstrap/app.php && php artisan test tests/Feature/Architecture/Phase1PublicSurfaceInventoryTest.php tests/Feature/Architecture/PwaSurfaceRemovalInventoryTest.php tests/Feature/Architecture/FilamentFoundationPlacementTest.php tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php --compact` | ✅ verified | ✅ complete |
| 01-04-02 | 04 | 3 | GOV-03, OPS-04 | integration | `composer guard:phase1` | ✅ verified | ✅ complete |
| 01-05-01 | 05 | 4 | GOV-03 | blocking post-merge checkpoint | `Manual prerequisite: workflow is merged to remote \`main\` and has one successful \`Phase 1 Guardrails\` run` | ⚠ external remote run required | ⚠ blocked external |
| 01-05-02 | 05 | 4 | GOV-03 | integration | `if command -v gh >/dev/null 2>&1; then gh api repos/ArturasMisevicius/vue_rent_counter/branches/main/protection --jq '.required_status_checks.checks[]?.context'; else curl -fsSL -H "Accept: application/vnd.github+json" -H "Authorization: Bearer \${GITHUB_TOKEN:?GITHUB_TOKEN is required when gh is unavailable}" https://api.github.com/repos/ArturasMisevicius/vue_rent_counter/branches/main/protection; fi \| rg 'Phase 1 Guardrails'` | ⚠ external auth/tooling may be required | ⚠ blocked external |

*Status: ⬜ pending · ✅ complete · ❌ red · ⚠ blocked external*

---

## Wave 0 Requirements

Existing phase plans create the missing verification assets in execution order; no separate Wave 0 plan is required.

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Task `01-05-01`: first successful remote workflow run exists on `main` | GOV-03 | The required status check cannot be configured until the workflow has been merged and run once on the remote branch | Merge the workflow change, wait for one successful `Phase 1 Guardrails` run on remote `main`, then resume execution for Task `01-05-02` |
| Authentication or tooling fallback for Task `01-05-02` | GOV-03 | Branch protection is planned as API/CLI automation first, but the executor may still hit a missing `gh` install or missing GitHub auth gate at runtime | If the automation step cannot authenticate or lacks CLI tooling, run `php artisan ops:phase1-guardrails-branch-protection` on an authorized machine to print the exact endpoint, payload, apply, and verify commands, then execute the printed remote commands after credentials are available |

---

## Validation Sign-Off

- [x] All executable auto tasks have concrete `<verify>` commands and the one post-merge checkpoint prerequisite is explicitly tracked
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Validation strategy matches the current 5-plan / 4-wave phase graph
- [x] GitHub branch protection is represented as post-merge checkpoint `01-05-01` plus auto task `01-05-02`
- [x] No watch-mode flags
- [x] Feedback latency < 120s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** local automated verification complete on 2026-03-24; remote branch-protection checkpoint still blocked pending GitHub access and first remote `main` workflow run
