# Proposal: Add Tenant Self-Service Portal

## Summary

Add the first complete tenant-facing self-service portal so authenticated tenant
users can navigate a mobile-first experience, review balances and readings,
submit new meter readings, browse invoices, inspect property details, and
manage their profile without entering Filament.

## Problem

The current tenant experience stops at a placeholder `tenant.home` page. There
is no fixed tenant navigation, no property-scoped dashboard data, no invoice
history, no reading submission flow, no profile management surface, and no
specification that locks down tenant-only authorization boundaries for these
pages.

## Proposed Change

- Add a tenant-only portal shell with a fixed four-item bottom navigation:
  `Home`, `Readings`, `Invoices`, and `Profile`
- Expand tenant home into a real dashboard with outstanding balance, current
  month usage, recent readings, payment instructions, and a `My Property` link
- Add a read-only property page for the tenant's assigned property and meters
- Add tenant-scoped meter reading submission using the shared validation and
  write path from the admin domain
- Add invoice history cards with status filters and protected PDF downloads
- Add tenant profile editing, locale switching, and password update flows
- Specify assignment-scoped authorization so tenants cannot access another
  property's meters, readings, or invoices

## Dependencies

- `docs/superpowers/plans/2026-03-17-foundation-auth-onboarding.md`
- `docs/superpowers/plans/2026-03-17-shared-interface-elements.md`
- `docs/superpowers/plans/2026-03-17-admin-organization-operations.md`

This change assumes the admin organization-operations work provides the shared
`Property`, `Meter`, `MeterReading`, `Invoice`, `OrganizationSetting`, and
policy/domain services that the tenant portal will reuse.

## Out Of Scope

- A tenant Filament panel
- A left sidebar for tenant users
- Online payment processing or gateway integration
- Tenant-only duplicate copies of invoice, meter, or reading business logic
- A fifth bottom-navigation item for `My Property`

## Impact

- Adds new tenant routes, controllers, Livewire components, presenters/query
  objects, actions, translations, and feature tests
- Turns the current tenant placeholder page into a shared portal entry point
- Requires durable policy and query scoping rules for tenant assignment
- Establishes the first OpenSpec change set for this repository's tenant portal
