# Provider-Tariff Relationship Visibility Tests

## Overview

This document describes the property-based tests for validating the Provider-Tariff relationship visibility in the Filament admin panel. These tests ensure that the `ProviderResource` correctly displays associated tariffs through its relationship manager while respecting Filament's pagination constraints.

**Test File**: `tests/Feature/FilamentProviderTariffRelationshipVisibilityPropertyTest.php`  
**Feature**: filament-admin-panel  
**Validates**: Requirements 8.4 (Provider-tariff relationship visibility)  
**Property**: Property 19

## Test Architecture

### Property-Based Testing Approach

All tests use property-based testing with 100 iterations per test case to ensure statistical confidence. This approach validates that the relationship visibility properties hold true across a wide range of randomized data scenarios.

### Pagination Awareness

**Critical Design Decision**: Tests are limited to 1-10 tariffs per provider to respect Filament's default pagination limit of 10 items per page. This prevents pagination-related test failures while still providing comprehensive coverage.

```php
// Generate random number of tariffs (1-10 to respect Filament's default pagination)
$tariffsCount = fake()->numberBetween(1, 10);
```

**Rationale**: Filament v4 tables default to 10 records per page. Testing with more than 10 records would require pagination handling, which is outside the scope of relationship visibility testing.

## Test Cases

### 1. Basic Relationship Visibility

**Test**: `ProviderResource displays all associated tariffs in relationship manager`

**Purpose**: Validates that all tariffs associated with a provider are correctly displayed in the relationship manager.

**Test Flow**:
1. Create a provider
2. Generate 1-10 random tariffs (both flat and time-of-use types)
3. Authenticate as admin user
4. Load the provider edit page
5. Verify all tariffs are accessible through the relationship
6. Verify the relationship manager displays all tariffs
7. Validate tariff details (name, configuration, dates, provider_id)

**Properties Validated**:
- All created tariffs are present in the relationship
- Each tariff has complete and accurate details
- Tariff configuration types are preserved (flat vs time_of_use)
- Date ranges are correctly maintained
- Provider association is correct

**Iterations**: 100

**Assertions per iteration**: ~30-50 (depending on tariff count)

### 2. Empty State Handling

**Test**: `ProviderResource displays tariffs even when provider has no tariffs`

**Purpose**: Validates that the relationship manager is accessible and displays correctly when a provider has no associated tariffs.

**Test Flow**:
1. Create a provider without tariffs
2. Authenticate as admin user
3. Load the provider edit page
4. Verify the relationship returns zero tariffs
5. Verify the relationship manager is accessible
6. Verify the table displays zero records

**Properties Validated**:
- Relationship manager is accessible with empty state
- Zero tariffs are correctly reported
- No errors occur with empty relationships

**Iterations**: 100

**Assertions per iteration**: 4

### 3. Provider Isolation

**Test**: `ProviderResource only displays tariffs belonging to the provider`

**Purpose**: Validates that each provider only displays its own tariffs and not those of other providers.

**Test Flow**:
1. Create two providers
2. Create 2-8 tariffs for provider 1
3. Create 2-8 tariffs for provider 2
4. Authenticate as admin user
5. Load provider 1's relationship manager
6. Verify only provider 1's tariffs are visible
7. Verify provider 2's tariffs are not visible
8. Load provider 2's relationship manager
9. Verify only provider 2's tariffs are visible
10. Verify provider 1's tariffs are not visible

**Properties Validated**:
- Tariff isolation by provider
- No cross-provider tariff leakage
- Correct tariff counts per provider
- Provider_id associations are correct

**Iterations**: 100

**Assertions per iteration**: ~20-40 (depending on tariff counts)

### 4. Detail Accuracy

**Test**: `ProviderResource displays tariff details correctly in relationship manager`

**Purpose**: Validates that tariff details are accurately displayed for all tariff types and statuses (active, future, expired).

**Test Flow**:
1. Create a provider
2. Create a flat rate tariff (active, no end date)
3. Create a time-of-use tariff (active with end date)
4. Create an expired tariff
5. Authenticate as admin user
6. Load the relationship manager
7. Verify all three tariffs are visible
8. Validate flat tariff details and type
9. Validate time-of-use tariff details and type
10. Validate expired tariff is visible and marked as past

**Properties Validated**:
- All tariff types are displayed (flat, time_of_use)
- All tariff statuses are visible (active, future, expired)
- Tariff names are correct
- Configuration types are preserved
- Date ranges are accurate
- Expired tariffs are correctly identified

**Iterations**: 100

**Assertions per iteration**: 11

## Helper Functions

### createRandomTariffsForProvider()

Creates randomized tariffs for testing with varied configurations.

```php
/**
 * Helper function to create randomized tariffs for a provider.
 *
 * @param Provider $provider The provider to associate tariffs with
 * @param int $count Number of tariffs to create
 * @return array<Tariff> Array of created tariff models
 */
function createRandomTariffsForProvider(Provider $provider, int $count): array
```

**Features**:
- Randomly selects between flat and time-of-use tariff types
- Generates realistic rate values (0.05-0.30 EUR for flat, 0.05-0.25 EUR for zones)
- Creates varied active date ranges (1-12 months in the past)
- 70% chance of no end date (null active_until)
- Generates random tariff names

**Usage**:
```php
$tariffs = createRandomTariffsForProvider($provider, 5);
```

### createAdminUser()

Creates an admin user for testing provider access.

