---
phase: 01-safety-freeze-and-guardrails
plan: "04"
subsystem: quality
tags: [guardrails, ci, composer, pest, pint]
requires: []
provides:
  - a shared local Phase 1 guard command for formatting and curated regressions
  - a GitHub Actions workflow that reuses the same guard command on pull requests and pushes to main
  - an executable Phase 1 static-check layer based on architecture and inventory tests rather than PHPStan bootstrap
affects: [phase-1, ci, local-workflow]
tech-stack:
  added: []
  patterns:
    - local and CI enforcement reuse one Composer command so the guard surface cannot drift
key-files:
  created:
    - .github/workflows/phase-1-guardrails.yml
  modified:
    - composer.json
    - tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php
key-decisions:
  - "Use `composer guard:phase1` as the single source of truth for both local and CI Phase 1 enforcement."
patterns-established:
  - "Phase guardrails combine a path-scoped Pint check, explicit architecture and inventory tests, and a curated regression bundle in one shared command."
requirements-completed:
  - GOV-03
  - OPS-04
duration: 7 min
completed: 2026-03-19
---

# Phase 01 Plan 04: Guard Command and CI Gate Summary

**Phase 1 now has one shared guard command for local use and a matching GitHub Actions workflow that runs the same curated formatting and regression bundle**

## Performance

- **Duration:** 7 min
- **Started:** 2026-03-19T06:08:07Z
- **Completed:** 2026-03-19T06:14:50Z
- **Tasks:** 2
- **Files modified:** 3

## Accomplishments

- Added `composer guard:phase1` so maintainers can clear config, run a source-only Pint check, and execute the curated Phase 1 regression bundle from one command.
- Kept the explicit Phase 1 static-check layer in executable tests by including `Phase1PublicSurfaceInventoryTest`, `PwaSurfaceRemovalInventoryTest`, `FilamentFoundationPlacementTest`, and `FilamentCrudCoverageInventoryTest` instead of introducing PHPStan during this phase.
- Created the `Phase 1 Guardrails` GitHub Actions workflow that provisions PHP 8.5 and runs `composer guard:phase1` on pull requests and pushes to `main`.

## Task Commits

1. **Task 1: Add the shared local Phase 1 guard command and clear the current Pint baseline** - `438bd623` (`chore`)
2. **Task 2: Add the GitHub Actions workflow that reuses the shared guard command** - `d39b5ee4` (`ci`)

## Files Created/Modified

- [`composer.json`](/Users/andrejprus/Herd/tenanto/composer.json) - adds the shared `guard:phase1` script with the exact Phase 1 Pint and Pest file bundle.
- [`FilamentCrudCoverageInventoryTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Admin/FilamentCrudCoverageInventoryTest.php) - normalizes import order so the path-scoped Pint baseline stays green.
- [`phase-1-guardrails.yml`](/Users/andrejprus/Herd/tenanto/.github/workflows/phase-1-guardrails.yml) - provisions PHP 8.5, installs dependencies, prepares the app key, and runs `composer guard:phase1`.

## Decisions Made

- Kept the Phase 1 static-check layer executable and repository-specific by using architecture and inventory tests in the guard command rather than adding PHPStan or Larastan in this milestone slice.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- The initial path-scoped Pint run failed only on `FilamentCrudCoverageInventoryTest.php`, so the local baseline fix stayed limited to import ordering before the broader guard command was verified.

## User Setup Required

None locally. The remaining follow-up is the remote `main` workflow run required by `01-05`.

## Next Phase Readiness

- Ready for `01-05-PLAN.md`.
- The last Phase 1 step is now an external checkpoint: merge the workflow, wait for the first successful remote `Phase 1 Guardrails` run on `main`, then enforce it as the required status check.

---
*Phase: 01-safety-freeze-and-guardrails*
*Completed: 2026-03-19*
