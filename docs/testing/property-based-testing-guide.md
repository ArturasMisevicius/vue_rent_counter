# Property-Based Testing Guide

## Overview

Property-based testing validates system invariants by generating random test data and ensuring properties hold across all scenarios. This approach provides higher confidence than traditional example-based tests by exploring edge cases automatically.

## Core Concepts

### Properties vs Examples
- **Example-based tests**: Test specific scenarios with fixed inputs
- **Property-based tests**: Test invariants that should hold for all valid inputs

### Test Structure
```php
public function test_property_name(): void
{
    $this->runPropertyTest($iterations, function (): void {
        // Generate random test data
        $input = $this->generateRandomInput();
        
        // Execute system under test
        $result = $this->systemUnderTest($input);
        
        // Assert invariant holds
        $this->assertInvariant($result, $input);
    });
}
```

## Heating Integration Property Tests

### Test Class: HeatingIntegrationAccuracyPropertyTest

**Purpose**: Validates that the universal billing system produces identical results to the existing heating calculator while preserving all behavioral characteristics.

**Key Invariants Tested**:

1. **Calculation Accuracy** (50 iterations)
   - Universal system matches existing calculator exactly
   - Base and consumption charges preserved
   - Floating-point precision: ±0.01

2. **Seasonal Adjustments** (30 iterations)
   - Winter/summer ratios preserved between systems
   - Seasonal multipliers applied consistently
   - Tolerance: ±10% for ratio comparisons

3. **Building Factors** (25 iterations)
   - Building age, size, efficiency factors preserved
   - Old vs new building cost relationships maintained
   - Tolerance: ±15% for building factor ratios

4. **Distribution Methods** (20 iterations)
   - Equal and area-based distribution accuracy
   - Shared cost allocation matches exactly
   - Per-property amounts within ±0.01

5. **Calculation Consistency** (15 iterations)
   - Deterministic behavior across periods
   - Same inputs produce same outputs
   - Pricing model consistency maintained

6. **Tariff Snapshots** (10 iterations)
   - Invoice immutability preserved
   - Configuration snapshots created
   - Historical rate preservation

### Test Data Generation

**Property Generation**:
```php
private function generatePropertyWithHeatingSetup(): Property
{
    // Creates realistic property with:
    // - Building (1970-2020, 500-2000 sqm)
    // - Property (40-150 sqm)
    // - Heating meter configuration
}
```

**Consumption Data**:
```php
private function generateHeatingConsumptionData(Property $property, BillingPeriod $period): UniversalConsumptionData
{
    // Generates realistic consumption based on:
    // - Property size (2-8 kWh per sqm base)
    // - Seasonal factors (winter: 1.2-2.0x, summer: 0.2-0.6x)
    // - Random variation within realistic bounds
}
```

**Billing Periods**:
- Random periods: 2023-2024, any month
- Winter periods: Oct-Apr (heating season)
- Summer periods: May-Sep (reduced heating)

## Running Property Tests

### Command Line
```bash
# Run all property tests
php artisan test --filter=PropertyTest

# Run specific heating integration tests
php artisan test tests/Property/HeatingIntegrationAccuracyPropertyTest.php

# Run with verbose output
php artisan test tests/Property/HeatingIntegrationAccuracyPropertyTest.php --verbose
```

### Test Configuration
```php
// Test constants for consistency
private const FLOATING_POINT_PRECISION = 0.01;
private const SEASONAL_RATIO_TOLERANCE = 0.1;
private const BUILDING_FACTOR_TOLERANCE = 0.15;
```

## Interpreting Results

### Success Indicators
- All iterations pass without exceptions
- Assertions validate within tolerance ranges
- No calculation discrepancies detected

### Failure Analysis
When property tests fail:

1. **Check iteration number**: Identifies specific scenario that failed
2. **Review assertion message**: Contains context about failure
3. **Examine generated data**: Random inputs that caused failure
4. **Validate system behavior**: Ensure both systems handle edge cases

### Common Failure Patterns
- **Floating-point precision**: Adjust tolerance constants
- **Seasonal edge cases**: Verify month boundary handling
- **Building factor extremes**: Check very old/new buildings
- **Distribution rounding**: Ensure consistent rounding rules

## Best Practices

### Test Design
- Use sufficient iterations for statistical confidence
- Generate realistic test data within domain bounds
- Include edge cases in random generation
- Validate both positive and negative scenarios

### Assertion Strategy
- Compare relative relationships, not absolute values
- Use appropriate tolerance for floating-point comparisons
- Validate structural properties (snapshots, configurations)
- Check both success and error conditions

### Maintenance
- Update tests when business rules change
- Adjust tolerances based on system precision requirements
- Add new properties as system evolves
- Monitor test execution time and optimize if needed

## Integration with CI/CD

Property tests run as part of the standard test suite:
```bash
# In CI pipeline
composer test:property
```

**Performance Considerations**:
- Heating integration tests: ~2-3 minutes
- Total property test suite: ~10-15 minutes
- Parallel execution supported for faster feedback

## Related Documentation
- [Universal Utility Management Architecture](../architecture/universal-utility-management.md)
- [Heating Calculator Integration](../services/heating-calculator-service.md)
- [Testing Standards](../testing/testing-standards.md)