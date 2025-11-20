# Project Structure

## Root Directory

```
vilnius-billing/
├── app/                    # Application code
├── bootstrap/              # Framework bootstrap
├── config/                 # Configuration files
├── database/               # Migrations, factories, seeders
├── public/                 # Web server document root
├── resources/              # Views, CSS, JS
├── routes/                 # Route definitions
├── storage/                # Logs, cache, uploads
├── tests/                  # Test files
└── vendor/                 # Composer dependencies
```

## Application Layer (`app/`)

### Models (`app/Models/`)
Eloquent models representing domain entities. All tenant-scoped models apply `TenantScope` global scope in the `booted()` method.

**Core models:**
- `Building.php` - Multi-unit buildings with gyvatukas calculations
- `Property.php` - Individual apartments/houses
- `Tenant.php` - Renters of properties
- `Meter.php` - Utility meters (electricity, water, heating)
- `MeterReading.php` - Recorded meter values
- `MeterReadingAudit.php` - Audit trail for reading corrections
- `Provider.php` - Utility service providers
- `Tariff.php` - Pricing configurations (JSON stored)
- `Invoice.php` - Generated bills
- `InvoiceItem.php` - Line items with snapshotted prices
- `User.php` - System users (Admin, Manager, Tenant roles)

### Enums (`app/Enums/`)
Backed enums for type safety on fixed value sets:
- `PropertyType.php` - apartment, house
- `MeterType.php` - electricity, water_cold, water_hot, heating
- `ServiceType.php` - electricity, water, heating
- `InvoiceStatus.php` - draft, finalized, paid
- `UserRole.php` - admin, manager, tenant

### Services (`app/Services/`)
Business logic layer for complex operations:
- `TariffResolver.php` - Selects active tariffs, calculates time-of-use rates
- `BillingService.php` - Invoice generation with snapshotting (to be implemented)
- `GyvatukasCalculator.php` - Seasonal circulation fee logic (to be implemented)

### HTTP Layer (`app/Http/`)

**Controllers (`app/Http/Controllers/`):**
- Prefer single-action controllers for focused operations
- Resource controllers for standard CRUD
- Keep thin - delegate to services

**Requests (`app/Http/Requests/`):**
Form request classes with validation logic:
- `StoreMeterReadingRequest.php` - Validates monotonicity, temporal validity
- `UpdateMeterReadingRequest.php` - Validates reading corrections
- `StoreTariffRequest.php` - Validates tariff configuration JSON
- `FinalizeInvoiceRequest.php` - Validates invoice finalization

**Middleware (`app/Http/Middleware/`):**
- `EnsureTenantContext.php` - Validates tenant_id in session

### Scopes (`app/Scopes/`)
- `TenantScope.php` - Global scope for multi-tenancy data isolation

### Providers (`app/Providers/`)
- `AppServiceProvider.php` - Application service bindings
- `DatabaseServiceProvider.php` - Enables WAL mode and foreign keys

## Database Layer (`database/`)

### Migrations (`database/migrations/`)
Timestamped schema definitions with strict naming convention:
- `0001_01_01_000000_create_users_table.php`
- `0001_01_01_000003_create_buildings_table.php`
- `0001_01_01_000004_create_properties_table.php`
- etc.

**Migration conventions:**
- Foreign keys with explicit cascade rules
- Indexes on tenant_id for all tenant-scoped tables
- JSON columns for flexible configurations (tariffs)
- Decimal types for monetary values

### Factories (`database/factories/`)
Test data generators using Faker:
- `PropertyFactory.php`
- `MeterFactory.php`
- `TariffFactory.php`
- `ProviderFactory.php`
- `UserFactory.php`

### Seeders (`database/seeders/`)
- `DatabaseSeeder.php` - Development data seeding

## Testing Layer (`tests/`)

### Unit Tests (`tests/Unit/`)
Focused tests for individual components:
- `TariffResolverTest.php` - Tariff selection and cost calculation
- `FormRequestValidationTest.php` - Form request validation rules
- Property-based tests with 100+ iterations

### Feature Tests (`tests/Feature/`)
Integration tests for full request/response cycles:
- `MultiTenancyTest.php` - Data isolation verification
- `DatabaseConfigurationTest.php` - WAL mode and foreign key checks

**Test conventions:**
- Use Pest syntax: `test()` and `expect()`
- Tag property tests with comments: `// Feature: vilnius-utilities-billing, Property X`
- Leverage factories for test data
- Use `->repeat(100)` for property-based tests

## Resources Layer (`resources/`)

### Views (`resources/views/`)
Blade templates for server-side rendering:
- Component-based architecture (x-card, x-meter-form, etc.)
- Alpine.js for reactive UI without build step

### Assets (`resources/css/`, `resources/js/`)
- `app.css` - Tailwind or custom styles
- `app.js` - Minimal JS bootstrap
- `bootstrap.js` - Axios configuration

## Configuration (`config/`)

Key configuration files:
- `database.php` - SQLite with foreign key enforcement
- `backup.php` - Spatie backup configuration
- `app.php` - Application settings

## Naming Conventions

- **Models**: Singular, PascalCase (`Property`, `MeterReading`)
- **Controllers**: PascalCase with Controller suffix (`MeterReadingController`)
- **Services**: PascalCase with descriptive suffix (`TariffResolver`, `BillingService`)
- **Form Requests**: Action + Model + Request (`StoreMeterReadingRequest`)
- **Migrations**: Snake_case with descriptive action (`create_meter_readings_table`)
- **Tests**: Descriptive sentences in test() function
- **Routes**: Kebab-case (`/meter-readings`, `/invoice-items`)

## Multi-Tenancy Pattern

All tenant-scoped models follow this pattern:

```php
protected static function booted(): void
{
    static::addGlobalScope(new TenantScope);
}
```

The `tenant_id` column is automatically filtered on all queries. Models requiring tenant isolation: Property, Meter, MeterReading, Invoice, Tenant, User.

## Documentation Files

- `README.md` - Standard Laravel readme
- `SETUP.md` - Completed setup steps
- `FORM_REQUESTS_IMPLEMENTATION.md` - Form request validation details
- `TASK_5_SUMMARY.md` - Task completion summary
- `.kiro/specs/vilnius-utilities-billing/` - Requirements, design, tasks
