# DistributionMethod Test Coverage

## Overview

Comprehensive test suite for the `DistributionMethod` enum covering all cases, methods, and behaviors.

**Test File:** `tests/Unit/Enums/DistributionMethodTest.php`  
**Test Framework:** Pest PHP  
**Total Tests:** 22  
**Total Assertions:** 70  
**Coverage:** 100% of enum methods

## Test Structure

### 1. Enum Structure Tests (2 tests)

#### Test: has all expected cases
**Purpose:** Verify all enum cases exist with correct values  
**Assertions:** 5
```php
it('has all expected cases', function () {
    $cases = DistributionMethod::cases();
    $values = array_map(fn ($case) => $case->value, $cases);
    
    expect($values)->toContain('equal');
    expect($values)->toContain('area');
    expect($values)->toContain('by_consumption');
    expect($values)->toContain('custom_formula');
    expect($cases)->toHaveCount(4);
});
```

### 2. Area Data Requirements (2 tests)

#### Test: correctly identifies methods that require area data
**Purpose:** Verify AREA method requires area data  
**Assertions:** 1
```php
it('correctly identifies methods that require area data', function () {
    expect(DistributionMethod::AREA->requiresAreaData())->toBeTrue();
});
```

#### Test: correctly identifies methods that do not require area data
**Purpose:** Verify non-area methods don't require area data  
**Assertions:** 3
```php
it('correctly identifies methods that do not require area data', function () {
    expect(DistributionMethod::EQUAL->requiresAreaData())->toBeFalse();
    expect(DistributionMethod::BY_CONSUMPTION->requiresAreaData())->toBeFalse();
    expect(DistributionMethod::CUSTOM_FORMULA->requiresAreaData())->toBeFalse();
});
```

### 3. Consumption Data Requirements (2 tests)

#### Test: correctly identifies methods that require consumption data
**Purpose:** Verify BY_CONSUMPTION method requires consumption data  
**Assertions:** 1

#### Test: correctly identifies methods that do not require consumption data
**Purpose:** Verify non-consumption methods don't require consumption data  
**Assertions:** 3

### 4. Custom Formula Support (2 tests)

#### Test: correctly identifies methods that support custom formulas
**Purpose:** Verify CUSTOM_FORMULA method supports formulas  
**Assertions:** 1

#### Test: correctly identifies methods that do not support custom formulas
**Purpose:** Verify other methods don't support formulas  
**Assertions:** 3

### 5. Supported Area Types (3 tests)

#### Test: returns area types for area-based distribution
**Purpose:** Verify AREA method returns correct area types  
**Assertions:** 4

#### Test: returns empty array for non-area-based methods
**Purpose:** Verify non-area methods return empty array  
**Assertions:** 3

#### Test: returns translated labels for area types
**Purpose:** Verify area type labels are translated strings  
**Assertions:** 2 (per area type)

### 6. Labels and Descriptions (3 tests)

#### Test: provides labels for all cases
**Purpose:** Verify all enum cases have non-empty labels  
**Assertions:** 8 (2 per case)

#### Test: provides descriptions for all cases
**Purpose:** Verify all enum cases have non-empty descriptions  
**Assertions:** 8 (2 per case)

#### Test: has unique labels for each case
**Purpose:** Verify no duplicate labels exist  
**Assertions:** 1

### 7. Backward Compatibility (2 tests)

#### Test: maintains existing EQUAL and AREA methods
**Purpose:** Verify original enum cases preserved  
**Assertions:** 2

#### Test: preserves requiresAreaData method behavior
**Purpose:** Verify original method behavior unchanged  
**Assertions:** 2

### 8. New Capabilities (5 tests)

#### Test: adds BY_CONSUMPTION method
**Purpose:** Verify new consumption-based case  
**Assertions:** 2

#### Test: adds CUSTOM_FORMULA method
**Purpose:** Verify new custom formula case  
**Assertions:** 2

#### Test: adds requiresConsumptionData method
**Purpose:** Verify new method exists  
**Assertions:** 1

#### Test: adds supportsCustomFormulas method
**Purpose:** Verify new method exists  
**Assertions:** 1

#### Test: adds getSupportedAreaTypes method
**Purpose:** Verify new method exists  
**Assertions:** 1

### 9. Method Combinations (2 tests)

#### Test: ensures methods have mutually exclusive primary characteristics
**Purpose:** Verify each method has at most one primary characteristic  
**Assertions:** 4 (1 per case)

#### Test: EQUAL method is the simplest with no special requirements
**Purpose:** Verify EQUAL has no requirements  
**Assertions:** 3

## Running Tests

### Run All Tests
```bash
php artisan test --filter=DistributionMethodTest
```

### Run Specific Test Group
```bash
php artisan test --filter="DistributionMethod.*area data"
php artisan test --filter="DistributionMethod.*consumption"
php artisan test --filter="DistributionMethod.*formula"
```

### Run with Coverage
```bash
php artisan test --filter=DistributionMethodTest --coverage
```

## Test Patterns

### Property-Based Testing
Tests verify properties hold for all enum cases:
```php
foreach (DistributionMethod::cases() as $method) {
    // Test property for each case
}
```

### Mutually Exclusive Characteristics
Tests ensure clean separation of concerns:
```php
$characteristics = array_filter([
    $requiresArea,
    $requiresConsumption,
    $supportsFormula,
]);
expect(count($characteristics))->toBeLessThanOrEqual(1);
```

## Related Documentation

- [DistributionMethod Enum](../enums/DISTRIBUTION_METHOD.md)
- [Testing Guide](./overview.md)
- [Enum Testing Patterns](./enum-testing-guide.md)
