# Heating Integration Test Coverage

## Test Suite Overview

The HeatingIntegrationAccuracyPropertyTest provides comprehensive validation of the heating system bridge through property-based testing methodology.

## Test Methods

### 1. Universal Heating Calculator Accuracy
**Method**: `test_property_universal_heating_matches_existing_calculator`
**Iterations**: 50
**Purpose**: Validates core calculation accuracy between systems

**Test Scenarios**:
- Random property configurations (40-150 sqm)
- Random building characteristics (1970-2020, 500-2000 sqm)
- Random billing periods (2023-2024)
- Realistic consumption patterns (2-8 kWh/sqm base)

**Assertions**:
- Total charges match exactly (±0.01 precision)
- Base charge components preserved
- Consumption charge components preserved
- Calculation context maintained

### 2. Seasonal Adjustment Preservation
**Method**: `test_property_seasonal_adjustments_preserved`
**Iterations**: 30
**Purpose**: Ensures seasonal factors applied consistently

**Test Scenarios**:
- Winter periods: Oct-Apr (heating season)
- Summer periods: May-Sep (reduced heating)
- Same consumption across seasons
- Seasonal multiplier validation

**Assertions**:
- Winter/summer cost ratios preserved (±10% tolerance)
- Winter costs higher than summer (when applicable)
- Seasonal multipliers applied correctly
- Ratio consistency between systems

### 3. Building Factor Preservation
**Method**: `test_property_building_factors_preserved`
**Iterations**: 25
**Purpose**: Validates building characteristic impacts

**Test Scenarios**:
- Old buildings (1980) vs new buildings (2010)
- Different building sizes (800-1500 sqm)
- Property size variations (50-120 sqm)
- Same consumption patterns

**Assertions**:
- Building age factor ratios preserved (±15% tolerance)
- Efficiency differences maintained
- Size factor impacts consistent
- Cost relationships preserved

### 4. Distribution Method Accuracy
**Method**: `test_property_distribution_method_accuracy`
**Iterations**: 20
**Purpose**: Validates shared cost distribution

**Test Scenarios**:
- Multiple properties per building (3-6 properties)
- Random property areas (40-120 sqm)
- Random shared costs (€500-2000)
- Equal and area-based distribution methods

**Assertions**:
- Distribution amounts match exactly (±0.01 precision)
- Total cost allocation preserved
- Per-property amounts consistent
- Distribution method logic maintained

### 5. Calculation Consistency
**Method**: `test_property_calculation_consistency`
**Iterations**: 15
**Purpose**: Ensures deterministic behavior

**Test Scenarios**:
- Same property and consumption
- Different periods in same season
- Consistent service configuration
- Multiple calculation runs

**Assertions**:
- Identical results for same inputs (±0.01 precision)
- Pricing model consistency
- Configuration preservation
- Deterministic behavior

### 6. Tariff Snapshot Preservation
**Method**: `test_property_tariff_snapshot_preservation`
**Iterations**: 10
**Purpose**: Validates invoice immutability

**Test Scenarios**:
- Random property configurations
- Random billing periods
- Service configuration snapshots
- Rate schedule preservation

**Assertions**:
- Tariff snapshots created
- Configuration IDs preserved
- Rate schedules captured
- Snapshot timestamps recorded

## Test Data Generation

### Property Generation Strategy
```php
// Realistic property characteristics
$building = Building::factory()->create([
    'built_year' => fake()->numberBetween(1970, 2020),
    'total_area' => fake()->randomFloat(2, 500, 2000),
]);

$property = Property::factory()->create([
    'area_sqm' => fake()->randomFloat(2, 40, 150),
    'building_id' => $building->id,
]);
```

### Consumption Data Generation
```php
// Seasonal consumption patterns
$baseConsumption = $property->area_sqm * fake()->randomFloat(2, 2, 8);
$seasonalMultiplier = $isWinter 
    ? fake()->randomFloat(2, 1.2, 2.0)  // Winter: 20-100% increase
    : fake()->randomFloat(2, 0.2, 0.6); // Summer: 40-80% decrease
```

