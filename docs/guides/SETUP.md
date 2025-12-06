# Vilnius Utilities Billing System - Setup Guide

## System Requirements

### Required Software

- **PHP**: 8.2 or higher (8.3+ recommended for optimal Laravel 12 performance)
- **Composer**: Latest version (2.x)
- **Database**: 
  - SQLite 3.x (development)
  - MySQL 8.0+ or PostgreSQL 13+ (production)
- **Node.js**: 18.x or higher
- **NPM**: 9.x or higher

### Framework Versions

This application uses the following major framework versions:

- **Laravel**: 12.x
- **Filament**: 4.x (admin panel framework)
- **Tailwind CSS**: 4.x (utility-first CSS)
- **Pest**: 3.x (testing framework)
- **PHPUnit**: 11.x (test runner)
- **Vite**: 5.x (build tool)

### PHP Extensions

Ensure the following PHP extensions are installed:
- PDO (with SQLite, MySQL, or PostgreSQL driver)
- OpenSSL
- Mbstring
- Tokenizer
- XML
- Ctype
- JSON
- BCMath
- Fileinfo

## Initial Setup

### 1. Prerequisites Check

Verify your system meets the requirements:

```bash
# Check PHP version (should be 8.2+, 8.3+ recommended)
php -v

# Check Composer version
composer --version

# Check Node.js version (should be 18+)
node -v

# Check NPM version
npm -v

# Check SQLite version
sqlite3 --version
```

### 2. Installation

Clone the repository and install dependencies:

```bash
# Clone repository
git clone <repository-url>
cd vilnius-billing

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Verify installations
composer show laravel/framework  # Should show ^12.0
composer show filament/filament   # Should show ^4.0
npm list tailwindcss              # Should show ^4.0.0
```

### 3. Environment Configuration

Create and configure your environment file:

```bash
# Copy example environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 4. Database Setup

Configure SQLite database:

```bash
# Create database file
touch database/database.sqlite

# Run migrations
php artisan migrate
```

### 5. Environment Variables

Update your `.env` file with the following configuration:

#### Database Configuration
```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
DB_FOREIGN_KEYS=true
```

#### Subscription Configuration
```env
# Grace period after subscription expires (days)
# During this period, admins have read-only access
SUBSCRIPTION_GRACE_PERIOD_DAYS=7

# Warning period before subscription expires (days)
# Admins will see renewal reminders during this period
SUBSCRIPTION_EXPIRY_WARNING_DAYS=14
```

**Configuration Details**:
- **SUBSCRIPTION_GRACE_PERIOD_DAYS**: Number of days after subscription expiry during which the Admin has read-only access. Default: 7 days.
- **SUBSCRIPTION_EXPIRY_WARNING_DAYS**: Number of days before expiry when the Admin starts seeing renewal reminders. Default: 14 days.

#### Subscription Limits by Plan
```env
# Basic Plan Limits
MAX_PROPERTIES_BASIC=10
MAX_TENANTS_BASIC=50

# Professional Plan Limits
MAX_PROPERTIES_PROFESSIONAL=50
MAX_TENANTS_PROFESSIONAL=200

# Enterprise Plan Limits (use high number for "unlimited")
MAX_PROPERTIES_ENTERPRISE=9999
MAX_TENANTS_ENTERPRISE=9999
```

**Subscription Plan Configuration**:

These environment variables control the limits for each subscription plan. The system enforces these limits when Admins attempt to create new properties or tenant accounts.

**Basic Plan** (Suitable for small property portfolios):
- `MAX_PROPERTIES_BASIC=10`: Maximum 10 properties
- `MAX_TENANTS_BASIC=50`: Maximum 50 tenant accounts
- Recommended for: Individual landlords, small property managers

**Professional Plan** (Suitable for medium-sized property management):
- `MAX_PROPERTIES_PROFESSIONAL=50`: Maximum 50 properties
- `MAX_TENANTS_PROFESSIONAL=200`: Maximum 200 tenant accounts
- Recommended for: Property management companies, medium portfolios

**Enterprise Plan** (Suitable for large property management companies):
- `MAX_PROPERTIES_ENTERPRISE=9999`: Effectively unlimited properties
- `MAX_TENANTS_ENTERPRISE=9999`: Effectively unlimited tenant accounts
- Recommended for: Large property management companies, enterprise clients

**Customizing Limits**:
You can adjust these values based on your business needs. For example:
```env
# Custom limits for a specific deployment
MAX_PROPERTIES_BASIC=5
MAX_TENANTS_BASIC=25
MAX_PROPERTIES_PROFESSIONAL=100
MAX_TENANTS_PROFESSIONAL=500
```

**Important Notes**:
- Changes to these values require restarting the application
- Existing subscriptions are not automatically updated when limits change
- Use `php artisan config:cache` after changing these values in production
- The system checks these limits before allowing resource creation

#### Email Configuration (Optional)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@vilnius-billing.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 6. Verification

Verify your configuration:

```bash
# Run all tests
php artisan test

