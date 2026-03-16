## 1. Inventory and Locale Decision
- [ ] 1.1 Inventory the enabled locale set from `lang/locales.php` and compare it with the directories present under `lang/`.
- [ ] 1.2 Inventory leftover application translation files under `resources/lang` and identify any content that must be merged into `lang/`.
- [ ] 1.3 Inventory runtime translation fallback bridges, compatibility tests, Filament alias shims, and compatibility route aliases that are candidates for removal.

## 2. Canonical Translation Tree
- [ ] 2.1 Keep `lt`, `en`, and `ru` as the canonical enabled locales for this phase.
- [ ] 2.2 Remove the partial `lang/es` locale artifacts from disk.
- [ ] 2.3 Merge or relocate any needed `resources/lang` translation content into `lang/` and delete the legacy `resources/lang` leftovers.
- [ ] 2.4 Ensure translation completeness checks derive their supported locale list from `lang/locales.php` instead of hardcoded test constants where appropriate.

## 3. Canonical Translation Keys and Fallback Removal
- [ ] 3.1 Inventory remaining translation lookups that still depend on legacy role-root keys or runtime missing-key remapping.
- [ ] 3.2 Migrate those callers to canonical keys under `lang/`, using shared/domain keyspaces directly instead of relying on runtime fallback behavior.
- [ ] 3.3 Remove the `handleMissingKeysUsing(...)` translation fallback bridge from `AppServiceProvider` once canonical keys are in place.
- [ ] 3.4 Update or replace fallback-specific helpers/tests so the suite verifies canonical key usage instead of the legacy bridge.

## 4. Filament and Route Compatibility Cleanup
- [ ] 4.1 Update remaining Filament resources, widgets, pages, Livewire helpers, and tests to current Filament 5 class imports.
- [ ] 4.2 Remove Filament `class_alias` compatibility shims from application bootstrap and test bootstrap after zero-usage verification.
- [ ] 4.3 Inventory compatibility-only route aliases such as `superadmin.compat.*` and `admin/filament` bridges and remove the dead ones after reference migration.
- [ ] 4.4 Delete dead compatibility files created solely for the legacy translation or Filament bridges.

## 5. Tests and Verification
- [ ] 5.1 Add or update Pest tests that fail when an enabled locale is missing canonical translation files or keys.
- [ ] 5.2 Add regression checks that fail on forbidden legacy patterns such as `resources/lang` application files, `handleMissingKeysUsing(`, and Filament bootstrap `class_alias(` shims.
- [ ] 5.3 Add route/file regression checks for removed compatibility aliases once cleanup is complete.
- [ ] 5.4 Produce a legacy-cleanup report listing removed locale artifacts, deleted files/routes, updated Filament imports, and any follow-up work.
- [ ] 5.5 Run targeted verification commands for translation tests, grep-based legacy checks, and relevant Filament/route suites.
