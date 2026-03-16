# Change: Migrate Tenant Portal to Livewire and Keep Backoffice CRUD Filament-First

## Why
The current tenant experience is split across custom tenant controllers and large Blade views, while the application also ships a separate Filament tenant panel and a partial Livewire page layer. At the same time, admin, manager, and superadmin CRUD workflows still exist in mixed controller/Blade and Filament forms. This leaves three overlapping UI surfaces in production and makes it hard to identify the canonical path for each role.

This change makes the tenant portal explicitly Livewire-first and keeps admin, manager, and superadmin CRUD explicitly Filament-first.

## What Changes
- Migrate the tenant-facing custom experience to Livewire-first modules for:
  - dashboard
  - property
  - meters
  - meter readings
  - invoices
  - notifications
  - profile
- Keep admin, manager, and superadmin CRUD flows Filament-first, using Filament resources/pages as the canonical CRUD surface.
- Remove or deprecate duplicate controller-rendered tenant page flows once Livewire replacements are verified.
- Remove or deprecate duplicate backoffice controller+Blade CRUD flows once the Filament surfaces are confirmed canonical.
- Prefer Livewire reactivity, polling, and server-side rendering; use events/broadcasting only for workflows that truly need cross-user realtime behavior.

## Impact
- **BREAKING**: tenant route handlers and duplicate controller-rendered page flows will be replaced or removed once Livewire parity is verified.
- Affected specs:
  - `tenant-portal-surface`
- Related pending changes:
  - `migrate-core-pages-to-livewire-modules`
  - `refactor-shared-layout-components`
  - `normalize-authorization-surfaces`
  - `enforce-validation-architecture`
  - `refactor-unified-ui-system`

## Scope Notes
- This change assumes tenant notifications will become a new Livewire module because there is no dedicated custom tenant notifications page in the current web surface.
- This change does **not** require all non-CRUD backoffice endpoints to move into Filament; custom exports, PDFs, and similar non-CRUD endpoints may remain outside Filament where appropriate.
