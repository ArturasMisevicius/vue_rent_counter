# Change: Update Role Dashboards and Billing Consolidation

## Why
The current application has overlapping role dashboards and duplicated controller/Blade vs Livewire/Filament surfaces. This makes role isolation harder to reason about and splits billing workflows across multiple UI paths. We need a single canonical role-aware dashboard and billing workflow rooted in Livewire/Filament with strict tenant isolation.

## What Changes
- Consolidate role-based dashboard routing and layouts into one canonical Livewire/Filament path using `RoleDashboardResolver`.
- Migrate remaining controller-rendered dashboard/profile/settings pages to Livewire components and remove duplicate routes/controllers.
- Harden role-scoped data loading and tenant/property isolation in dashboards and billing entry points.
- Standardize meter reading and billing form validation through Form Request classes.
- Align billing workflows around existing `BillingService` and `UniversalBillingCalculator` with time-of-use and localized adjustments preserved.
- Remove or deprecate obsolete UI flows that duplicate Filament/Livewire functionality.

## Impact
- **BREAKING**: Duplicate controller-rendered dashboards and legacy route paths will be removed once Livewire/Filament replacements are verified.
- Affected specs:
  - `role-dashboards`
  - `billing-engine`
  - `ui-consolidation`
- Affected code:
  - `routes/web.php`, role dashboard controllers, Livewire dashboard/profile/settings pages
  - Filament panel providers and resources that overlap with custom routes
  - Billing services, meter reading flows, and validation requests
  - `resources/views/pages/**`, `resources/views/layouts/**`
  - Feature tests for dashboards, role access, and billing workflows
