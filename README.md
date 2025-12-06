# Vilnius Utilities Billing System

A comprehensive web application for managing utility billing in the Vilnius (Lithuania) rental property market. The system automates calculation and tracking of utility payments for property management companies and their tenants.

## Overview

The Vilnius Utilities Billing System is a monolithic Laravel application that handles complex utility billing calculations specific to the Lithuanian market, including multi-tariff electricity plans, regulated water supply tariffs, and seasonal heating calculations with "gyvatukas" (hot water circulation fees).

## Key Features

- **Hierarchical User Management**: Three-tier user hierarchy (Superadmin → Admin → Tenant) with role-based access control
- **Subscription-Based Access**: Property owners subscribe to manage their portfolios with configurable limits
- **Multi-Tenancy**: Automatic data isolation between organizations using tenant_id scoping
- **Complex Billing**: Time-of-use electricity rates, water tariffs, seasonal heating calculations
- **Snapshot Invoicing**: Historical pricing preservation for accurate billing
- **Audit Logging**: Complete tracking of account management actions and tenant reassignments
- **Modern Admin Panel**: Built with Filament 4 for intuitive resource management

## User Roles and Hierarchy

The system implements a three-tier user hierarchy with distinct roles and permissions:

### Superadmin (System Owner)
- **Purpose**: Manages the entire system across all organizations
- **Permissions**:
  - Create and manage Admin accounts
  - Manage subscriptions for all organizations
  - View system-wide statistics and activity
  - Access all data across all tenants (bypasses tenant scope)
  - Configure system settings and limits
- **Access**: Full system access without restrictions
- **Default Account**: `superadmin@example.com` (password: `password`)

### Admin (Property Owner)
- **Purpose**: Manages their property portfolio and tenant accounts
- **Permissions**:
  - Create and manage buildings and properties within their portfolio
  - Create and manage tenant accounts for their properties
  - Assign and reassign tenants to properties
  - View and manage meters, readings, and invoices for their properties
  - Deactivate/reactivate tenant accounts
  - View their subscription status and usage limits
- **Access**: Limited to their own tenant_id scope (data isolation)
- **Subscription**: Requires active subscription with limits on properties and tenants
- **Example Accounts**: `admin@test.com`, `admin1@example.com`

### Tenant (Apartment Resident)
- **Purpose**: View billing information and submit meter readings for their apartment
- **Permissions**:
  - View their assigned property details
  - View meters and consumption history for their property
  - Submit meter readings (if enabled)
  - View and download invoices for their property
  - Update their profile information
- **Access**: Limited to their assigned property only (property_id scope)
- **Account Creation**: Created by Admin and linked to a specific property
- **Example Accounts**: `tenant@test.com`, `tenant1@example.com`

## Subscription Model

The system uses a subscription-based model for Admin accounts:

### Subscription Plans

| Plan | Max Properties | Max Tenants | Features |
|------|---------------|-------------|----------|
| **Basic** | 10 | 50 | Core billing features |
| **Professional** | 50 | 200 | Advanced reporting, bulk operations |
| **Enterprise** | Unlimited | Unlimited | Custom features, priority support |

### Subscription Features

- **Grace Period**: 7 days after expiry (configurable)
- **Expiry Warning**: 14 days before expiry (configurable)
- **Read-Only Mode**: Expired subscriptions allow viewing but not editing
- **Automatic Limits**: System enforces property and tenant limits based on plan
- **Renewal**: Admins can renew subscriptions through their profile

### Subscription Status

- **Active**: Full access to all features within plan limits
- **Expired**: Read-only access, cannot create new resources
- **Suspended**: Temporary suspension by Superadmin
- **Cancelled**: Subscription terminated, account deactivated

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

### Superadmin Account
- **Email**: `superadmin@example.com`
- **Role**: Superadmin
- **Access**: Full system access across all organizations

### Admin Accounts (Property Owners)
- **Tenant 1**: `admin@test.com` (Test Organization 1), `admin1@example.com` (Vilnius Properties Ltd)
- **Tenant 2**: `manager2@test.com` (Test Organization 2), `admin2@example.com` (Baltic Real Estate)
- **Tenant 3**: `admin3@example.com` (Old Town Management - expired subscription)
- **Role**: Admin
- **Access**: Limited to their tenant_id scope

### Manager Account (Legacy)
- **Email**: `manager@test.com`
- **Role**: Manager (legacy role, similar to Admin)
- **Access**: Limited to tenant_id 1

### Tenant Accounts (Apartment Residents)
- **Tenant 1**: `tenant@test.com`, `tenant1@example.com`, `tenant2@example.com`, etc.
- **Tenant 2**: `tenant4@example.com`, `tenant5@example.com`, etc.
- **Role**: Tenant
- **Access**: Limited to their assigned property only

