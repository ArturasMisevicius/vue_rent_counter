# Test Helpers API Reference

## Overview

This document provides a comprehensive API reference for test helper functions used across the Vilnius Utilities Billing Platform test suite.

## Multi-Tenant Test Helpers

### Building Test Helpers

Located in: `tests/Feature/FilamentBuildingResourceTenantScopeTest.php`

#### `createBuildingsForTenant()`

Creates a specified number of buildings for a given tenant using the Building factory.

**Signature**:
```php
function createBuildingsForTenant(int $tenantId, int $count): Collection
```

**Parameters**:
- `$tenantId` (int) - The tenant ID to assign to all created buildings
- `$count` (int) - Number of buildings to create

**Returns**:
- `Collection<int, Building>` - Collection of created Building models

**Usage**:
```php
// Create 5 buildings for tenant 1
$buildings = createBuildingsForTenant(1, 5);

// Create random number of buildings
$count = fake()->numberBetween(2, 8);
$buildings = createBuildingsForTenant($tenantId, $count);
```

**Implementation Details**:
- Uses `Building::factory()` for consistent data generation
- Sets `tenant_id` attribute on all created buildings
- Returns Eloquent Collection for easy manipulation
- Each building has randomized attributes (name, address, etc.)

**Related Functions**:
- `Building::factory()` - Factory definition
- `createManagerForTenant()` - Create manager for testing access

---

#### `createManagerForTenant()`

Creates a manager user assigned to a specific tenant.

**Signature**:
```php
function createManagerForTenant(int $tenantId): User
```

**Parameters**:
- `$tenantId` (int) - The tenant ID to assign to the manager

**Returns**:
- `User` - User model with MANAGER role and specified tenant_id

**Usage**:
```php
// Create manager for tenant 1
$manager = createManagerForTenant(1);

// Authenticate as manager
authenticateWithTenant($manager);
```

**Implementation Details**:
- Uses `User::factory()` for consistent user generation
- Sets `role` to `UserRole::MANAGER`
- Sets `tenant_id` to specified value
- Generates random name, email, and password

**Related Functions**:
- `createSuperadmin()` - Create superadmin user
- `authenticateWithTenant()` - Authenticate created user

---

#### `createSuperadmin()`

Creates a superadmin user with unrestricted cross-tenant access.

**Signature**:
```php
function createSuperadmin(): User
```

**Parameters**: None

**Returns**:
- `User` - User model with SUPERADMIN role and null tenant_id

**Usage**:
```php
// Create superadmin
$superadmin = createSuperadmin();

// Authenticate as superadmin
authenticateWithTenant($superadmin);

// Superadmin can access all tenant data
$allBuildings = Building::all();
```

**Implementation Details**:
- Uses `User::factory()` for consistent user generation
- Sets `role` to `UserRole::SUPERADMIN`
- Sets `tenant_id` to `null` for unrestricted access
- Bypasses tenant scope restrictions

**Related Functions**:
- `createManagerForTenant()` - Create tenant-scoped manager
- `authenticateWithTenant()` - Authenticate created user

---

#### `authenticateWithTenant()`

Authenticates a user and sets the session tenant context for testing.

**Signature**:
```php
function authenticateWithTenant(User $user): void
```

**Parameters**:
- `$user` (User) - The user to authenticate

**Returns**: void

**Side Effects**:
- Sets authenticated user in test context via `test()->actingAs()`
- Sets `tenant_id` in session for tenant scope filtering
- Enables tenant-scoped queries for subsequent operations

**Usage**:
```php
// Create and authenticate manager
$manager = createManagerForTenant(1);
authenticateWithTenant($manager);

// Now all queries are scoped to tenant 1
$buildings = Building::all(); // Only tenant 1 buildings

// Switch to different tenant
$manager2 = createManagerForTenant(2);
authenticateWithTenant($manager2);

// Now queries are scoped to tenant 2
$buildings = Building::all(); // Only tenant 2 buildings
```

