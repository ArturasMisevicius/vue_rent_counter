# Factory API Documentation

## Overview

This document describes the factory APIs for generating test data in the Laravel 12 multi-tenant utilities billing platform. Factories follow Laravel conventions and support hierarchical user management with tenant isolation.

## SubscriptionFactory

**Location:** `database/factories/SubscriptionFactory.php`

### Base Factory

```php
Subscription::factory()->create();
```

Generates a subscription with random attributes:
- `plan_type`: Random from SubscriptionPlanType enum
- `status`: Random from SubscriptionStatus enum
- `max_properties`: Random between 5-100
- `max_tenants`: Random between 10-500
- `starts_at`: Current timestamp
- `expires_at`: 1 year from start

### State Methods

#### `basic()`

Creates a basic subscription plan.

```php
Subscription::factory()->basic()->create();
```

**Attributes:**
- `plan_type`: `SubscriptionPlanType::BASIC`
- `status`: `SubscriptionStatus::ACTIVE`
- `max_properties`: 10
- `max_tenants`: 50
- `starts_at`: Current timestamp
- `expires_at`: 1 year from start

**Use Case:** Testing basic tier functionality and limits.

#### `professional()`

Creates a professional subscription plan.

```php
Subscription::factory()->professional()->create();
```

**Attributes:**
- `plan_type`: `SubscriptionPlanType::PROFESSIONAL`
- `status`: `SubscriptionStatus::ACTIVE`
- `max_properties`: 50
- `max_tenants`: 250
- `starts_at`: Current timestamp
- `expires_at`: 1 year from start

**Use Case:** Testing mid-tier functionality and higher limits.

#### `enterprise()`

Creates an enterprise subscription plan.

```php
Subscription::factory()->enterprise()->create();
```

**Attributes:**
- `plan_type`: `SubscriptionPlanType::ENTERPRISE`
- `status`: `SubscriptionStatus::ACTIVE`
- `max_properties`: 999
- `max_tenants`: 9999
- `starts_at`: Current timestamp
- `expires_at`: 1 year from start

**Use Case:** Testing unlimited/high-volume scenarios.

#### `expired()`

Creates an expired subscription.

```php
Subscription::factory()->expired()->create();
```

**Attributes:**
- `status`: `SubscriptionStatus::EXPIRED`
- `expires_at`: 30 days ago
- Other attributes: Random

**Use Case:** Testing subscription expiry handling and grace periods.

#### `suspended()`

Creates a suspended subscription.

```php
Subscription::factory()->suspended()->create();
```

**Attributes:**
- `status`: `SubscriptionStatus::SUSPENDED`
- Other attributes: Random

**Use Case:** Testing subscription suspension and reactivation flows.

### Examples

```php
// Create basic subscription for testing
$subscription = Subscription::factory()->basic()->create();

// Create expired subscription to test grace period
$expiredSub = Subscription::factory()->expired()->create();

// Create multiple subscriptions with different plans
$subscriptions = Subscription::factory()
    ->count(3)
    ->sequence(
        ['plan_type' => SubscriptionPlanType::BASIC],
        ['plan_type' => SubscriptionPlanType::PROFESSIONAL],
        ['plan_type' => SubscriptionPlanType::ENTERPRISE],
    )
    ->create();
```

## UserFactory

**Location:** `database/factories/UserFactory.php`

### Base Factory

```php
User::factory()->create();
```

Generates a user with random attributes:
- `name`: Fake name
- `email`: Fake unique email
- `password`: Hashed 'password'
- `role`: Random from UserRole enum
- `is_active`: true
- `email_verified_at`: Current timestamp

### State Methods

#### `admin(int $tenantId)`

Creates an admin user for a specific tenant.

```php
User::factory()->admin(999)->create();
```

**Parameters:**
- `$tenantId` (int): The tenant ID for organization isolation

**Attributes Set:**
- `role`: `UserRole::ADMIN`
- `tenant_id`: Provided tenant ID
- `organization_name`: Auto-generated (e.g., "Test Organization 999")
- `is_active`: true
- `property_id`: null
- `parent_user_id`: null

**Use Case:** Creating organization administrators with tenant isolation.

**Example:**
```php
$admin = User::factory()->admin(1)->create([
    'email' => 'admin@example.com',
    'organization_name' => 'Acme Properties',
]);
```

#### `tenant(int $tenantId, int $propertyId, int $parentUserId)`

Creates a tenant user assigned to a property.

```php
User::factory()->tenant(999, 1, 1)->create();
```

**Parameters:**
- `$tenantId` (int): The tenant ID (inherited from admin)
- `$propertyId` (int): The property ID for assignment
- `$parentUserId` (int): The admin user ID who created this tenant

**Attributes Set:**
- `role`: `UserRole::TENANT`
- `tenant_id`: Provided tenant ID
- `property_id`: Provided property ID
- `parent_user_id`: Provided parent user ID
- `is_active`: true
- `organization_name`: null

**Use Case:** Creating property tenants with hierarchical relationships.

**Example:**
```php
$admin = User::factory()->admin(1)->create();
$property = Property::factory()->create(['tenant_id' => 1]);

$tenant = User::factory()
    ->tenant(1, $property->id, $admin->id)
    ->create([
        'email' => 'tenant@example.com',
    ]);
```

