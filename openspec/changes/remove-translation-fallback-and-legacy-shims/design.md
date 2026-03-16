## Context
The repository already points Laravel at `lang/` as the translation path, and `lang/locales.php` only enables `lt`, `en`, and `ru`. Even so, the project still carries transitional artifacts:

- a partial `lang/es/app.php` locale that is not enabled,
- a leftover `resources/lang/en/integration.php` file,
- a runtime missing-key translation bridge in `AppServiceProvider`,
- `SharedTranslationKey` support and fallback-focused tests,
- Filament compatibility aliases in `App\Support\ServiceRegistration\CompatibilityRegistry`,
- a duplicate Filament alias in `tests/Pest.php`,
- and compatibility route aliases in `routes/web.php` that exist to support older UI paths.

That combination makes missing translations harder to detect and keeps current Filament 5 code from being the real source of truth.

## Goals / Non-Goals
- Goals:
  - One canonical enabled locale set aligned to `lang/locales.php`.
  - `lang/` as the only canonical application translation tree.
  - Direct canonical translation key usage without runtime missing-key remapping.
  - Current Filament 5 imports with no bootstrap alias bridge.
  - Regression tests that prevent legacy localization and compatibility patterns from reappearing.
- Non-Goals:
  - Adding a full Spanish translation set in this phase.
  - Redesigning translation copy or renaming every existing key namespace beyond what is required to remove the runtime bridge.
  - Removing compatibility routes that still have live callers before those callers are migrated.

## Decisions
- The canonical enabled locale set for this change is `lt`, `en`, and `ru`, exactly as declared in `lang/locales.php`.
- The partial `lang/es` tree is treated as dead legacy and removed rather than expanded in this phase.
- `lang/` is the only canonical application translation source; `resources/lang` leftovers must be merged or deleted.
- Cross-role and shared copy should resolve through canonical keys that already exist under `lang/<locale>/*.php`; callers should not depend on `AppServiceProvider` to remap missing keys at runtime.
- `SharedTranslationKey` may remain only if it still serves an explicit non-fallback utility after migration; runtime missing-key bridging must not remain in the translator boot path.
- Filament resources, widgets, pages, Livewire helpers, and tests should import Filament 5 classes directly, after which both the application alias registry and the test bootstrap alias can be removed.
- Compatibility route aliases are removed only after the layout/component and authorization changes have migrated their callers.

## Risks / Trade-offs
- Removing the translation fallback too early could expose still-unmigrated keys and break pages.
  - Mitigation: inventory current call sites first, migrate callers, then remove the fallback bridge last.
- Removing Filament aliases too early could break pages, widgets, or tests that still import legacy classes.
  - Mitigation: zero-usage verification across `app/` and `tests/` before deleting the aliases.
- Removing compatibility routes may break custom pages still linking to them.
  - Mitigation: gate route deletion behind reference searches and targeted route/view tests.
- Canonical locale enforcement can surface previously hidden translation drift.
  - Mitigation: make the locale decision explicit and add completeness tests that fail loudly.

## Migration Plan
1. Inventory enabled locales, on-disk locale directories, `resources/lang` leftovers, fallback logic, alias shims, and compatibility routes.
2. Remove the partial Spanish locale artifacts and consolidate the translation tree fully under `lang/`.
3. Upgrade translation callers to canonical keys until the runtime missing-key bridge is no longer needed.
4. Remove fallback-specific translation bootstrap code and update tests accordingly.
5. Upgrade remaining Filament imports to current APIs.
6. Remove Filament alias bridges from app and test bootstrap.
7. Remove dead compatibility routes/files once the shared UI and authorization changes no longer reference them.
8. Add regression tests and publish a legacy-cleanup report.
