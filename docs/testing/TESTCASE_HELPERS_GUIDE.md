# TestCase Helper Methods Guide

## Overview

The `Tests\TestCase` base class provides a comprehensive set of helper methods for creating test data and managing tenant context in tests. This guide explains how to use these helpers effectively.

## Authentication Helpers

### `actingAsAdmin(int $tenantId = 1, array $attributes = []): User`

Creates and authenticates as an admin user with proper tenant context.

```php
// Basic usage
$admin = $this->actingAsAdmin();

// With specific tenant
$admin = $this->actingAsAdmin(2);

// With custom attributes
$admin = $this->actingAsAdmin(1, ['name' => 'John Admin']);
```

**Features:**
- Automatically creates an Organization for the tenant
- Sets up TenantContext for the test
- Returns the created User instance
- Cleans up context in tearDown

### `actingAsManager(int $tenantId = 1, array $attributes = []): User`

Creates and authenticates as a manager user.

```php
$manager = $this->actingAsManager(1);
```

### `actingAsTenant(int $tenantId = 1, array $attributes = []): User`

Creates and authenticates as a tenant user.

```php
$tenant = $this->actingAsTenant(1);
```

### `actingAsSuperadmin(array $attributes = []): User`

Creates and authenticates as a superadmin user (no tenant context).

```php
$superadmin = $this->actingAsSuperadmin();
```

## Data Creation Helpers

### `createTestProperty(int|array $tenantIdOrAttributes = 1, array $attributes = []): Property`

Creates a test property with flexible parameter handling.

```php
// Simple usage
$property = $this->createTestProperty(1);

// With attributes
$property = $this->createTestProperty(1, [
    'type' => PropertyType::HOUSE,
    'area_sqm' => 100.0,
]);

// Array syntax
$property = $this->createTestProperty([
    'tenant_id' => 2,
    'type' => PropertyType::APARTMENT,
    'area_sqm' => 75.0,
]);
```

### `createTestBuilding(int $tenantId = 1, array $attributes = []): Building`

Creates a test building for a tenant.

```php
$building = $this->createTestBuilding(1, [
    'name' => 'Main Building',
]);
```

### `createTestMeter(int $propertyId, ?MeterType $type = null, array $attributes = []): Meter`

Creates a test meter for a property.

```php
$property = $this->createTestProperty(1);
$meter = $this->createTestMeter($property->id, MeterType::WATER);
```

### `createTestMeterReading(int $meterId, float $value, array $attributes = []): MeterReading`

Creates a test meter reading with automatic manager creation.

```php
$reading = $this->createTestMeterReading($meter->id, 100.5);

// With custom attributes
$reading = $this->createTestMeterReading($meter->id, 150.0, [
    'reading_date' => now()->subDays(7),
]);
```

**Features:**
- Automatically creates a manager user if one doesn't exist
- Reuses existing manager for the same tenant
- Properly sets tenant_id and entered_by

### `createTestInvoice(int $propertyId, array $attributes = []): Invoice`

Creates a test invoice for a property.

```php
$invoice = $this->createTestInvoice($property->id, [
    'status' => InvoiceStatus::DRAFT,
]);
```

## Tenant Context Helpers

### `ensureTenantExists(int $tenantId): Organization`

Ensures an organization exists for testing.

```php
$organization = $this->ensureTenantExists(5);
```

**Note:** This is called automatically by authentication and data creation helpers.

### `withinTenant(int $tenantId, callable $callback): mixed`

Executes a callback within a specific tenant context.

```php
$result = $this->withinTenant(2, function () {
    // Code here runs with tenant 2 context
    $property = Property::first();
    return $property->tenant_id; // Will be 2
});

// Original context is restored after callback
```

**Features:**
- Automatically restores previous context
- Handles exceptions gracefully
- Useful for testing cross-tenant scenarios

## Assertion Helpers

### `assertTenantContext(int $expectedTenantId): void`

Asserts that the current tenant context matches the expected tenant.

```php
$this->actingAsAdmin(1);
$this->assertTenantContext(1); // Passes
```

### `assertNoTenantContext(): void`

Asserts that no tenant context is set.

```php
$this->actingAsSuperadmin();
$this->assertNoTenantContext(); // Passes
```

## Best Practices

### 1. Use Role-Specific Helpers

```php
// ✅ Good - Clear intent
$admin = $this->actingAsAdmin();

// ❌ Avoid - Manual user creation
$admin = User::factory()->create(['role' => UserRole::ADMIN]);
$this->actingAs($admin);
```

### 2. Let Helpers Manage Tenant Context

