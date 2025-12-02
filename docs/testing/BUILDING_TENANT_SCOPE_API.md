# Building Tenant Scope Testing API Reference

## Overview

This document provides a complete API reference for the Building tenant scope testing infrastructure, including test helpers, assertions, and integration patterns.

## Test Files

### BuildingTenantScopeSimpleTest.php

**Location**: `tests/Feature/BuildingTenantScopeSimpleTest.php`

**Purpose**: Simple, straightforward verification of Building model tenant isolation

**Test Cases**:
1. `manager can only see their own tenant buildings`
2. `superadmin can see all tenant buildings`
3. `manager cannot access another tenant building by ID`

**Dependencies**:
- `App\Models\Building`
- `App\Models\User`
- `App\Enums\UserRole`
- `App\Traits\BelongsToTenant`
- `App\Scopes\TenantScope`

### FilamentBuildingResourceTenantScopeTest.php

**Location**: `tests/Feature/FilamentBuildingResourceTenantScopeTest.php`

**Purpose**: Comprehensive property-based testing of Filament BuildingResource tenant isolation

**Test Cases**:
1. `BuildingResource automatically filters buildings by authenticated user tenant_id` (100 iterations)
2. `BuildingResource edit page only allows editing buildings within tenant scope` (100 iterations)
3. `Superadmin users can access buildings from all tenants` (100 iterations)

**Dependencies**:
- All dependencies from simple tests
- `App\Filament\Resources\BuildingResource`
- `Livewire\Testing\TestableLivewire`

## Test Helpers

### Simple Test Helpers

#### Authentication Helper

```php
$this->actingAs(User $user): TestCase
```

**Description**: Authenticates a user for the test context

**Parameters**:
- `$user` (User): The user to authenticate

**Returns**: TestCase instance for method chaining

**Example**:
```php
$manager = User::factory()->create([
    'role' => UserRole::MANAGER,
    'tenant_id' => 1,
]);

$this->actingAs($manager);
```

### Property Test Helpers

#### createBuildingsForTenant()

```php
function createBuildingsForTenant(int $tenantId, int $count): Collection
```

**Description**: Creates a specified number of buildings for a given tenant

**Parameters**:
- `$tenantId` (int): The tenant ID to assign to buildings
- `$count` (int): Number of buildings to create

**Returns**: Collection of Building models

**Example**:
```php
$buildings = createBuildingsForTenant(1, 5);
// Creates 5 buildings for tenant ID 1
```

**Implementation**:
```php
function createBuildingsForTenant(int $tenantId, int $count): Collection
{
    return Building::factory()
        ->count($count)
        ->create(['tenant_id' => $tenantId]);
}
```

#### createManagerForTenant()

```php
function createManagerForTenant(int $tenantId): User
```

**Description**: Creates a manager user assigned to a specific tenant

**Parameters**:
- `$tenantId` (int): The tenant ID to assign to the manager

**Returns**: User model with MANAGER role

**Example**:
```php
$manager = createManagerForTenant(1);
// Creates a manager for tenant ID 1
```

**Implementation**:
```php
function createManagerForTenant(int $tenantId): User
{
    return User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenantId,
    ]);
}
```

#### createSuperadmin()

```php
function createSuperadmin(): User
```

**Description**: Creates a superadmin user with unrestricted access

**Returns**: User model with SUPERADMIN role and null tenant_id

**Example**:
```php
$superadmin = createSuperadmin();
// Creates a superadmin with cross-tenant access
```

**Implementation**:
```php
function createSuperadmin(): User
{
    return User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);
}
```

#### authenticateWithTenant()

```php
function authenticateWithTenant(User $user): void
```

**Description**: Authenticates a user and sets the session tenant context

**Parameters**:
- `$user` (User): The user to authenticate

**Side Effects**:
- Sets authenticated user in test context
- Sets `tenant_id` in session

