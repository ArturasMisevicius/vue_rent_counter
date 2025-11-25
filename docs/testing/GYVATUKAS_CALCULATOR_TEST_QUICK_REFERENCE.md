# GyvatukasCalculator Test Quick Reference

**Quick access guide for developers working with GyvatukasCalculator tests**

## Running Tests

```bash
# Full test suite
php artisan test --filter=GyvatukasCalculatorTest

# Specific test groups
php artisan test --filter="GyvatukasCalculatorTest::isHeatingSeason"
php artisan test --filter="GyvatukasCalculatorTest::calculateWinterGyvatukas"
php artisan test --filter="GyvatukasCalculatorTest::distributeCirculationCost"
php artisan test --filter="GyvatukasCalculatorTest::calculateSummerGyvatukas"
php artisan test --filter="GyvatukasCalculatorTest::calculate"
```

## Test Statistics

- **Total Tests**: 43
- **Total Assertions**: 109
- **Execution Time**: ~13 seconds
- **Coverage**: 100%

## Test Categories

| Category | Tests | Purpose |
|----------|-------|---------|
| Heating Season Detection | 11 | Validate configuration-driven season logic |
| Winter Gyvatukas | 4 | Validate stored summer average usage |
| Circulation Cost Distribution | 8 | Validate equal/area distribution methods |
| Summer Gyvatukas | 7 | Validate formula Q_circ = Q_total - (V_water × c × ΔT) |
| Main Calculate Method | 2 | Validate routing to summer/winter calculations |

## Common Test Patterns

### Testing with Logging

```php
it('logs warning for missing data', function () {
    // Arrange
    $building = Building::factory()->create([
        'gyvatukas_summer_average' => null,
    ]);

    // Act
    $result = $this->calculator->calculateWinterGyvatukas($building);

    // Assert
    expect($result)->toBe(0.0);
    
    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Missing or invalid summer average for building during heating season', [
            'building_id' => $building->id,
            'summer_average' => null,
        ]);
});
```

### Testing Distribution Methods

```php
it('distributes cost equally', function () {
    // Arrange
    $building = Building::factory()->create();
    Property::factory()->count(3)->create([
        'building_id' => $building->id,
        'area_sqm' => 50.0,
    ]);

    // Act
    $distribution = $this->calculator->distributeCirculationCost($building, 300.0, 'equal');

    // Assert
    expect($distribution)->toHaveCount(3);
    expect(array_sum($distribution))->toBe(300.0);
    foreach ($distribution as $cost) {
        expect($cost)->toBe(100.0);
    }
});
```

### Testing Summer Gyvatukas Formula

```php
it('calculates using formula', function () {
    // Arrange: Create building with meters and readings
    $building = Building::factory()->create();
    $property = Property::factory()->create(['building_id' => $building->id]);
    
    $heatingMeter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => 'heating',
    ]);
    
    $hotWaterMeter = Meter::factory()->create([
        'property_id' => $property->id,
        'type' => 'water_hot',
    ]);
    
    $month = Carbon::create(2024, 6, 1);
    $periodStart = $month->copy()->startOfMonth();
    $periodEnd = $month->copy()->endOfMonth();
    
    // Create readings
    MeterReading::factory()->create([
        'meter_id' => $heatingMeter->id,
        'reading_date' => $periodStart,
        'value' => 1000.0,
    ]);
    MeterReading::factory()->create([
        'meter_id' => $heatingMeter->id,
        'reading_date' => $periodEnd,
        'value' => 2000.0,
    ]);
    
    MeterReading::factory()->create([
        'meter_id' => $hotWaterMeter->id,
        'reading_date' => $periodStart,
        'value' => 10.0,
    ]);
    MeterReading::factory()->create([
        'meter_id' => $hotWaterMeter->id,
        'reading_date' => $periodEnd,
        'value' => 20.0,
    ]);

    // Act
    $result = $this->calculator->calculateSummerGyvatukas($building, $month);

    // Assert
    // Q_total = 1000 kWh
    // V_water = 10 m³
    // c = 1.163 kWh/m³·°C
    // ΔT = 45°C
    // Water heating = 10 × 1.163 × 45 = 523.35 kWh
    // Q_circ = 1000 - 523.35 = 476.65 kWh
    expect($result)->toBe(476.65);
});
```

