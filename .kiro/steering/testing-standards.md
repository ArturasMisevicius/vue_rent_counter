---
inclusion: always
---

# Testing & Code Quality Standards

## Rector v2 Integration
- Run `composer lint` before commits; it executes Rector v2 (with Laravel sets) followed by Pint, so Rector fixes are required for passing lint.
- Rector v2 uses composer-based detection (`withComposerBased(laravel: true)`) to automatically apply Laravel 12-specific refactoring rules.
- For read-only verification in CI contexts, use `composer test:refactor` (Rector dry-run) to surface pending refactors without writing files.
- Rector processes `app/`, `app-modules/`, `config/`, `database/`, `routes/`, and `tests/` with Laravel code quality, collection, testing, and type declaration sets.
- Custom rules remove debug helpers (`dd`, `ddd`, `dump`, `ray`, `var_dump`) automatically via `RemoveDumpDataDeadCodeRector`.
- Filament importer lifecycle hooks are skipped from privatization/unused method detection since they're called dynamically.
- See `docs/rector-v2-integration.md` for comprehensive usage, configuration, and best practices.

## Testing Standards
- Prefer the Laravel Expectations plugin (`defstudio/pest-plugin-laravel-expectations`) for HTTP/model/storage assertions in Pest (e.g., `toBeOk()`, `toBeRedirect()`, `toExist()`), keeping Filament v4.3+ tests consistent with Pest style.
- Use the Pest Route Testing plugin (`spatie/pest-plugin-route-testing`) to ensure all routes remain accessible; organize route tests by type (public, authenticated, API) in `tests/Feature/Routes/` with centralized configuration in `RouteTestingConfig`—see `.kiro/steering/pest-route-testing.md`.
- Stress checks use `pestphp/pest-plugin-stressless`; keep them opt-in with `RUN_STRESS_TESTS=1` + `STRESSLESS_TARGET`, and gate concurrency/duration (`STRESSLESS_CONCURRENCY`, `STRESSLESS_DURATION`, `STRESSLESS_P95_THRESHOLD_MS`) to avoid hammering shared/staging infra.