# Check Laravel version
php artisan --version  # Should show Laravel Framework 12.x

# Check Filament installation
php artisan about | grep Filament  # Should show Filament 4.x

# Check WAL mode is enabled (SQLite only)
php artisan tinker --execute="echo DB::select('PRAGMA journal_mode;')[0]->journal_mode;"

# Check foreign keys are enabled (SQLite only)
php artisan tinker --execute="echo DB::select('PRAGMA foreign_keys;')[0]->foreign_keys;"
```

### 7. Build Frontend Assets (REQUIRED)

The application requires compiled assets for Alpine.js functionality:

```bash
# Development build with hot reload (recommended for development)
npm run dev

# Production build (required for production)
npm run build
```

**Important Changes**:
- **Alpine.js** (v3.14.0) is now bundled via Vite (no longer CDN)
- **Tailwind CSS** (v4.x) still uses CDN for rapid prototyping
- **Chart.js** is bundled for dashboard visualizations
- Running `npm run dev` or `npm run build` is **required** for the application to function

**Development Workflow**:
1. Run `npm run dev` in a separate terminal for hot module replacement
2. Changes to JavaScript/Alpine components will auto-reload
3. Vite dev server runs on `http://localhost:5173` by default

## Hierarchical User Management Setup

### Understanding the Migration

The hierarchical user management system requires migrating existing users to the new structure. This involves:

1. Adding new columns to the `users` table
2. Creating the `subscriptions` table
3. Creating the `user_assignments_audit` table
4. Updating existing user data to fit the new hierarchy

### Migration Command

The system includes a migration command to update existing data to the hierarchical user structure. This command is essential when upgrading from a non-hierarchical system or when setting up the system for the first time with existing user data.

```bash
php artisan migrate:hierarchical-users
```

#### What the Migration Does

The migration command performs the following operations:

1. **Updates User Roles**:
   - Converts existing 'manager' role to 'admin' role
   - Preserves 'tenant' role users
   - Identifies or creates a superadmin account
   - Ensures all users have a valid role in the new hierarchy

2. **Assigns Tenant IDs**:
   - Assigns unique `tenant_id` to each admin/manager user
   - Ensures tenant users inherit their admin's `tenant_id`
   - Maintains data isolation between different admins
   - Generates sequential tenant IDs starting from 1

3. **Creates Subscriptions**:
   - Creates active subscriptions for all admin users
   - Sets default expiry date (1 year from migration)
   - Assigns default plan (Professional with 50 properties, 200 tenants)
   - Ensures all admins have immediate access to the system

4. **Activates Accounts**:
   - Sets `is_active = true` for all existing users
   - Ensures no disruption to current users
   - Allows all users to log in immediately after migration

5. **Preserves Data Integrity**:
   - Maintains all existing relationships (properties, meters, readings, invoices)
   - Does not delete or modify existing data
   - Creates audit log entries for all changes

#### Migration Options

**Preview Changes (Dry Run)**:
```bash
php artisan migrate:hierarchical-users --dry-run
```

This option shows what changes would be made without actually modifying the database. Use this to:
- Verify the migration will work correctly
- See which users will be affected
- Check tenant ID assignments
- Review subscription creation

**Run with Rollback Capability**:
```bash
php artisan migrate:hierarchical-users --rollback
```

