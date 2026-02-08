# Debugging & Code Quality Guide

**Hooks covered:** `laravel-bug-debugger`, `code-quality-analyzer`, `laravel-expert-assistant`, `laravel-code-refactor`, `laravel-legacy-refactor`.  
**Stack:** Laravel 12, Filament v4, Pest, Pint, PHPStan.

## Tooling
- **Static analysis:** PHPStan (raise level gradually); run on CI. Add stub files as needed for enums/Filament helpers.
- **Style:** Laravel Pint; keep config consistent with project root.
- **Tests:** Pest for unit/feature; prefer data providers where helpful; use factories/seeders for realistic data.
- **Debugging:** use `ray()`/`dump()` sparingly; prefer feature/integration tests. For `tinker`, set `XDG_CONFIG_HOME=/tmp` in constrained environments to avoid history write errors.
- **Logging:** include `tenant_id`, `user_id`, request ID in app logs for traceability.

## Refactor/Legacy Touchpoints
- Keep controllers thin; move orchestration to services, complex queries to repositories (see [SERVICE_AND_REPOSITORY_GUIDE.md](SERVICE_AND_REPOSITORY_GUIDE.md)).
- Preserve `BelongsToTenant` scoping; never remove tenant filters when refactoring queries.
- Add regression tests before refactoring legacy areas; cover policies, scoping, and validation.
- Avoid broad catch blocks; let the exception handler map domain exceptions to responses.

## Performance/N+1 Hygiene
- Eager load relations in Filament resources and controllers; use `withCount` for aggregates.
- Add indexes to match new query patterns if refactors change filters/orderings.
- Use chunks/queues for heavy backfills or recalculations.

## Checklists
- **Before merging:** `phpstan`, `pint`, targeted Pest suites, seed run (`migrate:fresh --seed --seeder=TestDatabaseSeeder`), manual smoke of key Filament pages if touched.
- **When fixing bugs:** add a failing test, fix, re-run relevant suite; log why the regression happened in commit/changelog.

## When to Update This Doc
- New debugging tools/process changes.
- CI pipeline adjustments (new analyzers, coverage thresholds).
- Major refactors introducing new patterns.*** End Patch
