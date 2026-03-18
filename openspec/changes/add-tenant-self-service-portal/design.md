# Design: Tenant Self-Service Portal

## Context

Today the repository exposes only one tenant route, `tenant.home`, backed by a
placeholder Blade view and a controller that simply checks `isTenant()`. This
OpenSpec change captured the larger tenant portal rollout before the current
repository had a full `openspec/` scaffold.

## Goals

- Define the tenant portal as a stable product surface before implementation
- Keep the tenant experience outside Filament and inside the shared web shell
- Reuse shared admin-domain models, policies, validation services, and invoice
  document paths instead of forking tenant business logic
- Make tenant isolation explicit across routes, queries, downloads, and writes

## Non-Goals

- Re-architecting the admin domain
- Introducing online bill payment
- Designing a second tenant-only model layer

## Architecture Overview

The portal stays server-rendered with Blade pages and uses small Livewire
islands only where the product needs refresh or immediate feedback. The home
summary and reading submission form are the only planned Livewire surfaces.
Everything else remains controller-driven Blade with actions, presenters, and
query objects handling the orchestration.

## Capability Breakdown

### Tenant portal shell

- Tenant-only routes live inside the authenticated locale-aware middleware
  group already used by the current tenant home page.
- The shell presents a fixed bottom navigation with exactly four items:
  `Home`, `Readings`, `Invoices`, and `Profile`.
- `My Property` remains a secondary route that is reachable from the dashboard
  and property-related UI, but not from the bottom navigation.

### Home dashboard

- `TenantHomePresenter` gathers the dashboard payload with eager loading.
- `HomeSummary` becomes a `wire:poll.120s` Livewire island so balance and
  reading data can refresh without reloading the whole page.
- Payment instructions are resolved from organization billing/contact settings,
  not from a checkout integration.

### Property overview

- The property page is read-only and only exposes the signed-in tenant's
  assigned property, associated building context, and assigned meters.
- Latest reading data should be loaded together with meters to avoid per-row
  queries.

### Reading submission

- `SubmitReadingPage` stays thin and delegates persistence to
  `SubmitTenantReadingAction`.
- Validation rules such as non-decreasing values, future-date rejection, and
  anomaly handling must reuse the shared admin-domain validator and create
  action rather than diverge in tenant code.
- Single-meter tenants should see a preselected, locked meter input.

### Invoice history

- `TenantInvoiceIndexQuery` owns filtering, eager loading, and pagination for
  invoice cards.
- Filters should remain query-string based so pagination and browser back
  behavior are stable.
- PDF download must authorize against the existing invoice policy and reuse the
  existing invoice document path/generator.

### Profile management

- Profile and password updates are split into separate Form Request and Action
  flows.
- Locale persistence continues through `UpdateUserLocaleAction`, and a
  successful locale change should affect the immediately redirected page.

### Tenant access isolation

- Every read and write path is scoped to the tenant's assigned property,
  meters, readings, and invoices only.
- There is no cross-property fallback. The system must deny access instead of
  silently broadening scope.

## Data And Query Strategy

- Use Eloquent only, with explicit eager loading for every relationship used on
  the page
- Keep list logic in presenters/query objects instead of controller-local
  chains
- Reuse shared policies and domain services rather than writing tenant-only
  duplicates
- Avoid new configuration files unless the implementation truly needs them;
  the referenced plan mentions `config/tenanto.php`, but the current repo does
  not contain that file

## Testing Strategy

- Add tenant feature tests for navigation, home, property, readings, invoices,
  profile, and cross-tenant isolation
- Extend the existing auth access-isolation coverage with at least one
  high-level tenant portal regression
- Use Livewire tests for reading form interactions and normal feature tests for
  route/page flows

## Risks And Dependencies

- This change depends on the admin organization-operations plan landing shared
  models and policies first, or being merged into the same branch
- If the shared reading validator or invoice document path is not available
  yet, tenant implementation must block until that shared path exists rather
  than duplicating the logic
