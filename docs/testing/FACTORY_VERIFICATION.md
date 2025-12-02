# Factory Verification Guide

## Overview

The `test_factories.php` script provides a quick verification mechanism for the hierarchical user management factories introduced in the Laravel 12 multi-tenant utilities billing platform. It validates that `SubscriptionFactory` and `UserFactory` correctly generate test data with appropriate attributes for different user roles.

## Purpose

This verification script ensures:
- Factory state methods work correctly for all user roles
- Tenant isolation attributes are properly set
- Subscription plans generate with correct limits
- Hierarchical relationships are established correctly

## Usage

### Basic Execution

```bash
php test_factories.php
```

### Expected Output

```
Testing SubscriptionFactory...
✓ Plan: basic, Max Properties: 10, Max Tenants: 50

Testing UserFactory - Admin...
✓ Role: admin, Tenant ID: 999, Org: Test Organization 999

Testing UserFactory - Tenant...
✓ Role: tenant, Tenant ID: 999, Property ID: 1, Parent: 1

Testing UserFactory - Superadmin...
✓ Role: superadmin, Tenant ID: null

✅ All factories working correctly!
```

## Verified Factories

### SubscriptionFactory

**Location:** `database/factories/SubscriptionFactory.php`

**State Methods Tested:**
- `basic()` - Creates basic subscription plan with default limits
- `professional()` - Creates professional plan (not tested in script but available)
- `enterprise()` - Creates enterprise plan (not tested in script but available)

**Verified Attributes:**
- `plan_type` - Subscription plan type enum
- `max_properties` - Maximum properties allowed
- `max_tenants` - Maximum tenants allowed
- `status` - Subscription status (active by default)

### UserFactory

**Location:** `database/factories/UserFactory.php`

**State Methods Tested:**

#### 1. `admin(int $tenantId)`
Creates an admin user with:
- `role` = UserRole::ADMIN
- `tenant_id` = provided tenant ID
- `organization_name` = auto-generated organization name
- `is_active` = true

#### 2. `tenant(int $tenantId, int $propertyId, int $parentUserId)`
Creates a tenant user with:
- `role` = UserRole::TENANT
- `tenant_id` = provided tenant ID
- `property_id` = provided property ID
- `parent_user_id` = provided parent user ID
- `is_active` = true

#### 3. `superadmin()`
Creates a superadmin user with:
- `role` = UserRole::SUPERADMIN
- `tenant_id` = null (no tenant isolation)
- `is_active` = true

## Integration with Testing

### Use in Pest Tests

```php
use App\Models\User;
use App\Models\Subscription;

it('creates admin with subscription', function () {
    $subscription = Subscription::factory()->basic()->create();
    $admin = User::factory()
        ->admin(999)
        ->create(['subscription_id' => $subscription->id]);
    
    expect($admin->role)->toBe(UserRole::ADMIN)
        ->and($admin->tenant_id)->toBe(999)
        ->and($admin->organization_name)->not->toBeNull();
});

it('creates tenant with property assignment', function () {
    $tenant = User::factory()
        ->tenant(999, 1, 1)
        ->create();
    
    expect($tenant->role)->toBe(UserRole::TENANT)
        ->and($tenant->property_id)->toBe(1)
        ->and($tenant->parent_user_id)->toBe(1);
});
```

### Use in Seeders

```php
// database/seeders/HierarchicalUsersSeeder.php

// Create superadmin
$superadmin = User::factory()->superadmin()->create([
    'email' => 'superadmin@example.com',
]);

// Create admin with subscription
$subscription = Subscription::factory()->basic()->create();
$admin = User::factory()->admin(1)->create([
    'email' => 'admin@example.com',
    'subscription_id' => $subscription->id,
]);

// Create tenant for admin
$tenant = User::factory()->tenant(1, 1, $admin->id)->create([
    'email' => 'tenant@example.com',
]);
```

## Troubleshooting

### Factory Not Found

**Error:** `Class "Database\Factories\SubscriptionFactory" not found`

**Solution:**
```bash
composer dump-autoload
```

### Missing Enum Values

**Error:** `Undefined constant UserRole::ADMIN`

**Solution:** Ensure `app/Enums/UserRole.php` includes all required roles:
```php
enum UserRole: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case TENANT = 'tenant';
}
```

### Database Connection Issues

**Error:** `SQLSTATE[HY000] [2002] Connection refused`

**Solution:** Ensure database is running and `.env` is configured:
```bash
php artisan config:clear
php artisan cache:clear
```

## Related Documentation

- [Hierarchical User Management Spec](../tasks/tasks.md)
- [Factory Documentation](./FACTORY_GUIDE.md)
- [Testing Guide](../guides/TESTING_GUIDE.md)
- [Seeder Documentation](../database/SEEDERS_SUMMARY.md)

## Architecture Notes

### Multi-Tenancy Pattern

The factories implement the multi-tenancy pattern where:
- **Superadmins** have no `tenant_id` (global access)
- **Admins** have a `tenant_id` (organization-scoped)
- **Tenants** have `tenant_id` + `property_id` (property-scoped)

### Hierarchical Relationships

```
Superadmin (tenant_id: null)
    └── Admin (tenant_id: 1, organization_name: "Org 1")
        ├── Tenant 1 (tenant_id: 1, property_id: 1, parent_user_id: admin.id)
        └── Tenant 2 (tenant_id: 1, property_id: 2, parent_user_id: admin.id)
```

### Subscription Limits

Subscriptions enforce limits on:
- `max_properties` - Maximum properties an admin can manage
- `max_tenants` - Maximum tenants an admin can create

These limits are checked by `SubscriptionService` during tenant/property creation.

## Quality Gates

### Before Committing

Run the verification script to ensure factories work:
```bash
php test_factories.php
```

### CI/CD Integration

Add to your CI pipeline:
```yaml
# .github/workflows/tests.yml
- name: Verify Factories
  run: php test_factories.php
```

### Pest Integration

The verification logic is also covered by Pest tests:
```bash
php artisan test --filter=FactoryTest
```

## Changelog

### 2024-11-26
- Initial factory verification script created
- Added support for admin, tenant, and superadmin state methods
- Documented integration with hierarchical user management

## See Also

- [Task 13.1: Create SubscriptionFactory](../tasks/tasks.md#131-create-subscriptionfactory)
- [Task 13.2: Update UserFactory](../tasks/tasks.md#132-update-userfactory-for-hierarchical-users)
- [SubscriptionFactory API](../api/SUBSCRIPTION_FACTORY_API.md)
- [UserFactory API](../api/USER_FACTORY_API.md)
