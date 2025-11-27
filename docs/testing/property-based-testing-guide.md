# Property-Based Testing Guide

## Overview

This guide explains the property-based testing approach used in the Vilnius Utilities Billing Platform, particularly for Filament v4 resource testing and multi-tenant isolation verification.

## What is Property-Based Testing?

Property-based testing verifies that certain properties (invariants) hold true across a wide range of inputs, rather than testing specific examples. Instead of writing:

```php
test('user can create building', function () {
    $building = Building::create(['name' => 'Test Building']);
    expect($building->name)->toBe('Test Building');
});
```

We write:

```php
test('buildings always belong to authenticated user tenant', function () {
    $tenantId = fake()->numberBetween(1, 1000);
    $building = Building::factory()->create(['tenant_id' => $tenantId]);
    
    expect($building->tenant_id)->toBe($tenantId);
})->repeat(100);
```

## Benefits

### Statistical Confidence

Running tests 100 times with randomized data provides statistical confidence that properties hold across various scenarios:

- Different tenant IDs
- Varying record counts
- Random data combinations
- Edge cases discovered automatically

### Regression Detection

Property-based tests catch regressions that example-based tests might miss:

- Tenant scope bypasses
- Authorization holes
- Data leakage between tenants
- Inconsistent behavior across data ranges

### Self-Documenting

Properties serve as executable specifications:

```php
/**
 * Property: Managers only see buildings from their tenant
 * Property: Cross-tenant buildings are completely inaccessible
 * Property: All tenant buildings are present (no data loss)
 */
test('BuildingResource automatically filters buildings by authenticated user tenant_id')
```

## Implementation Patterns

### Pattern 1: Randomized Test Data

Generate random values to test across different scenarios:

```php
// Random tenant IDs to avoid conflicts
$tenantId1 = fake()->numberBetween(1, 1000);
$tenantId2 = fake()->numberBetween(1001, 2000);

// Random counts to test varying data sizes
$buildingsCount = fake()->numberBetween(2, 8);
```

**Why**: Ensures tests work with different data ranges and prevents hardcoded assumptions.

### Pattern 2: Helper Functions

Extract common operations into reusable helpers:

```php
function createBuildingsForTenant(int $tenantId, int $count): Collection
{
    return Building::factory()
        ->count($count)
        ->create(['tenant_id' => $tenantId]);
}

function authenticateWithTenant(User $user): void
{
    test()->actingAs($user);
    session(['tenant_id' => $user->tenant_id]);
}
```

**Why**: Reduces duplication, improves readability, and ensures consistent test setup.

### Pattern 3: Property Verification

Verify properties hold across all test data:

```php
// Property: All returned buildings should belong to tenant 1
expect($tableRecords)
    ->toHaveCount($buildingsCount1)
    ->each(fn ($building) => $building->tenant_id->toBe($tenantId1));

// Property: Cross-tenant buildings are inaccessible
$buildings2->each(fn ($building) => 
    expect(Building::find($building->id))->toBeNull()
);
```

**Why**: Explicitly states and verifies the invariants that must hold.

### Pattern 4: Iteration Count

Use `repeat()` modifier for statistical confidence:

```php
test('property holds across scenarios', function () {
    // Test implementation
})->repeat(100);
```

**Why**: 100 iterations provide ~99% confidence that properties hold consistently.

## Multi-Tenant Testing Patterns

### Tenant Isolation Verification

```php
test('resource respects tenant scope', function () {
    // Create data for two tenants
    $tenant1Data = createDataForTenant($tenantId1);
    $tenant2Data = createDataForTenant($tenantId2);
    
    // Authenticate as tenant 1
    authenticateWithTenant($tenant1User);
    
    // Verify only tenant 1 data is visible
    $visible = Resource::all();
    expect($visible)->each(fn ($item) => 
        $item->tenant_id->toBe($tenantId1)
    );
    
    // Verify tenant 2 data is inaccessible
    $tenant2Data->each(fn ($item) =>
        expect(Resource::find($item->id))->toBeNull()
    );
})->repeat(100);
```

### Cross-Tenant Access Prevention