```php
// ✅ Good - Automatic context management
$manager = $this->actingAsManager(1);
$property = $this->createTestProperty(1);

// ❌ Avoid - Manual context management
TenantContext::set(1);
$property = Property::factory()->create(['tenant_id' => 1]);
```

### 3. Use withinTenant for Cross-Tenant Tests

```php
// ✅ Good - Safe context switching
$this->actingAsAdmin(1);
$result = $this->withinTenant(2, function () {
    return Property::count();
});

// ❌ Avoid - Manual context switching
TenantContext::set(2);
$count = Property::count();
TenantContext::set(1); // Easy to forget!
```

### 4. Leverage Flexible Parameter Handling

```php
// Both are valid
$property1 = $this->createTestProperty(1, ['area_sqm' => 50]);
$property2 = $this->createTestProperty(['tenant_id' => 1, 'area_sqm' => 50]);
```

## Common Patterns

### Testing Multi-Tenant Isolation

```php
public function test_users_can_only_see_their_tenant_properties(): void
{
    // Create properties for different tenants
    $property1 = $this->createTestProperty(1);
    $property2 = $this->createTestProperty(2);
    
    // Act as tenant 1 user
    $this->actingAsTenant(1);
    
    // Assert only tenant 1 properties are visible
    $properties = Property::all();
    $this->assertCount(1, $properties);
    $this->assertEquals($property1->id, $properties->first()->id);
}
```

### Testing with Complete Data Setup

```php
public function test_invoice_generation_with_meter_readings(): void
{
    // Setup
    $manager = $this->actingAsManager(1);
    $property = $this->createTestProperty(1);
    $meter = $this->createTestMeter($property->id, MeterType::ELECTRICITY);
    $reading = $this->createTestMeterReading($meter->id, 100.0);
    
    // Act
    $invoice = $this->createTestInvoice($property->id);
    
    // Assert
    $this->assertEquals($property->tenant_id, $invoice->tenant_id);
}
```

### Testing Superadmin Cross-Tenant Access

```php
public function test_superadmin_can_access_all_tenants(): void
{
    $property1 = $this->createTestProperty(1);
    $property2 = $this->createTestProperty(2);
    
    $superadmin = $this->actingAsSuperadmin();
    
    // Superadmin can switch between tenants
    $count1 = $this->withinTenant(1, fn() => Property::count());
    $count2 = $this->withinTenant(2, fn() => Property::count());
    
    $this->assertEquals(1, $count1);
    $this->assertEquals(1, $count2);
}
```

## Automatic Cleanup

The TestCase automatically cleans up tenant context in `tearDown()`:

```php
protected function tearDown(): void
{
    TenantContext::clear();
    parent::tearDown();
}
```

This ensures tests don't leak context between test methods.

## Migration from Old Patterns

### Before (Manual Setup)

```php
public function test_old_pattern(): void
{
    $user = User::factory()->create([
        'tenant_id' => 1,
        'role' => UserRole::ADMIN,
    ]);
    $this->actingAs($user);
    
    $property = Property::factory()->create([
        'tenant_id' => 1,
    ]);
    
    // Test code...
}
```

### After (Using Helpers)

```php
public function test_new_pattern(): void
{
    $admin = $this->actingAsAdmin(1);
    $property = $this->createTestProperty(1);
    
    // Test code...
}
```

## Running Tests

```bash
# Run all tests
php artisan test

# Run TestCase helper tests
php artisan test --filter=TestCaseHelpersTest

# Run with fresh database
php artisan test:setup --fresh
php artisan test
```

## Troubleshooting

### Issue: Tenant context not set

**Solution:** Use authentication helpers that automatically set context:

```php
// ✅ Sets context automatically
$this->actingAsAdmin(1);

// ❌ Doesn't set context
$this->actingAsSuperadmin();
```

### Issue: Cross-tenant data leakage

**Solution:** Verify TenantScope is applied and use assertions:

```php
$this->actingAsManager(1);
$this->assertTenantContext(1);

$properties = Property::all();
$this->assertTrue($properties->every(fn($p) => $p->tenant_id === 1));
```

### Issue: Manager not found for meter reading

**Solution:** The helper automatically creates managers, but ensure the meter exists:

```php
$property = $this->createTestProperty(1);
$meter = $this->createTestMeter($property->id); // Must exist first
$reading = $this->createTestMeterReading($meter->id, 100.0);
```

## See Also

- [Testing Guide](../guides/TESTING_GUIDE.md)
- [Multi-Tenancy Documentation](../architecture/MULTI_TENANCY.md)
- [Property-Based Testing](./PROPERTY_BASED_TESTING.md)