**Implementation Details**:
- Uses Pest's `test()->actingAs()` for authentication
- Sets session variable `tenant_id` for scope filtering
- Works with Laravel's session system
- Compatible with Filament authentication

**Related Functions**:
- `createManagerForTenant()` - Create manager to authenticate
- `createSuperadmin()` - Create superadmin to authenticate

---

## Usage Patterns

### Pattern 1: Basic Tenant Isolation Test

```php
test('resource respects tenant scope', function () {
    // Setup: Create data for two tenants
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    
    $buildings1 = createBuildingsForTenant($tenantId1, 5);
    $buildings2 = createBuildingsForTenant($tenantId2, 5);
    
    // Test: Authenticate as tenant 1 manager
    $manager1 = createManagerForTenant($tenantId1);
    authenticateWithTenant($manager1);
    
    // Verify: Only tenant 1 buildings visible
    $visible = Building::all();
    expect($visible)->toHaveCount(5);
    expect($visible)->each(fn ($b) => $b->tenant_id->toBe($tenantId1));
    
    // Verify: Tenant 2 buildings inaccessible
    $buildings2->each(fn ($b) => 
        expect(Building::find($b->id))->toBeNull()
    );
});
```

### Pattern 2: Superadmin Access Test

```php
test('superadmin has unrestricted access', function () {
    // Setup: Create data for multiple tenants
    $buildings1 = createBuildingsForTenant(1, 3);
    $buildings2 = createBuildingsForTenant(2, 3);
    
    // Test: Authenticate as superadmin
    $superadmin = createSuperadmin();
    authenticateWithTenant($superadmin);
    
    // Verify: Can see all buildings
    $allBuildings = Building::all();
    expect($allBuildings->count())->toBeGreaterThanOrEqual(6);
    
    // Verify: Can access any building
    expect(Building::find($buildings1->first()->id))->not->toBeNull();
    expect(Building::find($buildings2->first()->id))->not->toBeNull();
});
```

### Pattern 3: Cross-Tenant Access Prevention

```php
test('cross-tenant access is blocked', function () {
    // Setup: Create buildings for two tenants
    $building1 = createBuildingsForTenant(1, 1)->first();
    $building2 = createBuildingsForTenant(2, 1)->first();
    
    // Test: Authenticate as tenant 1 manager
    $manager1 = createManagerForTenant(1);
    authenticateWithTenant($manager1);
    
    // Verify: Can access own building
    $component = Livewire::test(EditBuilding::class, [
        'record' => $building1->id
    ]);
    $component->assertSuccessful();
    
    // Verify: Cannot access other tenant building
    expect(fn () => Livewire::test(EditBuilding::class, [
        'record' => $building2->id
    ]))->toThrow(ModelNotFoundException::class);
});
```

### Pattern 4: Property-Based Testing

```php
test('tenant isolation holds across scenarios', function () {
    // Randomize test data
    $tenantId1 = fake()->numberBetween(1, 1000);
    $tenantId2 = fake()->numberBetween(1001, 2000);
    $count1 = fake()->numberBetween(2, 8);
    $count2 = fake()->numberBetween(2, 8);
    
    // Create randomized data
    $buildings1 = createBuildingsForTenant($tenantId1, $count1);
    $buildings2 = createBuildingsForTenant($tenantId2, $count2);
    
    // Test with tenant 1
    $manager1 = createManagerForTenant($tenantId1);
    authenticateWithTenant($manager1);
    
    $visible = Building::all();
    expect($visible)->toHaveCount($count1);
    
    // Test with tenant 2
    $manager2 = createManagerForTenant($tenantId2);
    authenticateWithTenant($manager2);
    
    $visible = Building::all();
    expect($visible)->toHaveCount($count2);
})->repeat(100);
```

## Best Practices

### 1. Use Randomized Data

