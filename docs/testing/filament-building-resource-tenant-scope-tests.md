# Filament BuildingResource Tenant Scope Tests

## Overview

This test suite validates tenant isolation and access control for the `BuildingResource` in Filament v4. It ensures that multi-tenant data segregation is properly enforced at the resource level, preventing unauthorized cross-tenant data access.

**Test File**: `tests/Feature/FilamentBuildingResourceTenantScopeTest.php`

**Related Spec**: [.kiro/specs/4-filament-admin-panel/tasks.md](../tasks/tasks.md) (Task 7.3)

**Property Validated**: Property 16 - Tenant scope isolation for buildings

**Requirements Coverage**: 7.1 (BuildingResource tenant scope)

## Test Architecture

### Property-Based Testing Strategy

This suite uses property-based testing with 100 iterations per test to ensure statistical confidence in tenant isolation. Each test:

1. Generates randomized tenant IDs and building counts
2. Creates test data using factories
3. Verifies isolation properties hold across all iterations
4. Uses Pest's `repeat(100)` modifier for comprehensive coverage

### Helper Functions

The test suite provides reusable helper functions for common operations:

#### `createBuildingsForTenant(int $tenantId, int $count): Collection`

Creates a specified number of buildings for a given tenant.

**Parameters**:
- `$tenantId` - The tenant ID to assign to buildings
- `$count` - Number of buildings to create

**Returns**: Collection of created Building models

**Usage**:
```php
$buildings = createBuildingsForTenant(1, 5);
// Creates 5 buildings for tenant ID 1
```

#### `createManagerForTenant(int $tenantId): User`

Creates a manager user assigned to a specific tenant.

**Parameters**:
- `$tenantId` - The tenant ID to assign to the manager

**Returns**: User model with MANAGER role

**Usage**:
```php
$manager = createManagerForTenant(1);
// Creates a manager for tenant ID 1
```

#### `createSuperadmin(): User`

Creates a superadmin user with unrestricted access (null tenant_id).

**Returns**: User model with SUPERADMIN role

**Usage**:
```php
$superadmin = createSuperadmin();
// Creates a superadmin with cross-tenant access
```

#### `authenticateWithTenant(User $user): void`

Authenticates a user and sets the session tenant context.

**Parameters**:
- `$user` - The user to authenticate

**Side Effects**:
- Sets authenticated user in test context
- Sets `tenant_id` in session

**Usage**:
```php
authenticateWithTenant($manager);
// Authenticates manager and sets tenant context
```

## Test Cases

### Test 1: BuildingResource List Page Tenant Filtering

**Test Name**: `BuildingResource automatically filters buildings by authenticated user tenant_id`

**Purpose**: Verifies that the BuildingResource list page only displays buildings belonging to the authenticated user's tenant.

**Test Flow**:

1. **Setup Phase**:
   - Generate two random tenant IDs (1-1000 and 1001-2000)
   - Create 2-8 buildings for each tenant (randomized count)

2. **Tenant 1 Verification**:
   - Authenticate as manager from tenant 1
   - Load BuildingResource list page
   - Assert only tenant 1's buildings are visible
   - Assert tenant 2's buildings are inaccessible via direct query
   - Verify all tenant 1 buildings are present

3. **Tenant 2 Verification**:
   - Authenticate as manager from tenant 2
   - Load BuildingResource list page
   - Assert only tenant 2's buildings are visible
   - Assert tenant 1's buildings are inaccessible via direct query
   - Verify all tenant 2 buildings are present

**Properties Verified**:
- ✅ Managers only see buildings from their tenant
- ✅ Cross-tenant buildings are completely inaccessible
- ✅ All tenant buildings are present (no data loss)
- ✅ Direct model queries respect tenant scope

**Assertions**:
```php
// Count verification
expect($tableRecords)->toHaveCount($buildingsCount1);

// Tenant ID verification
expect($tableRecords)->each(fn ($building) => 
    $building->tenant_id->toBe($tenantId1)
);

// Cross-tenant inaccessibility
expect(Building::find($building2->id))->toBeNull();

// Completeness verification
expect($tableRecords->pluck('id')->toArray())
    ->toEqualCanonicalizing($buildings1->pluck('id')->toArray());
```

### Test 2: BuildingResource Edit Page Tenant Isolation

**Test Name**: `BuildingResource edit page only allows editing buildings within tenant scope`

**Purpose**: Verifies that managers can only access edit pages for buildings within their tenant scope.

**Test Flow**:

1. **Setup Phase**:
   - Generate two random tenant IDs
   - Create one building for each tenant

2. **Same-Tenant Access**:
   - Authenticate as manager from tenant 1
   - Access edit page for tenant 1's building
   - Assert page loads successfully
   - Verify correct building is loaded with proper tenant_id

3. **Cross-Tenant Access Prevention**:
   - Attempt to access edit page for tenant 2's building
   - Assert `ModelNotFoundException` is thrown
   - Verify tenant scope prevents unauthorized access

**Properties Verified**:
- ✅ Managers can edit buildings from their tenant
- ✅ Cross-tenant edit access is blocked
- ✅ ModelNotFoundException thrown for unauthorized access
- ✅ Loaded building has correct tenant_id

