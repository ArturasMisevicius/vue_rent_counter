## Context
The current repository already includes:
- custom tenant controllers and Blade pages for dashboard, property, meters, meter readings, invoices, and profile
- a partial generic Livewire page layer (`DashboardPage`, `ProfilePage`)
- a Filament tenant panel with tenant dashboard, profile, resources, and widgets
- controller-rendered CRUD surfaces for admin, manager, and superadmin that overlap with Filament resources

This means the application currently has overlapping render paths for the same role experiences. The request is to make the tenant portal clearly Livewire-first while keeping backoffice CRUD clearly Filament-first.

## Goals / Non-Goals
- Goals:
  - One canonical tenant portal surface based on Livewire modules.
  - One canonical backoffice CRUD surface based on Filament resources/pages.
  - No duplicated controller+Blade tenant flows after Livewire replacements are verified.
  - Realtime behavior kept simple unless true cross-user updates require more.
- Non-Goals:
  - Replacing Filament with custom backoffice Livewire CRUD.
  - Rewriting non-CRUD utility endpoints such as PDFs or exports unless the UI duplication requires it.
  - Forcing broadcasting into flows that only need Livewire reactivity or polling.

## Decisions
- Tenant-facing page modules are Livewire-first, even when the tenant panel already exposes similar Filament resources.
- Admin, manager, and superadmin CRUD are Filament-first; if a CRUD flow is duplicated in controller+Blade and Filament, Filament becomes canonical.
- Route names and authorization contracts should be preserved where possible while swapping the underlying render path.
- Tenant notifications become a dedicated Livewire module because the current custom tenant web surface does not have a standalone notifications page.
- Realtime defaults:
  - use plain Livewire reactivity for form and page state
  - use polling only when freshness matters and single-user refresh is enough
  - use events/broadcasting only when multi-user state synchronization is required

## Risks / Trade-offs
- There is overlap between existing custom tenant pages and the Filament tenant panel.
  - Mitigation: explicitly declare Livewire as canonical for tenant-facing modules and treat Filament tenant surfaces as secondary or deprecate them where duplicated.
- Some backoffice controller routes may still carry custom behavior not represented in Filament.
  - Mitigation: classify them as CRUD vs non-CRUD before removal.
- Route and view cleanup may affect existing tests and bookmarked paths.
  - Mitigation: preserve route names where possible and add migration-focused regression tests.

## Migration Plan
1. Inventory tenant routes/views/controllers and existing Filament tenant pages/resources.
2. Define the canonical Livewire module for each tenant area.
3. Swap tenant routes to Livewire modules while preserving route contracts.
4. Classify backoffice controller routes and move duplicate CRUD ownership to Filament resources/pages.
5. Remove duplicate tenant Blade-only pages and redundant controller render flows.
6. Add tests for tenant Livewire behavior and Filament-first backoffice CRUD behavior.
7. Publish a migration report with retained exceptions and follow-up items.
