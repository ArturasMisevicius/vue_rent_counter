# Technology Stack

## Backend

- **Framework**: Laravel 11 with PHP 8.2+; Filesystem-backed tenancy enforced by `BelongsToTenant`, `TenantScope`, and `TenantContext`.
- **Admin Layer**: Filament 3 resources (Properties, Buildings, Meters, MeterReadings, Invoices, Tariffs, Providers, Users, Subscriptions) with navigation visibility controlled by role-aware `can*` methods.
- **Billing**: `BillingService`, `TariffResolver`, `GyvatukasCalculator`, `BillingCalculatorFactory`, and `MeterReadingObserver` manage tariff snapshots, gyvatukas seasons, meter readings audits, and invoice recalculations.
- **Multi-tenancy & security**: Policies gate every resource/action; sessions regenerate on login, and superadmin-only switches respect `TenantContext::switch`.
- **Persistence**: SQLite (dev) plus MySQL/PostgreSQL (prod) with WAL mode enabled and nightly Spatie backups (`config/backup.php`), ensuring `php artisan backup:run` succeeds even under load.

## Frontend

- **Markup**: Blade templates share reusable components (`resources/views/components/`) for cards, data tables, breadcrumbs, modals, and meter-reading forms.
- **Styling & interactivity**: Tailwind CSS and Alpine.js loaded via CDN in `layouts/app.blade.php`; Filament ships its own frontend bundle under `public/js/filament`.
- **Assets**: `resources/js/bootstrap.js` wires Axios defaults; Vite (`vite.config.js`) is kept minimal for future custom builds but currently has no inputs.

## Testing

- **Framework**: PestPHP with PHPUnit runner; `tests/Feature` includes API/Filament suites plus dozens of property-based tests (`*PropertyTest.php`).
- **Deterministic data**: `TestDatabaseSeeder` orchestrates providers, users, buildings, properties, meters, readings, tariffs, and invoices; `php artisan test:setup --fresh` rebuilds that dataset.
- **Property-based coverage**: Suites cover multi-tenancy, tariff selection, gyvatukas math, meter reading validation, invoice immutability, and authorization invariants (`FilamentMeterReadingMonotonicityPropertyTest`, `SubscriptionRenewalPropertyTest`, etc.).
- **Accessibility/UX**: Accessibility tests include `FilamentPanelAccessibilityTest`, Breadcrumb/Navigation tests, and docs describing keyboard-first flows.

## Code Quality

- **Style/analysis**: `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse`, and optional Rector runs keep standards high.
- **Documentation**: `docs/frontend/FRONTEND.md`, `docs/routes/ROUTES_IMPLEMENTATION_COMPLETE.md`, and `.kiro/specs/*` (filament, billing, hierarchical users, authentication testing) document intentions.
- **Helpers**: `tests/TestCase.php` exposes `actingAsAdmin/Manager/Tenant`, `createTestProperty`, and `createTestMeterReading` helpers for repeatable property tests.

## Common Commands

### Setup
```bash
composer install
npm install          # Optional until assets are added
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan test:setup --fresh
```

### Development
```bash
php artisan serve
php artisan pail         # Tail Kafka-ish logs
npx vite dev             # Only if you add compiled assets later
```

### Testing
```bash
php artisan test
php artisan test --parallel
./vendor/bin/pest --testsuite=Feature
```

### Code Quality
```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
rector
```

### Database & Backup
```bash
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed
php artisan backup:run
```

## Configuration Files

- `config/billing.php` – Rates, meter mapping, gyvatukas connection.
- `config/gyvatukas.php` – Summer/winter logic and circulation formulas.
- `config/subscription.php` – Seat limits, grace periods, automatic read-only mode.
- `config/backup.php` – Spatie backup storing WAL files.
- `config/auth.php`, `config/session.php`, `config/database.php` – Standard Laravel settings wired for multi-tenancy.
- `pint.json`, `phpstan.neon`, `phpunit.xml` – Quality gate configuration.
- `vite.config.js` – Placeholder for future asset builds; currently empty input list.
