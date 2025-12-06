# GyvatukasCalculator API Reference (ARCHIVED)

> **Status**: ARCHIVED - This API is preserved for reference only  
> **Current Implementation**: Manual entry system (see `docs/features/GYVATUKAS_MANUAL_ENTRY.md`)

## Quick Reference

```php
use App\Services\GyvatukasCalculator;
use App\Models\Building;
use Carbon\Carbon;

$calculator = new GyvatukasCalculator();

// Calculate circulation energy
$kwh = $calculator->calculate($building, $billingMonth);

// Distribute cost among properties
$distribution = $calculator->distributeCirculationCost($building, $totalCost, 'area');

// Cache management
$calculator->clearCache();
$calculator->clearBuildingCache($buildingId);
```

## Constructor

### `__construct()`

Initializes the calculator with configuration values.

**Configuration Sources:**
- `config('gyvatukas.water_specific_heat', 1.163)`
- `config('gyvatukas.temperature_delta', 45.0)`
- `config('gyvatukas.heating_season_start_month', 10)`
- `config('gyvatukas.heating_season_end_month', 4)`

**Example:**
```php
$calculator = new GyvatukasCalculator();
```

## Public Methods

### `calculate(Building $building, Carbon $billingMonth): float`

Calculates gyvatukas (circulation fee) for a building in a given billing month.

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$building` | `Building` | Building model with relationships loaded |
| `$billingMonth` | `Carbon` | Billing period month |

**Returns:** `float` - Circulation energy in kWh

**Behavior:**
- Routes to `calculateWinterGyvatukas()` if heating season (Oct-Apr)
- Routes to `calculateSummerGyvatukas()` if non-heating season (May-Sep)
- Results are cached internally

**Example:**
```php
$building = Building::with('properties.meters.readings')->find(1);
$billingMonth = Carbon::parse('2024-07-01');

$circulationEnergy = $calculator->calculate($building, $billingMonth);
// Returns: 125.50 (kWh)
```

**Related Requirements:** 4.1, 4.2, 4.3

---

### `isHeatingSeason(Carbon $date): bool`

Determines if a given date falls within the heating season.

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$date` | `Carbon` | Date to check |

**Returns:** `bool` - True if heating season (Oct-Apr), false otherwise

**Logic:**
```php
$month = $date->month;
return $month >= 10 || $month <= 4;
```

**Example:**
```php
$isWinter = $calculator->isHeatingSeason(Carbon::parse('2024-12-15'));
// Returns: true

$isSummer = $calculator->isHeatingSeason(Carbon::parse('2024-07-15'));
// Returns: false
```

**Related Requirements:** 4.1, 4.2

---

### `calculateSummerGyvatukas(Building $building, Carbon $month): float`

Calculates summer gyvatukas using thermodynamic formula.

**Formula:**
```
Q_circ = Q_total - (V_water × c × ΔT)
```

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$building` | `Building` | Building with loaded relationships |
| `$month` | `Carbon` | Billing month (May-September) |

**Returns:** `float` - Circulation energy in kWh (rounded to 2 decimals)

**Process:**
1. Check cache for existing result
2. Calculate period boundaries (start/end of month)
3. Fetch total heating energy (`Q_total`)
4. Fetch hot water volume (`V_water`)
5. Calculate water heating energy: `V_water × c × ΔT`
6. Calculate circulation energy: `Q_total - water_heating_energy`
7. Handle negative values (return 0.0 with warning)
8. Cache and return result

**Example:**
```php
$building = Building::with('properties.meters.readings')->find(1);
$month = Carbon::parse('2024-07-01');

$circulationEnergy = $calculator->calculateSummerGyvatukas($building, $month);
// Returns: 125.50 (kWh)
```

**Error Handling:**
- Negative results: Logs warning, returns 0.0
- Missing meter readings: Returns 0.0

**Related Requirements:** 4.1, 4.3

---

### `calculateWinterGyvatukas(Building $building): float`

Calculates winter gyvatukas using stored summer average.

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$building` | `Building` | Building model |

**Returns:** `float` - Circulation energy in kWh (from stored average)

**Process:**
1. Retrieve `$building->gyvatukas_summer_average`
2. Validate value exists and is positive
3. Return value or 0.0 with warning

**Example:**
```php
$building = Building::find(1);
// Assume: $building->gyvatukas_summer_average = 125.50

$circulationEnergy = $calculator->calculateWinterGyvatukas($building);
// Returns: 125.50 (kWh)
```

**Error Handling:**
- Missing average: Logs warning, returns 0.0
- Invalid average (≤ 0): Logs warning, returns 0.0

**Related Requirements:** 4.2

---

### `distributeCirculationCost(Building $building, float $totalCirculationCost, string $method = 'equal'): array`

Distributes circulation cost among apartments in a building.

**Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `$building` | `Building` | - | Building with loaded properties |
| `$totalCirculationCost` | `float` | - | Total cost to distribute (€) |
| `$method` | `string` | `'equal'` | Distribution method: `'equal'` or `'area'` |

