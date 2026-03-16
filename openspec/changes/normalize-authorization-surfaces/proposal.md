# Change: Normalize Authorization Across Blade, Livewire, and Filament

## Why
Authorization in the current application is partially centralized in policies, but the actual UI surface still mixes policy-based checks with raw role-string conditionals, custom Filament `can*()` methods, and ad hoc superadmin checks. This creates drift between controllers, Blade views, Livewire components, and Filament resources, and makes it harder to guarantee that sensitive actions and deep metadata are exposed consistently and only to the correct users.

This change introduces one canonical authorization model across the non-Filament UI, Livewire, and Filament resources/pages.

## What Changes
- Define or complete policy coverage for the core resources used across the application:
  - `Organization`
  - `Tenant`
  - `Property`
  - `Building`
  - `Meter`
  - `MeterReading`
  - `Invoice`
  - `Subscription`
  - `User`
  - `Tariff`
  - `Provider`
  - `Language`
  - `Translation`
- Normalize sensitive UI visibility to use policies and authorization directives/hooks instead of raw role-string checks.
- Establish superadmin as the only global full-control role for create, edit, delete, restore, force-delete, export, impersonation, audit access, and system configuration access.
- Ensure `admin`, `manager`, and `tenant` remain tenant-scoped or property-scoped according to the existing domain boundaries.
- Restrict deep operational metadata to superadmin-only surfaces, including internal IDs, audit fields, timestamps, relationship diagnostics, and workflow state.
- Add targeted Pest coverage to verify authorization behavior across controllers, Blade, Livewire, and Filament.

## Impact
- **BREAKING**: views, Livewire components, and Filament resources/pages that currently depend on raw role checks or bespoke authorization logic will be migrated to policy-driven behavior.
- Affected specs:
  - `authorization-surface`
- Related pending changes:
  - `refactor-shared-layout-components`
  - `refactor-unified-ui-system`
  - `update-role-dashboards-billing`

## Scope Notes
- This change does **not** remove route middleware; middleware remains a coarse access boundary while policies become the canonical action-level rule source.
- This change does **not** redefine tenant-isolation business rules; it standardizes how those rules are enforced and rendered.
