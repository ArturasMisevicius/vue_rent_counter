# Project Structure

## Directory Organization

### Application Code (`app/`)

```
app/
├── Console/               # Artisan commands (`TestSetupCommand`, `MigrateToHierarchicalUsersCommand`, helpers in `.kiro/scripts`)
├── Contracts/             # Interfaces for services (e.g., billing calculators)
├── Enums/                 # Domain enums (UserRole, MeterType, TariffType, TariffZone, WeekendLogic)
├── Filament/              # Resources/Pages/Widgets for admin interface (Filament 4.x)
├── Http/
│   ├── Controllers/       # Role-specific controllers (Superadmin, Manager, Tenant, shared helpers)
│   ├── Middleware/        # Tenancy/session guards, security headers (SecurityHeaders), impersonation
│   └── Requests/          # FormRequests driving validation for meter readings, invoices, tariffs
├── Logging/               # Custom log processors (RedactSensitiveData for PII protection)
├── Models/                # Core models (Building, Property, Meter, MeterReading, Invoice, Subscription, Tenant)
├── Notifications/         # Tenant welcome, meter reading, subscription warnings
├── Observers/             # MeterReadingObserver, FaqObserver (cache invalidation)
├── Policies/              # Authorization policies tied to every Filament resource
├── Scopes/                # Global scopes (`TenantScope`, `HierarchicalScope`)
├── Services/              # BillingService, AccountManagementService, TariffResolver, GyvatukasCalculator, SubscriptionService, TenantContext
├── Support/               # Global helpers (`helpers.php`)
├── Traits/                # `BelongsToTenant`, tenancy helpers
├── ValueObjects/          # InvoiceItemData, BillingPeriod, TimeRange, ConsumptionData
├── View/                  # View layer components
│   ├── Components/        # Blade components (reusable UI elements)
│   └── Composers/         # View composers (NavigationComposer for navigation logic)
└── Providers/             # Register scopes/observers (AppServiceProvider, DatabaseServiceProvider)
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
- `config/backup.php` – Spatie Backup 10.x targeting SQLite/WAL files with enhanced verification.
- `config/faq.php` – FAQ categories, cache TTL, and pagination settings.
- `config/security.php` – Security headers, CSP directives, and audit logging configuration.
- `config/logging.php` – Log channels with PII redaction via RedactSensitiveData processor.
- Standard Laravel 12 configs (`auth.php`, `session.php`, `queue.php`, `database.php`, `app.php`).

### Routes & Authorization

- `routes/web.php` defines guest, superadmin, manager, tenant, and shared authenticated routes with role-based middleware.
- `routes/api.php` defines API endpoints for external integrations.
- `routes/console.php` defines Artisan console routes.
- `bootstrap/app.php` (Laravel 12) configures middleware aliases, groups, and exception handling using the new `Application::configure()` pattern.
- Middleware registration uses Laravel 12's `withMiddleware()` method with alias definitions and group assignments.
- Filament 4.x resources register via `Filament::serving`, with navigation visibility controlled through `shouldRegisterNavigation`.
- Policies live in `app/Policies` and are referenced in controllers and Filament `can*` overrides.
- Security headers applied globally via `SecurityHeaders` middleware (CSP, X-Frame-Options, HSTS).

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
│   ├── Filament/          # Filament 4.x-specific scenarios (access, resource scope, namespace consolidation)
│   ├── Http/              # Controller-based feature tests (invoices, meter readings, reports)
│   ├── Middleware/        # Middleware behavior tests (security headers, tenant context)
│   ├── Performance/       # Performance regression tests
│   ├── PropertyTests/     # Property-based test suites
│   ├── Security/          # Security-focused feature tests (authorization, PII redaction)
│   └── *PropertyTest.php  # Property-based suites (100+ iterations each)
├── Performance/           # Dedicated performance tests (N+1 detection, query optimization)
├── Security/              # Security audit tests (authorization, data isolation)
├── Unit/
│   ├── Filament/          # Filament component unit tests
│   ├── Services/          # Service layer unit tests
│   ├── ValueObjects/      # Value object tests
│   └── *Test.php          # Unit-level logic (services, helpers, calculators)
├── Pest.php               # Pest 3.x bootstrap with custom expectations
└── TestCase.php           # Custom helpers (actingAsAdmin/Manager/Tenant, createTestMeterReading)
```

Property tests verify invariants spanning tariff selection, meter reading validation, multi-tenancy, invoice immutability, and framework upgrade regression prevention. Performance tests ensure query optimization and N+1 detection. Security tests validate authorization, PII redaction, and audit logging.

### Documentation (`docs/` + `.kiro/specs/`)

