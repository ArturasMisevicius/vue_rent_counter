# Vilnius Utilities Billing System

A comprehensive web application for managing utility billing in the Vilnius (Lithuania) rental property market. The system automates calculation and tracking of utility payments for property management companies and their tenants.

## Overview

The Vilnius Utilities Billing System is a monolithic Laravel 11 application that handles complex utility billing calculations specific to the Lithuanian market, including multi-tariff electricity plans, regulated water supply tariffs, and seasonal heating calculations with "gyvatukas" (hot water circulation fees).

## Key Features

- **Hierarchical User Management**: Three-tier user hierarchy with role-based access control
- **Subscription-Based Access**: Property owners subscribe to manage their portfolios
- **Multi-Tenancy**: Automatic data isolation between organizations
- **Complex Billing**: Time-of-use electricity rates, water tariffs, seasonal heating calculations
- **Snapshot Invoicing**: Historical pricing preservation for accurate billing
- **Audit Logging**: Complete tracking of account management actions

## User Hierarchy 

The system implements a three-tier hierarchical user structure:

```
Superadmin (System Owner)
    │
    ├─→ Admin 1 (Property Owner / Organization)
    │       ├─→ Tenant 1.1 (Apartment Resident)
    │       ├─→ Tenant 1.2 (Apartment Resident)
    │       └─→ Tenant 1.3 (Apartment Resident)
    │
    └─→ Admin 2 (Property Owner / Organization)
            ├─→ Tenant 2.1 (Apartment Resident)
            └─→ Tenant 2.2 (Apartment Resident)
```

### User Roles

#### Superadmin
- **Purpose**: System owner who manages the entire platform
- **Access**: Unrestricted access to all organizations and data
- **Capabilities**:
  - Create and manage Admin accounts
  - Manage subscriptions for all organizations
  - View system-wide statistics and metrics
  - Access all resources across all tenants
  - Full audit trail visibility

#### Admin (Property Owner)
- **Purpose**: Property owner who manages their rental portfolio
- **Access**: Limited to their own organization's data (tenant_id scope)
- **Capabilities**:
  - Create and manage buildings and properties
  - Create tenant accounts for residents
  - Assign tenants to properties
  - Enter meter readings and generate invoices
  - View portfolio statistics and reports
  - Manage organization profile
- **Subscription Required**: Yes - access controlled by subscription status

#### Tenant (Apartment Resident)
- **Purpose**: Resident who views their utility information
- **Access**: Limited to their assigned property only
- **Capabilities**:
  - View assigned property details
  - Submit meter readings
  - View consumption history and trends
  - View and download invoices
  - Update profile information
- **Subscription Required**: No - created by Admin

## Subscription Model

Admins operate under a subscription-based model with the following characteristics:

### Subscription Plans

- **Basic**: Up to 10 properties, 50 tenants
- **Professional**: Up to 50 properties, 200 tenants
- **Enterprise**: Unlimited properties and tenants

### Subscription Status

- **Active**: Full access to all features
- **Expired**: Read-only access, cannot create or modify data
- **Suspended**: Access restricted by Superadmin
- **Cancelled**: Account deactivated

### Subscription Limits

The system enforces limits based on the subscription plan:
- Maximum number of properties
- Maximum number of tenant accounts
- Attempts to exceed limits result in validation errors

### Subscription Lifecycle

1. Superadmin creates Admin account with initial subscription
2. Admin manages properties and tenants within subscription limits
3. System displays warnings when subscription nears expiry
4. Expired subscriptions restrict to read-only mode
5. Renewal restores full access

## Data Isolation

The system implements strict data isolation through multi-tenancy:

### Tenant ID Scoping

- Each Admin receives a unique `tenant_id` upon account creation
- All resources (buildings, properties, meters, invoices) inherit the Admin's `tenant_id`
- Tenant accounts inherit their Admin's `tenant_id`
- Database queries automatically filter by `tenant_id` based on user role

### Property ID Scoping

- Tenant users are assigned to a specific property via `property_id`
- Tenants can only access data for their assigned property
- Property reassignment preserves historical data

### Access Control

- **Superadmin**: Bypasses all scoping, sees all data
- **Admin**: Sees only data with matching `tenant_id`
- **Tenant**: Sees only data with matching `tenant_id` AND `property_id`

## Permissions

### Superadmin Permissions

- Create/update/delete Admin accounts
- Manage all subscriptions
- View all organizations and their data
- Access system-wide reports and metrics
- Perform any action without restriction

### Admin Permissions

- Create/update/delete buildings and properties (within their tenant_id)
- Create/update/deactivate tenant accounts
- Assign/reassign tenants to properties
- Enter meter readings
- Generate and finalize invoices
- View portfolio reports
- Update organization profile
- Renew subscription

### Tenant Permissions

- View assigned property details
- Submit meter readings for assigned property
- View consumption history
- View and download invoices
- Update own profile (email, password)

## Technology Stack

- **Framework**: Laravel 11 (PHP 8.2+)
- **Database**: SQLite with WAL mode
- **Testing**: Pest PHP with property-based testing
- **Frontend**: Blade templates with Alpine.js
- **Authentication**: Laravel's built-in authentication

## Requirements

- PHP 8.2 or higher
- Composer
- SQLite 3
- Node.js and NPM (for asset compilation)

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd vilnius-billing
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Set up database:
```bash
touch database/database.sqlite
php artisan migrate
```

5. Seed initial data (optional):
```bash
php artisan db:seed --class=HierarchicalUsersSeeder
```

6. Start development server:
```bash
php artisan serve
```

## Configuration

### Environment Variables

Key configuration options in `.env`:

```env
# Database
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
DB_FOREIGN_KEYS=true

# Subscription Configuration
SUBSCRIPTION_GRACE_PERIOD_DAYS=7
SUBSCRIPTION_EXPIRY_WARNING_DAYS=14

# Subscription Limits
MAX_PROPERTIES_BASIC=10
MAX_PROPERTIES_PROFESSIONAL=50
MAX_PROPERTIES_ENTERPRISE=9999
MAX_TENANTS_BASIC=50
MAX_TENANTS_PROFESSIONAL=200
MAX_TENANTS_ENTERPRISE=9999
```

## Testing

Run the test suite:
```bash
php artisan test
```

Run specific test file:
```bash
php artisan test tests/Feature/MultiTenancyTest.php
```

Run with coverage:
```bash
php artisan test --coverage
```

## Default Accounts

After seeding with `HierarchicalUsersSeeder`:

**Superadmin:**
- Email: superadmin@example.com
- Password: password

**Admin (Sample Organization):**
- Email: admin@example.com
- Password: password

**Tenant (Sample):**
- Email: tenant@example.com
- Password: password

## Documentation

- [Hierarchical User Guide](HIERARCHICAL_USER_GUIDE.md) - Detailed guide for each user role
- [Setup Instructions](SETUP.md) - Installation and migration instructions
- [Testing Guide](TESTING_GUIDE.md) - Testing approach and conventions

## License

This project is proprietary software.

## Support

For technical support or questions, contact the system administrator.
