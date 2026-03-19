---
phase: 01-safety-freeze-and-guardrails
plan: "01"
subsystem: security
tags: [routing, pest, security, regression]
requires: []
provides:
  - live web routes no longer include the public testing route file
  - external route inventory proof for missing __test routes
  - Pest-only helper route proof for auth-redirect coverage
affects: [phase-1, auth-tests, route-guardrails]
tech-stack:
  added: []
  patterns:
    - test-only helper routes live in tests/Pest.php instead of public route files
key-files:
  created:
    - tests/Feature/Architecture/Phase1PublicSurfaceInventoryTest.php
  modified:
    - routes/web.php
    - routes/testing.php
key-decisions:
  - "Keep shared __test helper routes defined only in tests/Pest.php and out of the public route graph."
patterns-established:
  - "Public route exposure is verified with an external artisan route inventory check plus direct endpoint assertions."
requirements-completed:
  - SEC-05
duration: 9 min
completed: 2026-03-19
---

# Phase 01 Plan 01: Public Surface Inventory Summary

**Live `__test` route exposure is removed from the public route graph while Pest-only helper routes stay available inside feature tests**

## Performance

- **Duration:** 9 min
- **Started:** 2026-03-19T05:45:17Z
- **Completed:** 2026-03-19T05:54:17Z
- **Tasks:** 1
- **Files modified:** 3

## Accomplishments

- Removed the live `routes/testing.php` include from [`routes/web.php`](/Users/andrejprus/Herd/tenanto/routes/web.php).
- Deleted [`routes/testing.php`](/Users/andrejprus/Herd/tenanto/routes/testing.php) so public route loading no longer has a test-only shim.
- Added [`Phase1PublicSurfaceInventoryTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Architecture/Phase1PublicSurfaceInventoryTest.php) to prove the live `__test` route inventory is empty while Pest bootstrap helpers still work in tests.

## Task Commits

1. **Task 1: Add public-surface regression proof and remove the live test-route include** - `22c826f5` (`fix`)

## Files Created/Modified

- [`routes/web.php`](/Users/andrejprus/Herd/tenanto/routes/web.php) - removes the live include of the testing route file.
- [`routes/testing.php`](/Users/andrejprus/Herd/tenanto/routes/testing.php) - deleted so test-only routes no longer exist in the public route graph.
- [`Phase1PublicSurfaceInventoryTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Architecture/Phase1PublicSurfaceInventoryTest.php) - proves the live `__test` route inventory is empty and Pest-only helpers still redirect through auth as expected.

## Decisions Made

- Kept shared `__test` helper routes defined only in `tests/Pest.php` instead of retaining any public route include or `runningUnitTests()` shim under `routes/`.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- `php artisan route:list --path=__test --json` reports “no routes matching” instead of returning literal `[]` when the filtered inventory is empty. The regression proof was adjusted to assert that real empty-inventory signal.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Ready for `01-02-PLAN.md`.
- The live route graph is clean enough for the PWA surface removal work to proceed without public test-route noise.

---
*Phase: 01-safety-freeze-and-guardrails*
*Completed: 2026-03-19*
