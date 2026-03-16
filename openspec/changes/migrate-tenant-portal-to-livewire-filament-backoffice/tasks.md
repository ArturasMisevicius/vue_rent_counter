## 1. Surface Inventory and Canonical Mapping
- [ ] 1.1 Inventory all tenant-facing routes, controllers, Livewire pages, Filament tenant pages/resources, and Blade views.
- [ ] 1.2 Map each tenant experience area to its canonical Livewire module:
  - dashboard
  - property
  - meters
  - meter readings
  - invoices
  - notifications
  - profile
- [ ] 1.3 Inventory admin/manager/superadmin CRUD routes that duplicate Filament resources and map each one to its canonical Filament replacement.

## 2. Tenant Portal Livewire Migration
- [ ] 2.1 Make tenant dashboard rendering Livewire-first.
- [ ] 2.2 Make tenant property and meters views Livewire-first.
- [ ] 2.3 Make tenant meter reading flows Livewire-first while preserving validation and tenant/property isolation.
- [ ] 2.4 Make tenant invoices and receipt/detail experiences Livewire-first where appropriate while preserving PDF/download flows.
- [ ] 2.5 Introduce a tenant notifications Livewire module as the canonical notifications surface.
- [ ] 2.6 Make tenant profile rendering and updates Livewire-first.

## 3. Backoffice CRUD Filament Consolidation
- [ ] 3.1 Identify admin CRUD flows that should be handled by Filament resources/pages instead of custom controller+Blade CRUD paths.
- [ ] 3.2 Identify manager CRUD flows that should be handled by Filament resources/pages instead of custom controller+Blade CRUD paths.
- [ ] 3.3 Identify superadmin CRUD flows that should be handled by Filament resources/pages instead of custom controller+Blade CRUD paths.
- [ ] 3.4 Preserve non-CRUD endpoints outside Filament only where they remain justified.

## 4. Realtime Behavior Rules
- [ ] 4.1 Document which tenant workflows need simple Livewire reactivity only.
- [ ] 4.2 Document which workflows, if any, need polling.
- [ ] 4.3 Limit events/broadcasting to cases that require cross-user realtime updates.

## 5. Legacy Flow Removal
- [ ] 5.1 Remove tenant controller+Blade render paths that are superseded by Livewire modules.
- [ ] 5.2 Remove duplicate backoffice controller+Blade CRUD paths that are superseded by Filament.
- [ ] 5.3 Remove tenant Blade-only pages after replacement verification and zero-reference checks.

## 6. Tests and Verification
- [ ] 6.1 Add or update Pest feature tests for tenant route rendering and behavior through Livewire modules.
- [ ] 6.2 Add or update Livewire tests for tenant dashboard, property, meters, meter readings, invoices, notifications, and profile flows.
- [ ] 6.3 Add or update Filament tests to verify canonical CRUD behavior for admin, manager, and superadmin resources.
- [ ] 6.4 Add regression checks that fail when duplicate tenant controller+Blade flows or duplicate backoffice CRUD flows are reintroduced.
- [ ] 6.5 Produce a migration report listing removed routes, removed views, canonical replacements, and any intentionally retained exceptions.