This option allows you to undo the migration if needed. The rollback will:
- Remove assigned tenant IDs
- Delete created subscriptions
- Restore original user roles
- Revert is_active flags

**Force Migration (Skip Confirmations)**:
```bash
php artisan migrate:hierarchical-users --force
```

This option skips all confirmation prompts. Use with caution in production.

#### Migration Process

The migration follows this process:

1. **Pre-Migration Checks**:
   - Verifies database connection
   - Checks for required tables (users, subscriptions)
   - Validates existing user data
   - Displays summary of changes to be made

2. **User Role Migration**:
   - Identifies all users with 'manager' role
   - Converts 'manager' to 'admin'
   - Ensures superadmin account exists
   - Logs all role changes

3. **Tenant ID Assignment**:
   - Assigns unique tenant_id to each admin
   - Updates related tenant users with matching tenant_id
   - Maintains parent-child relationships
   - Logs all tenant ID assignments

4. **Subscription Creation**:
   - Creates subscription for each admin
   - Sets plan type based on configuration
   - Sets expiry date (default: 1 year)
   - Sets limits based on plan type
   - Logs all subscription creations

5. **Account Activation**:
   - Sets is_active = true for all users
   - Ensures immediate access
   - Logs activation status

6. **Post-Migration Verification**:
   - Verifies all users have tenant_id (except superadmin)
   - Verifies all admins have subscriptions
   - Verifies all relationships are intact
   - Displays migration summary

#### Migration Output

The migration command provides detailed output:

```
Hierarchical Users Migration
============================

Pre-Migration Summary:
- Total users: 15
- Manager users: 3
- Tenant users: 11
- Superadmin users: 1

Migration Steps:
✓ Converting manager roles to admin
✓ Assigning tenant IDs
✓ Creating subscriptions
✓ Activating accounts

Post-Migration Summary:
- Admin users: 3 (tenant IDs: 1, 2, 3)
- Tenant users: 11 (assigned to admins)
- Subscriptions created: 3
- All accounts activated: 15

Migration completed successfully!
```

#### Environment Variables Used

The migration command uses these environment variables:

```env
# Default subscription plan for migrated admins
DEFAULT_SUBSCRIPTION_PLAN=professional

# Default subscription duration (days)
DEFAULT_SUBSCRIPTION_DURATION=365

# Subscription limits (from plan configuration)
MAX_PROPERTIES_PROFESSIONAL=50
MAX_TENANTS_PROFESSIONAL=200
```

You can customize these values before running the migration to set different defaults.

### Seeding Hierarchical Users

For development or testing, seed user data with the hierarchical structure:

```bash
# Seed all data (users, properties, meters, etc.)
php artisan db:seed

# Or seed only users (requires properties to exist first)
php artisan db:seed --class=UsersSeeder
```

#### What the UsersSeeder Creates

The `UsersSeeder` creates a comprehensive set of test users for development and testing with the complete hierarchical structure:

1. **Superadmin Account** (1 user):
   - **Email**: `superadmin@example.com`
   - **Password**: `password`
   - **Role**: superadmin
   - **Tenant ID**: null (bypasses tenant scope)
   - **Access**: Full system access across all tenants
   - **Purpose**: System administration, managing all organizations

2. **Admin Accounts** (5 users across 3 tenant IDs):
   
   **Tenant ID 1** (2 admins):
   - `admin@test.com` - Test Organization 1
     - Subscription: Professional (active)
     - Organization: Test Organization 1
   - `admin1@example.com` - Vilnius Properties Ltd
     - Subscription: Professional (active)
     - Organization: Vilnius Properties Ltd
   
   **Tenant ID 2** (2 admins):
   - `manager2@test.com` - Test Organization 2
     - Subscription: Basic (active)
     - Organization: Test Organization 2
   - `admin2@example.com` - Baltic Real Estate
     - Subscription: Professional (active)
     - Organization: Baltic Real Estate
   
   **Tenant ID 3** (1 admin):
   - `admin3@example.com` - Old Town Management
     - Subscription: Professional (**expired** - for testing expired subscriptions)
     - Organization: Old Town Management
   
   **All Admin Accounts**:
   - Password: `password`
   - Role: admin
   - Each with unique tenant_id for data isolation
   - Each with subscription (active or expired)
   - Can create and manage properties and tenants within their scope

