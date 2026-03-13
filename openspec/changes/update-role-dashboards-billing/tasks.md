## 1. Discovery and Canonical Path Mapping
- [ ] 1.1 Inventory all role dashboards, profile/settings pages, and meter-reading flows across controllers, Livewire, and Filament.
- [ ] 1.2 Map duplicate routes/controllers to their Livewire/Filament replacements and document removals.
- [ ] 1.3 Confirm canonical dashboard resolver usage (`RoleDashboardResolver`) across web and panel entry points.

## 2. Role Dashboard Consolidation (Livewire/Filament)
- [ ] 2.1 Replace controller-rendered dashboards with `App\Livewire\Pages\DashboardPage` as canonical renderer.
- [ ] 2.2 Migrate profile/settings pages to Livewire components and remove duplicate controller routes.
- [ ] 2.3 Unify backoffice layout for superadmin/admin/manager and keep tenant layout isolated.
- [ ] 2.4 Remove `@php` usage in dashboard Blade views and pass all view data from Livewire.

## 3. Authorization and Scope Hardening
- [ ] 3.1 Ensure role-based middleware and policies enforce tenant/property isolation on all dashboard and billing entry points.
- [ ] 3.2 Update any direct route access to fail closed (403/404) on cross-tenant access.

## 4. Billing Workflow Consolidation
- [ ] 4.1 Confirm billing calculations use `BillingService` + `UniversalBillingCalculator` and time-of-use logic.
- [ ] 4.2 Ensure invoice item snapshots include meter reading and tariff calculation context.
- [ ] 4.3 Align manager meter-reading submission flow with billing rules and validations.

## 5. Validation Centralization
- [ ] 5.1 Introduce Form Request classes for Livewire/Filament actions currently using inline validation.
- [ ] 5.2 Apply scoped `exists` rules to enforce tenant/property ownership in requests.

## 6. UI and Localization Cleanup
- [ ] 6.1 Ensure all touched UI strings use translation keys under `lang/` and reuse existing keys.
- [ ] 6.2 Consolidate repeated markup into reusable `<x-...>` components where safe.

## 7. Tests and Regression Coverage
- [ ] 7.1 Update dashboard and route access tests to match Livewire/Filament canonical paths.
- [ ] 7.2 Add tests for meter reading validation, time-of-use invoice items, and billing idempotency.
- [ ] 7.3 Run targeted Pest suites and update failures.

## 8. Quality Gates
- [ ] 8.1 Run `vendor/bin/pint --dirty --format agent`.
- [ ] 8.2 Run focused `php artisan test --compact` suites for modified features.

## 9. Documentation and Knowledge Updates
- [ ] 9.1 Update relevant docs under `docs/` for dashboard architecture, billing flows, and UI migration.
- [ ] 9.2 Update AGENTS/skills/README or other guidance to reflect the canonical Livewire/Filament paths.