- `docs/overview/`, `docs/frontend/`, `docs/routes/`, `docs/reviews/` keep implementation notes, accessibility guides, and route cleanup info.
- `docs/upgrades/` contains upgrade guides (Laravel 12, Filament 4, Tailwind 4, batch migrations).
- `docs/performance/` documents optimization strategies (N+1 detection, query optimization, caching).
- `docs/security/` contains security audits, implementation guides, and testing procedures.
- `docs/testing/` provides testing guides, verification procedures, and test coverage reports.
- `docs/api/` documents API architecture and middleware patterns.
- `docs/architecture/` explains database schema, service patterns, and multi-tenancy architecture.
- `.kiro/specs/` tracks requirements for features (`1-framework-upgrade`, `filament-admin-panel`, `hierarchical-user-management`, `vilnius-utilities-billing`, `authentication-testing`, `user-group-frontends`, `6-filament-namespace-consolidation`).
- `docs/frontend/FRONTEND.md` explains CDN Tailwind 4.x/Alpine usage; `docs/testing/README.md` documents `php artisan test:setup`.

## Architectural Notes

- **Laravel 12 Bootstrap**: Application configuration uses `Application::configure()` pattern in `bootstrap/app.php` with `withMiddleware()`, `withRouting()`, and `withExceptions()` methods for centralized setup.
- **Middleware Architecture**: Laravel 12 middleware registration uses alias definitions and group assignments via `Middleware` configuration object. Security headers, locale handling, and impersonation applied globally.
- **Boundaries**: Superadmin, manager, and tenant responsibilities are separated via controllers, policies, and Filament 4.x navigation. Shared services (`BillingService`, `SubscriptionService`) coordinate cross-cutting logic.
- **Multi-tenancy**: `BelongsToTenant` trait, `TenantScope`, `HierarchicalScope`, and `TenantContext` ensure every write/read is scoped by `tenant_id` with hierarchical access control.
- **Billing math**: `MeterReadingObserver` audits adjustments, `GyvatukasCalculator` handles seasons, and `TariffResolver` selects zone-based rates before `InvoiceItemData` persists them.
- **UI composition**: Filament 4.x components (with Livewire 3 lazy hydration) reuse Blade cards, modals, and data tables; tenant views mirror the same primitives for consistent UX. View composers (e.g., `NavigationComposer`) handle navigation logic without inline PHP in Blade.
- **Routing**: `routes/web.php` orchestrates role-based middleware; shared controllers (`BuildingController`, `PropertyController`, `MeterController`, `InvoiceController`) include resource routes for both managers and admins.
- **Performance**: Eager-load relationships (property→meters→readings) in controllers, index hot columns (tenant_id, meter_id, billing_period_start), cache translations and authorization checks, and keep backup/queue tasks observable. Filament 4.x uses `->live(onBlur: true)` for reduced re-renders.
- **Security**: `SecurityHeaders` middleware applies CSP, X-Frame-Options, HSTS, and other OWASP-recommended headers. `RedactSensitiveData` log processor removes PII from logs. Authorization exceptions handled with user-friendly messages and audit logging.

## Extensibility & Ops

- To add a new meter/invoice feature: update Filament 4.x Resource + policy, extend `BillingService`, capture data in audit tables, include property tests, and add performance tests for N+1 detection.
- Tenant context shifts require `TenantContext::set`, clearing the cache, and verifying `BelongsToTenant` and `HierarchicalScope` global scopes.
- Deployment steps include `composer install`, `npm install` (if introducing assets), `php artisan migrate --force`, `php artisan test:setup --fresh`, `php artisan optimize`, and `php artisan config:cache`.
- Observability uses `php artisan pail` for logs with PII redaction, `spatie/laravel-backup` (v10.x) for nightly snapshots with enhanced verification, and seeders/test helpers for reproducibility.
- Cache invalidation handled by observers (e.g., `FaqObserver` for FAQ cache, `MeterReadingObserver` for invoice recalculation).
- Security monitoring includes audit logging for authorization failures, PII redaction in logs, and CSP violation reporting.

## Laravel 12 Conventions

### Bootstrap & Configuration

- **Application Bootstrap**: `bootstrap/app.php` uses `Application::configure()` with fluent configuration methods.
- **Middleware Registration**: Middleware aliases and groups defined via `withMiddleware()` callback.
- **Exception Handling**: Custom exception rendering via `withExceptions()` callback with structured logging.
- **Route Registration**: Routes defined via `withRouting()` with explicit file paths and health check endpoint.

### Middleware Patterns

- **Alias Registration**: Middleware aliases registered in `bootstrap/app.php` via `$middleware->alias()`.
- **Group Assignment**: Middleware added to groups via `$middleware->appendToGroup()`.
- **Conditional Middleware**: Test environment middleware removal via `$middleware->remove()` and `$middleware->removeFromGroup()`.
- **Rate Limiting**: API throttling via `$middleware->throttleApi()`.

### View Layer

- **View Composers**: Registered in `AppServiceProvider` to prepare data for views without inline PHP in Blade.
- **Blade Components**: Reusable components in `app/View/Components/` for consistent UI patterns.
- **No @php Blocks**: All logic moved to view composers, components, or Filament resources per blade-guardrails.md.

### Testing with Pest 3.x

- **Property-Based Tests**: Run 100+ iterations per property to ensure statistical confidence.
- **Test Organization**: Feature, Unit, Performance, and Security test directories with clear separation.
- **Custom Helpers**: `TestCase.php` provides role-based authentication helpers and test data factories.
- **Framework Version Tests**: `FrameworkVersionTest.php` validates Laravel 12.x, Filament 4.x, and dependency versions.
