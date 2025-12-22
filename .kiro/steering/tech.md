# Technology Stack

## Backend

- **Framework**: Laravel 12.x with PHP 8.3+ (8.2 minimum); Filesystem-backed tenancy enforced by `BelongsToTenant`, `TenantScope`, and `TenantContext`; production builds run with opcache preloading and cached config/route/view. Laravel 12 brings improved validation rules, enhanced query performance, and refined middleware architecture.
- **Admin Layer**: Filament 4.x resources (Properties, Buildings, Meters, MeterReadings, Invoices, Tariffs, Providers, Users, Subscriptions) with navigation visibility controlled by role-aware `can*` methods. Filament 4 leverages Livewire 3 for improved performance through lazy hydration, deferred loading, and optimized table rendering. Use `->live(onBlur: true)` on form fields to reduce re-renders while maintaining validation, and eager-load relationships in table queries to minimize N+1 issues.
- **Billing**: `BillingService`, `TariffResolver`, `BillingCalculatorFactory`, and `MeterReadingObserver` manage tariff snapshots, meter readings audits, and invoice recalculations.
- **Multi-tenancy & security**: Policies gate every resource/action; sessions regenerate on login, and superadmin-only switches respect `TenantContext::switch`.
- **Persistence**: SQLite (dev) plus MySQL/PostgreSQL (prod) with WAL mode enabled and nightly Spatie Backup 10.x (`config/backup.php`), ensuring `php artisan backup:run` succeeds even under load. Spatie Backup 10 includes improved notification channels and enhanced backup verification.

## Frontend

- **Markup**: Blade templates share reusable components (`resources/views/components/`) for cards, data tables, breadcrumbs, modals, and meter-reading forms.
- **Styling & interactivity**: Tailwind CSS 4.x and Alpine.js loaded via CDN in `layouts/app.blade.php`; Filament ships its own frontend bundle under `public/js/filament`. Tailwind 4 introduces modern CSS features including native cascade layers, improved performance through the new engine, and enhanced utility classes. The CDN URL is pinned to version 4.x for stability.
- **Assets**: `resources/js/bootstrap.js` wires Axios defaults; Vite (`vite.config.js`) is kept minimal for future custom builds but currently has no inputs.

## Testing

- **Framework**: Pest 3.x with PHPUnit 11.x runner; `tests/Feature` includes API/Filament suites plus dozens of property-based tests (`*PropertyTest.php`). Pest 3 introduces improved type coverage, enhanced plugin architecture, and better IDE integration. PHPUnit 11 brings refined assertion methods and improved test isolation.
- **Deterministic data**: `TestDatabaseSeeder` orchestrates providers, users, buildings, properties, meters, readings, tariffs, and invoices; `php artisan test:setup --fresh` rebuilds that dataset.
- **Property-based coverage**: Suites cover multi-tenancy, tariff selection, meter reading validation, invoice immutability, and authorization invariants (`FilamentMeterReadingMonotonicityPropertyTest`, `SubscriptionRenewalPropertyTest`, etc.). Property tests run with 100+ iterations to ensure statistical confidence.
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

## Framework Versions

| Component | Version | Key Features |
|-----------|---------|--------------|
| Laravel | 12.x | Improved validation, enhanced query performance, refined middleware architecture |
| Filament | 4.x | Livewire 3 integration, lazy hydration, optimized table rendering, improved form performance |
| PHP | 8.3+ (8.2 min) | Performance improvements, enhanced type system |
| Tailwind CSS | 4.x (CDN) | Modern CSS features, native cascade layers, improved performance engine |
| Pest | 3.x | Enhanced type coverage, improved plugin architecture, better IDE integration |
| PHPUnit | 11.x | Refined assertions, improved test isolation |
| Spatie Backup | 10.x | Enhanced notification channels, improved backup verification |

## Performance Tips

### Filament 4 with Livewire 3

- **Lazy Hydration**: Use `->live(onBlur: true)` on form fields instead of `->reactive()` to reduce re-renders while maintaining validation feedback
- **Deferred Loading**: Leverage `wire:init` for non-critical data to speed up initial page loads
- **Table Optimization**: Eager-load relationships in table queries using `->with()` to prevent N+1 queries
- **Selective Updates**: Use `->afterStateUpdated()` sparingly and only when necessary to minimize server round-trips
- **Caching**: Cache expensive computations in table columns and form fields where data doesn't change frequently

### Laravel 12

- **Query Optimization**: Leverage improved query builder performance for complex billing calculations
- **Validation**: Use new validation rules for cleaner, more expressive validation logic
- **Middleware**: Take advantage of refined middleware architecture for better request handling

### Tailwind CSS 4

- **Modern CSS**: Utilize native cascade layers for better style organization
- **Performance**: Benefit from the new engine's improved compilation and runtime performance
- **CDN Delivery**: Version-pinned CDN ensures consistent styling across deployments

## Configuration Files

- `config/billing.php` – Rates, meter mapping, and tariff configuration.
- `config/subscription.php` – Seat limits, grace periods, automatic read-only mode.
- `config/backup.php` – Spatie Backup 10.x storing WAL files with enhanced verification.
- `config/auth.php`, `config/session.php`, `config/database.php` – Standard Laravel settings wired for multi-tenancy.
- `pint.json`, `phpstan.neon`, `phpunit.xml` – Quality gate configuration.
- `vite.config.js` – Placeholder for future asset builds; currently empty input list.