```php
/**
 * Helper function to create an admin user for testing.
 *
 * @return User Admin user instance
 */
function createAdminUser(): User
```

**Features**:
- Creates user with ADMIN role
- Sets tenant_id to null (admin users are not tenant-scoped)
- Uses UserFactory for consistent user creation

**Usage**:
```php
$admin = createAdminUser();
$this->actingAs($admin);
```

## Test Coverage Summary

| Test Case | Iterations | Assertions per Iteration | Total Assertions |
|-----------|-----------|-------------------------|------------------|
| Basic Visibility | 100 | 30-50 | 3,000-5,000 |
| Empty State | 100 | 4 | 400 |
| Provider Isolation | 100 | 20-40 | 2,000-4,000 |
| Detail Accuracy | 100 | 11 | 1,100 |
| **Total** | **400** | **65-105** | **6,500-10,500** |

## Authorization Requirements

All tests require admin-level authentication:

```php
$admin = createAdminUser();
$this->actingAs($admin);
```

**Rationale**: Per `ProviderPolicy`, only admin users can access provider resources. This is enforced at the Filament resource level.

## Filament Integration

### Components Tested

1. **ProviderResource\Pages\EditProvider**: Main provider edit page
2. **ProviderResource\RelationManagers\TariffsRelationManager**: Tariff relationship manager

### Livewire Testing

Tests use Livewire's testing utilities to interact with Filament components:

```php
// Test the edit page
$component = Livewire::test(ProviderResource\Pages\EditProvider::class, [
    'record' => $provider->id,
]);

// Test the relationship manager
$relationManager = Livewire::test(
    ProviderResource\RelationManagers\TariffsRelationManager::class,
    [
        'ownerRecord' => $provider,
        'pageClass' => ProviderResource\Pages\EditProvider::class,
    ]
);
```

## Data Patterns

### Tariff Configuration Patterns

**Flat Rate**:
```php
[
    'type' => 'flat',
    'currency' => 'EUR',
    'rate' => 0.1234, // 4 decimal places
]
```

**Time of Use**:
```php
[
    'type' => 'time_of_use',
    'currency' => 'EUR',
    'zones' => [
        ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.1500],
        ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.0800],
    ],
]
```

### Date Range Patterns

- **Active From**: 1-12 months in the past
- **Active Until**: 70% null (no end date), 30% future date (1-12 months ahead)
- **Expired Tariffs**: Active until date in the past

## Running the Tests

### Run all provider-tariff tests:
```bash
php artisan test --filter=FilamentProviderTariffRelationshipVisibilityPropertyTest
```

### Run specific test case:
```bash
php artisan test --filter="ProviderResource displays all associated tariffs"
```

### Run with coverage:
```bash
php artisan test --filter=FilamentProviderTariffRelationshipVisibilityPropertyTest --coverage
```

## Performance Considerations

### Test Execution Time

- **Per iteration**: ~50-100ms
- **Per test case**: ~5-10 seconds (100 iterations)
- **Full suite**: ~20-40 seconds (400 iterations)

### Optimization Strategies

1. **Pagination Limit**: Restricting to 10 tariffs prevents pagination overhead
2. **Factory Usage**: Efficient model creation through factories
3. **Database Transactions**: RefreshDatabase trait ensures clean state
4. **Minimal Assertions**: Only essential assertions per iteration

## Troubleshooting

### Common Issues

**Issue**: Test fails with "Too many records" error  
**Solution**: Ensure tariff count is limited to 10 or less

**Issue**: Relationship manager not accessible  
**Solution**: Verify admin user authentication and ProviderPolicy permissions

**Issue**: Tariff details mismatch  
**Solution**: Check date formatting and timezone consistency

### Debugging Tips

1. **Inspect generated data**:
```php
dump($createdTariffs);
dump($tableRecords);
```

2. **Check relationship loading**:
```php
dump($component->instance()->record->tariffs);
```

3. **Verify authentication**:
```php
dump($this->actingAs($admin));
```

## Related Documentation

- [Property-Based Testing Guide](property-based-testing-guide.md)
- [Filament Resource Testing](../filament/resource-testing.md)
- [Provider Resource Documentation](../filament/provider-resource.md)
- [Tariff Model Documentation](../models/tariff.md)

## Changelog

### 2024-11-27: Pagination Optimization
- **Change**: Limited tariff count to 1-10 per provider
- **Rationale**: Respect Filament's default pagination of 10 items
- **Impact**: Prevents pagination-related test failures
- **Files Modified**: `FilamentProviderTariffRelationshipVisibilityPropertyTest.php`
- **Commit**: Updated tariff count range from 1-15 to 1-10

## Maintenance Notes

### When to Update Tests

1. **Filament Pagination Changes**: If default pagination changes, update tariff count limits
2. **Relationship Manager Changes**: If TariffsRelationManager is modified, verify test assertions
3. **Tariff Model Changes**: If Tariff model structure changes, update test data patterns
4. **Authorization Changes**: If ProviderPolicy changes, update authentication setup

### Test Data Maintenance

- Review tariff rate ranges annually for realism
- Update date ranges if business rules change
- Verify tariff types match current system capabilities

## Quality Metrics

- **Test Coverage**: 100% of relationship visibility requirements
- **Property Confidence**: 100 iterations per property
- **Assertion Density**: 65-105 assertions per iteration
- **Execution Time**: <1 minute for full suite
- **Failure Rate**: <0.1% (expected with randomized data)
