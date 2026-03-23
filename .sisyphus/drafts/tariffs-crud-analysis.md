# Draft: Tariffs CRUD Coverage

## Requirements (confirmed)
- User request: "create tariffs to all crud to all possible places where it need, make analyze and fix all"
- User requested analysis-first mode with parallel context gathering.

## Technical Decisions
- Working mode: Interview + analysis-first before plan generation.
- Intent classification (initial): Mid-sized/complex feature expansion (Tariff CRUD across app touchpoints).

## Research Findings
- Direct scan (grep/LSP/glob) confirms active Tariff domain already exists in current app:
  - `app/Models/Tariff.php`, `app/Filament/Resources/Tariffs/*`, `app/Filament/Actions/Admin/Tariffs/*`, `app/Http/Requests/Admin/Tariffs/TariffRequest.php`, `database/factories/TariffFactory.php`.
- Tariff CRUD is present in Filament Admin resource pages (`List/Create/View/Edit`) and covered by feature tests (`tests/Feature/Admin/TariffsResourceTest.php`, `tests/Feature/Admin/TariffsAndProvidersTest.php`).
- No `Route::resource` / `Route::apiResource` entries were found in `routes/*.php` from direct search, indicating CRUD likely runs via Filament panel and custom action flow rather than explicit REST resource routes.
- Tariff relationships and usage signals exist across billing and configuration flows:
  - `Provider::tariffs()`, `ServiceConfiguration::tariff()`, `app/Services/Billing/TariffResolver.php`.
- Legacy `_old/` contains prior tariff controllers/policies/relation managers that may be reference-only and should be excluded from active scope unless explicitly requested.
- Pending (background agents): deep gap map, canonical CRUD pattern comparison, external best-practice synthesis.

### Background Agent Synthesis (completed)
- **Existing Tariff CRUD is already present** in active code (not just legacy):
  - Model: `app/Models/Tariff.php`
  - Filament resource + pages: `app/Filament/Resources/Tariffs/*` (List/Create/View/Edit)
  - Mutation actions: `app/Filament/Actions/Admin/Tariffs/{Create,Update,Delete}TariffAction.php`
  - Validation: `app/Http/Requests/Admin/Tariffs/TariffRequest.php`
  - Tests: `tests/Feature/Admin/TariffsResourceTest.php`, `tests/Feature/Admin/TariffsAndProvidersTest.php`
- **Confirmed likely gaps / expansion opportunities**:
  - No explicit API CRUD endpoints for tariffs (resource routes/controllers absent)
  - Authorization handled inline in Filament Resource (no dedicated `TariffPolicy` in active code)
  - Tariff resource navigation hidden (`$shouldRegisterNavigation = false`)
  - No explicit tariff import/export workflow found
  - No dedicated active seeder focused solely on tariffs (mostly indirect via foundation seeder)
- **Canonical repository pattern** (for business CRUD modules):
  - Filament Resource + schema/table/infolist + page classes
  - Admin actions in `app/Filament/Actions/Admin/{Domain}`
  - Form Request validation in `app/Http/Requests/Admin/...`
  - Org/tenant scope enforcement via `OrganizationContext` + resource query guards
  - Pest feature tests under `tests/Feature/Admin/*ResourceTest.php`
- **External best-practice guidance** (agent synthesis):
  - Money/value modeling should avoid float arithmetic; use decimal discipline/value objects
  - Strong tenancy boundary checks in query + authorization
  - Auditability/activity logging for tariff changes
  - Comprehensive negative-path tests for authorization and cross-tenant access

## Scope Boundaries
- INCLUDE (tentative): identify all required places where Tariff CRUD should exist.
- EXCLUDE (tentative): implementation changes until plan is approved.
- EXCLUDE (tentative): `_old/` legacy code unless user asks to port or revive it.

## Implementation Status (executed after scope confirmation)
- User-confirmed direction: **Harden existing Tariff CRUD** (not broad new-surface expansion).
- User-confirmed quality mode: **TDD**.
- Implemented hardening changes:
  - Added explicit `TariffPolicy` and mapped it in `AuthServiceProvider`.
  - Updated `TariffResource` authorization to use centralized Gate policy checks.
  - Hardened `DeleteTariffAction` to enforce delete authorization when authenticated context exists.
  - Added policy coverage test: `tests/Feature/Admin/TariffPolicyTest.php`.
  - Added cross-organization delete guard test: `tests/Feature/Admin/DeleteTariffAuthorizationTest.php`.

## Verification Evidence
- Focused tests (Pest):
  - `tests/Feature/Admin/TariffPolicyTest.php`
  - `tests/Feature/Admin/TariffsResourceTest.php`
  - `tests/Feature/Admin/TariffsAndProvidersTest.php`
  - `tests/Feature/Admin/DeleteTariffAuthorizationTest.php`
  - Result: **7 passed (81 assertions)**.
- Pint formatting: `vendor/bin/pint --dirty --format agent` → **pass**.
- LSP diagnostics (all touched files) → **no diagnostics found**.

## Open Questions
- Which tariff domains are in scope (Filament admin only vs API endpoints + tenant/manager UI + exports/imports + permissions)?
- Should this include tenant-specific tariffs, global tariffs, or both?
- Preferred test strategy: TDD, tests-after, or no automated tests?
- Since admin Tariff CRUD exists already, do you want:
  - hardening/fixing existing Tariff CRUD gaps, or
  - adding Tariff CRUD into additional modules/surfaces that currently lack it?
