# Factory Quick Reference

Quick reference for using factories in tests and seeders.

## Common Patterns

### Create Superadmin

```php
$superadmin = User::factory()->superadmin()->create([
    'email' => 'superadmin@example.com',
]);
```

### Create Admin with Subscription

```php
$subscription = Subscription::factory()->basic()->create();
$admin = User::factory()->admin(1)->create([
    'email' => 'admin@example.com',
    'subscription_id' => $subscription->id,
]);
```

### Create Tenant with Property

```php
$admin = User::factory()->admin(1)->create();
$property = Property::factory()->create(['tenant_id' => 1]);
$tenant = User::factory()
    ->tenant(1, $property->id, $admin->id)
    ->create(['email' => 'tenant@example.com']);
```

### Create Manager

```php
$manager = User::factory()->manager(1)->create([
    'email' => 'manager@example.com',
]);
```

## Subscription Plans

```php
// Basic: 10 properties, 50 tenants
$basic = Subscription::factory()->basic()->create();

// Professional: 50 properties, 250 tenants
$pro = Subscription::factory()->professional()->create();

// Enterprise: 999 properties, 9999 tenants
$enterprise = Subscription::factory()->enterprise()->create();

// Expired subscription
$expired = Subscription::factory()->expired()->create();
```

## Multi-Tenant Setup

```php
// Organization 1
$admin1 = User::factory()->admin(1)->create();
$property1 = Property::factory()->create(['tenant_id' => 1]);
$tenant1 = User::factory()->tenant(1, $property1->id, $admin1->id)->create();

// Organization 2 (isolated)
$admin2 = User::factory()->admin(2)->create();
$property2 = Property::factory()->create(['tenant_id' => 2]);
$tenant2 = User::factory()->tenant(2, $property2->id, $admin2->id)->create();
```

## Testing Scenarios

### Test Subscription Limits

```php
$subscription = Subscription::factory()->basic()->create();
$admin = User::factory()->admin(1)->create([
    'subscription_id' => $subscription->id,
]);

// Create up to limit (10 properties for basic)
$properties = Property::factory()
    ->count(10)
    ->create(['tenant_id' => 1]);

// Attempting 11th should fail
expect(fn() => Property::factory()->create(['tenant_id' => 1]))
    ->toThrow(SubscriptionLimitExceededException::class);
```

### Test Tenant Isolation

```php
$tenant1 = User::factory()->tenant(1, 1, 1)->create();
$tenant2 = User::factory()->tenant(2, 2, 2)->create();

actingAs($tenant1);
$properties = Property::all(); // Only sees tenant_id = 1

actingAs($tenant2);
$properties = Property::all(); // Only sees tenant_id = 2
```

### Test Inactive Users

```php
$inactiveAdmin = User::factory()
    ->admin(1)
    ->inactive()
    ->create();

expect($inactiveAdmin->is_active)->toBeFalse();
```

## Verification

```bash
# Quick verification
php test_factories.php

# Run factory tests
php artisan test --filter=FactoryTest
```

## See Also

- [Full Factory API Documentation](../api/FACTORY_API.md)
- [Factory Verification Guide](./FACTORY_VERIFICATION.md)
- [Testing Guide](./TESTING_GUIDE.md)
