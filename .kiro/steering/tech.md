# Technology Stack

## Framework & Language

- **Laravel 11** (PHP 8.2+)
- **Pest PHP** (v2.36+) for testing with property-based testing approach
- **Alpine.js** (loaded via CDN, no build step required)

## Database

- **SQLite** with Write-Ahead Logging (WAL mode) enabled
- Foreign key constraints enforced (`DB_FOREIGN_KEYS=true`)
- Configured via `DatabaseServiceProvider` for WAL mode initialization

## Key Dependencies

### Production
- `laravel/framework`: ^11.0
- `laravel/tinker`: ^2.9
- `spatie/laravel-backup`: ^9.3

### Development
- `pestphp/pest`: ^2.36
- `pestphp/pest-plugin-laravel`: ^2.4
- `laravel/pint`: ^1.13 (code style)
- `spatie/laravel-ignition`: ^2.4 (error pages)

### Frontend
- `vite`: ^5.0
- `laravel-vite-plugin`: ^1.0
- `axios`: ^1.6.4
- Alpine.js (CDN)

## Common Commands

### Development
```bash
# Start development server
php artisan serve

# Run tests
php artisan test

# Run specific test file
php artisan test tests/Unit/TariffResolverTest.php

# Run tests with coverage
php artisan test --coverage

# Code style fixing
./vendor/bin/pint
```

### Database
```bash
# Run migrations
php artisan migrate

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Check WAL mode
php artisan tinker --execute="echo DB::select('PRAGMA journal_mode;')[0]->journal_mode;"

# Check foreign keys
php artisan tinker --execute="echo DB::select('PRAGMA foreign_keys;')[0]->foreign_keys;"
```

### Artisan
```bash
# Create model with migration and factory
php artisan make:model ModelName -mf

# Create form request
php artisan make:request StoreModelRequest

# Create service class
php artisan make:class Services/ServiceName

# Create enum
php artisan make:enum EnumName

# Clear caches
php artisan optimize:clear
```

## Build System

No JavaScript build step required for development. Alpine.js is loaded via CDN. Vite is configured for production asset compilation if needed:

```bash
# Build assets for production
npm run build

# Watch assets during development (optional)
npm run dev
```

## Testing Philosophy

- **Property-based testing**: Each correctness property runs 100+ iterations with randomized inputs
- **Test tagging**: All property tests include comments referencing design document properties
- **Pest syntax**: Use `test()` and `expect()` over PHPUnit class-based tests
- **Factory usage**: Leverage factories for test data generation

## Code Style

- PSR-12 compliant (enforced by Laravel Pint)
- Type hints required for method parameters and return types
- DocBlocks for public methods explaining purpose and parameters
- Enum usage for fixed value sets (PropertyType, MeterType, InvoiceStatus, etc.)