See [User Accounts Documentation](docs/overview/readme.md#seeded-user-accounts) for complete list and [Hierarchical User Guide](docs/guides/HIERARCHICAL_USER_GUIDE.md) for role-specific instructions.

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

## Verification Scripts

Automated verification scripts validate system configuration and upgrade readiness:

```bash
# Verify Filament resources (Batch 3: User, Subscription, Organization, OrganizationActivityLog)
php verify-batch3-resources.php

# Verify Filament resources (Batch 4: Faq, Language, Translation)
php verify-batch4-resources.php

# Verify Eloquent models (11 core models: casts, relationships, Laravel 12 compatibility)
php verify-models.php

# Verify factories (SubscriptionFactory, UserFactory state methods)
php test_factories.php

# Run all verifications
php verify-batch3-resources.php && \
php verify-batch4-resources.php && \
php verify-models.php && \
php test_factories.php && \
echo "✓ All verifications passed"
```

See [Verification Documentation](docs/testing/README.md#verification-scripts) for detailed guides.

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

### Filament Resources

#### User Management
- **[UserResource API](docs/filament/USER_RESOURCE_API.md)** - Complete API reference
  - Form schema, validation rules, and table configuration
  - Role-based access control and tenant scoping
  - Authorization matrix and security considerations
  - Usage examples and testing strategies
- **[UserResource Usage Guide](docs/filament/USER_RESOURCE_USAGE_GUIDE.md)** - User-facing guide
  - Creating and managing users
  - Common workflows and best practices
  - Troubleshooting and FAQs
- **[UserResource Architecture](docs/filament/USER_RESOURCE_ARCHITECTURE.md)** - Technical architecture
  - Component relationships and data flow
  - Security architecture and performance optimization
  - Testing strategy and integration points

#### Building Management
- **[BuildingResource](docs/filament/BUILDING_RESOURCE.md)** - Complete user guide
- **[BuildingResource API](docs/filament/BUILDING_RESOURCE_API.md)** - API reference

#### Content Management
- **[FaqResource API](docs/filament/FAQ_RESOURCE_API.md)** - FAQ management API reference

### General Documentation

### Guides
- **[Setup Guide](docs/guides/SETUP.md)** - Detailed installation and configuration
- **[User Guide](docs/guides/HIERARCHICAL_USER_GUIDE.md)** - Guide for each user role
- **[Testing Guide](docs/guides/TESTING_GUIDE.md)** - Testing approach and conventions
- **[Upgrade Guide](docs/upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md)** - Framework upgrade notes

### Architecture
- **[Invoice Finalization Flow](docs/architecture/INVOICE_FINALIZATION_FLOW.md)** - Complete finalization architecture
- **[Multi-Tenancy Architecture](docs/architecture/MULTI_TENANCY_ARCHITECTURE.md)** - Tenant isolation patterns

### API Reference
- **[FinalizeInvoiceController API](docs/api/FINALIZE_INVOICE_CONTROLLER_API.md)** - Invoice finalization endpoint
- **[BillingService API](docs/api/BILLING_SERVICE_API.md)** - Billing service methods

### Quick Reference
- **[Invoice Finalization](docs/reference/INVOICE_FINALIZATION_QUICK_REFERENCE.md)** - Quick reference guide

### Implementation Details
- **[Project Overview](docs/overview/readme.md)** - Complete feature documentation
- **[Invoice Controller Implementation](docs/controllers/INVOICE_CONTROLLER_IMPLEMENTATION_COMPLETE.md)** - Invoice management
- **[FinalizeInvoiceController Usage](docs/controllers/FINALIZE_INVOICE_CONTROLLER_USAGE.md)** - Usage examples

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

# Subscription Configuration
SUBSCRIPTION_GRACE_PERIOD_DAYS=7
SUBSCRIPTION_EXPIRY_WARNING_DAYS=14

# Subscription Limits by Plan
MAX_PROPERTIES_BASIC=10
MAX_PROPERTIES_PROFESSIONAL=50
MAX_PROPERTIES_ENTERPRISE=9999
MAX_TENANTS_BASIC=50
MAX_TENANTS_PROFESSIONAL=200
MAX_TENANTS_ENTERPRISE=9999
```

### Subscription Limit Configuration

The subscription limits control how many properties and tenants each Admin can manage based on their subscription plan:

- **Basic Plan**: 10 properties, 50 tenants - suitable for small property portfolios
- **Professional Plan**: 50 properties, 200 tenants - suitable for medium-sized property management
- **Enterprise Plan**: Unlimited (9999) - suitable for large property management companies

These limits are enforced when Admins attempt to create new properties or tenant accounts. The system will prevent creation if the limit is reached and display an appropriate error message.

See [Setup Guide](docs/guides/SETUP.md) for complete configuration options and [Hierarchical User Guide](docs/guides/HIERARCHICAL_USER_GUIDE.md) for subscription management instructions.

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

## Security

### Recent Security Updates

#### 🔴 Critical Security Fix (2024-12-05)
Fixed path traversal vulnerability in `InputSanitizer` service. All users should update immediately.

**Details**: [Security Patch 2024-12-05](docs/security/SECURITY_PATCH_2024-12-05.md)

### Security Best Practices

- Always use `InputSanitizer` for external system IDs and user input
- Review [Input Sanitizer Quick Reference](docs/security/INPUT_SANITIZER_QUICK_REFERENCE.md)
- Monitor logs for security events: `grep "Path traversal attempt" storage/logs/laravel.log`
- Report security issues to: security@example.com

### Security Documentation

- [Input Sanitizer Service](docs/services/INPUT_SANITIZER_SERVICE.md) - Complete API reference
- [Security Fix Details](docs/security/input-sanitizer-security-fix.md) - Vulnerability analysis
- [Quick Reference](docs/security/INPUT_SANITIZER_QUICK_REFERENCE.md) - Developer guide

## Support

For technical support or questions:
- Review the [documentation](docs/)
- Check the [test suite](tests/) for examples
- Contact the system administrator

For security issues:
- **Email**: security@example.com
- **On-Call**: +1-XXX-XXX-XXXX
- **Incident Response**: incidents@example.com

## License

This project is proprietary software.

## Version History

- **Current**: Laravel 12.x, Filament 4.x, Tailwind CSS 4.x, Pest 3.x
- **Previous**: Laravel 11.x, Filament 3.x, Tailwind CSS 3.x, Pest 2.x

See [Upgrade Guide](docs/upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md) for migration details.
