# Quality Playbook

## Quality Gates (must pass)

- **Static**: `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse`, and any custom Rector/Formatter runs referenced in docs. Treat style or static warnings as blockers.
- **Tests**: Pest + PHPUnit suites (Feature, Unit, Filament folders) plus property tests inside `tests/Feature/*PropertyTest.php` that enforce tenant isolation, tariff accuracy, gyvatukas math, and authorization.
- **Accessibility & UX**: Blade components and Filament panels must render accessible markup with descriptive labels, focus outlines, and keyboard-friendly tables/filters; note CDN-based Tailwind & Alpine additions in `resources/views/layouts/app.blade.php`.
- **Billing integrity**: Every invoice must snapshot tariffs/gyvatukas and include audited meter reading snapshots; `MeterReadingObserver` recalculations should not re-open finalized invoices.
- **Security**: Policies guard Filament resources/controllers, `BelongsToTenant` enforces tenant scope, session regeneration and CSP/headers stay intact, and Spatie backup runs confirm data durability.

## Checklists

- **Backend**: Keep services composable (`BillingService`, `TariffResolver`, `GyvatukasCalculator`, `AccountManagementService`); prefer FormRequests for validation and policies for every mutating action; guard `tenant_id` via `TenantScope`.
- **Filament**: Resource forms reuse helpers (tenant filters, validation messages, `InvoiceItem` bulk updates); display togglable columns, rename badges, and always hide navigation from tenants when appropriate.
- **Frontend**: Blade components under `resources/views/components/` supply consistent cards, tables, breadcrumbs, and modals; use Alpine CDN for inline reactivity and Tailwind CDN for styling, avoiding compiled assets unless necessary.
- **Database**: Seeders (`TestBuildingsSeeder`, `TestMetersSeeder`, `TestInvoicesSeeder`, etc.) must stay deterministic; migrations keep foreign keys, indexes on `tenant_id`, `published_at`, `meter_id`, and use WAL with SQLite plus `spatie/laravel-backup` for persistence.

## Release Confidence

- Run `php artisan test:setup --fresh` to rebuild deterministic test data, then `php artisan test` (or targeted Pest suites) and property tests before merging.
- Validate Filament dashboards with `tests/Feature/Filament*` and `tests/Feature/FilamentPanelAccessibilityTest.php`.
- Ensure backup & observability checks (`spatie/laravel-backup` status, `php artisan pail` for logs when investigating) stay green; surface any `tenant_id` leaks via multi-tenancy tests before shipping.
