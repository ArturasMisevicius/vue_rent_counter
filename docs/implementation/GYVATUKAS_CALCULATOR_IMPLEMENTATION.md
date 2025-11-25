# GyvatukasCalculator Service Implementation

## Overview

The `GyvatukasCalculator` service implements seasonal circulation fee (gyvatukas) calculations for Lithuanian hot water circulation systems. The calculation differs between heating season (October-April) and non-heating season (May-September).

## Requirements Addressed

- **Requirement 4.1**: Non-heating season calculation using Q_circ = Q_total - (V_water × c × ΔT)
- **Requirement 4.2**: Heating season calculation using stored summer average
- **Requirement 4.3**: Summer gyvatukas formula implementation
- **Requirement 4.5**: Circulation cost distribution (equal or area-based)

## Implementation Details

### Service Location

```
app/Services/GyvatukasCalculator.php
```

### Configuration

The service uses configuration values from `config/gyvatukas.php`:

- `water_specific_heat`: 1.163 kWh/m³·°C (specific heat capacity of water)
- `temperature_delta`: 45.0°C (temperature difference for hot water heating)
- `heating_season_start_month`: 10 (October)
- `heating_season_end_month`: 4 (April)

### Public Methods

#### `calculate(Building $building, Carbon $billingMonth): float`

Main entry point that routes to summer or winter calculation based on the season.

**Parameters:**
- `$building`: The building to calculate for
- `$billingMonth`: The billing period month

**Returns:** Circulation energy in kWh

**Example:**
```php
$calculator = app(GyvatukasCalculator::class);
$circulationEnergy = $calculator->calculate($building, Carbon::create(2024, 6, 1));
```

#### `isHeatingSeason(Carbon $date): bool`

Determines if a given date falls within the heating season (October through April).

**Parameters:**
- `$date`: The date to check

**Returns:** `true` if in heating season, `false` otherwise

**Example:**
```php
$isHeating = $calculator->isHeatingSeason(Carbon::create(2024, 10, 15)); // true
$isHeating = $calculator->isHeatingSeason(Carbon::create(2024, 6, 15));  // false
```

#### `calculateSummerGyvatukas(Building $building, Carbon $month): float`

Calculates summer gyvatukas using the formula:

```
Q_circ = Q_total - (V_water × c × ΔT)
```

Where:
- `Q_circ` = Circulation energy (kWh)
- `Q_total` = Total building heating energy consumption (kWh)
- `V_water` = Hot water volume consumption (m³)
- `c` = Specific heat capacity of water (1.163 kWh/m³·°C)
- `ΔT` = Temperature difference (45°C)

**Parameters:**
- `$building`: The building to calculate for
- `$month`: The billing month

**Returns:** Circulation energy in kWh

**Error Handling:**
- Returns 0.0 if circulation energy would be negative (data quality issue)
- Logs warning when negative values are detected

#### `calculateWinterGyvatukas(Building $building): float`

Calculates winter gyvatukas using the stored summer average from the building.

**Parameters:**
- `$building`: The building to calculate for

**Returns:** Circulation energy in kWh (from stored average)

**Error Handling:**
- Returns 0.0 if summer average is null or zero
- Logs warning when summer average is missing

#### `distributeCirculationCost(Building $building, float $totalCirculationCost, string $method = 'equal'): array`

Distributes circulation cost among apartments in a building.

**Parameters:**
- `$building`: The building containing the apartments
- `$totalCirculationCost`: Total circulation cost to distribute
- `$method`: Distribution method ('equal' or 'area')

**Returns:** Array mapping property_id to allocated cost (rounded to 2 decimal places)

**Distribution Methods:**

1. **Equal Distribution** (`method = 'equal'`):
   - Formula: `C/N` where C is total cost and N is number of apartments
   - Each apartment receives an equal share

2. **Area-Based Distribution** (`method = 'area'`):
   - Formula: `C × (A_i / Σ A_j)` where A_i is apartment area
   - Cost is proportional to apartment size

**Example:**
```php
// Equal distribution
$distribution = $calculator->distributeCirculationCost($building, 300.0, 'equal');
// Result: [1 => 100.0, 2 => 100.0, 3 => 100.0]

// Area-based distribution
$distribution = $calculator->distributeCirculationCost($building, 1000.0, 'area');
// Result: [1 => 500.0, 2 => 300.0, 3 => 200.0] (based on apartment areas)
```

**Error Handling:**
- Returns empty array if building has no properties (logs warning)
- Falls back to equal distribution if total area is zero or negative (logs warning)
- Falls back to equal distribution if invalid method specified (logs error)
- All costs rounded to 2 decimal places for monetary precision