```php
test('cross-tenant access is blocked', function () {
    $tenant1Item = createItemForTenant($tenantId1);
    $tenant2Item = createItemForTenant($tenantId2);
    
    authenticateWithTenant($tenant1User);
    
    // Should succeed for same tenant
    $component = Livewire::test(EditPage::class, [
        'record' => $tenant1Item->id
    ]);
    $component->assertSuccessful();
    
    // Should throw exception for different tenant
    expect(fn () => Livewire::test(EditPage::class, [
        'record' => $tenant2Item->id
    ]))->toThrow(ModelNotFoundException::class);
})->repeat(100);
```

### Superadmin Unrestricted Access

```php
test('superadmin has unrestricted access', function () {
    $tenant1Item = createItemForTenant($tenantId1);
    $tenant2Item = createItemForTenant($tenantId2);
    
    authenticateWithTenant($superadmin);
    
    // Should see all tenant data
    $allItems = Resource::all();
    expect($allItems->pluck('id'))
        ->toContain($tenant1Item->id)
        ->toContain($tenant2Item->id);
    
    // Should edit any tenant data
    $component1 = Livewire::test(EditPage::class, [
        'record' => $tenant1Item->id
    ]);
    $component1->assertSuccessful();
    
    $component2 = Livewire::test(EditPage::class, [
        'record' => $tenant2Item->id
    ]);
    $component2->assertSuccessful();
})->repeat(100);
```

## Filament Resource Testing

### List Page Testing

```php
test('list page respects tenant scope', function () {
    $items = createItemsForTenant($tenantId, $count);
    authenticateWithTenant($user);
    
    $component = Livewire::test(ListPage::class);
    $component->assertSuccessful();
    
    $tableRecords = $component->instance()->getTableRecords();
    
    // Verify count
    expect($tableRecords)->toHaveCount($count);
    
    // Verify tenant isolation
    expect($tableRecords)->each(fn ($item) =>
        $item->tenant_id->toBe($tenantId)
    );
    
    // Verify completeness
    expect($tableRecords->pluck('id')->toArray())
        ->toEqualCanonicalizing($items->pluck('id')->toArray());
})->repeat(100);
```

### Edit Page Testing

```php
test('edit page respects tenant scope', function () {
    $ownItem = createItemForTenant($ownTenantId);
    $otherItem = createItemForTenant($otherTenantId);
    
    authenticateWithTenant($user);
    
    // Can edit own tenant item
    $component = Livewire::test(EditPage::class, [
        'record' => $ownItem->id
    ]);
    $component->assertSuccessful();
    expect($component->instance()->record->id)->toBe($ownItem->id);
    
    // Cannot edit other tenant item
    expect(fn () => Livewire::test(EditPage::class, [
        'record' => $otherItem->id
    ]))->toThrow(ModelNotFoundException::class);
})->repeat(100);
```

### Form Validation Testing

```php
test('form validation is consistent', function () {
    authenticateWithTenant($user);
    
    $component = Livewire::test(CreatePage::class);
    
    // Test invalid data
    $component->fillForm([
        'name' => '', // Required field
        'email' => 'invalid-email', // Invalid format
    ])->call('create');
    
    $component->assertHasFormErrors([
        'name' => 'required',
        'email' => 'email',
    ]);
    
    // Test valid data
    $component->fillForm([
        'name' => fake()->name(),
        'email' => fake()->email(),
    ])->call('create');
    
    $component->assertHasNoFormErrors();
})->repeat(100);
```

## Best Practices

### 1. Use Descriptive Property Comments

```php
/**
 * Property: Managers only see buildings from their tenant
 * Property: Cross-tenant buildings are completely inaccessible
 * Property: All tenant buildings are present (no data loss)
 * Property: Direct model queries respect tenant scope
 */
test('BuildingResource automatically filters buildings')
```

### 2. Test Both Positive and Negative Cases

```php
// Positive: Can access own tenant data
$component = Livewire::test(EditPage::class, ['record' => $ownItem->id]);
$component->assertSuccessful();

// Negative: Cannot access other tenant data
expect(fn () => Livewire::test(EditPage::class, ['record' => $otherItem->id]))
    ->toThrow(ModelNotFoundException::class);
```

### 3. Verify Completeness

```php
// Not just that data is filtered, but that ALL expected data is present
expect($tableRecords->pluck('id')->toArray())
    ->toEqualCanonicalizing($expectedItems->pluck('id')->toArray());
```