**Returns:** `array<int, float>` - Map of property_id => allocated cost (rounded to 2 decimals)

**Distribution Methods:**

#### Equal Distribution (`'equal'`)
```
Cost per property = Total Cost / Number of Properties
```

**Example:**
```php
$distribution = $calculator->distributeCirculationCost($building, 150.00, 'equal');
// 3 properties
// Returns: [
//     1 => 50.00,
//     2 => 50.00,
//     3 => 50.00,
// ]
```

#### Area-Based Distribution (`'area'`)
```
Cost per property = Total Cost × (Property Area / Total Area)
```

**Example:**
```php
$distribution = $calculator->distributeCirculationCost($building, 150.00, 'area');
// Property 1: 50 m², Property 2: 75 m², Property 3: 75 m² (Total: 200 m²)
// Returns: [
//     1 => 37.50,  // 150 × (50/200)
//     2 => 56.25,  // 150 × (75/200)
//     3 => 56.25,  // 150 × (75/200)
// ]
```

**Error Handling:**
- Empty properties: Logs warning, returns empty array
- Zero/negative total area: Logs warning, falls back to equal distribution
- Invalid method: Logs error, falls back to equal distribution

**Related Requirements:** 4.5

---

### `clearCache(): void`

Clears all internal caches (calculation and consumption).

**Use Cases:**
- After meter readings are updated
- When processing multiple buildings to prevent memory buildup
- Before batch operations

**Example:**
```php
// Update meter readings
MeterReading::create([...]);

// Clear cache to ensure fresh calculations
$calculator->clearCache();

// Recalculate
$newResult = $calculator->calculate($building, $billingMonth);
```

---

### `clearBuildingCache(int $buildingId): void`

Clears cache for a specific building only.

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$buildingId` | `int` | Building ID to clear cache for |

**Use Cases:**
- After meter readings updated for specific building
- More efficient than clearing entire cache

**Example:**
```php
// Update meter readings for building 5
MeterReading::where('building_id', 5)->update([...]);

// Clear only building 5's cache
$calculator->clearBuildingCache(5);

// Recalculate for building 5
$building = Building::find(5);
$newResult = $calculator->calculate($building, $billingMonth);
```

## Private Methods

### `getBuildingHeatingEnergy(Building $building, Carbon $periodStart, Carbon $periodEnd): float`

Fetches total heating energy consumption for a building in a period.

**Optimization:** Uses eager loading to prevent N+1 queries (2 queries total).

**Returns:** `float` - Total heating energy in kWh

**Cache Key:** `heating_{building_id}_{start_date}_{end_date}`

---

### `getBuildingHotWaterVolume(Building $building, Carbon $periodStart, Carbon $periodEnd): float`

Fetches total hot water volume consumption for a building in a period.

**Optimization:** Uses eager loading to prevent N+1 queries (2 queries total).

**Returns:** `float` - Total hot water volume in m³

**Cache Key:** `water_{building_id}_{start_date}_{end_date}`

## Constants

### `DECIMAL_PRECISION`

```php
private const DECIMAL_PRECISION = 2;
```

Decimal precision for monetary calculations (2 decimal places).

## Configuration

### Required Configuration File: `config/gyvatukas.php`

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

## Database Requirements

### Buildings Table

```sql
ALTER TABLE buildings ADD COLUMN gyvatukas_summer_average DECIMAL(10,2) DEFAULT NULL;
```

### Required Relationships

```php
// Building.php
public function properties()
{
    return $this->hasMany(Property::class);
}

// Property.php
public function meters()
{
    return $this->hasMany(Meter::class);
}

// Meter.php
public function readings()
{
    return $this->hasMany(MeterReading::class);
}
```

## Performance Characteristics

### Query Optimization

**Without Eager Loading:**
```
1 query (building) + 
N queries (properties) + 
M queries (meters) + 
P queries (readings)
= O(n²) queries
```

**With Eager Loading:**
```
1 query (building with properties) +
1 query (meters with readings)
= 2 queries total
```

### Cache Effectiveness

**Cache Hit Scenarios:**
- Same building + month requested multiple times
- Consumption data reused across calculations

**Cache Miss Scenarios:**
- First calculation for building + month
- After `clearCache()` or `clearBuildingCache()`

### Memory Usage

**Per Building:**
- Calculation cache: ~100 bytes per month
- Consumption cache: ~200 bytes per period

**Recommendation:** Clear cache after processing large batches.

## Error Codes and Logging

### Warning Logs

**Negative Circulation Energy:**
```php
Log::warning('Negative circulation energy calculated for building', [
    'building_id' => $building->id,
    'month' => $month->format('Y-m'),
    'total_heating' => $totalHeatingEnergy,
    'water_heating' => $waterHeatingEnergy,
    'circulation' => $circulationEnergy,
]);
```

**Missing Summer Average:**
```php
Log::warning('Missing or invalid summer average for building during heating season', [
    'building_id' => $building->id,
    'summer_average' => $summerAverage,
]);
```

**No Properties Found:**
```php
Log::warning('No properties found for building during circulation cost distribution', [
    'building_id' => $building->id,
]);
```

**Zero/Negative Total Area:**
```php
Log::warning('Total area is zero or negative for building', [
    'building_id' => $building->id,
    'total_area' => $totalArea,
]);
```

### Error Logs

**Invalid Distribution Method:**
```php
Log::error('Invalid distribution method specified', [
    'method' => $method,
    'building_id' => $building->id,
]);
```

## Integration Examples

### With BillingService

```php
use App\Services\GyvatukasCalculator;
use App\Services\BillingService;

