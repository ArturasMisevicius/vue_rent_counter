# TestCase API Reference

## Overview

The `Tests\TestCase` class is the foundation for all tests in the Vilnius Utilities Billing Platform. It provides a comprehensive set of helper methods for testing multi-tenant functionality with proper context management.

**Location**: `tests/TestCase.php`

**Extends**: `Illuminate\Foundation\Testing\TestCase`

**Traits**: `RefreshDatabase`

## Table of Contents

- [Authentication Helpers](#authentication-helpers)
- [Data Creation Helpers](#data-creation-helpers)
- [Tenant Context Helpers](#tenant-context-helpers)
- [Assertion Helpers](#assertion-helpers)
- [Usage Patterns](#usage-patterns)
- [Best Practices](#best-practices)

---

## Authentication Helpers

### `actingAsAdmin(int $tenantId = 1, array $attributes = []): User`

Creates and authenticates as an admin user with proper tenant context.

**Parameters**:
- `$tenantId` (int): The tenant ID (default: 1)
- `$attributes` (array): Additional user attributes to merge

**Returns**: `User` - The created admin user

**Behavior**:
- Creates an admin user with `UserRole::ADMIN`
- Generates unique email: `test-admin-{uniqid}@test.com`
- Sets password to `'password'` (hashed)
- Authenticates the user via `actingAs()`
- Ensures organization exists for the tenant
- Sets `TenantContext` to the specified tenant
- Skips context setup for superadmin users

**Example**:
```php
// Basic usage
$admin = $this->actingAsAdmin();

// With specific tenant
$admin = $this->actingAsAdmin(2);

// With custom attributes
$admin = $this->actingAsAdmin(1, [
    'name' => 'John Admin',
    'email_verified_at' => now(),
]);
```

**Related**: `actingAsManager()`, `actingAsTenant()`, `actingAsSuperadmin()`

---

### `actingAsManager(int $tenantId = 1, array $attributes = []): User`

Creates and authenticates as a manager user with proper tenant context.

**Parameters**:
- `$tenantId` (int): The tenant ID (default: 1)
- `$attributes` (array): Additional user attributes to merge

**Returns**: `User` - The created manager user

**Behavior**:
- Creates a manager user with `UserRole::MANAGER`
- Generates unique email: `test-manager-{uniqid}@test.com`
- Sets password to `'password'` (hashed)
- Authenticates the user via `actingAs()`
- Ensures organization exists for the tenant
- Sets `TenantContext` to the specified tenant

**Example**:
```php
// Basic usage
$manager = $this->actingAsManager();

// With specific tenant
$manager = $this->actingAsManager(3);

// With custom attributes
$manager = $this->actingAsManager(1, [
    'name' => 'Jane Manager',
]);
```

**Related**: `actingAsAdmin()`, `actingAsTenant()`, `actingAsSuperadmin()`

---

### `actingAsTenant(int $tenantId = 1, array $attributes = []): User`

Creates and authenticates as a tenant user with proper tenant context.

**Parameters**:
- `$tenantId` (int): The tenant ID (default: 1)
- `$attributes` (array): Additional user attributes to merge

**Returns**: `User` - The created tenant user

**Behavior**:
- Creates a tenant user with `UserRole::TENANT`
- Generates unique email: `test-tenant-{uniqid}@test.com`
- Sets password to `'password'` (hashed)
- Authenticates the user via `actingAs()`
- Ensures organization exists for the tenant
- Sets `TenantContext` to the specified tenant

**Example**:
```php
// Basic usage
$tenant = $this->actingAsTenant();

// With specific tenant
$tenant = $this->actingAsTenant(2);

// With custom attributes
$tenant = $this->actingAsTenant(1, [
    'name' => 'Bob Tenant',
]);
```

**Related**: `actingAsAdmin()`, `actingAsManager()`, `actingAsSuperadmin()`

---

### `actingAsSuperadmin(array $attributes = []): User`

Creates and authenticates as a superadmin user.

**Parameters**:
- `$attributes` (array): Additional user attributes to merge

**Returns**: `User` - The created superadmin user

**Behavior**:
- Creates a superadmin user with `UserRole::SUPERADMIN`
- Sets `tenant_id` to `null` (superadmins are not tenant-scoped)
- Generates unique email: `test-superadmin-{uniqid}@test.com`
- Sets password to `'password'` (hashed)
- Authenticates the user via `actingAs()`
- **Does NOT set tenant context** (superadmins can access all tenants)

**Example**:
```php
// Basic usage
$superadmin = $this->actingAsSuperadmin();

// With custom attributes
$superadmin = $this->actingAsSuperadmin([
    'name' => 'Super Admin',
]);

// Verify no tenant context
$this->assertNoTenantContext();
```

**Note**: Use `withinTenant()` to temporarily switch context for superadmin operations.

**Related**: `actingAsAdmin()`, `withinTenant()`

---

## Data Creation Helpers

### `createTestProperty(int|array $tenantIdOrAttributes = 1, array $attributes = []): Property`

Creates a test property for a specific tenant.

**Parameters**:
- `$tenantIdOrAttributes` (int|array): Tenant ID or attributes array
- `$attributes` (array): Additional attributes (when first param is int)

**Returns**: `Property` - The created property

**Behavior**:
- Supports two calling patterns (see examples)
- Ensures organization exists for the tenant
- Generates unique address: `Test Address {uniqid}`
- Sets default type to `PropertyType::APARTMENT`
- Sets default area to `50.0` sqm
- Sets `building_id` to `null` by default

**Examples**:
```php
// Simple usage with tenant ID
$property = $this->createTestProperty(1);

// With additional attributes
$property = $this->createTestProperty(1, [
    'type' => PropertyType::HOUSE,
    'area_sqm' => 100.0,
]);

// Array syntax (all attributes in one parameter)
$property = $this->createTestProperty([
    'tenant_id' => 2,
    'type' => PropertyType::APARTMENT,
    'area_sqm' => 75.0,
    'building_id' => $building->id,
]);
```

**Related**: `createTestBuilding()`, `createTestMeter()`

---

### `createTestBuilding(int $tenantId = 1, array $attributes = []): Building`

Creates a test building for a specific tenant.

**Parameters**:
- `$tenantId` (int): The tenant ID (default: 1)
- `$attributes` (array): Additional building attributes

**Returns**: `Building` - The created building

**Behavior**:
- Ensures organization exists for the tenant
- Generates unique name: `Test Building {uniqid}`
- Generates unique address: `Test Building Address {uniqid}`

**Example**:
```php
// Basic usage
$building = $this->createTestBuilding(1);

// With custom attributes
$building = $this->createTestBuilding(1, [
    'name' => 'Main Building',
    'address' => '123 Main St',
]);
```

**Related**: `createTestProperty()`

---

### `createTestMeter(int $propertyId, ?MeterType $type = null, array $attributes = []): Meter`

Creates a test meter for a specific property.

**Parameters**:
- `$propertyId` (int): The property ID
- `$type` (MeterType|null): The meter type (default: `MeterType::ELECTRICITY`)
- `$attributes` (array): Additional meter attributes

**Returns**: `Meter` - The created meter

**Behavior**:
- Fetches the property to get `tenant_id`
- Generates unique serial number: `TEST-{uniqid}`
- Defaults to `MeterType::ELECTRICITY` if type not specified

**Example**:
```php
$property = $this->createTestProperty(1);

// Basic usage (electricity meter)
$meter = $this->createTestMeter($property->id);

// With specific type
$waterMeter = $this->createTestMeter($property->id, MeterType::WATER);
$gasMeter = $this->createTestMeter($property->id, MeterType::GAS);

// With custom attributes
$meter = $this->createTestMeter($property->id, MeterType::ELECTRICITY, [
    'serial_number' => 'CUSTOM-123',
]);
```

**Related**: `createTestProperty()`, `createTestMeterReading()`

---

### `createTestMeterReading(int $meterId, float $value, array $attributes = []): MeterReading`

Creates a test meter reading for a specific meter.

**Parameters**:
- `$meterId` (int): The meter ID
- `$value` (float): The reading value
- `$attributes` (array): Additional reading attributes

**Returns**: `MeterReading` - The created meter reading

**Behavior**:
- Fetches the meter to get `tenant_id`
- **Automatically creates or reuses a manager user** for the tenant
- Sets `reading_date` to current timestamp
- Sets `entered_by` to the manager user ID
- Sets `zone` to `null` by default

**Performance Note**: Reuses existing manager users to avoid N+1 queries when creating multiple readings.

**Example**:
```php
$property = $this->createTestProperty(1);
$meter = $this->createTestMeter($property->id);

// Basic usage
$reading = $this->createTestMeterReading($meter->id, 100.5);

// With custom attributes
$reading = $this->createTestMeterReading($meter->id, 150.0, [
    'reading_date' => now()->subDays(7),
    'zone' => 'day',
]);

// Multiple readings (manager is reused)
$reading1 = $this->createTestMeterReading($meter->id, 100.0);
$reading2 = $this->createTestMeterReading($meter->id, 150.0);
// Both readings use the same manager user
```

**Related**: `createTestMeter()`, `createTestInvoice()`

---

### `createTestInvoice(int $propertyId, array $attributes = []): Invoice`

Creates a test invoice for a specific property.

**Parameters**:
- `$propertyId` (int): The property ID
- `$attributes` (array): Additional invoice attributes

**Returns**: `Invoice` - The created invoice

**Behavior**:
- Fetches the property to get `tenant_id`
- Sets `billing_period_start` to start of current month
- Sets `billing_period_end` to end of current month

**Example**:
```php
$property = $this->createTestProperty(1);

// Basic usage
$invoice = $this->createTestInvoice($property->id);

// With custom attributes
$invoice = $this->createTestInvoice($property->id, [
    'status' => InvoiceStatus::DRAFT,
    'billing_period_start' => now()->subMonth()->startOfMonth(),
    'billing_period_end' => now()->subMonth()->endOfMonth(),
]);
```

**Related**: `createTestProperty()`, `createTestMeterReading()`

---

## Tenant Context Helpers

### `ensureTenantExists(int $tenantId): Organization`

Ensures a tenant (organization) exists for testing.

**Parameters**:
- `$tenantId` (int): The tenant ID

**Returns**: `Organization` - The organization (existing or newly created)

**Behavior**:
- Uses `firstOrCreate()` to avoid duplicate organizations
- Creates organization with default attributes:
  - `name`: `Test Organization {tenantId}`
  - `status`: `'active'`
  - `subscription_plan`: `'basic'`
  - `subscription_status`: `'active'`
  - `subscription_expires_at`: One year from now

**Example**:
```php
// Ensure organization exists
$organization = $this->ensureTenantExists(5);

// Safe to call multiple times
$org1 = $this->ensureTenantExists(5);
$org2 = $this->ensureTenantExists(5);
// $org1 and $org2 are the same instance
```

**Note**: This method is called automatically by authentication and data creation helpers.

**Related**: `actingAsAdmin()`, `createTestProperty()`

---

### `withinTenant(int $tenantId, callable $callback): mixed`

Executes a callback within a specific tenant context.

**Parameters**:
- `$tenantId` (int): The tenant ID to switch to
- `$callback` (callable): The callback to execute

**Returns**: `mixed` - The callback result

**Behavior**:
- Ensures organization exists for the tenant
- Temporarily switches to the specified tenant context
- Executes the callback
- **Automatically restores the previous context** after execution
- Handles exceptions gracefully (context is restored even if callback throws)

**Example**:
```php
// Test cross-tenant operations
$this->actingAsAdmin(1);
$this->assertTenantContext(1);

$result = $this->withinTenant(2, function () {
    $this->assertTenantContext(2);
    return Property::count();
});

// Context is restored
$this->assertTenantContext(1);

// Superadmin testing multiple tenants
$superadmin = $this->actingAsSuperadmin();

$count1 = $this->withinTenant(1, fn() => Property::count());
$count2 = $this->withinTenant(2, fn() => Property::count());

$this->assertEquals(5, $count1);
$this->assertEquals(3, $count2);
```

**Use Cases**:
- Testing superadmin operations across tenants
- Verifying tenant isolation
- Testing cross-tenant data access prevention

**Related**: `actingAsSuperadmin()`, `assertTenantContext()`

---

## Assertion Helpers

### `assertTenantContext(int $expectedTenantId): void`

Asserts that the current tenant context matches the expected tenant.

**Parameters**:
- `$expectedTenantId` (int): The expected tenant ID

**Throws**: `AssertionFailedError` if context doesn't match

**Example**:
```php
$this->actingAsAdmin(1);
$this->assertTenantContext(1); // Passes

$this->actingAsManager(2);
$this->assertTenantContext(2); // Passes
$this->assertTenantContext(1); // Fails
```

**Related**: `assertNoTenantContext()`, `withinTenant()`

---

### `assertNoTenantContext(): void`

Asserts that no tenant context is set.

**Throws**: `AssertionFailedError` if context is set

**Example**:
```php
$this->actingAsSuperadmin();
$this->assertNoTenantContext(); // Passes

$this->actingAsAdmin(1);
$this->assertNoTenantContext(); // Fails
```

**Use Cases**:
- Verifying superadmin has no default context
- Testing context cleanup
- Verifying context is cleared after operations

**Related**: `assertTenantContext()`, `actingAsSuperadmin()`

---

## Usage Patterns

### Pattern 1: Basic Feature Test

```php
public function test_manager_can_create_property(): void
{
    // Arrange
    $manager = $this->actingAsManager(1);
    $building = $this->createTestBuilding(1);
    
    // Act
    $response = $this->post(route('properties.store'), [
        'building_id' => $building->id,
        'address' => '123 Test St',
        'type' => PropertyType::APARTMENT->value,
        'area_sqm' => 75.0,
    ]);
    
    // Assert
    $response->assertRedirect();
    $this->assertDatabaseHas('properties', [
        'tenant_id' => 1,
        'address' => '123 Test St',
    ]);
}
```

### Pattern 2: Multi-Tenant Isolation Test

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
    $this->assertTenantContext(1);
}
```

### Pattern 3: Complex Data Setup

```php
public function test_invoice_generation_with_meter_readings(): void
{
    // Setup
    $manager = $this->actingAsManager(1);
    $property = $this->createTestProperty(1);
    $meter = $this->createTestMeter($property->id, MeterType::ELECTRICITY);
    $reading1 = $this->createTestMeterReading($meter->id, 100.0);
    $reading2 = $this->createTestMeterReading($meter->id, 150.0);
    
    // Act
    $invoice = $this->createTestInvoice($property->id);
    
    // Assert
    $this->assertEquals($property->tenant_id, $invoice->tenant_id);
    $this->assertCount(2, $meter->readings);
}
```

### Pattern 4: Superadmin Cross-Tenant Test

```php
public function test_superadmin_can_access_all_tenants(): void
{
    $property1 = $this->createTestProperty(1);
    $property2 = $this->createTestProperty(2);
    
    $superadmin = $this->actingAsSuperadmin();
    $this->assertNoTenantContext();
    
    // Superadmin can switch between tenants
    $count1 = $this->withinTenant(1, fn() => Property::count());
    $count2 = $this->withinTenant(2, fn() => Property::count());
    
    $this->assertEquals(1, $count1);
    $this->assertEquals(1, $count2);
}
```

### Pattern 5: Authorization Test

```php
public function test_tenant_cannot_delete_property(): void
{
    $property = $this->createTestProperty(1);
    $this->actingAsTenant(1);
    
    $response = $this->delete(route('properties.destroy', $property));
    
    $response->assertForbidden();
    $this->assertDatabaseHas('properties', ['id' => $property->id]);
}
```

---

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

### 5. Verify Tenant Context in Tests

```php
public function test_tenant_isolation(): void
{
    $this->actingAsManager(1);
    $this->assertTenantContext(1); // Verify context is set
    
    $properties = Property::all();
    $this->assertTrue($properties->every(fn($p) => $p->tenant_id === 1));
}
```

---

## Architecture Notes

### Automatic Cleanup

The `tearDown()` method automatically clears tenant context after each test:

```php
protected function tearDown(): void
{
    TenantContext::clear();
    parent::tearDown();
}
```

This prevents context leakage between tests.

### Manager Reuse Optimization

The `createTestMeterReading()` method reuses existing manager users to avoid N+1 queries:

```php
// First reading creates a manager
$reading1 = $this->createTestMeterReading($meter->id, 100.0);

// Second reading reuses the same manager
$reading2 = $this->createTestMeterReading($meter->id, 150.0);
```

### Organization Creation

All helpers ensure organizations exist before creating tenant-scoped data:

```php
protected function createTestProperty(int|array $tenantIdOrAttributes = 1, array $attributes = []): Property
{
    // ...
    $this->ensureTenantExists($tenantId);
    // ...
}
```

This prevents foreign key constraint violations.

---

## Related Documentation

- [TestCase Helpers Guide](TESTCASE_HELPERS_GUIDE.md) - Usage guide and examples
- [TestCase Refactoring Summary](TESTCASE_REFACTORING_SUMMARY.md) - Implementation details
- [Testing Guide](README.md) - General testing documentation
- [Multi-Tenancy Documentation](../architecture/MULTI_TENANCY.md) - Tenant architecture

---

## Changelog

### 2024-12-06
- Added comprehensive API documentation
- Documented all helper methods with parameters and examples
- Added usage patterns and best practices
- Documented architecture and optimization details

### 2024-12-05
- Enhanced TestCase with comprehensive helpers
- Added superadmin support
- Improved tenant context management
- Added assertion helpers
