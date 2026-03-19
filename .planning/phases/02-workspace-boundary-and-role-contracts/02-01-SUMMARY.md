---
phase: 02-workspace-boundary-and-role-contracts
plan: "01"
subsystem: security
tags: [workspace, authorization, tenancy, livewire, filament]
requires:
  - 01-04-PLAN.md
provides:
  - a shared immutable workspace context and resolver for platform, organization, and tenant requests
  - a role-based superadmin authority contract that no longer trusts the legacy boolean flag
  - a reusable tenant workspace guard for direct Livewire component entrypoints
  - architecture and security tests that lock boundary wiring across the highest-risk surfaces
affects: [phase-2, auth, tenant-portal, filament, policies]
tech-stack:
  added:
    - app/Filament/Support/Workspace/WorkspaceContext.php
    - app/Filament/Support/Workspace/WorkspaceResolver.php
    - app/Livewire/Concerns/ResolvesTenantWorkspace.php
  patterns:
    - request, panel, and tenant portal entrypoints resolve workspace once and reuse the shared contract downstream
key-files:
  created:
    - app/Filament/Support/Workspace/WorkspaceContext.php
    - app/Filament/Support/Workspace/WorkspaceResolver.php
    - app/Livewire/Concerns/ResolvesTenantWorkspace.php
    - tests/Feature/Security/WorkspaceContextResolutionTest.php
    - tests/Feature/Security/RoleAuthorityContractTest.php
    - tests/Feature/Security/TenantPropertyBoundaryContractTest.php
    - tests/Feature/Architecture/WorkspaceBoundaryInventoryTest.php
  modified:
    - app/Models/User.php
    - app/Http/Middleware/EnsureAccountIsAccessible.php
    - app/Http/Middleware/EnsureUserIsTenant.php
    - app/Filament/Support/Admin/OrganizationContext.php
    - app/Providers/Filament/AppPanelProvider.php
    - app/Filament/Pages/TenantPortalPage.php
    - app/Http/Controllers/TenantPortalRouteController.php
    - app/Filament/Support/Tenant/Portal/TenantInvoiceIndexQuery.php
    - app/Filament/Support/Tenant/Portal/TenantHomePresenter.php
    - app/Filament/Support/Tenant/Portal/TenantPropertyPresenter.php
    - app/Filament/Actions/Tenant/Readings/SubmitTenantReadingAction.php
    - app/Livewire/Tenant/InvoiceHistory.php
    - app/Livewire/Tenant/PropertyDetails.php
    - app/Livewire/Tenant/SubmitReadingPage.php
    - app/Livewire/Pages/Dashboard/TenantDashboard.php
requirements-completed:
  - SEC-01
  - SEC-02
  - SEC-03
  - SEC-04
completed: 2026-03-19
---

# Phase 02 Plan 01: Workspace Boundary and Role Contracts Summary

**Phase 2 now resolves one shared workspace contract across protected entrypoints, uses the role enum as the canonical platform authority source, and guards tenant self-service components with the same tenant boundary rules used by portal routes**

## Accomplishments

- Added `WorkspaceContext` and `WorkspaceResolver` so platform, organization, and tenant requests resolve the same normalized workspace contract before downstream scoping decisions.
- Updated `OrganizationContext`, `EnsureAccountIsAccessible`, `EnsureUserIsTenant`, `AppPanelProvider`, and `TenantPortalPage` to consume the shared workspace contract instead of rebuilding authority from raw auth state.
- Normalized `User::isSuperadmin()` to derive platform authority from the role enum only, with regression coverage for legacy `is_super_admin` mismatches.
- Added `ResolvesTenantWorkspace` and threaded it through the tenant dashboard, invoice history, property details, and submit-reading components so direct Livewire entrypoints fail closed for non-tenant accounts.
- Hardened the tenant portal query, presenter, route alias, and submit-reading action layers to consume the shared workspace contract instead of duplicating ad hoc organization and property lookups.
- Added a focused architecture inventory test that locks the boundary contract into the highest-risk staff and tenant entrypoints.

## Issues Encountered

- The first repo-wide test pass exposed an onboarding regression because incomplete admins without an organization were being treated as broken workspace accounts and logged out too early.
- Narrowed the invalid-workspace check so legitimate onboarding admins remain valid while managers and tenants without a workspace still fail closed.

## Validation

- `php artisan test tests/Feature/Security/WorkspaceContextResolutionTest.php --compact`
- `php artisan test tests/Feature/Security/RoleAuthorityContractTest.php tests/Feature/Admin/ManagerPolicyParityTest.php --compact`
- `php artisan test tests/Feature/Security/TenantPropertyBoundaryContractTest.php tests/Feature/Tenant/TenantAccessIsolationTest.php tests/Feature/Security/TenantPortalIsolationTest.php --compact`
- `php artisan test tests/Feature/Architecture/WorkspaceBoundaryInventoryTest.php tests/Feature/Security/WorkspaceContextResolutionTest.php tests/Feature/Security/RoleAuthorityContractTest.php tests/Feature/Security/TenantPropertyBoundaryContractTest.php --compact`
- `vendor/bin/pint --dirty`
- `php artisan test --compact` -> `704 passed (9281 assertions)`

## Deviations from Plan

None in scope. The only notable adjustment was preserving the existing onboarding admin bootstrap state while tightening the invalid workspace guard for every other protected account type.

## Next Phase Readiness

- Ready for `03-01-PLAN.md`.
- Phase 3 can now assume one shared workspace contract exists for protected requests, one canonical platform authority rule exists for superadmin access, and tenant self-service entrypoints are locked to the shared tenant workspace guard.

---
*Phase: 02-workspace-boundary-and-role-contracts*
*Completed: 2026-03-19*
