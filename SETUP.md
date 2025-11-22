# Vilnius Utilities Billing System - Setup Guide

## Initial Setup

### 1. Prerequisites

Ensure you have the following installed:
- PHP 8.2 or higher
- Composer
- SQLite 3
- Node.js and NPM (for asset compilation)

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
SUBSCRIPTION_GRACE_PERIOD_DAYS=7

# Warning period before subscription expires (days)
SUBSCRIPTION_EXPIRY_WARNING_DAYS=14
```

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

# Check WAL mode is enabled
php artisan tinker --execute="echo DB::select('PRAGMA journal_mode;')[0]->journal_mode;"

# Check foreign keys are enabled
php artisan tinker --execute="echo DB::select('PRAGMA foreign_keys;')[0]->foreign_keys;"
```

## Hierarchical User Management Setup

### Understanding the Migration

The hierarchical user management system requires migrating existing users to the new structure. This involves:

1. Adding new columns to the `users` table
2. Creating the `subscriptions` table
3. Creating the `user_assignments_audit` table
4. Updating existing user data to fit the new hierarchy

### Migration Command

The system includes a migration command to update existing data:

```bash
php artisan migrate:hierarchical-users
```

#### What the Migration Does

1. **Updates User Roles**:
   - Converts existing 'manager' role to 'admin' role
   - Preserves 'tenant' role users
   - Identifies or creates a superadmin account

2. **Assigns Tenant IDs**:
   - Assigns unique `tenant_id` to each admin/manager user
   - Ensures tenant users inherit their admin's `tenant_id`

3. **Creates Subscriptions**:
   - Creates active subscriptions for all admin users
   - Sets default expiry date (1 year from migration)
   - Assigns default plan (Professional)

4. **Activates Accounts**:
   - Sets `is_active = true` for all existing users
   - Ensures no disruption to current users

#### Migration Options

Run with dry-run to preview changes:
```bash
php artisan migrate:hierarchical-users --dry-run
```

Run with rollback capability:
```bash
php artisan migrate:hierarchical-users --rollback
```

### Seeding Hierarchical Users

For development or testing, seed hierarchical user data:

```bash
php artisan db:seed --class=HierarchicalUsersSeeder
```

#### What the Seeder Creates

1. **Superadmin Account**:
   - Email: superadmin@example.com
   - Password: password
   - Role: superadmin
   - Full system access

2. **Admin Accounts** (3 sample organizations):
   - Email: admin@example.com, admin2@example.com, admin3@example.com
   - Password: password
   - Role: admin
   - Each with unique `tenant_id`
   - Each with active subscription

3. **Tenant Accounts** (2-3 per admin):
   - Email: tenant@example.com, tenant2@example.com, etc.
   - Password: password
   - Role: tenant
   - Assigned to properties
   - Inherit admin's `tenant_id`

### Fresh Installation with Hierarchical Users

For a completely fresh installation:

```bash
# Drop all tables and re-run migrations
php artisan migrate:fresh

# Seed hierarchical users
php artisan db:seed --class=HierarchicalUsersSeeder

# Optionally seed additional test data
php artisan db:seed --class=TestDatabaseSeeder
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
php artisan serve
```

Access the application at `http://localhost:8000`

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/MultiTenancyTest.php

# Run with coverage
php artisan test --coverage

# Run property-based tests only
php artisan test --filter Property
```

### Code Style

Format code using Laravel Pint:

```bash
./vendor/bin/pint
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
- [ ] Configure production database
- [ ] Set up email service
- [ ] Configure backup schedule
- [ ] Set up SSL certificate
- [ ] Configure web server (Apache/Nginx)

### Deployment Steps

1. **Pull latest code**:
```bash
git pull origin main
```

2. **Install dependencies**:
```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

3. **Run migrations**:
```bash
php artisan migrate --force
```

4. **Optimize application**:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

5. **Set permissions**:
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
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

## Additional Resources

- [README.md](README.md) - Project overview and features
- [HIERARCHICAL_USER_GUIDE.md](HIERARCHICAL_USER_GUIDE.md) - User guide for all roles
- [TESTING_GUIDE.md](TESTING_GUIDE.md) - Testing approach and conventions
- [Laravel Documentation](https://laravel.com/docs/11.x) - Framework documentation

## Support

For technical support or questions:
- Check the documentation files
- Review the test suite for examples
- Contact the system administrator
