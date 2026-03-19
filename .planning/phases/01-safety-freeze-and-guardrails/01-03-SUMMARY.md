---
phase: 01-safety-freeze-and-guardrails
plan: "03"
subsystem: security
tags: [csp, rate-limiting, pruning, pest, telemetry]
requires: []
provides:
  - a named per-IP throttle for the public /csp/report endpoint
  - tagged CSP security-violation records with bounded retention
  - executable proof for acceptance flow, throttling, and prune targeting
affects: [phase-1, public-web, security-monitoring]
tech-stack:
  added: []
  patterns:
    - public telemetry endpoints stay available but must be explicitly throttled, tagged, and retention-scoped
key-files:
  created:
    - tests/Feature/Security/CspReportRateLimitTest.php
  modified:
    - app/Http/Controllers/CspViolationReportController.php
    - app/Models/SecurityViolation.php
    - app/Providers/AppServiceProvider.php
    - routes/console.php
    - routes/web.php
    - tests/Feature/Security/SecurityHeadersTest.php
key-decisions:
  - "Keep `/csp/report` public for valid browser telemetry, but bind it with a per-IP throttle and prune only tagged CSP records after fourteen days."
patterns-established:
  - "Public write endpoints are hardened with route throttles plus explicit metadata tags so cleanup rules can stay narrow."
requirements-completed:
  - SEC-05
duration: 8 min
completed: 2026-03-19
---

# Phase 01 Plan 03: CSP Telemetry Hardening Summary

**The public `/csp/report` endpoint stays usable for valid browser reports while repeated abuse is throttled and accepted telemetry is tagged for narrow retention cleanup**

## Performance

- **Duration:** 8 min
- **Started:** 2026-03-19T06:00:36Z
- **Completed:** 2026-03-19T06:08:07Z
- **Tasks:** 1
- **Files modified:** 7

## Accomplishments

- Added the named `security-csp-report` limiter and attached it to the public [`/csp/report`](/Users/andrejprus/Herd/tenanto/routes/web.php) route without changing the existing `202 Accepted` behavior for valid reports.
- Tagged accepted CSP violations with `metadata['source'] = 'csp-report'` and added a narrow `MassPrunable` rule so only old CSP telemetry rows are eligible for cleanup.
- Scheduled targeted `model:prune` execution and added focused Pest coverage for acceptance, 429 throttling, and prune targeting.

## Task Commits

1. **Task 1: Add CSP throttle, source tagging, prune targeting, and focused regression coverage** - `30a5c0a2` (`fix`)

## Files Created/Modified

- [`CspViolationReportController.php`](/Users/andrejprus/Herd/tenanto/app/Http/Controllers/CspViolationReportController.php) - tags accepted CSP reports with the explicit `csp-report` metadata source.
- [`SecurityViolation.php`](/Users/andrejprus/Herd/tenanto/app/Models/SecurityViolation.php) - adds `MassPrunable` and a retention query scoped only to old tagged CSP telemetry rows.
- [`AppServiceProvider.php`](/Users/andrejprus/Herd/tenanto/app/Providers/AppServiceProvider.php) - registers the `security-csp-report` per-IP limiter.
- [`routes/web.php`](/Users/andrejprus/Herd/tenanto/routes/web.php) - keeps `/csp/report` public and CSRF-exempt while adding route throttling.
- [`routes/console.php`](/Users/andrejprus/Herd/tenanto/routes/console.php) - schedules the targeted `model:prune` command for `SecurityViolation`.
- [`SecurityHeadersTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Security/SecurityHeadersTest.php) - extends the CSP acceptance proof to require the new source tag.
- [`CspReportRateLimitTest.php`](/Users/andrejprus/Herd/tenanto/tests/Feature/Security/CspReportRateLimitTest.php) - proves ten accepted requests, a `429` on the eleventh, and narrow prune targeting.

## Decisions Made

- Preserved the public CSP intake path for real browser violation reporting instead of moving it behind auth, but constrained it with rate limiting and a retention-specific metadata tag.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

- The acceptance-flow proof and the new throttle/prune tests all failed cleanly on the red step, so no plan adjustments were needed beyond the intended implementation.

## User Setup Required

None - the pruning schedule hooks into the existing Laravel scheduler surface.

## Next Phase Readiness

- Ready for `01-04-PLAN.md`.
- The remaining Phase 1 work can now focus on local and CI guardrails rather than live public endpoint exposure.

---
*Phase: 01-safety-freeze-and-guardrails*
*Completed: 2026-03-19*
