# GyvatukasCalculator Service (ARCHIVED)

> **Status**: ARCHIVED as of 2025-12-05  
> **Reason**: Business decision to simplify Gyvatukas handling with manual entry  
> **Location**: `_archive/Gyvatukas_Complex_Logic/GyvatukasCalculator.php`

## Overview

The GyvatukasCalculator service implements seasonal circulation fee (gyvatukas) calculations for Lithuanian hot water circulation systems. The calculation methodology differs between heating season (October-April) and non-heating season (May-September).

### Business Context

**Gyvatukas** (Lithuanian for "heated towel rail") refers to the circulation fee for maintaining hot water circulation in residential buildings. This ensures hot water is immediately available at taps without waiting for water to heat up.

## Architecture

### Component Role

- **Type**: Domain Service
- **Layer**: Business Logic / Calculation Engine
- **Responsibility**: Calculate seasonal circulation fees based on meter readings and building characteristics
- **Dependencies**: 
  - `App\Models\Building`
  - `App\Models\MeterReading`
  - `App\Enums\MeterType`
  - `Carbon\Carbon`
  - Configuration: `config/gyvatukas.php`

### Design Patterns

1. **Strategy Pattern**: Different calculation strategies for summer vs. winter
2. **Caching Pattern**: Internal caching to prevent redundant database queries
3. **Configuration-Driven**: All constants externalized to configuration
4. **Eager Loading**: Optimized N+1 query prevention

## Calculation Methodology

### Summer Calculation (May-September)

Uses the thermodynamic formula:

```
Q_circ = Q_total - (V_water × c × ΔT)
```

Where:
- `Q_circ` = Circulation energy (kWh)
- `Q_total` = Total building heating energy consumption (kWh)
- `V_water` = Hot water volume consumption (m³)
- `c` = Specific heat capacity of water (1.163 kWh/m³·°C)
- `ΔT` = Temperature difference (45.0°C)

**Rationale**: During summer, heating meters measure both water heating and circulation. By subtracting the energy needed to heat the water, we isolate the circulation component.

### Winter Calculation (October-April)

Uses pre-calculated summer average:

```php
$circulationEnergy = $building->gyvatukas_summer_average;
```

**Rationale**: During heating season, heating meters measure space heating + water heating + circulation. It's impossible to separate these components, so we use the summer average as a proxy.

### Seasonal Determination

```php
public function isHeatingSeason(Carbon $date): bool
{
    $month = $date->month;
    return $month >= 10 || $month <= 4; // Oct-Apr
}
```

## API Reference

### Core Methods

#### `calculate(Building $building, Carbon $billingMonth): float`

Main entry point for gyvatukas calculation.

**Parameters:**
- `$building` - Building model instance with relationships
- `$billingMonth` - Carbon instance representing the billing period

**Returns:** `float` - Circulation energy in kWh

**Example:**
```php
$calculator = new GyvatukasCalculator();
$building = Building::with('properties.meters')->find(1);
$billingMonth = Carbon::parse('2024-07-01');

$circulationEnergy = $calculator->calculate($building, $billingMonth);
// Returns: 125.50 kWh (example)
```

#### `distributeCirculationCost(Building $building, float $totalCirculationCost, string $method = 'equal'): array`

Distributes circulation cost among apartments.

**Parameters:**
- `$building` - Building with loaded properties relationship
- `$totalCirculationCost` - Total cost to distribute (€)
- `$method` - Distribution method: `'equal'` or `'area'`

**Returns:** `array<int, float>` - Map of property_id => allocated cost

**Distribution Methods:**

1. **Equal Distribution** (`'equal'`):
   ```
   Cost per property = Total Cost / Number of Properties
   ```

2. **Area-Based Distribution** (`'area'`):
   ```
   Cost per property = Total Cost × (Property Area / Total Area)
   ```

**Example:**
```php
$totalCost = 150.00; // €150 total circulation cost
$distribution = $calculator->distributeCirculationCost(
    $building, 
    $totalCost, 
    'area'
);

// Returns: [
//     1 => 45.50,  // Property 1: 45.50€
//     2 => 52.25,  // Property 2: 52.25€
//     3 => 52.25,  // Property 3: 52.25€
// ]
```

### Cache Management

#### `clearCache(): void`

Clears all internal caches. Call when meter readings are updated.

