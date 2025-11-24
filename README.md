# Vilnius Utilities Billing System

A comprehensive web application for managing utility billing in the Vilnius (Lithuania) rental property market. The system automates calculation and tracking of utility payments for property management companies and their tenants.

## Overview

The Vilnius Utilities Billing System is a monolithic Laravel application that handles complex utility billing calculations specific to the Lithuanian market, including multi-tariff electricity plans, regulated water supply tariffs, and seasonal heating calculations with "gyvatukas" (hot water circulation fees).

## Key Features

- **Hierarchical User Management**: Three-tier user hierarchy with role-based access control
- **Subscription-Based Access**: Property owners subscribe to manage their portfolios
- **Multi-Tenancy**: Automatic data isolation between organizations
- **Complex Billing**: Time-of-use electricity rates, water tariffs, seasonal heating calculations
- **Snapshot Invoicing**: Historical pricing preservation for accurate billing
- **Audit Logging**: Complete tracking of account management actions
- **Modern Admin Panel**: Built with Filament 4 for intuitive resource management

## Technology Stack

### Backend
- **Framework**: Laravel 12.x
- **PHP**: 8.2+ (8.3+ recommended)
- **Admin Panel**: Filament 4.x
- **Database**: SQLite (development), MySQL/PostgreSQL (production)
- **Testing**: Pest 3.x, PHPUnit 11.x

### Frontend
- **Styling**: Tailwind CSS 4.x
- **JavaScript**: Alpine.js 3.x
- **Build Tool**: Vite 5.x
- **Charts**: Chart.js 4.x

### Key Packages
- **Backup**: Spatie Laravel Backup 9.3+
- **Code Quality**: Laravel Pint 1.25+
- **Development**: Laravel Sail 1.48+, Laravel Debugbar 3.16+

## Requirements

- **PHP**: 8.2 or higher (8.3+ recommended for optimal Laravel 12 performance)
- **Composer**: Latest version
- **SQLite**: 3.x (development) or MySQL 8.0+/PostgreSQL 13+ (production)
- **Node.js**: 18.x or higher
- **NPM**: 9.x or higher

## Quick Start

### 1. Clone and Install

```bash
# Clone repository
git clone <repository-url>
cd vilnius-billing

# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 2. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create SQLite database (development)
touch database/database.sqlite
```

### 3. Database Setup

```bash
# Run migrations
php artisan migrate

# Seed test data (optional)
php artisan db:seed
```

### 4. Start Development Server

```bash
# Start Laravel development server
php artisan serve

# In another terminal, start Vite (if using compiled assets)
npm run dev
```

Access the application at `http://localhost:8000`

## Default User Accounts

After seeding, you can log in with these accounts (password: `password`):

- **Superadmin**: `superadmin@example.com`
- **Admin**: `admin@test.com` or `admin1@example.com`
- **Manager**: `manager@test.com`
- **Tenant**: `tenant@test.com`

See [User Accounts Documentation](docs/overview/readme.md#seeded-user-accounts) for complete list.

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage

# Run property-based tests
php artisan test --filter Property
```

## Code Quality

```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Check code style without fixing
./vendor/bin/pint --test

# Run static analysis (if configured)
./vendor/bin/phpstan analyse
```

## Documentation

- **[Setup Guide](docs/guides/SETUP.md)** - Detailed installation and configuration
- **[User Guide](docs/guides/HIERARCHICAL_USER_GUIDE.md)** - Guide for each user role
- **[Testing Guide](docs/guides/TESTING_GUIDE.md)** - Testing approach and conventions
- **[Project Overview](docs/overview/readme.md)** - Complete feature documentation
- **[Upgrade Guide](docs/upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md)** - Framework upgrade notes

## Project Structure

```
app/
├── Console/          # Artisan commands
├── Filament/         # Filament resources, pages, widgets
├── Http/             # Controllers, middleware, requests
├── Models/           # Eloquent models
├── Policies/         # Authorization policies
├── Services/         # Business logic services
└── ValueObjects/     # Domain value objects

resources/
├── views/            # Blade templates
├── js/               # JavaScript assets
└── css/              # CSS assets

tests/
├── Feature/          # Feature tests
├── Unit/             # Unit tests
└── Performance/      # Performance benchmarks
```

## Configuration

Key environment variables:

```env
# Application
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
DB_FOREIGN_KEYS=true

# Subscription Limits
MAX_PROPERTIES_BASIC=10
MAX_PROPERTIES_PROFESSIONAL=50
MAX_PROPERTIES_ENTERPRISE=9999
MAX_TENANTS_BASIC=50
MAX_TENANTS_PROFESSIONAL=200
MAX_TENANTS_ENTERPRISE=9999
```

See [Setup Guide](docs/guides/SETUP.md) for complete configuration options.

## Deployment

### Production Checklist

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Configure production database (MySQL/PostgreSQL)
- [ ] Set up SSL certificate
- [ ] Configure email service
- [ ] Set up automated backups
- [ ] Configure web server (Nginx/Apache)
- [ ] Run `composer install --optimize-autoloader --no-dev`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`

See [Setup Guide](docs/guides/SETUP.md#production-deployment) for detailed deployment steps.

## Support

For technical support or questions:
- Review the [documentation](docs/)
- Check the [test suite](tests/) for examples
- Contact the system administrator

## License

This project is proprietary software.

## Version History

- **Current**: Laravel 12.x, Filament 4.x, Tailwind CSS 4.x, Pest 3.x
- **Previous**: Laravel 11.x, Filament 3.x, Tailwind CSS 3.x, Pest 2.x

See [Upgrade Guide](docs/upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md) for migration details.