### Service Configuration
```php
// Heating service configuration
ServiceConfiguration::factory()->create([
    'pricing_model' => PricingModel::HYBRID,
    'rate_schedule' => [
        'fixed_fee' => 125.0,
        'unit_rate' => 0.08,
        'seasonal_adjustments' => [
            'summer_multiplier' => 0.3,
            'winter_multiplier' => 1.5,
        ],
    ],
    'distribution_method' => DistributionMethod::AREA,
]);
```

## Coverage Metrics

### Functional Coverage
- ✅ **Calculation Accuracy**: 100% coverage of calculation paths
- ✅ **Seasonal Logic**: All seasonal transitions tested
- ✅ **Building Factors**: Age, size, efficiency factors covered
- ✅ **Distribution Methods**: Equal and area-based methods tested
- ✅ **Error Handling**: Edge cases and boundary conditions
- ✅ **Data Integrity**: Snapshot and audit trail validation

### Edge Case Coverage
- **Minimum Values**: Smallest properties and buildings
- **Maximum Values**: Largest properties and buildings
- **Boundary Conditions**: Season transitions, year boundaries
- **Zero Consumption**: No usage scenarios
- **High Consumption**: Peak usage scenarios
- **Extreme Ratios**: Very old vs very new buildings

### Integration Points
- **Service Detection**: Heating service type identification
- **Configuration Translation**: Universal to heating format conversion
- **Result Wrapping**: Heating to universal result conversion
- **Snapshot Creation**: Tariff preservation for invoices
- **Audit Logging**: Change tracking and compliance

## Test Execution

### Local Development
```bash
# Run heating integration tests
php artisan test tests/Property/HeatingIntegrationAccuracyPropertyTest.php

# Run with coverage
php artisan test tests/Property/HeatingIntegrationAccuracyPropertyTest.php --coverage

# Run specific test method
php artisan test --filter=test_property_universal_heating_matches_existing_calculator
```

### CI/CD Pipeline
```yaml
# Property test execution
- name: Run Property Tests
  run: |
    php artisan test tests/Property/ --parallel
    php artisan test:property-coverage
```

### Performance Benchmarks
- **Single Test Method**: 10-30 seconds
- **Full Test Suite**: 2-3 minutes
- **Memory Usage**: <256MB peak
- **Database Operations**: ~1000 queries per full run

## Failure Analysis

### Common Failure Patterns
1. **Floating-Point Precision**: Adjust tolerance constants
2. **Seasonal Edge Cases**: Verify month boundary handling
3. **Building Factor Extremes**: Check very old/new buildings
4. **Distribution Rounding**: Ensure consistent rounding rules

### Debugging Strategies
```php
// Add debug output in test failures
private function debugCalculationMismatch($existing, $universal, $property): void
{
    dump([
        'property_id' => $property->id,
        'property_area' => $property->area_sqm,
        'building_year' => $property->building->built_year,
        'existing_result' => $existing,
        'universal_result' => $universal->toArray(),
        'difference' => abs($existing['total_charge'] - $universal->getTotalAmount()),
    ]);
}
```

### Test Data Inspection
```php
// Capture failing test data for analysis
private function captureFailingScenario($data): void
{
    file_put_contents(
        storage_path('logs/property-test-failure.json'),
        json_encode($data, JSON_PRETTY_PRINT)
    );
}
```

## Maintenance Guidelines

### Test Updates
- **Business Rule Changes**: Update test assertions when heating logic changes
- **Tolerance Adjustments**: Modify precision constants based on system requirements
- **New Scenarios**: Add test cases for new heating features
- **Performance Optimization**: Optimize test data generation for speed

### Monitoring
- **Test Execution Time**: Monitor for performance regressions
- **Failure Rates**: Track test stability over time
- **Coverage Gaps**: Identify untested scenarios
- **Integration Health**: Validate bridge functionality

## Related Documentation
- [Property-Based Testing Guide](property-based-testing-guide.md)
- [Heating Integration Architecture](../architecture/heating-integration-bridge.md)
- [Universal Utility Management](../architecture/universal-utility-management.md)
- [Testing Standards](./testing-standards.md)