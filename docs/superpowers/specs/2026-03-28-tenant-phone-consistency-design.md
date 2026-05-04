# Tenant Phone Consistency Design

> **AI agent usage:** This is a design/spec artifact. Read `AGENTS.md`, `docs/SESSION-BOOTSTRAP.md`, and `docs/AI-AGENT-DOCS.md`, then verify live code and tests before assuming the behavior still matches this document.

## Goal

Ensure tenant phone data is handled consistently across all tenant-user update surfaces in the application:

- tenant self-profile editing
- admin tenant editing
- tenant portal read models and views

The intended outcome is that `users.phone` remains a first-class tenant contact field everywhere tenant identity is edited or displayed, without introducing new query paths or duplicate business logic.

## Problem

The write surfaces are already mostly aligned:

- the shared profile request/action validates and persists `phone`
- the admin tenant request/action validates and persists `phone`
- the admin tenant resource form renders `phone`

The read surfaces are not fully aligned:

- tenant portal presenters expose `tenant_name` and `tenant_email`
- tenant portal views render name and email, but not phone
- automated coverage proves some phone behavior in admin and profile surfaces, but not the full cross-surface consistency contract

This creates a drift where tenant phone can be edited in some places but is not treated as part of the canonical tenant contact identity everywhere the tenant sees or admins manage their account.

## Scope

This slice includes:

- keeping tenant self-profile phone editing covered and verified
- keeping admin tenant edit phone persistence covered and verified
- adding `tenant_phone` to tenant portal presenter payloads
- rendering tenant phone in tenant-facing identity cards where tenant name/email are already shown
- adding focused Pest feature coverage for the cross-surface phone contract

This slice does not include:

- schema or migration changes
- phone-format normalization beyond the current validation rules
- new tenant contact models, DTOs, or separate contact-preferences settings
- KYC/contact-person redesign

## Approved Product Decisions

- `users.phone` remains the single source of truth for tenant phone contact data.
- Tenant self-profile and admin tenant edit continue to use their existing request/action paths.
- Tenant portal pages should display tenant phone anywhere they already present tenant identity cards with name and email.
- The change should not add new queries; only existing tenant lookups may widen their selected columns.

## Architecture

The implementation keeps current write paths intact and fixes the contract at the edges:

1. `UpdateProfileRequest` and `UpdateProfileAction` remain the self-profile write path.
2. `UpdateTenantRequest` and `UpdateTenantAction` remain the admin write path.
3. `TenantHomePresenter` and `TenantPropertyPresenter` become the tenant-portal read-model source for phone display.
4. Tenant Livewire Blade views render the new `tenant_phone` field only where tenant identity is already rendered.

This preserves the existing separation of responsibilities:

- validation in `app/Http/Requests`
- write logic in `app/Filament/Actions`
- read-model shaping in `app/Filament/Support`
- UI rendering in Livewire Blade views

## Query Impact

Expected query delta: no additional queries.

Expected payload delta:

- widen existing tenant portal tenant selects to include `phone`
- add `tenant_phone` to presenter arrays derived from the same model instance already being loaded

## Testing Strategy

The change should be implemented with TDD:

1. Add failing tenant profile coverage for prefilled phone and phone persistence.
2. Add failing admin tenant update coverage for changing phone through `UpdateTenantAction`.
3. Add failing tenant portal coverage that presenter output and rendered pages include tenant phone.
4. Implement the minimum code to satisfy each test.
5. Run focused Pest files, then the consolidated targeted suite.

## Risks and Caveats

- Keep existing explicit `select([...])` clauses intact; only add the missing `phone` column.
- Do not move logic into Blade templates or Filament closures.
- Do not introduce extra tenant queries where eager-loaded data or existing presenter queries already provide the record.
- If any tenant portal card should omit phone for UX reasons, the omission must be intentional and backed by a test, not an accidental missing field.
