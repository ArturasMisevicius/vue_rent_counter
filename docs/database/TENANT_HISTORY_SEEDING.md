# Tenant History Seeding & Factory Guide

**Scope:** Historical tenant records (≈100 per tenant_id), pivot history, and tenant-aware factories for seeds/tests.  
**Audience:** Backend devs running seeds, writing factories, and validating tenant-scoped data.

## What It Seeds
- `TenantHistorySeeder` (added): creates time-ordered historical tenants per `tenant_id` and inserts matching `property_tenant` rows with `assigned_at`/`vacated_at`.
- Existing test seeders now rely on factories:
  - `TestTenantsSeeder`/`TestPropertiesSeeder`/`TestMetersSeeder`/`TestMeterReadingsSeeder`/`TestInvoicesSeeder`
  - Pivot assignments and meter readings inherit tenant IDs from relationships (no hardcoded IDs).

## How to Run
- Fresh load with history:  
  `php artisan migrate:fresh --seed --seeder=TestDatabaseSeeder`
- Just the history slice (after properties exist):  
  `php artisan db:seed --class=TenantHistorySeeder`

## Factory Helpers (tenant-safe)
- `PropertyFactory::forTenantId($tenantId)`
- `TenantFactory::forTenantId($tenantId)` and `TenantFactory::forProperty($property)`
- `BuildingFactory::forTenantId($tenantId)`
- `MeterFactory::forProperty($property)` / `forTenantId($tenantId)`
- `MeterReadingFactory::forMeter($meter)` (aligns `entered_by` tenant)
- `InvoiceFactory::forTenantRenter($tenant)` (syncs `tenant_id`)
- `InvoiceItemFactory` recalculates `total` when not provided.

**Rule:** Prefer these helpers over manual `tenant_id`/`property_id` assignment to preserve scope consistency and satisfy `BelongsToTenant` global scope.

## Data Shape & Assumptions
- History depth: ~100 tenants per `tenant_id`, spread across available properties.
- Dates: start at ~12 years ago; each lease spans 3–12 months; `vacated_at` is set in `property_tenant`.
- Active tenants: `TestInvoicesSeeder` targets only tenants with `lease_end` null or in the future.

## Tests Covering This
- Factories: `tests/Unit/FactoryCoverageTest.php`
- History seeder: `tests/Feature/TenantHistorySeederTest.php`
- Invoices + seeds: `tests/Unit/TestInvoicesSeederTest.php`, `tests/Feature/SeederCoverageTest.php`

## When to Update This Doc
- Changing history volume or date generation.
- Adding new tenant-aware factory states.
- Adjusting seed order or adding new seeders that depend on tenant scope.