```php
// ❌ Bad: Hardcoded values
$buildings = createBuildingsForTenant(1, 5);

// ✅ Good: Randomized values
$tenantId = fake()->numberBetween(1, 1000);
$count = fake()->numberBetween(2, 8);
$buildings = createBuildingsForTenant($tenantId, $count);
```

### 2. Clean Authentication State

```php
// Always authenticate before testing
authenticateWithTenant($user);

// Switch users when needed
authenticateWithTenant($manager1);
// ... test manager1 access ...

authenticateWithTenant($manager2);
// ... test manager2 access ...
```

### 3. Verify Both Visibility and Inaccessibility

```php
// Verify own tenant data is visible
$visible = Building::all();
expect($visible)->each(fn ($b) => $b->tenant_id->toBe($ownTenantId));

// Verify other tenant data is inaccessible
$otherBuildings->each(fn ($b) => 
    expect(Building::find($b->id))->toBeNull()
);
```

### 4. Use Descriptive Variable Names

```php
// ❌ Bad: Unclear names
$b1 = createBuildingsForTenant(1, 5);
$u1 = createManagerForTenant(1);

// ✅ Good: Clear names
$tenant1Buildings = createBuildingsForTenant(1, 5);
$tenant1Manager = createManagerForTenant(1);
```

## Common Pitfalls

### 1. Forgetting to Authenticate

```php
// ❌ Bad: No authentication
$buildings = createBuildingsForTenant(1, 5);
$visible = Building::all(); // May return unexpected results

// ✅ Good: Authenticate first
$buildings = createBuildingsForTenant(1, 5);
$manager = createManagerForTenant(1);
authenticateWithTenant($manager);
$visible = Building::all(); // Properly scoped
```

### 2. Reusing Tenant IDs

```php
// ❌ Bad: Same tenant ID in parallel tests
$buildings1 = createBuildingsForTenant(1, 5);
$buildings2 = createBuildingsForTenant(1, 5); // Conflict!

// ✅ Good: Unique tenant IDs
$tenantId1 = fake()->numberBetween(1, 1000);
$tenantId2 = fake()->numberBetween(1001, 2000);
$buildings1 = createBuildingsForTenant($tenantId1, 5);
$buildings2 = createBuildingsForTenant($tenantId2, 5);
```

### 3. Not Verifying Inaccessibility

```php
// ❌ Bad: Only tests visibility
expect($visible)->each(fn ($b) => $b->tenant_id->toBe($tenantId));

// ✅ Good: Tests both visibility and inaccessibility
expect($visible)->each(fn ($b) => $b->tenant_id->toBe($tenantId));
$otherBuildings->each(fn ($b) => 
    expect(Building::find($b->id))->toBeNull()
);
```

## Extension Points

### Creating Similar Helpers for Other Resources

```php
// Property helper
function createPropertiesForTenant(int $tenantId, int $count): Collection
{
    return Property::factory()
        ->count($count)
        ->create(['tenant_id' => $tenantId]);
}

// Invoice helper
function createInvoicesForTenant(int $tenantId, int $count): Collection
{
    return Invoice::factory()
        ->count($count)
        ->create(['tenant_id' => $tenantId]);
}

// Meter helper
function createMetersForProperty(int $propertyId, int $count): Collection
{
    return Meter::factory()
        ->count($count)
        ->create(['property_id' => $propertyId]);
}
```

### Creating Role-Specific Helpers

```php
// Admin helper
function createAdminForTenant(int $tenantId): User
{
    return User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
    ]);
}

// Tenant helper
function createTenantUser(int $tenantId): User
{
    return User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $tenantId,
    ]);
}
```

## Related Documentation

- [Property-Based Testing Guide](./property-based-testing-guide.md)
- [Filament Building Resource Tests](./filament-building-resource-tenant-scope-tests.md)
- [Multi-Tenancy Testing Strategy](./multi-tenancy-testing.md)
- [Test Data Factories](../database/factories.md)

## Changelog

### 2025-11-27
- Initial API reference documentation
- Documented Building test helpers
- Added usage patterns and best practices
- Included common pitfalls and solutions