**Example**:
```php
$manager = createManagerForTenant(1);
authenticateWithTenant($manager);
// Authenticates manager and sets tenant context
```

**Implementation**:
```php
function authenticateWithTenant(User $user): void
{
    test()->actingAs($user);
    session(['tenant_id' => $user->tenant_id]);
}
```

## Assertion Patterns

### Collection Count Assertions

```php
// Assert exact count
expect($buildings)->toHaveCount(1);

// Assert minimum count
expect($buildings->count())->toBeGreaterThanOrEqual(2);

// Assert with custom message
expect($buildings)->toHaveCount(1, 'Manager should see exactly 1 building');
```

### Model Attribute Assertions

```php
// Assert single attribute
expect($building->tenant_id)->toBe(1);

// Assert chained attributes
expect($building)
    ->id->toBe($expectedId)
    ->tenant_id->toBe($expectedTenantId);

// Assert with null check
expect($building)
    ->not->toBeNull()
    ->id->toBe($expectedId);
```

### Null/Existence Assertions

```php
// Assert model exists
expect(Building::find($id))->not->toBeNull();

// Assert model doesn't exist (cross-tenant)
expect(Building::find($crossTenantId))->toBeNull();

// Assert with custom message
expect(Building::find($id))
    ->toBeNull('Cross-tenant building should be inaccessible');
```

### Collection Content Assertions

```php
// Assert all items match condition
expect($buildings)->each(fn ($building) => 
    $building->tenant_id->toBe($expectedTenantId)
);

// Assert collection contains IDs
expect($buildings->pluck('id')->toArray())
    ->toContain($building1->id)
    ->toContain($building2->id);

// Assert exact ID match (any order)
expect($buildings->pluck('id')->toArray())
    ->toEqualCanonicalizing($expectedIds);
```

### Livewire Component Assertions

```php
// Assert component loads successfully
$component->assertSuccessful();

// Assert component record
expect($component->instance()->record)
    ->id->toBe($expectedId)
    ->tenant_id->toBe($expectedTenantId);

// Assert exception thrown
expect(fn () => Livewire::test(EditBuilding::class, ['record' => $id]))
    ->toThrow(ModelNotFoundException::class);
```

## Test Data Patterns

### Fixed Tenant IDs (Simple Tests)

```php
// Use fixed, predictable tenant IDs
$tenant1 = 1;
$tenant2 = 2;

// Create one building per tenant
$building1 = Building::factory()->create(['tenant_id' => $tenant1]);
$building2 = Building::factory()->create(['tenant_id' => $tenant2]);
```

**Advantages**:
- Predictable behavior
- Easy to debug
- Clear test intent
- Fast execution

### Random Tenant IDs (Property Tests)

```php
// Generate random tenant IDs
$tenant1 = fake()->numberBetween(1, 1000);
$tenant2 = fake()->numberBetween(1001, 2000);

// Create random number of buildings
$count1 = fake()->numberBetween(2, 8);
$buildings1 = createBuildingsForTenant($tenant1, $count1);
```

**Advantages**:
- Tests edge cases
- Prevents test pollution
- Statistical confidence
- Parallel test safety

## Integration Patterns

### Model-Level Testing

```php
// Test direct model queries
$buildings = Building::all();
$building = Building::find($id);

// Test with scope bypass
$allBuildings = Building::withoutGlobalScope(TenantScope::class)->get();
```

### Filament Resource Testing

```php
// Test list page
$component = Livewire::test(ListBuildings::class);
$records = $component->instance()->getTableRecords();

// Test edit page
$component = Livewire::test(EditBuilding::class, [
    'record' => $buildingId,
]);
```

### Session Context Testing

```php
// Set session tenant context
session(['tenant_id' => $tenantId]);

// Verify session context
expect(session('tenant_id'))->toBe($tenantId);
```

## Error Handling

### Expected Exceptions