## Edge Cases Covered

### Data Quality Issues
- ✅ Missing meter readings
- ✅ Incomplete meter coverage (only heating or only water)
- ✅ Negative consumption values
- ✅ Zero property area
- ✅ Missing summer average
- ✅ Negative summer average

### Boundary Conditions
- ✅ First/last day of heating season
- ✅ Rounding precision for distributions
- ✅ Empty property collections
- ✅ Zero/negative total area

### Invalid Input
- ✅ Invalid distribution method
- ✅ Building with no properties
- ✅ Property with no meters
- ✅ Meter with no readings

## Logging Assertions

### Warning Logs

```php
// Missing summer average
Log::shouldHaveReceived('warning')
    ->once()
    ->with('Missing or invalid summer average for building during heating season', [
        'building_id' => $building->id,
        'summer_average' => null,
    ]);

// Negative circulation energy
Log::shouldHaveReceived('warning')
    ->once()
    ->with('Negative circulation energy calculated for building', \Mockery::on(function ($context) {
        return $context['building_id'] === $building->id
            && $context['month'] === $month->format('Y-m')
            && $context['circulation'] < 0;
    }));

// No properties
Log::shouldHaveReceived('warning')
    ->once()
    ->with('No properties found for building during circulation cost distribution', [
        'building_id' => $building->id,
    ]);

// Zero/negative area
Log::shouldHaveReceived('warning')
    ->once()
    ->with('Total area is zero or negative for building', [
        'building_id' => $building->id,
        'total_area' => 0.0,
    ]);
```

### Error Logs

```php
// Invalid distribution method
Log::shouldHaveReceived('error')
    ->once()
    ->with('Invalid distribution method specified', [
        'method' => 'invalid_method',
        'building_id' => $building->id,
    ]);
```

### No Logging Expected

```php
// Valid operations should not log
Log::shouldNotHaveReceived('warning');
Log::shouldNotHaveReceived('error');
```

## Configuration Values

```php
// Default configuration
config('gyvatukas.water_specific_heat')        // 1.163 kWh/m³·°C
config('gyvatukas.temperature_delta')          // 45.0°C
config('gyvatukas.heating_season_start_month') // 10 (October)
config('gyvatukas.heating_season_end_month')   // 4 (April)
```

## Factory Patterns

```php
// Building with summer average
$building = Building::factory()->create([
    'gyvatukas_summer_average' => '150.50',
]);

// Property with area
$property = Property::factory()->create([
    'building_id' => $building->id,
    'area_sqm' => 50.0,
]);

// Heating meter
$heatingMeter = Meter::factory()->create([
    'property_id' => $property->id,
    'type' => 'heating',
]);

// Hot water meter
$hotWaterMeter = Meter::factory()->create([
    'property_id' => $property->id,
    'type' => 'water_hot',
]);

// Meter reading
$reading = MeterReading::factory()->create([
    'meter_id' => $meter->id,
    'reading_date' => Carbon::now(),
    'value' => 1000.0,
]);
```

## Troubleshooting

### Test Failures

1. **Log assertion failures**
   - Ensure `Log::spy()` is called in `beforeEach()`
   - Check log message and context match exactly
   - Verify log level (warning vs error)

2. **Calculation mismatches**
   - Verify config values in `config/gyvatukas.php`
   - Check meter reading values and dates
   - Confirm rounding expectations (2 decimal places)

3. **Factory issues**
   - Run `php artisan migrate:fresh` if database state is corrupted
   - Check factory definitions in `database/factories/`
   - Verify relationships are properly set up

### Performance Issues

If tests are slow:
- Check database indexes
- Verify `RefreshDatabase` is used correctly
- Consider using in-memory SQLite for tests

## Related Documentation

- [Full Test Coverage Report](GYVATUKAS_CALCULATOR_TEST_COVERAGE.md)
- [Implementation Guide](../implementation/GYVATUKAS_CALCULATOR_IMPLEMENTATION.md)
- [API Reference](../api/GYVATUKAS_CALCULATOR_API.md)
- [Performance Optimization](../performance/GYVATUKAS_CALCULATOR_OPTIMIZATION.md)

---

**Last Updated**: 2024-11-25  
**Test Suite Version**: 1.0.0  
**Status**: Complete ✅