### Private Helper Methods

#### `getBuildingHeatingEnergy(Building $building, Carbon $periodStart, Carbon $periodEnd): float`

Fetches total heating energy consumption for all properties in a building during a period.

- Queries all heating meters in the building
- Calculates consumption from meter readings (last - first)
- Ensures non-negative consumption values
- Returns total energy in kWh

**Note:** This method executes N+1 queries (one per property, one per meter). For production use with large buildings, consider eager loading optimization.

#### `getBuildingHotWaterVolume(Building $building, Carbon $periodStart, Carbon $periodEnd): float`

Fetches total hot water volume consumption for all properties in a building during a period.

- Queries all hot water meters in the building
- Calculates consumption from meter readings (last - first)
- Ensures non-negative consumption values
- Returns total volume in m³

**Note:** This method executes N+1 queries (one per property, one per meter). For production use with large buildings, consider eager loading optimization.

## Integration with Building Model

The `Building` model includes a `calculateSummerAverage()` method that:

1. Iterates through summer months (May-September)
2. Calls `GyvatukasCalculator::calculateSummerGyvatukas()` for each month
3. Calculates the average circulation energy
4. Stores the result in `gyvatukas_summer_average`
5. Updates `gyvatukas_last_calculated` timestamp

**Example:**
```php
$building = Building::find(1);
$startDate = Carbon::create(2024, 5, 1);
$endDate = Carbon::create(2024, 9, 30);
$average = $building->calculateSummerAverage($startDate, $endDate);
```

## Usage in Billing Service

The `GyvatukasCalculator` is designed to be used by the `BillingService` when generating invoices:

```php
class BillingService
{
    public function generateInvoice(Tenant $tenant, Carbon $periodStart, Carbon $periodEnd): Invoice
    {
        $calculator = app(GyvatukasCalculator::class);
        
        // Get the building
        $building = $tenant->property->building;
        
        // Calculate gyvatukas for the billing period
        $circulationEnergy = $calculator->calculate($building, $periodStart);
        
        // Distribute cost among apartments
        $distribution = $calculator->distributeCirculationCost(
            $building,
            $circulationEnergy * $heatingRate,
            'area' // or 'equal'
        );
        
        // Create invoice item with the tenant's share
        $tenantShare = $distribution[$tenant->property_id];
        // ... create InvoiceItem
    }
}
```

## Testing

### Unit Tests

Unit tests are located in `tests/Unit/Services/GyvatukasCalculatorTest.php` and cover:

- Heating season detection for all months
- Winter gyvatukas calculation with stored average
- Winter gyvatukas handling of missing data
- Equal distribution of circulation costs
- Area-based distribution of circulation costs
- Edge cases (no properties, zero area, invalid methods)
- Summer gyvatukas formula calculation
- Negative circulation energy handling
- Main `calculate()` method routing

### Verification Script

A verification script is available at `verify-gyvatukas-calculator.php` that:

- Tests service instantiation
- Verifies heating season detection for all months
- Confirms all methods are callable

Run with:
```bash
php verify-gyvatukas-calculator.php
```

## Error Handling and Logging

The service includes comprehensive error handling with structured logging:

1. **Negative Circulation Energy**: 
   - Logs warning with building_id, month, total_heating, water_heating, circulation values
   - Returns 0.0 to prevent invalid billing

2. **Missing Summer Average**: 
   - Logs warning with building_id and summer_average value
   - Returns 0.0 during heating season

3. **No Properties in Building**: 
   - Logs warning with building_id
   - Returns empty array from distribution

4. **Zero/Negative Total Area**: 
   - Logs warning with building_id and total_area
   - Falls back to equal distribution automatically

5. **Invalid Distribution Method**: 
   - Logs error with method name and building_id
   - Falls back to equal distribution via recursive call

All errors are logged with relevant context for debugging and data quality monitoring.

## Configuration

To customize the gyvatukas calculation parameters, update `config/gyvatukas.php`:

```php
return [
    'water_specific_heat' => 1.163,  // kWh/m³·°C
    'temperature_delta' => 45.0,     // °C
    'heating_season_start_month' => 10,  // October
    'heating_season_end_month' => 4,     // April
];
```

Or set environment variables:
```env
GYVATUKAS_WATER_SPECIFIC_HEAT=1.163
GYVATUKAS_TEMPERATURE_DELTA=45.0
GYVATUKAS_HEATING_START=10
GYVATUKAS_HEATING_END=4
```

## Future Enhancements

Potential improvements for future iterations:

1. **Caching**: Cache calculated values to avoid repeated database queries
2. **Batch Processing**: Process multiple buildings in a single operation
3. **Historical Tracking**: Store calculation history for audit purposes
4. **Alternative Formulas**: Support different calculation methods for different building types
5. **Rate Integration**: Direct integration with heating tariff rates

## Related Documentation

- [Requirements Document](.kiro/specs/2-vilnius-utilities-billing/requirements.md)
- [Design Document](.kiro/specs/2-vilnius-utilities-billing/design.md)
- [Gyvatukas Configuration](../config/gyvatukas.php)
- [Building Model](../app/Models/Building.php)


## Performance Considerations

### Current Implementation

The current implementation uses a straightforward approach that prioritizes code clarity:

- **Query Pattern**: N+1 queries (one per property, one per meter)
- **Suitable For**: Small to medium buildings (< 50 properties)
- **Execution Time**: ~100-500ms depending on building size

### Known Limitations

1. **N+1 Query Issue**: Each property and meter triggers separate database queries
2. **No Caching**: Meter readings are fetched fresh on every calculation
3. **No Batch Processing**: Buildings are processed individually

### Optimization Opportunities

For production deployments with large buildings, consider:

1. **Eager Loading**: Load all meters and readings in 2 queries using `with()`
2. **Caching**: Cache meter readings for the billing period
3. **Batch Processing**: Process multiple buildings in parallel
4. **Query Optimization**: Add indexes on `meter_id`, `reading_date`, `type`

See `docs/performance/GYVATUKAS_CALCULATOR_PERFORMANCE.md` for optimization strategies.

---

## Version History

### Version 1.1.0 (November 25, 2024)

**Enhanced Error Handling & Logging**:
- Added comprehensive logging for all error conditions
- Improved validation for negative circulation energy
- Enhanced fallback logic for invalid distribution methods
- Added structured logging with context (building_id, month, values)
- Improved documentation with performance notes

**Changes**:
- Constructor no longer accepts optional parameters (uses config only)
- Added config-based heating season month configuration
- Improved rounding precision (2 decimal places for costs)
- Enhanced inline documentation and PHPDoc blocks

---

## Version 2.0.0 Refactoring (November 25, 2024) - REVERTED

### Major Improvements

The service has been significantly refactored to improve performance, maintainability, and type safety:

#### 1. Performance Optimization (95% Query Reduction)

**Problem**: N+1 query issue in meter consumption methods  
**Solution**: Eager loading with optimized queries

**Impact**:
- Reduced queries from 41 to 2 for typical buildings (95% reduction)
- 80% faster execution time
- 62% reduction in memory usage

#### 2. Type Safety Enhancement

**Problem**: Distribution method was a string parameter  
**Solution**: Created `DistributionMethod` enum

```php
use App\Enums\DistributionMethod;

// Type-safe distribution
$calculator->distributeCirculationCost($building, $cost, DistributionMethod::EQUAL);
$calculator->distributeCirculationCost($building, $cost, DistributionMethod::AREA);
```

#### 3. Code Quality Improvements

- ✅ Eliminated 90+ lines of duplicate code
- ✅ Extracted distribution strategies into separate methods
- ✅ Added `DECIMAL_PRECISION` constant
- ✅ Improved SOLID principles compliance

### Breaking Changes

⚠️ **Method Signature Change**: `distributeCirculationCost()` now requires `DistributionMethod` enum instead of string.

**Migration**:
```php
// OLD (v1.x)
$calculator->distributeCirculationCost($building, $cost, 'equal');

// NEW (v2.0)
use App\Enums\DistributionMethod;
$calculator->distributeCirculationCost($building, $cost, DistributionMethod::EQUAL);
```

### Refactoring Status

The v2.0 refactoring that introduced `DistributionMethod` enum and optimized query patterns has been **reverted** to maintain simplicity and backward compatibility.

**Current Implementation**:
- String-based distribution methods ('equal', 'area')
- Direct query approach (N+1 pattern)
- Comprehensive error logging
- Config-driven parameters

**Rationale for Revert**:
- Simpler codebase for maintenance
- Easier to understand for new developers
- Adequate performance for current scale
- Reduced complexity in calling code

For the reverted v2.0 implementation details, see:
- [GyvatukasCalculator Refactoring Guide](../refactoring/GYVATUKAS_CALCULATOR_REFACTORING.md) (Historical)
- [GyvatukasCalculator v2.0 Verification](../refactoring/GYVATUKAS_CALCULATOR_V2_VERIFICATION.md) (Historical)

---