### 4. Use Meaningful Assertion Messages

```php
expect($tableRecords)
    ->toHaveCount($buildingsCount, 'Manager should see exactly their tenant\'s buildings')
    ->each(fn ($building) => 
        $building->tenant_id->toBe($tenantId, 'All buildings must belong to tenant 1')
    );
```

### 5. Test Edge Cases

```php
// Test with minimum data
$items = createItemsForTenant($tenantId, 1);

// Test with maximum reasonable data
$items = createItemsForTenant($tenantId, 100);

// Test with empty results
$items = collect();
```

## Performance Considerations

### Iteration Count

- **100 iterations**: Standard for critical properties (tenant isolation, security)
- **50 iterations**: Acceptable for less critical properties
- **10 iterations**: Minimum for basic property verification

### Test Execution Time

- Each iteration creates fresh database records
- Use `RefreshDatabase` trait for isolation
- Consider `--parallel` flag for faster execution
- Monitor total test suite time

### Optimization Strategies

```php
// Use factories efficiently
Building::factory()->count(5)->create(['tenant_id' => $tenantId]);

// Reuse test data when possible
beforeEach(function () {
    $this->tenant1 = createTenant();
    $this->tenant2 = createTenant();
});

// Use database transactions for speed
uses(RefreshDatabase::class);
```

## Common Pitfalls

### 1. Hardcoded Test Data

❌ **Bad**:
```php
$building = Building::create(['tenant_id' => 1]);
```

✅ **Good**:
```php
$tenantId = fake()->numberBetween(1, 1000);
$building = Building::factory()->create(['tenant_id' => $tenantId]);
```

### 2. Insufficient Iterations

❌ **Bad**:
```php
test('property holds')->repeat(1); // Not property-based!
```

✅ **Good**:
```php
test('property holds')->repeat(100); // Statistical confidence
```

### 3. Missing Negative Cases

❌ **Bad**:
```php
// Only tests that own tenant data is visible
expect($visible)->each(fn ($item) => $item->tenant_id->toBe($tenantId));
```

✅ **Good**:
```php
// Tests both visibility and inaccessibility
expect($visible)->each(fn ($item) => $item->tenant_id->toBe($tenantId));
$otherTenantData->each(fn ($item) => expect(Model::find($item->id))->toBeNull());
```

### 4. Unclear Property Statements

❌ **Bad**:
```php
test('it works')
```

✅ **Good**:
```php
/**
 * Property: Managers only see buildings from their tenant
 */
test('BuildingResource automatically filters buildings by authenticated user tenant_id')
```

## Related Documentation

- [Filament Building Resource Tests](./filament-building-resource-tenant-scope-tests.md)
- [Multi-Tenancy Testing Strategy](./multi-tenancy-testing.md)
- [Pest Testing Framework](https://pestphp.com/)
- [Filament Testing Documentation](https://filamentphp.com/docs/3.x/panels/testing)

## Examples in Codebase

### Implemented Property Tests

1. **BuildingResource Tenant Scope** (`FilamentBuildingResourceTenantScopeTest.php`)
   - List page filtering
   - Edit page isolation
   - Superadmin access

2. **UserResource Validation** (`FilamentUserValidationConsistencyPropertyTest.php`)
   - Form validation consistency
   - Conditional tenant requirements
   - Null tenant allowance

3. **InvoiceResource Status** (`FilamentInvoiceStatusFilteringPropertyTest.php`)
   - Status filtering
   - Tenant isolation during filtering
   - Edge case handling

## Maintenance

### When to Add Property Tests

Add property-based tests when:
- Implementing multi-tenant features
- Adding authorization logic
- Creating Filament resources
- Implementing data filtering
- Adding validation rules

### When to Update Property Tests

Update tests when:
- Model relationships change
- Tenant scope logic is modified
- Authorization rules change
- Validation requirements evolve
- New user roles are added

### Test Review Checklist

- [ ] Properties clearly stated in comments
- [ ] Both positive and negative cases tested
- [ ] Appropriate iteration count (50-100)
- [ ] Randomized test data used
- [ ] Helper functions for common operations
- [ ] Meaningful assertion messages
- [ ] Edge cases considered
- [ ] Performance acceptable (<30s per test)
