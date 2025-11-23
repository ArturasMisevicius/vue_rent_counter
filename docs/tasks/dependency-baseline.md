# Dependency Baseline - Pre-Upgrade

**Date:** November 24, 2025  
**Branch:** main  
**Git Tag:** pre-upgrade-baseline  
**Purpose:** Document all dependency versions before Laravel 12 + Filament 4 upgrade

## PHP Version

```bash
PHP 8.2+
```

## Core Framework Versions

| Package | Current Version | Target Version |
|---------|----------------|----------------|
| Laravel Framework | 11.46.1 | 12.x |
| Filament | 3.3.45 | 4.x |
| PHP | 8.2+ | 8.3+ |

## PHP Dependencies (Composer)

### Production Dependencies

| Package | Version |
|---------|---------|
| laravel/framework | 11.46.1 |
| filament/filament | 3.3.45 |
| spatie/laravel-backup | 9.3.6 |

### Development Dependencies

| Package | Version |
|---------|---------|
| barryvdh/laravel-debugbar | 3.16.1 |
| fakerphp/faker | 1.24.1 |
| laravel/pint | 1.25.1 |
| laravel/sail | 1.48.1 |
| laravel/tinker | 2.10.1 |
| mockery/mockery | 1.6.12 |
| nunomaduro/collision | 8.5.0 |
| pestphp/pest | 2.36.0 |
| pestphp/pest-plugin-laravel | 2.4.0 |
| phpunit/phpunit | 10.5.36 |
| spatie/laravel-ignition | 2.9.1 |

## Node Dependencies (NPM)

### Development Dependencies

| Package | Version |
|---------|---------|
| axios | ^1.6.4 |
| laravel-vite-plugin | ^1.0 |
| vite | ^5.0 |

**Note:** Node modules not currently installed (CDN-based frontend)

## Frontend Stack

- **Tailwind CSS:** CDN-delivered (version 3.x)
- **Alpine.js:** CDN-delivered
- **Build Tool:** Vite 5.x (configured but not actively used)

## Database

- **Development:** SQLite with WAL mode
- **Production:** MySQL/PostgreSQL support
- **Migrations:** All up to date

## Configuration Files

- `composer.json` - PHP dependencies
- `package.json` - Node dependencies
- `config/app.php` - Laravel configuration
- `config/auth.php` - Authentication configuration
- `config/database.php` - Database configuration
- `config/billing.php` - Custom billing configuration
- `config/gyvatukas.php` - Custom gyvatukas configuration
- `config/subscription.php` - Custom subscription configuration
- `config/backup.php` - Spatie backup configuration
- `vite.config.js` - Vite configuration
- `tailwind.config.js` - Tailwind configuration (if exists)

## Key Features to Preserve

- Multi-tenancy with `BelongsToTenant`, `TenantScope`, `TenantContext`
- Filament resources (Properties, Buildings, Meters, MeterReadings, Invoices, Tariffs, Providers, Users, Subscriptions)
- Billing system with `BillingService`, `TariffResolver`, `GyvatukasCalculator`
- Role-based access control (superadmin, admin, manager, tenant)
- Property-based testing suite
- SQLite WAL mode with Spatie backups

## Test Suite Status

Test results will be captured in `test-results-baseline.txt`

## Performance Metrics

Performance benchmarks will be captured in `performance-baseline.json`

## Upgrade Path

1. Laravel 11.46.1 → Laravel 12.x
2. Filament 3.3.45 → Filament 4.x
3. Tailwind CSS 3.x (CDN) → Tailwind CSS 4.x (CDN)
4. Pest 2.36.0 → Pest 3.x
5. PHPUnit 10.5.36 → PHPUnit 11.x
6. Spatie Laravel Backup 9.3.6 → 10.x

## Rollback Information

- **Git Tag:** pre-upgrade-baseline
- **Branch:** main
- **Commit:** Latest commit before upgrade branch creation
- **Database Backup:** Required before production deployment