3. **Manager Account** (1 user, legacy role):
   - **Email**: `manager@test.com`
   - **Password**: `password`
   - **Role**: manager (legacy role, similar to admin)
   - **Tenant ID**: 1
   - **Purpose**: Used for meter reading entry and property management
   - **Note**: This role is maintained for backward compatibility

4. **Tenant Accounts** (9 users):
   
   **Tenant ID 1** (6 tenants):
   - `tenant@test.com` - Assigned to property in Building 1
   - `tenant1@example.com` - Assigned to property in Building 1
   - `tenant2@example.com` - Assigned to property in Building 1
   - `tenant3@example.com` - Assigned to property in Building 2
   - `tenant6@example.com` - Assigned to property in Building 2
   - `deactivated@example.com` - Inactive tenant (for testing deactivation)
   
   **Tenant ID 2** (3 tenants):
   - `tenant4@example.com` - Assigned to property in Building 3
   - `tenant5@example.com` - Assigned to property in Building 3
   - `tenant7@example.com` - Assigned to property in Building 4
   
   **All Tenant Accounts**:
   - Password: `password`
   - Role: tenant
   - Each assigned to a specific property
   - Each inherits their admin's tenant_id
   - Can only view and manage their assigned property

#### Seeding Order and Dependencies

The seeding process follows this order to maintain referential integrity:

1. **Providers and Tariffs**: Base data for billing
2. **Buildings**: Physical structures
3. **Properties**: Individual units within buildings
4. **Users**: Hierarchical user accounts
   - Superadmin (no dependencies)
   - Admins with subscriptions (no dependencies)
   - Tenants (requires properties to exist)
5. **Meters**: Utility meters for properties
6. **Meter Readings**: Historical consumption data
7. **Invoices**: Billing records

**Important**: The `UsersSeeder` is called automatically by `TestDatabaseSeeder` after properties are created, ensuring all dependencies are satisfied.

#### Subscription Configuration in Seeding

The seeder creates subscriptions with these configurations:

**Professional Plan Subscriptions**:
```php
'plan_type' => 'professional',
'status' => 'active',
'starts_at' => now(),
'expires_at' => now()->addYear(),
'max_properties' => 50,
'max_tenants' => 200,
```

**Basic Plan Subscriptions**:
```php
'plan_type' => 'basic',
'status' => 'active',
'starts_at' => now(),
'expires_at' => now()->addYear(),
'max_properties' => 10,
'max_tenants' => 50,
```

**Expired Subscription** (for testing):
```php
'plan_type' => 'professional',
'status' => 'expired',
'starts_at' => now()->subYear(),
'expires_at' => now()->subMonth(),
'max_properties' => 50,
'max_tenants' => 200,
```

#### Testing Different Scenarios

The seeded data allows testing various scenarios:

1. **Active Subscriptions**: Test normal operations with `admin@test.com` or `admin1@example.com`
2. **Expired Subscriptions**: Test read-only mode with `admin3@example.com`
3. **Multiple Tenants**: Test tenant isolation with different tenant IDs
4. **Tenant Assignment**: Test property assignment with various tenant accounts
5. **Inactive Accounts**: Test deactivation with `deactivated@example.com`
6. **Subscription Limits**: Test limit enforcement by creating properties/tenants

#### Customizing Seeded Data

You can customize the seeded data by modifying the seeder files:

**Location**: `database/seeders/UsersSeeder.php`

**Example Customization**:
```php
// Change subscription plan
'plan_type' => 'enterprise',

// Change expiry date
'expires_at' => now()->addMonths(6),

// Change limits
'max_properties' => 100,
'max_tenants' => 500,
```

After modifying, re-run the seeder:
```bash
php artisan db:seed --class=UsersSeeder
```

