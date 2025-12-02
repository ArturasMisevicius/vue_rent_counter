# Building Tenant Scope Tests - Quick Reference

## Test Files Overview

### Simple Tests (Smoke Testing)
**File**: `tests/Feature/BuildingTenantScopeSimpleTest.php`
- **Purpose**: Quick verification of basic tenant isolation
- **Tests**: 3 test cases
- **Duration**: ~0.5s
- **Use**: Pre-commit hooks, debugging, learning

### Property-Based Tests (Comprehensive)
**File**: `tests/Feature/FilamentBuildingResourceTenantScopeTest.php`
- **Purpose**: Statistical confidence in tenant isolation
- **Tests**: 3 test cases × 100 iterations = 300 tests
- **Duration**: ~15s
- **Use**: Full test suite, production validation

## Quick Commands

```bash
# Run simple tests (fast)
php artisan test --filter=BuildingTenantScopeSimpleTest

# Run property-based tests (comprehensive)
php artisan test --filter=FilamentBuildingResourceTenantScopeTest

# Run all Building tenant scope tests
php artisan test --filter="Building.*Tenant.*Scope"

# Run specific test
php artisan test --filter="manager can only see their own tenant buildings"
```

## Test Coverage Matrix

| Scenario | Simple Tests | Property Tests |
|----------|--------------|----------------|
| Manager sees only own buildings | ✅ | ✅ |
| Superadmin sees all buildings | ✅ | ✅ |
| Direct ID access prevention | ✅ | ✅ |
| Filament list page filtering | ❌ | ✅ |
| Filament edit page isolation | ❌ | ✅ |
| Random data scenarios | ❌ | ✅ |
| Edge cases | ❌ | ✅ |

## Common Assertions

### Manager Isolation
```php
// Manager sees only their buildings
expect(Building::all())->toHaveCount(1);

// Cross-tenant building is inaccessible
expect(Building::find($otherTenantBuildingId))->toBeNull();
```

### Superadmin Access
```php
// Superadmin sees all buildings
expect(Building::all())->toHaveCount($totalBuildings);

// Can access any building by ID
expect(Building::find($anyBuildingId))->not->toBeNull();
```

### Filament Resource
```php
// List page shows only tenant buildings
$component = Livewire::test(ListBuildings::class);
expect($component->instance()->getTableRecords())->toHaveCount($expectedCount);

// Edit page blocks cross-tenant access
expect(fn () => Livewire::test(EditBuilding::class, ['record' => $crossTenantId]))
    ->toThrow(ModelNotFoundException::class);
```

## Debugging Checklist

### Test Fails: Manager sees wrong buildings

- [ ] Check Building model uses `BelongsToTenant` trait
- [ ] Verify `TenantScope` is registered in boot method
- [ ] Confirm authenticated user has correct `tenant_id`
- [ ] Check session has `tenant_id` set
- [ ] Verify scope applies WHERE clause: `dd(Building::query()->toSql())`

### Test Fails: Superadmin doesn't see all buildings

- [ ] Confirm superadmin has `tenant_id = null`
- [ ] Check `TenantScope` bypasses when `tenant_id` is null
- [ ] Verify `HierarchicalScope` allows superadmin access
- [ ] Check user role is `UserRole::SUPERADMIN`

### Test Fails: Cross-tenant access not blocked

- [ ] Verify `TenantScope` is active
- [ ] Check scope condition: `auth()->user()->tenant_id !== null`
- [ ] Confirm scope adds WHERE clause to find() queries
- [ ] Test without scope: `Building::withoutGlobalScope(TenantScope::class)->find($id)`

## Key Concepts

### Tenant Scope Mechanism
```php
// Automatic filtering by tenant_id
Building::all() 
// SQL: SELECT * FROM buildings WHERE tenant_id = ?

// Superadmin bypass (tenant_id = null)
Building::all() 
// SQL: SELECT * FROM buildings (no WHERE clause)
```

### Security Guarantees

✅ **Query-Level Protection**: Scope applies to all queries
✅ **ID-Based Access**: find() respects tenant scope
✅ **Collection Methods**: all(), get(), first() are filtered
✅ **Relationship Queries**: Eager loading respects scope

### Attack Prevention

🛡️ **URL Manipulation**: `/buildings/456` returns 404 if different tenant
🛡️ **API Injection**: `?building_id=456` returns null if different tenant
🛡️ **Form Tampering**: `<input value="456">` is filtered by scope
🛡️ **Direct Queries**: `Building::find(456)` returns null if different tenant

## Documentation Links

- **Simple Tests Guide**: [building-tenant-scope-simple-tests.md](./building-tenant-scope-simple-tests.md)
- **Property Tests Guide**: [filament-building-resource-tenant-scope-tests.md](./filament-building-resource-tenant-scope-tests.md)
- **Multi-Tenancy Architecture**: [../architecture/multi-tenancy.md](../architecture/multi-tenancy.md)
- **Tenant Scope Implementation**: [../architecture/tenant-scope.md](../architecture/tenant-scope.md)

## When to Use Each Test Suite

### Use Simple Tests When:
- Running pre-commit hooks (fast feedback)
- Debugging test failures (easy to understand)
- Learning the codebase (clear examples)
- Smoke testing after changes (quick verification)
- Demonstrating tenant isolation (documentation)

### Use Property-Based Tests When:
- Running full test suite (comprehensive coverage)
- Validating production readiness (statistical confidence)
- Testing edge cases (randomized scenarios)
- Verifying Filament integration (UI-level testing)
- Ensuring no regressions (thorough validation)

## Performance Comparison

| Metric | Simple Tests | Property Tests |
|--------|--------------|----------------|
| Test Count | 3 | 300 (3 × 100) |
| Execution Time | ~0.5s | ~15s |
| Database Operations | ~15 | ~1500 |
| Suitable for CI | ✅ Yes | ⚠️ Slow |
| Suitable for Pre-commit | ✅ Yes | ❌ Too slow |

## Maintenance

### Update Tests When:
- Building model structure changes
- Tenant scope logic is modified
- New user roles are added
- Multi-tenancy requirements evolve
- Security vulnerabilities are discovered

### Test Data Strategy:
- **Simple Tests**: Fixed tenant IDs (1, 2) for predictability
- **Property Tests**: Random tenant IDs (1-2000) for coverage
- **Both**: Use factories for consistent data generation
- **Both**: RefreshDatabase ensures clean state per test

## Related Test Suites

- **Property Tests**: `tests/Feature/PropertyMultiTenancyTest.php`
- **Invoice Tests**: `tests/Feature/InvoiceMultiTenancyTest.php`
- **Meter Reading Tests**: `tests/Feature/MeterReadingAuthorizationTest.php`
- **Multi-Tenancy Tests**: `tests/Feature/MultiTenancyTest.php`

## Support

For questions or issues:
1. Check test documentation in `docs/testing/`
2. Review architecture docs in `docs/architecture/`
3. Examine test implementation for examples
4. Run tests with `--verbose` flag for details
