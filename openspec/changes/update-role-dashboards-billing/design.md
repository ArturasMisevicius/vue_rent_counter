## Context
The repository currently contains overlapping UI surfaces: controller-rendered dashboards and Livewire pages, plus Filament panel resources for admin/superadmin/tenant. Billing logic is implemented in `BillingService` and `UniversalBillingCalculator`, but some UI flows still embed validation or duplicate data loading. We need a single canonical role-aware dashboard architecture and billing workflow while preserving tenant isolation and time-of-use calculations.

## Goals / Non-Goals
- Goals:
  - Consolidate role dashboards to a single Livewire/Filament path.
  - Preserve strict tenant/property isolation and direct-route safety.
  - Keep billing calculation logic centralized with time-of-use support and snapshots.
  - Eliminate duplicate controller-based rendering paths.
- Non-Goals:
  - Introducing new dependencies or switching away from Filament/Livewire.
  - Replacing existing billing calculation engine with a new one.

## Decisions
- Decision: Use `App\Livewire\Pages\DashboardPage`, `ProfilePage`, and `SettingsPage` as the canonical renderers for role dashboards/profile/settings, and remove controller-based duplicates once tests pass.
  - Why: Livewire already aggregates per-role dashboard data and aligns with the no-API requirement.
- Decision: Keep Filament panels and resources for operational CRUD (admin/superadmin/tenant) while ensuring navigation does not duplicate custom web routes.
  - Why: Filament resources already encode policies, tenant scopes, and admin workflows.
- Decision: Preserve `BillingService` + `UniversalBillingCalculator` as the core billing engine, and ensure UI flows only call into these services.
  - Why: The engine already supports time-of-use, tiered, and localized adjustments with snapshots.

## Risks / Trade-offs
- Removing controller routes may require updating tests and navigation links; missing updates could break deep links.
- Ensuring no duplication between Filament and custom routes requires careful navigation cleanup and route access tests.

## Migration Plan
1. Map duplicate routes to Livewire/Filament components and update route bindings.
2. Remove redundant controllers/views after Livewire replacements are verified.
3. Align validation to Form Request classes used by Livewire/Filament actions.
4. Update tests and run targeted suites.
5. Update documentation and guidance.

## Open Questions
- None (proceed with repo-inferred defaults).
