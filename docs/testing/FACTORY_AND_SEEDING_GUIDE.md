# Factory & Seeding Guide (Multi-Tenant, Filament v4)

**Why:** Keep seeds/factories aligned with tenant scope, avoid Filament boot fatals, and ensure tests cover data setup reliably.  
**Audience:** Developers writing seeds/tests/factories in this Laravel 12 + Filament v4 multi-tenant app.

## Patterns to Follow
- **Tenant-aware factories:** Use helper states instead of hardcoding IDs:
  - `PropertyFactory::forTenantId($tenantId)`
  - `TenantFactory::forTenantId($tenantId)` / `forProperty($property)`
  - `MeterFactory::forProperty($property)` / `forTenantId($tenantId)`
  - `MeterReadingFactory::forMeter($meter)`
  - `InvoiceFactory::forTenantRenter($tenant)`
  - `InvoiceItemFactory` recalculates `total` when missing.
- **Seeder order:** Properties → tenants → history (`TenantHistorySeeder`) → meters → readings → tariffs → invoices. This preserves tenant scope and active-tenant filtering for invoices.
- **Historical data:** `TenantHistorySeeder` seeds ~100 historical tenants per tenant_id and inserts `property_tenant` pivot rows with `assigned_at`/`vacated_at`.
- **Filament v4 boot:** Resources must use `getNavigationIcon()` / `getNavigationGroup()` and `form(Schema $schema): Schema`. Tests include a `class_alias` shim (`tests/Pest.php`) for legacy Form references.

## Commands
- Full reset with history:  
  `php artisan migrate:fresh --seed --seeder=TestDatabaseSeeder`
- Seed history only (requires properties):  
  `php artisan db:seed --class=TenantHistorySeeder`
- Focused verification:  
  `php artisan test tests/Unit/FactoryCoverageTest.php tests/Feature/TenantHistorySeederTest.php tests/Unit/TestInvoicesSeederTest.php tests/Feature/SeederCoverageTest.php`

## Coverage & Expectations
- Factories: `tests/Unit/FactoryCoverageTest.php`
- History seeding: `tests/Feature/TenantHistorySeederTest.php`
- Invoice seeding logic: `tests/Unit/TestInvoicesSeederTest.php`
- Global seed sanity: `tests/Feature/SeederCoverageTest.php`

## When to Update This Doc
- Adding or changing tenant-aware factory states.
- Altering seed order or introducing new tenant-scoped seeders.
- Removing the Filament Form alias once all components are on `Filament\Schemas\Schema`.
