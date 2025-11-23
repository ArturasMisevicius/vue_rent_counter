# Project Structure

## Directory Organization

### Application Code (`app/`)

```
app/
├── Console/               # Artisan commands (`TestSetupCommand`, `MigrateToHierarchicalUsersCommand`, helpers in `.kiro/scripts`)
├── Contracts/             # Interfaces for services (e.g., billing calculators)
├── Enums/                 # Domain enums (UserRole, MeterType, TariffType, TariffZone, WeekendLogic)
├── Filament/              # Resources/Pages/Widgets for admin interface
├── Http/
│   ├── Controllers/       # Role-specific controllers (Superadmin, Manager, Tenant, shared helpers)
│   ├── Middleware/        # Tenancy/session guards and security headers
│   └── Requests/          # FormRequests driving validation for meter readings, invoices, tariffs
├── Models/                # Core models (Building, Property, Meter, MeterReading, Invoice, Subscription, Tenant)
├── Notifications/         # Tenant welcome, meter reading, subscription warnings
├── Observers/             # MeterReadingObserver, etc.
├── Policies/              # Authorization policies tied to every Filament resource
├── Scopes/                # Global scopes (`TenantScope`)
├── Services/               # BillingService, AccountManagementService, TariffResolver, GyvatukasCalculator, SubscriptionService, TenantContext
├── Support/               # Global helpers (`helpers.php`)
├── Traits/                 # `BelongsToTenant`, tenancy helpers
├── ValueObjects/           # InvoiceItemData, BillingPeriod, TimeRange, ConsumptionData
└── Providers/              # Register scopes/observers (AppServiceProvider)
```

### Views (`resources/views/`)

```
resources/views/
├── admin/                  # Admin dashboards, reports, dashboards
├── buildings/              # Building mgmt forms and summaries
├── components/             # Reusable Blade components (cards, tables, modals, breadcrumbs, meter-reading form)
├── errors/                 # Error pages (401, 403, 404, 422, 500)
├── invoices/               # Invoice display/download templates
├── layouts/                # `layouts/app.blade.php` loads CDN Tailwind + Alpine
├── manager/                # Manager dashboards, readings, invoices
├── meter-readings/         # Dedicated meter-reading workflows
├── meters/                 # Meter listing/stats
├── properties/             # Property-level views
├── reports/                # Reports & charts (consumption, revenue, compliance)
├── superadmin/             # Organization/subscription dashboards
├── tenant/                 # Tenant dashboards/profile
├── tenants/                # Tenant management views
└── welcome.blade.php       # Public landing page
```

Components directory holds shared primitives (stat cards, data tables, modal wrappers) reused by Filament and tenant-facing pages.

### Frontend Assets

- `resources/js/bootstrap.js` wires Axios; `resources/js/app.js` is placeholder for future bundling.
- Tailwind and Alpine are loaded via CDN directly in the layout (`resources/views/layouts/app.blade.php`); Filament ships its own JS/CSS under `public/js/filament`.
- Vite config remains minimal (`vite.config.js`) for potential future asset bundling but has no input files by default.

### Configuration (`config/`)

- `config/billing.php` – Tariff, meter, gyvatukas constants and rates.
- `config/gyvatukas.php` – Season thresholds and formulas for circulation fees.
- `config/subscription.php` – Plans, limits, gracefully degrade when expired.
- `config/backup.php` – Spatie backup targeting SQLite/WAL files.
- Standard Laravel configs (`auth.php`, `session.php`, `queue.php`, `database.php`, `app.php`).

### Routes & Authorization

- `routes/web.php` defines guest, superadmin, manager, tenant, and shared authenticated routes with role-based middleware.
- Filament resources register via `Filament::serving`, with navigation visibility controlled through `shouldRegisterNavigation`.
- Policies live in `app/Policies` and are referenced in controllers and Filament `can*` overrides.

### Database (`database/`)

```
database/
├── factories/             # Factories for buildings, properties, meters, invoices
├── migrations/            # Tenant-aware tables, meters, readings, invoices, gyvatukas data
└── seeders/
    ├── ProvidersSeeder.php
    ├── OrganizationSeeder.php
    ├── HierarchicalUsersSeeder.php
    ├── Test*Seeder.php     # Dedicated seeders for buildings, properties, meters, meter readings, tariffs, invoices, users
    └── DatabaseSeeder.php
```

`TestDatabaseSeeder` and `php artisan test:setup --fresh` command guarantee predictable data for CI/property tests.

### Tests (`tests/`)

```
tests/
├── Feature/
│   ├── Filament/          # Filament-specific scenarios (access, resource scope)
│   ├── Http/              # Controller-based feature tests (invoices, meter readings, reports)
│   ├── PropertyTests      # Property-based suites scattered across files (`*PropertyTest.php`)
│   └── Authentication/... # Multi-role auth + multi-tenancy verification
├── Unit/                  # Unit-level logic (services, helpers)
├── Pest.php               # Pest bootstrap
└── TestCase.php           # Custom helpers (actingAsAdmin/Manager/Tenant, createTestMeterReading)
```

Property tests verify invariants spanning tariff selection, meter reading validation, multi-tenancy, and invoice immutability.

### Documentation (`docs/` + `.kiro/specs/`)

- `docs/overview/`, `docs/frontend/`, `docs/routes/`, `docs/reviews/` keep implementation notes, accessibility guides, and route cleanup info.
- `.kiro/specs/` tracks requirements for features (`filament-admin-panel`, `hierarchical-user-management`, `vilnius-utilities-billing`, `authentication-testing`, `user-group-frontends`).
- `docs/frontend/FRONTEND.md` explains CDN Tailwind/Alpine usage; `docs/tests/` (if present) documents `php artisan test:setup`.

## Architectural Notes

- **Boundaries**: Superadmin, manager, and tenant responsibilities are separated via controllers, policies, and Filament navigation. Shared services (`BillingService`, `SubscriptionService`) coordinate cross-cutting logic.
- **Multi-tenancy**: `BelongsToTenant` trait, `TenantScope`, and `TenantContext` ensure every write/read is scoped by `tenant_id`.
- **Billing math**: `MeterReadingObserver` audits adjustments, `GyvatukasCalculator` handles seasons, and `TariffResolver` selects zone-based rates before `InvoiceItemData` persists them.
- **UI composition**: Filament components reuse Blade cards, modals, and data tables; tenant views mirror the same primitives for consistent UX.
- **Routing**: `routes/web.php` orchestrates role-based middleware; share controllers (`BuildingController`, `PropertyController`, `MeterController`, `InvoiceController`) include resource routes for both managers and admins.
- **Performance**: Eager-load relationships (property→meters→readings) in controllers, index hot columns (tenant_id, meter_id, billing_period_start), and keep backup/queue tasks observable.

## Extensibility & Ops

- To add a new meter/invoice feature: update Filament Resource + policy, extend `BillingService`, capture data in audit tables, and include property tests.
- Tenant context shifts require `TenantContext::set`, clearing the cache, and verifying `BelongsToTenant` global scopes.
- Deployment steps include `composer install`, `npm install` (if introducing assets), `php artisan migrate --force`, `php artisan test:setup --fresh`, and `php artisan optimize`.
- Observability uses `php artisan pail` for logs, `spatie/laravel-backup` for nightly snapshots, and seeders/test helpers for reproducibility.
