# Provider-Tariff Relationship Tests - Quick Reference

## Test File
`tests/Feature/FilamentProviderTariffRelationshipVisibilityPropertyTest.php`

## Quick Run Commands

```bash
# Run all provider-tariff tests
php artisan test --filter=FilamentProviderTariffRelationshipVisibilityPropertyTest

# Run specific test
php artisan test --filter="ProviderResource displays all associated tariffs"

# Run with coverage
php artisan test --filter=FilamentProviderTariffRelationshipVisibilityPropertyTest --coverage
```

## Test Summary

| Test | Purpose | Iterations | Key Validation |
|------|---------|-----------|----------------|
| Basic Visibility | All tariffs display correctly | 100 | 1-10 tariffs per provider |
| Empty State | Handles providers with no tariffs | 100 | Zero tariffs, no errors |
| Provider Isolation | Each provider sees only own tariffs | 100 | No cross-provider leakage |
| Detail Accuracy | Tariff details are correct | 100 | All types and statuses |

## Critical Design Decisions

### Pagination Limit
**Tariff count limited to 1-10 per provider**

**Why?** Filament v4 defaults to 10 records per page. Testing with more requires pagination handling.

```php
// Correct: Respects pagination
$tariffsCount = fake()->numberBetween(1, 10);

// Incorrect: May cause pagination issues
$tariffsCount = fake()->numberBetween(1, 50);
```

### Authorization
**All tests require admin authentication**

```php
$admin = createAdminUser(); // Creates ADMIN role with null tenant_id
$this->actingAs($admin);
```

## Helper Functions

### createRandomTariffsForProvider()
Creates randomized tariffs for testing.

```php
$tariffs = createRandomTariffsForProvider($provider, 5);
// Returns array of 5 Tariff models with varied configurations
```

**Features**:
- Random flat/time-of-use types
- Realistic rate values (0.05-0.30 EUR)
- Varied date ranges
- 70% chance of no end date

### createAdminUser()
Creates admin user for testing.

```php
$admin = createAdminUser();
// Returns User with ADMIN role and null tenant_id
```

## Common Patterns

### Test a Relationship Manager
```php
$relationManager = Livewire::test(
    ProviderResource\RelationManagers\TariffsRelationManager::class,
    [
        'ownerRecord' => $provider,
        'pageClass' => ProviderResource\Pages\EditProvider::class,
    ]
);

$tableRecords = $relationManager->instance()->getTableRecords();
expect($tableRecords)->toHaveCount($expectedCount);
```

### Verify Tariff Details
```php
$foundTariff = $providerTariffs->firstWhere('id', $createdTariff->id);

expect($foundTariff)->not->toBeNull();
expect($foundTariff->name)->toBe($createdTariff->name);
expect($foundTariff->configuration['type'])->toBe('flat'); // or 'time_of_use'
expect($foundTariff->provider_id)->toBe($provider->id);
```

## Troubleshooting

### "Too many records" error
**Solution**: Ensure tariff count ≤ 10

```php
// Fix this:
$tariffsCount = fake()->numberBetween(1, 15); // ❌

// To this:
$tariffsCount = fake()->numberBetween(1, 10); // ✅
```

### Relationship manager not accessible
**Solution**: Verify admin authentication

```php
$admin = createAdminUser();
$this->actingAs($admin); // Must be called before testing
```

### Tariff details mismatch
**Solution**: Check date formatting

```php
// Use consistent formatting
expect($foundTariff->active_from->format('Y-m-d H:i:s'))
    ->toBe($createdTariff->active_from->format('Y-m-d H:i:s'));
```

## Test Data Patterns

### Flat Rate Tariff
```php
[
    'type' => 'flat',
    'currency' => 'EUR',
    'rate' => 0.1234, // 4 decimal places
]
```

### Time of Use Tariff
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

## Performance

- **Per iteration**: ~50-100ms
- **Per test case**: ~5-10 seconds (100 iterations)
- **Full suite**: ~20-40 seconds (400 iterations)

## Coverage

- **Total iterations**: 400
- **Total assertions**: 6,500-10,500
- **Test cases**: 4
- **Property validated**: Property 19 (Requirements 8.4)

## Related Files

- **Test**: `tests/Feature/FilamentProviderTariffRelationshipVisibilityPropertyTest.php`
- **Resource**: `app/Filament/Resources/ProviderResource.php`
- **Relation Manager**: `app/Filament/Resources/ProviderResource/RelationManagers/TariffsRelationManager.php`
- **Policy**: `app/Policies/ProviderPolicy.php`
- **Full Documentation**: `docs/testing/provider-tariff-relationship-tests.md`

## Maintenance Checklist

- [ ] Verify tariff count limit (1-10) if pagination changes
- [ ] Update rate ranges if business rules change
- [ ] Review date patterns annually
- [ ] Check authorization if ProviderPolicy changes
- [ ] Update test data if Tariff model structure changes

## Quick Debug Commands

```php
// Inspect generated tariffs
dump($createdTariffs);

// Check relationship loading
dump($component->instance()->record->tariffs);

// Verify table records
dump($tableRecords->toArray());

// Check authentication
dump($this->actingAs($admin));
```

## Key Takeaways

1. ✅ Always limit tariffs to 1-10 per provider
2. ✅ Authenticate as admin before testing
3. ✅ Use helper functions for consistent test data
4. ✅ Verify both relationship and relation manager
5. ✅ Test all tariff types and statuses
6. ✅ Validate provider isolation
7. ✅ Check empty state handling

## Last Updated
2024-11-27: Pagination optimization and comprehensive documentation