```php
$calculator->clearCache();
```

#### `clearBuildingCache(int $buildingId): void`

Clears cache for a specific building.

```php
$calculator->clearBuildingCache($buildingId);
```

## Configuration

Located in `config/gyvatukas.php`:

```php
return [
    // Specific heat capacity of water (kWh/m³·°C)
    'water_specific_heat' => 1.163,
    
    // Temperature difference for hot water heating (°C)
    'temperature_delta' => 45.0,
    
    // Heating season start month (October = 10)
    'heating_season_start_month' => 10,
    
    // Heating season end month (April = 4)
    'heating_season_end_month' => 4,
];
```

## Performance Optimization

### N+1 Query Prevention

The service uses eager loading to prevent N+1 queries:

```php
$building->load([
    'properties.meters' => function ($query) {
        $query->where('type', MeterType::HEATING)
              ->select('id', 'property_id', 'type');
    },
    'properties.meters.readings' => function ($query) use ($periodStart, $periodEnd) {
        $query->whereBetween('reading_date', [$periodStart, $periodEnd])
              ->orderBy('reading_date')
              ->select('id', 'meter_id', 'reading_date', 'value');
    }
]);
```

**Query Reduction**: From `1 + N properties + M meters` to just **2 queries**.

### Internal Caching

Two-level caching strategy:

1. **Calculation Cache**: Stores final results by building + month
2. **Consumption Cache**: Stores intermediate meter consumption calculations

Cache keys:
```php
// Calculation cache
$cacheKey = "{$building->id}_{$month->format('Y-m')}";

// Consumption cache
$cacheKey = "heating_{$building->id}_{$periodStart}_{$periodEnd}";
$cacheKey = "water_{$building->id}_{$periodStart}_{$periodEnd}";
```

## Integration Points

### Database Schema Requirements

**Buildings Table:**
```sql
ALTER TABLE buildings ADD COLUMN gyvatukas_summer_average DECIMAL(10,2) DEFAULT NULL;
```

**Required Relationships:**
- `Building` → `hasMany` → `Property`
- `Property` → `hasMany` → `Meter`
- `Meter` → `hasMany` → `MeterReading`

### Meter Types Required

From `App\Enums\MeterType`:
- `MeterType::HEATING` - For heating energy meters
- `MeterType::WATER_HOT` - For hot water volume meters

### Service Integration Example

```php
use App\Services\GyvatukasCalculator;
use App\Models\Building;
use Carbon\Carbon;

class BillingService
{
    public function __construct(
        private GyvatukasCalculator $gyvatukasCalculator
    ) {}
    
    public function calculateBuildingInvoice(Building $building, Carbon $billingMonth)
    {
        // Calculate circulation energy
        $circulationKwh = $this->gyvatukasCalculator->calculate(
            $building, 
            $billingMonth
        );
        
        // Get tariff rate
        $tariffRate = $this->getTariffRate('circulation', $billingMonth);
        
        // Calculate total cost
        $totalCost = $circulationKwh * $tariffRate;
        
        // Distribute among properties
        $distribution = $this->gyvatukasCalculator->distributeCirculationCost(
            $building,
            $totalCost,
            $building->distribution_method ?? 'equal'
        );
        
        // Create invoice items for each property
        foreach ($distribution as $propertyId => $cost) {
            $this->createInvoiceItem($propertyId, 'gyvatukas', $cost);
        }
    }
}
```

## Error Handling

### Negative Circulation Energy

When calculation results in negative values (data quality issue):

```php
if ($circulationEnergy < 0) {
    Log::warning('Negative circulation energy calculated for building', [
        'building_id' => $building->id,
        'month' => $month->format('Y-m'),
        'total_heating' => $totalHeatingEnergy,
        'water_heating' => $waterHeatingEnergy,
        'circulation' => $circulationEnergy,
    ]);
    
    return 0.0; // Fail-safe: return zero
}
```

### Missing Summer Average

When winter calculation lacks summer average:

```php
if ($summerAverage === null || $summerAverage <= 0) {
    Log::warning('Missing or invalid summer average for building during heating season', [
        'building_id' => $building->id,
        'summer_average' => $summerAverage,
    ]);
    
    return 0.0; // Fail-safe: return zero
}
```

### Invalid Distribution Method

When invalid distribution method specified:

```php
Log::error('Invalid distribution method specified', [
    'method' => $method,
    'building_id' => $building->id,
]);

// Fall back to equal distribution
return $this->distributeCirculationCost($building, $totalCirculationCost, 'equal');
```

## Testing Considerations

### Test Coverage Areas

1. **Seasonal Detection**
   - October-April returns true
   - May-September returns false
   - Edge cases: month boundaries

2. **Summer Calculation**
   - Correct formula application
   - Negative value handling
   - Cache effectiveness

3. **Winter Calculation**
   - Summer average retrieval
   - Missing average handling

4. **Distribution Methods**
   - Equal distribution accuracy
   - Area-based distribution accuracy
   - Zero/negative area handling
   - Empty properties handling

5. **Performance**
   - N+1 query prevention
   - Cache hit rates
   - Memory usage with large datasets

### Example Test

```php
use Tests\TestCase;
use App\Services\GyvatukasCalculator;
use App\Models\Building;
use Carbon\Carbon;

class GyvatukasCalculatorTest extends TestCase
{
    public function test_summer_calculation_uses_formula()
    {
        $building = Building::factory()
            ->has(Property::factory()->count(3))
            ->create();
            
        $calculator = new GyvatukasCalculator();
        $billingMonth = Carbon::parse('2024-07-15'); // July (summer)
        
        $result = $calculator->calculate($building, $billingMonth);
        
        $this->assertGreaterThanOrEqual(0, $result);
        $this->assertIsFloat($result);
    }
    
    public function test_winter_calculation_uses_summer_average()
    {
        $building = Building::factory()->create([
            'gyvatukas_summer_average' => 125.50
        ]);
        
        $calculator = new GyvatukasCalculator();
        $billingMonth = Carbon::parse('2024-12-15'); // December (winter)
        
        $result = $calculator->calculate($building, $billingMonth);
        
        $this->assertEquals(125.50, $result);
    }
}
```

## Migration to Manual Entry

### Replacement Approach

The complex calculation logic was replaced with a simpler manual entry system:

**Before (Automated):**
```php
$gyvatukas = $calculator->calculate($building, $billingMonth);
```

**After (Manual):**
```php
// Landlord enters flat fee in building settings
$gyvatukas = $building->gyvatukas_monthly_fee ?? 0;
```

### Data Migration

If reverting to automated calculations:

1. Ensure `gyvatukas_summer_average` is populated for all buildings
2. Verify meter readings exist for all properties
3. Run summer calculations for May-September to establish baselines
4. Test distribution methods match business requirements

## Related Documentation

- **Service Layer**: `docs/architecture/SERVICE_LAYER_ARCHITECTURE.md`
- **Billing Service**: `docs/services/BILLING_SERVICE_API.md`
- **Meter Reading**: `docs/api/METER_READING_CONTROLLER_API.md`
- **Configuration**: `docs/reference/CONFIGURATION_REFERENCE.md`
- **Multi-tenancy**: `docs/architecture/MULTI_TENANT_ARCHITECTURE.md`

## Changelog Impact

### Version History

**v2.5.0 (2025-12-05)** - ARCHIVED
- Archived complex seasonal calculation logic
- Replaced with manual entry approach
- Preserved for potential future use

**v2.0.0 (2024-11-15)** - Performance Optimization
- Added eager loading to prevent N+1 queries
- Implemented two-level caching strategy
- Reduced query count from O(n²) to O(1)

**v1.5.0 (2024-09-20)** - Distribution Methods
- Added area-based distribution method
- Improved error handling for edge cases

**v1.0.0 (2024-06-01)** - Initial Implementation
- Seasonal calculation logic
- Summer formula implementation
- Winter average-based calculation

## Future Considerations

If business requirements change back to automated calculations:

1. **Restore Service**: Move from `_archive/` back to `app/Services/`
2. **Update Configuration**: Verify `config/gyvatukas.php` values
3. **Database Migration**: Ensure `gyvatukas_summer_average` column exists
4. **Seed Summer Averages**: Run calculations for May-September to populate averages
5. **Update Tests**: Restore test suite from archive
6. **Update Documentation**: Mark as active in documentation

## Support

For questions about this archived implementation:
- Review git history around 2025-12-05
- Check `_archive/Gyvatukas_Complex_Logic/README.txt`
- Consult project stakeholders for business context
