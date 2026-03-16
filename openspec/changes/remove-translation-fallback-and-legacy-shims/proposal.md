# Change: Remove Translation Fallbacks and Legacy Compatibility Shims

## Why
The application still has a split localization surface and multiple temporary compatibility bridges. The canonical locale configuration only enables `lt`, `en`, and `ru`, but a partial `lang/es/app.php` is present on disk. The application also still keeps a legacy `resources/lang` file, a runtime missing-key fallback in `AppServiceProvider`, and Filament `class_alias` shims in both application and test bootstrap.

This leaves the project in a transitional state where missing translations can be masked, incomplete locales can drift unnoticed, and legacy Filament APIs continue to load through alias bridges instead of current imports. The requested cleanup should finish that migration and make regressions visible in tests.

## What Changes
- Keep `lang/locales.php` as the source of truth for supported locales and align the repository to that set.
- Treat `lt`, `en`, and `ru` as the canonical enabled locales for this phase and remove the partial `es` locale artifacts from disk.
- Make `lang/` the only canonical application translation tree and remove leftover `resources/lang` files after any needed merge.
- Migrate remaining translation lookups to canonical keys so the runtime missing-key fallback bridge in `AppServiceProvider` can be removed.
- Update the remaining Filament resources, widgets, pages, and tests that still rely on legacy class names so the Filament compatibility aliases can be deleted.
- Remove dead compatibility files and routes that only exist to support the legacy translation or legacy UI/Filament bridges once their callers are migrated.
- Add focused Pest coverage and grep-style regression checks for locale completeness and forbidden legacy patterns.

## Impact
- **BREAKING**: incomplete locale artifacts, legacy translation fallback behavior, Filament alias bridges, and dead compatibility files/routes will be removed once callers are migrated.
- Affected specs:
  - `localization-integrity`
- Related pending changes:
  - `refactor-shared-layout-components`
  - `normalize-authorization-surfaces`
  - `enforce-validation-architecture`
  - `refactor-unified-ui-system`

## Scope Notes
- This change does **not** add a fully translated Spanish locale; it explicitly chooses the lower-risk option of removing the partial `es` artifacts in this phase.
- This change does **not** remove compatibility routes that are still actively referenced; route cleanup is gated by zero-reference verification during implementation.
