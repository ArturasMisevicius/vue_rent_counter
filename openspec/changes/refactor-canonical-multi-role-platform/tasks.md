## Phase 1. Architecture Baseline and Mapping
- [ ] 1.1 Inventory current layout shells, role-scoped Blade components, Livewire modules, Filament resources/pages, translation trees, validation patterns, compatibility routes, and compatibility shims.
- [ ] 1.2 Map each current surface to its canonical target: shared Blade shell, shared component, Livewire module, Filament resource/page, or deletion.
- [ ] 1.3 Confirm the enabled locale set from `lang/locales.php` and record any non-enabled locale artifacts for removal.

## Phase 2. Shared UI Shell and Design System
- [ ] 2.1 Make `resources/views/layouts/app.blade.php` the only canonical non-Filament layout shell.
- [ ] 2.2 Consolidate reusable UI primitives into the shared component namespace and migrate role-specific usages.
- [ ] 2.3 Remove superseded non-Filament layouts and duplicate components after zero-reference verification.

## Phase 3. Authorization Normalization
- [ ] 3.1 Complete policy coverage for core managed resources used across Blade, Livewire, controllers, and Filament.
- [ ] 3.2 Replace raw role-string presentation checks with policy-driven visibility and authorization hooks.
- [ ] 3.3 Ensure superadmin has full global CRUD and deep metadata visibility while admin, manager, and tenant remain scoped.

## Phase 4. Validation Architecture
- [ ] 4.1 Convert every mutating controller action to a dedicated `FormRequest`.
- [ ] 4.2 Remove route-closure validation and move those endpoints behind controllers plus `FormRequest` classes.
- [ ] 4.3 Move Livewire validation into form objects or dedicated validator/rules classes and eliminate inline component validation rules.

## Phase 5. Canonical Interaction Surfaces
- [ ] 5.1 Migrate tenant-facing custom pages to Livewire-first modules for dashboard, property, meters, meter readings, invoices, notifications, and profile.
- [ ] 5.2 Keep admin, manager, and superadmin CRUD Filament-first and remove duplicate controller+Blade CRUD paths after parity verification.
- [ ] 5.3 Use events or broadcasting only where true cross-user realtime behavior is needed.

## Phase 6. Localization and Legacy Cleanup
- [ ] 6.1 Keep enabled locale support aligned to `lang/locales.php` and remove unsupported locale artifacts.
- [ ] 6.2 Make `lang/` the only canonical translation tree and remove `resources/lang` leftovers.
- [ ] 6.3 Remove translation fallback bridges once canonical keys are fully migrated.
- [ ] 6.4 Remove Filament compatibility aliases and dead compatibility routes/files after zero-usage verification.

## Phase 7. Pest Coverage and Verification
- [ ] 7.1 Add or update Pest tests for role access by role, superadmin full CRUD, shared component rendering, locale completeness, and legacy regression checks.
- [ ] 7.2 Run targeted Pest suites during each phase and the full `php artisan test --compact` suite before claiming completion.
- [ ] 7.3 Produce a final legacy-removal report listing deleted layouts, deleted role components, removed compatibility routes, removed translation fallbacks, removed class aliases, and any remaining follow-up work.