#### `superadmin()`

Creates a superadmin user with global access.

```php
User::factory()->superadmin()->create();
```

**Attributes Set:**
- `role`: `UserRole::SUPERADMIN`
- `tenant_id`: null (no tenant isolation)
- `is_active`: true
- `property_id`: null
- `parent_user_id`: null
- `organization_name`: null

**Use Case:** Creating platform administrators with unrestricted access.

**Example:**
```php
$superadmin = User::factory()->superadmin()->create([
    'email' => 'superadmin@example.com',
    'name' => 'Platform Administrator',
]);
```

#### `manager(int $tenantId)`

Creates a manager user for a specific tenant.

```php
User::factory()->manager(999)->create();
```

**Parameters:**
- `$tenantId` (int): The tenant ID for organization isolation

**Attributes Set:**
- `role`: `UserRole::MANAGER`
- `tenant_id`: Provided tenant ID
- `is_active`: true
- `property_id`: null
- `parent_user_id`: null

**Use Case:** Creating property managers with tenant-scoped access.

**Example:**
```php
$manager = User::factory()->manager(1)->create([
    'email' => 'manager@example.com',
]);
```

#### `inactive()`

Creates an inactive user.

```php
User::factory()->inactive()->create();
```

**Attributes Set:**
- `is_active`: false
- Other attributes: Random

**Use Case:** Testing account deactivation and reactivation flows.

**Example:**
```php
$inactiveUser = User::factory()
    ->admin(1)
    ->inactive()
    ->create();
```

### Complex Examples

#### Creating Complete Hierarchy

```php
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

// Create properties for admin
$property1 = Property::factory()->create(['tenant_id' => 1]);
$property2 = Property::factory()->create(['tenant_id' => 1]);

// Create tenants for properties
$tenant1 = User::factory()
    ->tenant(1, $property1->id, $admin->id)
    ->create(['email' => 'tenant1@example.com']);

$tenant2 = User::factory()
    ->tenant(1, $property2->id, $admin->id)
    ->create(['email' => 'tenant2@example.com']);
```

#### Testing Multi-Tenancy Isolation

```php
// Create two separate organizations
$admin1 = User::factory()->admin(1)->create();
$admin2 = User::factory()->admin(2)->create();

// Create properties for each
$property1 = Property::factory()->create(['tenant_id' => 1]);
$property2 = Property::factory()->create(['tenant_id' => 2]);

// Create tenants - each isolated to their tenant_id
$tenant1 = User::factory()
    ->tenant(1, $property1->id, $admin1->id)
    ->create();

$tenant2 = User::factory()
    ->tenant(2, $property2->id, $admin2->id)
    ->create();

// Verify isolation
expect($tenant1->tenant_id)->toBe(1)
    ->and($tenant2->tenant_id)->toBe(2);
```

## Related Factories

### PropertyFactory

```php
Property::factory()->create(['tenant_id' => 1]);
```

Creates properties with tenant isolation.

### BuildingFactory

```php
Building::factory()->create(['tenant_id' => 1]);
```

Creates buildings with tenant isolation.

### MeterFactory

```php
Meter::factory()->create([
    'property_id' => $property->id,
    'tenant_id' => 1,
]);
```

Creates meters assigned to properties.

### InvoiceFactory

```php
Invoice::factory()->create([
    'property_id' => $property->id,
    'tenant_id' => 1,
]);
```

Creates invoices for properties.

## Best Practices

### 1. Always Set Tenant ID

```php
// ✅ Good - explicit tenant isolation
$property = Property::factory()->create(['tenant_id' => 1]);

// ❌ Bad - may cause cross-tenant issues
$property = Property::factory()->create();
```

### 2. Use Hierarchical Relationships

```php
// ✅ Good - proper hierarchy
$admin = User::factory()->admin(1)->create();
$tenant = User::factory()
    ->tenant(1, $property->id, $admin->id)
    ->create();

// ❌ Bad - orphaned tenant
$tenant = User::factory()->tenant(1, 1, 999)->create();
```

### 3. Match Subscription Limits

```php
// ✅ Good - respects subscription limits
$subscription = Subscription::factory()->basic()->create();
$admin = User::factory()->admin(1)->create([
    'subscription_id' => $subscription->id,
]);

// Create within limits (max 10 properties for basic)
$properties = Property::factory()
    ->count(5)
    ->create(['tenant_id' => 1]);
```

### 4. Use State Methods for Clarity

```php
// ✅ Good - clear intent
$admin = User::factory()->admin(1)->create();

// ❌ Bad - manual attribute setting
$admin = User::factory()->create([
    'role' => UserRole::ADMIN,
    'tenant_id' => 1,
    'organization_name' => 'Test Org',
]);
```

## Verification

Run the factory verification script:

```bash
php test_factories.php
```

Expected output confirms all factories work correctly.

## See Also

- [Factory Verification Guide](../testing/FACTORY_VERIFICATION.md)
- [Testing Guide](../testing/TESTING_GUIDE.md)
- [Seeder Documentation](../database/SEEDERS_SUMMARY.md)
- [Hierarchical User Management Spec](../../.kiro/specs/3-hierarchical-user-management/tasks.md)