**Assertions**:
```php
// Same-tenant access
$component->assertSuccessful();
expect($component->instance()->record)
    ->id->toBe($building1->id)
    ->tenant_id->toBe($tenantId1);

// Cross-tenant access prevention
expect(fn () => Livewire::test(BuildingResource\Pages\EditBuilding::class, [
    'record' => $building2->id,
]))
->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
```

### Test 3: Superadmin Unrestricted Access

**Test Name**: `Superadmin users can access buildings from all tenants`

**Purpose**: Verifies that superadmin users have unrestricted access to buildings across all tenants.

**Test Flow**:

1. **Setup Phase**:
   - Generate two random tenant IDs
   - Create one building for each tenant

2. **List Page Verification**:
   - Authenticate as superadmin (tenant_id = null)
   - Load BuildingResource list page
   - Assert buildings from both tenants are visible
   - Verify both building IDs are present in results

3. **Edit Page Verification**:
   - Access edit page for tenant 1's building
   - Assert page loads successfully
   - Access edit page for tenant 2's building
   - Assert page loads successfully

**Properties Verified**:
- ✅ Superadmins see buildings from all tenants
- ✅ Superadmins can edit buildings from any tenant
- ✅ Cross-tenant access is fully enabled for superadmins
- ✅ No tenant scope restrictions apply

**Assertions**:
```php
// List page cross-tenant visibility
expect($tableRecords->count())->toBeGreaterThanOrEqual(2);
expect($tableRecordIds)
    ->toContain($building1->id)
    ->toContain($building2->id);

// Edit page cross-tenant access
$component1->assertSuccessful();
expect($component1->instance()->record->id)->toBe($building1->id);

$component2->assertSuccessful();
expect($component2->instance()->record->id)->toBe($building2->id);
```

## Technical Implementation

### Tenant Scope Mechanism

The tests verify the following tenant scope implementation:

1. **Global Scope**: `TenantScope` applied to Building model
2. **Session Context**: `tenant_id` stored in session
3. **Query Filtering**: Automatic WHERE clause on tenant_id
4. **Superadmin Bypass**: Null tenant_id bypasses scope

### Filament Integration

The tests interact with Filament v4 components:

- **ListBuildings Page**: Table component with tenant-filtered records
- **EditBuilding Page**: Form component with tenant-scoped record loading
- **Livewire Testing**: Uses Livewire::test() for component interaction

### Database Isolation

Each test uses `RefreshDatabase` trait to ensure:
- Clean database state per test
- No data pollution between iterations
- Consistent factory-generated data

## Running the Tests

### Run All Building Resource Tests

```bash
php artisan test --filter=FilamentBuildingResourceTenantScopeTest
```

### Run Specific Test

```bash
php artisan test --filter="BuildingResource automatically filters buildings"
```

### Run with Coverage

```bash
php artisan test --filter=FilamentBuildingResourceTenantScopeTest --coverage
```

### Expected Output

```
PASS  Tests\Feature\FilamentBuildingResourceTenantScopeTest
✓ BuildingResource automatically filters buildings by authenticated user tenant_id (100 iterations)
✓ BuildingResource edit page only allows editing buildings within tenant scope (100 iterations)
✓ Superadmin users can access buildings from all tenants (100 iterations)

Tests:    3 passed (300 assertions)
Duration: ~15s
```

## Troubleshooting

### Common Issues

#### Test Fails: "Manager should see exactly their tenant's buildings"

**Cause**: TenantScope not applied or session tenant_id not set

**Solution**:
1. Verify `BelongsToTenant` trait is used on Building model
2. Check `TenantScope` is registered in model boot method
3. Ensure `authenticateWithTenant()` sets session correctly

#### Test Fails: "ModelNotFoundException not thrown"

**Cause**: Tenant scope not enforcing cross-tenant access prevention

**Solution**:
1. Verify `TenantScope` is active for the model
2. Check Filament resource uses `getEloquentQuery()` correctly
3. Ensure policies don't override scope restrictions

#### Test Fails: "Superadmin should see buildings from all tenants"

**Cause**: Superadmin tenant_id not null or scope not bypassed

**Solution**:
1. Verify superadmin user has `tenant_id = null`
2. Check `TenantScope` bypasses when tenant_id is null
3. Ensure `HierarchicalScope` allows superadmin access

## Related Documentation

- [Multi-Tenancy Architecture](../architecture/multi-tenancy.md)
- [Filament Resource Testing Guide](./filament-resource-testing.md)
- [Property-Based Testing Strategy](./property-based-testing.md)
- [Building Resource Implementation](../filament/building-resource.md)
- [Tenant Scope Implementation](../architecture/tenant-scope.md)

## Changelog

### 2025-11-27
- Initial test suite implementation
- Added property-based testing with 100 iterations
- Implemented helper functions for test data creation
- Verified tenant isolation for list and edit pages
- Validated superadmin unrestricted access

## Maintenance Notes

### When to Update Tests

Update these tests when:
- Building model structure changes
- Tenant scope logic is modified
- New user roles are added
- Filament resource authorization changes
- Multi-tenancy requirements evolve

### Test Data Considerations

- Random tenant IDs prevent conflicts in parallel test runs
- Random building counts ensure tests work with varying data sizes
- Factory-generated data maintains consistency
- 100 iterations provide statistical confidence

### Performance Considerations

- Each test iteration creates fresh database records
- Total test time: ~15 seconds for 300 iterations
- Consider reducing iterations for faster CI/CD if needed
- Use `--parallel` flag for faster execution