See the [README.md](../../README.md#default-user-accounts) and [HIERARCHICAL_USER_GUIDE.md](HIERARCHICAL_USER_GUIDE.md) for complete user account details and usage instructions.

### Fresh Installation with Hierarchical Users

For a completely fresh installation:

```bash
# Drop all tables and re-run migrations
php artisan migrate:fresh

# Seed all data (users, properties, meters, readings, invoices, etc.)
php artisan db:seed

# Or seed in steps:
# 1. Seed base data (properties, buildings, etc.)
php artisan db:seed --class=TestDatabaseSeeder

# 2. Users are automatically seeded by TestDatabaseSeeder
# (UsersSeeder is called after properties are created)
```

### Updating Existing Installation

If you have an existing installation with data:

1. **Backup your database**:
```bash
cp database/database.sqlite database/database.sqlite.backup
```

2. **Run new migrations**:
```bash
php artisan migrate
```

3. **Run the hierarchical users migration**:
```bash
php artisan migrate:hierarchical-users
```

4. **Verify the migration**:
```bash
# Check user roles and tenant_ids
php artisan tinker --execute="User::select('id', 'email', 'role', 'tenant_id')->get();"

# Check subscriptions were created
php artisan tinker --execute="Subscription::with('user')->get();"
```

5. **Test the system**:
```bash
php artisan test
```

### Post-Migration Steps

After migrating to hierarchical users:

1. **Update Admin Accounts**:
   - Login as each admin
   - Update organization name
   - Verify subscription details
   - Update contact information

2. **Assign Tenants to Properties**:
   - Review tenant accounts
   - Ensure each tenant has a `property_id` assigned
   - Use the reassignment feature if needed

3. **Configure Subscriptions**:
   - Login as superadmin
   - Review all subscriptions
   - Adjust expiry dates as needed
   - Set appropriate plan limits

4. **Test Access Control**:
   - Login as different user roles
   - Verify data isolation works correctly
   - Test that admins only see their data
   - Test that tenants only see their property

### Troubleshooting Migration Issues

#### Issue: Migration fails with foreign key constraint error

**Solution**:
```bash
# Check foreign keys are enabled
php artisan tinker --execute="echo DB::select('PRAGMA foreign_keys;')[0]->foreign_keys;"

# If not enabled, add to .env:
DB_FOREIGN_KEYS=true

# Re-run migration
php artisan migrate:hierarchical-users
```

#### Issue: Existing users have no tenant_id after migration

**Solution**:
```bash
# Run the migration command again
php artisan migrate:hierarchical-users

# Or manually assign tenant_ids in tinker:
php artisan tinker
>>> $admin = User::where('role', 'admin')->first();
>>> $admin->tenant_id = 1;
>>> $admin->save();
```

#### Issue: Subscriptions not created

**Solution**:
```bash
# Manually create subscriptions for admins
php artisan tinker
>>> $admin = User::where('role', 'admin')->first();
>>> Subscription::create([
...   'user_id' => $admin->id,
...   'plan_type' => 'professional',
...   'status' => 'active',
...   'starts_at' => now(),
...   'expires_at' => now()->addYear(),
...   'max_properties' => 50,
...   'max_tenants' => 200,
... ]);
```

## Development Workflow

### Starting Development Server

```bash
# Start Laravel development server
php artisan serve
```

Access the application at `http://localhost:8000`

### Using Laravel Sail (Docker)

Alternatively, use Laravel Sail for a containerized development environment:

```bash
# Start Sail containers
./vendor/bin/sail up

# Run artisan commands through Sail
./vendor/bin/sail artisan migrate

# Run tests through Sail
./vendor/bin/sail test
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run specific test file
php artisan test tests/Feature/MultiTenancyTest.php

# Run with coverage
php artisan test --coverage

# Run property-based tests only
php artisan test --filter Property

# Run tests in parallel (faster)
php artisan test --parallel

# Run with detailed output
php artisan test --verbose
```

### Test Setup Command

For reproducible test data:

```bash
# Fresh database with test data
php artisan test:setup --fresh

# This command:
# 1. Drops all tables
# 2. Runs migrations
# 3. Seeds test data (users, properties, meters, readings, invoices)
```

### Code Style

Format code using Laravel Pint:

```bash
# Fix code style issues
./vendor/bin/pint

# Check code style without fixing
./vendor/bin/pint --test

# Fix specific files or directories
./vendor/bin/pint app/Services
./vendor/bin/pint app/Models/User.php
```

### Clearing Caches

```bash
# Clear all caches
php artisan optimize:clear

# Clear specific caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Production Deployment

### Pre-Deployment Checklist

- [ ] Update `.env` with production values
- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Verify PHP 8.3+ is installed on production server
- [ ] Configure production database (MySQL 8.0+ or PostgreSQL 13+)
- [ ] Set up email service (SMTP/SES/Mailgun)
- [ ] Configure backup schedule (Spatie Backup)
- [ ] Set up SSL certificate (Let's Encrypt/Cloudflare)
- [ ] Configure web server (Nginx recommended, Apache supported)
- [ ] Set up queue worker (for background jobs)
- [ ] Configure cron jobs for scheduled tasks

### Deployment Steps

1. **Pull latest code**:
```bash
git pull origin main
```

2. **Install dependencies**:
```bash
# Install PHP dependencies (production mode)
composer install --optimize-autoloader --no-dev

# Install Node dependencies (REQUIRED for Alpine.js)
npm ci --production

# Build frontend assets (REQUIRED - Alpine.js is bundled)
npm run build
```

**Important**: As of the latest version, Alpine.js is bundled via Vite and no longer loaded from CDN. Running `npm run build` is **mandatory** for production deployments. The application will not function correctly without compiled assets.

3. **Run migrations**:
```bash
php artisan migrate --force
```

4. **Clear and optimize caches**:
```bash
# Clear all caches
php artisan optimize:clear

# Rebuild optimized caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Optimize autoloader
composer dump-autoload --optimize
```

5. **Set permissions**:
```bash
# Set correct ownership (adjust user/group as needed)
chown -R www-data:www-data storage bootstrap/cache

# Set correct permissions
chmod -R 775 storage bootstrap/cache
chmod -R 755 public
```

6. **Restart services**:
```bash
# Restart PHP-FPM (adjust service name as needed)
sudo systemctl restart php8.3-fpm

# Restart queue workers
php artisan queue:restart

# Restart web server
sudo systemctl restart nginx
```

### Backup Configuration

Configure automated backups in `config/backup.php`:

```php
'backup' => [
    'name' => env('APP_NAME', 'vilnius-billing'),
    'source' => [
        'files' => [
            'include' => [
                base_path(),
            ],
            'exclude' => [
                base_path('vendor'),
                base_path('node_modules'),
            ],
        ],
        'databases' => [
            'sqlite',
        ],
    ],
],
```

Run backup manually:
```bash
php artisan backup:run
```

Schedule automated backups in `app/Console/Kernel.php`:
```php
$schedule->command('backup:run')->daily()->at('02:00');
```

## Framework Versions and Compatibility

### Current Stack

This application uses the following framework versions:

| Component | Version | Notes |
|-----------|---------|-------|
| Laravel | 12.x | Latest stable release |
| Filament | 4.x | Admin panel framework with Livewire 3 |
| PHP | 8.2+ (8.3+ recommended) | Minimum 8.2, 8.3+ for best performance |
| Tailwind CSS | 4.x | Utility-first CSS framework |
| Alpine.js | 3.x | Lightweight JavaScript framework |
| Pest | 3.x | Testing framework |
| PHPUnit | 11.x | Test runner |
| Vite | 5.x | Build tool |
| Spatie Backup | 9.3+ | Backup solution |

### Upgrade History

The application has been upgraded from:
- Laravel 11.x → Laravel 12.x
- Filament 3.x → Filament 4.x
- Tailwind CSS 3.x → Tailwind CSS 4.x
- Pest 2.x → Pest 3.x
- PHPUnit 10.x → PHPUnit 11.x

See [Upgrade Guide](../upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md) for detailed migration notes.

### PHP Version Recommendations

- **Minimum**: PHP 8.2 (required)
- **Recommended**: PHP 8.3+ (for optimal Laravel 12 performance)
- **Production**: PHP 8.3+ with OPcache enabled

### Database Compatibility

| Database | Minimum Version | Recommended | Notes |
|----------|----------------|-------------|-------|
| SQLite | 3.35+ | 3.40+ | Development only, WAL mode required |
| MySQL | 8.0+ | 8.0.30+ | Production recommended |
| PostgreSQL | 13+ | 15+ | Production recommended |
| MariaDB | 10.10+ | 10.11+ | Alternative to MySQL |

### Node.js and NPM

- **Node.js**: 18.x or higher (20.x LTS recommended)
- **NPM**: 9.x or higher (10.x recommended)

### Browser Support

The admin panel (Filament) and frontend support:
- Chrome/Edge: Last 2 versions
- Firefox: Last 2 versions
- Safari: Last 2 versions
- Mobile browsers: iOS Safari 14+, Chrome Android 90+

## Additional Resources

- [README.md](../../README.md) - Project overview and quick start
- [Project Overview](../overview/readme.md) - Detailed feature documentation
- [HIERARCHICAL_USER_GUIDE.md](HIERARCHICAL_USER_GUIDE.md) - User guide for all roles
- [TESTING_GUIDE.md](TESTING_GUIDE.md) - Testing approach and conventions
- [Upgrade Guide](../upgrades/LARAVEL_12_FILAMENT_4_UPGRADE.md) - Framework upgrade notes
- [Laravel 12 Documentation](https://laravel.com/docs/12.x) - Framework documentation
- [Filament 4 Documentation](https://filamentphp.com/docs/4.x) - Admin panel documentation
- [Tailwind CSS 4 Documentation](https://tailwindcss.com/docs) - CSS framework documentation

## Troubleshooting

### Common Setup Issues

#### PHP Version Issues

**Problem**: `composer install` fails with PHP version error

**Solution**:
```bash
# Check current PHP version
php -v

# If using multiple PHP versions, specify the correct one
/usr/bin/php8.3 /usr/local/bin/composer install

# Or update your PATH to use PHP 8.3 by default
```

#### Composer Dependency Conflicts

**Problem**: Composer reports version conflicts

**Solution**:
```bash
# Clear Composer cache
composer clear-cache

# Update dependencies
composer update

# If issues persist, check for conflicting packages
composer why-not laravel/framework 12.0
```

#### Node/NPM Version Issues

**Problem**: `npm install` fails or Vite doesn't work

**Solution**:
```bash
# Check Node.js version (should be 18+)
node -v

# Update Node.js using nvm (recommended)
nvm install 18
nvm use 18

# Or update NPM
npm install -g npm@latest

# Clear NPM cache and reinstall
rm -rf node_modules package-lock.json
npm cache clean --force
npm install
```

#### Database Connection Issues

**Problem**: Cannot connect to database

**Solution**:
```bash
# For SQLite, ensure file exists and is writable
touch database/database.sqlite
chmod 664 database/database.sqlite

# For MySQL/PostgreSQL, verify credentials in .env
php artisan tinker --execute="DB::connection()->getPdo();"
```

#### Filament Installation Issues

**Problem**: Filament assets not loading or admin panel not working

**Solution**:
```bash
# Publish Filament assets
php artisan filament:assets

# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan config:cache
php artisan view:cache

# Verify Filament version
composer show filament/filament
```

#### Permission Issues

**Problem**: Storage or cache directories not writable

**Solution**:
```bash
# Set correct permissions
chmod -R 775 storage bootstrap/cache

# Set correct ownership (adjust user as needed)
chown -R www-data:www-data storage bootstrap/cache

# For development, you might need
chmod -R 777 storage bootstrap/cache
```

#### Migration Issues

**Problem**: Migrations fail or foreign key constraints error

**Solution**:
```bash
# Ensure foreign keys are enabled (SQLite)
# Add to .env:
DB_FOREIGN_KEYS=true

# Fresh migration (WARNING: destroys data)
php artisan migrate:fresh

# Or rollback and re-run
php artisan migrate:rollback
php artisan migrate
```

### Getting Help

For technical support or questions:
- Check the [documentation files](../)
- Review the [test suite](../../tests/) for examples
- Check [Laravel 12 upgrade guide](https://laravel.com/docs/12.x/upgrade)
- Check [Filament 4 upgrade guide](https://filamentphp.com/docs/4.x/support/upgrade-guide)
- Contact the system administrator

## Support

For technical support or questions:
- Check the documentation files
- Review the test suite for examples
- Contact the system administrator