```php
// ModelNotFoundException for cross-tenant access
expect(fn () => Livewire::test(EditBuilding::class, ['record' => $crossTenantId]))
    ->toThrow(ModelNotFoundException::class);

// AuthorizationException for unauthorized actions
expect(fn () => $building->delete())
    ->toThrow(AuthorizationException::class);
```

### Null Returns

```php
// Cross-tenant queries return null (not exception)
$building = Building::find($crossTenantId);
expect($building)->toBeNull();
```

## Performance Considerations

### Simple Tests

- **Execution Time**: ~0.5s for 3 tests
- **Database Operations**: ~15 queries
- **Memory Usage**: Minimal
- **Suitable For**: Pre-commit hooks, CI smoke tests

### Property Tests

- **Execution Time**: ~15s for 300 tests
- **Database Operations**: ~1500 queries
- **Memory Usage**: Moderate
- **Suitable For**: Full test suite, production validation

## Best Practices

### Test Naming

```php
// ✅ Good: Descriptive, behavior-focused
test('manager can only see their own tenant buildings')

// ❌ Bad: Implementation-focused
test('tenant scope filters buildings')
```

### Assertion Messages

```php
// ✅ Good: Clear, actionable message
expect($buildings)->toHaveCount(1, 'Manager should see exactly 1 building');

// ❌ Bad: Generic message
expect($buildings)->toHaveCount(1);
```

### Test Data Setup

```php
// ✅ Good: Clear variable names, explicit values
$tenant1 = 1;
$building1 = Building::factory()->create([
    'tenant_id' => $tenant1,
    'name' => 'Tenant 1 Building',
]);

// ❌ Bad: Magic numbers, unclear intent
$b = Building::factory()->create(['tenant_id' => 1]);
```

### Test Organization

```php
// ✅ Good: Logical sections with comments
// Create test data
$tenant1 = 1;
$building1 = Building::factory()->create(['tenant_id' => $tenant1]);

// Authenticate user
$manager = User::factory()->create(['tenant_id' => $tenant1]);
$this->actingAs($manager);

// Verify isolation
expect(Building::all())->toHaveCount(1);

// ❌ Bad: No structure, unclear flow
$t = 1;
$b = Building::factory()->create(['tenant_id' => $t]);
$u = User::factory()->create(['tenant_id' => $t]);
$this->actingAs($u);
expect(Building::all())->toHaveCount(1);
```

## Debugging Techniques

### Query Inspection

```php
// View generated SQL
dd(Building::query()->toSql());
// Output: SELECT * FROM buildings WHERE tenant_id = ?

// View query bindings
dd(Building::query()->getBindings());
// Output: [1]
```

### Scope Verification

```php
// Test with scope
$withScope = Building::all();

// Test without scope
$withoutScope = Building::withoutGlobalScope(TenantScope::class)->get();

// Compare results
dd([
    'with_scope' => $withScope->count(),
    'without_scope' => $withoutScope->count(),
]);
```

### Session Inspection

```php
// Check authenticated user
dd([
    'user_id' => auth()->id(),
    'tenant_id' => auth()->user()->tenant_id,
    'role' => auth()->user()->role,
]);

// Check session data
dd([
    'session_tenant_id' => session('tenant_id'),
    'all_session' => session()->all(),
]);
```

## Related Documentation

- [Building Simple Tests Guide](building-tenant-scope-simple-tests.md)
- [Building Property Tests Guide](filament-building-resource-tenant-scope-tests.md)
- [Building Tests Quick Reference](BUILDING_TENANT_SCOPE_QUICK_REFERENCE.md)
- [Multi-Tenancy Architecture](../architecture/multi-tenancy.md)
- [Tenant Scope Implementation](../architecture/tenant-scope.md)

## Changelog

### 2025-11-27
- Initial API documentation
- Documented test helpers and assertion patterns
- Added integration patterns and best practices
- Included debugging techniques and performance notes
