---
inclusion: always
---

# Technology Stack

## Core Framework

- **Laravel 12** - Modern PHP framework
- **PHP 8.4.7** - Latest PHP with strict types
- **Filament v4** - Admin panel framework with dual-panel architecture (admin + user)
- **Livewire v3** - Reactive components
- **Tailwind CSS v4** - CSS-first configuration

## Key Libraries

- **barryvdh/laravel-dompdf** - PDF generation for invoices
- **PHPUnit v11** - Testing framework
- **Laravel Pint** - Code formatting (PSR-12)
- **Vite** - Frontend build tool
- **Alpine.js** - Minimal JavaScript framework

## Database

- **SQLite** - Development
- **PostgreSQL** - Production
- **Eloquent ORM** - Database abstraction

## Common Commands

### Setup & Installation
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### Development
```bash
# Start all services (server, queue, logs, vite)
composer dev

# Or individually:
php artisan serve
php artisan queue:work
npm run dev
```

### Testing
```bash
php artisan test                    # Run all tests
php artisan test --coverage         # With coverage
php artisan test --parallel         # Faster execution
php artisan test --filter=test_name # Specific test
```

### Code Quality
```bash
vendor/bin/pint                     # Format code (PSR-12)
php artisan test                    # Run test suite
```

### Database
```bash
php artisan migrate                 # Run migrations
php artisan migrate:fresh --seed    # Fresh database with seed data
php artisan db:seed                 # Seed only
```

### Queue Management
```bash
php artisan queue:work              # Process jobs
php artisan queue:monitor           # Monitor queue stats
php artisan queue:optimize          # Optimize performance
```

### Filament
```bash
php artisan make:filament-resource ModelName  # Create resource
php artisan filament:upgrade                  # Upgrade Filament
```

## Build & Deploy

### Production Build
```bash
composer install --optimize-autoloader --no-dev
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Asset Compilation
```bash
npm run dev      # Development with hot reload
npm run build    # Production build
```