class InvoiceController
{
    public function __construct(
        private GyvatukasCalculator $gyvatukasCalculator,
        private BillingService $billingService
    ) {}
    
    public function generateInvoice(Building $building, Carbon $billingMonth)
    {
        // Calculate circulation energy
        $circulationKwh = $this->gyvatukasCalculator->calculate(
            $building,
            $billingMonth
        );
        
        // Get tariff rate
        $tariffRate = $this->billingService->getTariffRate(
            'circulation',
            $billingMonth
        );
        
        // Calculate total cost
        $totalCost = $circulationKwh * $tariffRate;
        
        // Distribute among properties
        $distribution = $this->gyvatukasCalculator->distributeCirculationCost(
            $building,
            $totalCost,
            $building->distribution_method ?? 'equal'
        );
        
        // Create invoice items
        foreach ($distribution as $propertyId => $cost) {
            InvoiceItem::create([
                'property_id' => $propertyId,
                'service_type' => 'gyvatukas',
                'amount' => $cost,
                'kwh' => $circulationKwh * ($cost / $totalCost),
                'billing_month' => $billingMonth,
            ]);
        }
    }
}
```

### With Artisan Command

```php
use App\Services\GyvatukasCalculator;
use Illuminate\Console\Command;

class CalculateSummerAveragesCommand extends Command
{
    protected $signature = 'gyvatukas:calculate-summer-averages {year}';
    
    public function handle(GyvatukasCalculator $calculator)
    {
        $year = $this->argument('year');
        
        // Summer months: May-September
        $summerMonths = collect([5, 6, 7, 8, 9]);
        
        Building::chunk(100, function ($buildings) use ($calculator, $year, $summerMonths) {
            foreach ($buildings as $building) {
                $summerTotal = 0;
                $monthCount = 0;
                
                foreach ($summerMonths as $month) {
                    $billingMonth = Carbon::create($year, $month, 1);
                    $energy = $calculator->calculate($building, $billingMonth);
                    
                    if ($energy > 0) {
                        $summerTotal += $energy;
                        $monthCount++;
                    }
                }
                
                if ($monthCount > 0) {
                    $average = $summerTotal / $monthCount;
                    $building->update(['gyvatukas_summer_average' => $average]);
                    
                    $this->info("Building {$building->id}: {$average} kWh average");
                }
            }
            
            // Clear cache after each chunk
            $calculator->clearCache();
        });
    }
}
```

## Testing

### Unit Test Example

```php
use Tests\TestCase;
use App\Services\GyvatukasCalculator;
use App\Models\Building;
use Carbon\Carbon;

class GyvatukasCalculatorTest extends TestCase
{
    private GyvatukasCalculator $calculator;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new GyvatukasCalculator();
    }
    
    public function test_calculate_routes_to_winter_during_heating_season()
    {
        $building = Building::factory()->create([
            'gyvatukas_summer_average' => 125.50
        ]);
        
        $result = $this->calculator->calculate(
            $building,
            Carbon::parse('2024-12-15') // December
        );
        
        $this->assertEquals(125.50, $result);
    }
    
    public function test_distribute_equal_divides_cost_evenly()
    {
        $building = Building::factory()
            ->has(Property::factory()->count(3))
            ->create();
        
        $distribution = $this->calculator->distributeCirculationCost(
            $building,
            150.00,
            'equal'
        );
        
        $this->assertCount(3, $distribution);
        $this->assertEquals(50.00, $distribution[$building->properties[0]->id]);
        $this->assertEquals(50.00, $distribution[$building->properties[1]->id]);
        $this->assertEquals(50.00, $distribution[$building->properties[2]->id]);
    }
}
```

## Migration Guide

### From Automated to Manual Entry

**Before:**
```php
$circulationEnergy = $calculator->calculate($building, $billingMonth);
$cost = $circulationEnergy * $tariffRate;
```

**After:**
```php
$cost = $building->gyvatukas_monthly_fee ?? 0;
```

### From Manual Entry Back to Automated

1. Restore service from archive
2. Populate `gyvatukas_summer_average` for all buildings
3. Run summer calculations to establish baselines
4. Update billing service integration
5. Test thoroughly before production deployment

## See Also

- [GyvatukasCalculator Service Documentation](GYVATUKAS_CALCULATOR_ARCHIVED.md)
- [Billing Service API](BILLING_SERVICE_API.md)
- [Meter Reading API](METER_READING_CONTROLLER_API.md)
- [Configuration Reference](../reference/CONFIGURATION_REFERENCE.md)
