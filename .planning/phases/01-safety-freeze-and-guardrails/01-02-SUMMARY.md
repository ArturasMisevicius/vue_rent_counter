---
phase: 01-safety-freeze-and-guardrails
plan: "02"
subsystem: testing
tags: [pwa, composer, public-assets, regression]
requires: []
provides:
  - composer manifests no longer require erag/laravel-pwa
  - public PWA config and assets are removed
  - tracked package discovery caches prove the package stays absent
affects: [phase-1, build-surface, public-web]
tech-stack:
  added: []
  patterns:
    - removal plans prove both rendered-surface absence and repository inventory absence
key-files:
  created:
    - tests/Feature/Architecture/PwaSurfaceRemovalInventoryTest.php
  modified:
    - composer.json
    - composer.lock
    - bootstrap/cache/packages.php
    - bootstrap/cache/services.php
    - tests/Feature/Public/PwaIntegrationTest.php
key-decisions:
  - "Remove the PWA surface completely instead of keeping dormant manifest or service-worker placeholders."
patterns-established:
  - "Deletion-heavy cleanup work is verified by rendered-page assertions, direct endpoint checks, and inventory checks against tracked files."
requirements-completed:
  - SEC-05
duration: 5 min
completed: 2026-03-19
---

# Phase 01 Plan 02: PWA Surface Removal Summary

**The legacy PWA package, public assets, and tracked package-discovery traces are removed with regression proof that pages no longer expose dormant hooks**

## Performance

- **Duration:** 5 min
- **Started:** 2026-03-19T05:54:35Z
- **Completed:** 2026-03-19T05:59:35Z
- **Tasks:** 2
- **Files modified:** 10

## Accomplishments

- Removed `erag/laravel-pwa` from Composer and deleted the tracked PWA config plus public `manifest`, `offline`, and `sw` assets.
- Inverted the public, guest, and tenant rendered-surface tests so they now fail if manifest or service-worker hooks come back.
- Extended the inventory proof so tracked package-discovery caches also stay free of `erag/laravel-pwa`.

## Task Commits

1. **Task 1: Replace PWA-presence tests and remove the dependency plus public PWA surface** - `967181b1` (`chore`)
2. **Task 2: Refresh tracked package-discovery cache traces and extend the absence proof** - `9b53e5d0` (`chore`)

## Files Created/Modified

- [`composer.json`](/Users/andrejprus/Herd/tenanto/composer.json) - removes the `erag/laravel-pwa` dependency.
- [`composer.lock`](/Users/andrejprus/Herd/tenanto/composer.lock) - drops the package from the resolved lock graph.
- [`bootstrap/cache/packages.php`](/Users/andrejprus/Herd/tenanto/bootstrap/cache/packages.php) - refreshes tracked package discovery without the PWA package.
- [`bootstrap/cache/services.php`](/Users/andrejprus/Herd/tenanto/bootstrap/cache/services.php) - refreshes tracked service discovery without the PWA package.
- [`PwaIntegrationTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Public/PwaIntegrationTest.php) - proves rendered public surfaces do not emit PWA hooks.
- [`PwaSurfaceRemovalInventoryTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Architecture/PwaSurfaceRemovalInventoryTest.php) - proves package, file, cache, and endpoint absence.

## Decisions Made

- Removed the PWA surface completely instead of keeping dormant manifest, offline, or service-worker placeholders that could drift back into the product unnoticed.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- `GET /manifest.json` and `GET /sw.js` already returned `404` inside the feature harness even before file deletion, so the hard red signal came from the repository inventory assertions rather than the HTTP checks.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Ready for `01-03-PLAN.md`.
- The public surface is now reduced enough that the remaining Phase 1 web exposure work is concentrated on `/csp/report`.

---
*Phase: 01-safety-freeze-and-guardrails*
*Completed: 2026-03-19*
