# GyvatukasCalculator Service Documentation

## Overview

The `GyvatukasCalculator` service handles circulation energy (gyvatukas) calculations for Lithuanian utilities billing. Gyvatukas represents the energy cost for circulating hot water through building systems, with different calculation methods for summer and winter periods.

## Key Concepts

### Lithuanian Utility Billing Context
- **Gyvatukas**: Circulation energy required to maintain hot water circulation in buildings
- **Summer Period**: May 1 - September 30 (non-heating season)
- **Winter Period**: October 1 - April 30 (heating season)
- **Summer Average**: Baseline calculation used for winter adjustments

### Calculation Periods
- **Summer Calculations**: Based on actual circulation energy consumption
- **Winter Calculations**: Use summer average with heating season adjustments
- **Peak Winter Months**: December, January, February (30% increase)
- **Shoulder Months**: October, November, March, April (15% increase)

## Service Architecture

### Dependencies
```php
public function __construct(
    private readonly CacheRepository $cache,
    private readonly ConfigRepository $config,
    private readonly LoggerInterface $logger
) {}
```

### Interface Implementation
Implements `GyvatukasCalculatorInterface` providing:
- Season detection methods
- Summer/winter calculation methods
- Cache management
- Cost distribution utilities

## Core Methods

### Season Detection

#### `isHeatingSeason(Carbon $date): bool`
Determines if a date falls within the heating season (October 1 - April 30).

```php
$calculator = app(GyvatukasCalculatorInterface::class);
$isHeating = $calculator->isHeatingSeason(Carbon::create(2024, 12, 15)); // true
$isHeating = $calculator->isHeatingSeason(Carbon::create(2024, 7, 15));  // false
```

#### `isSummerPeriod(Carbon $date): bool`
Determines if a date falls within the summer period (May 1 - September 30).

```php
$isSummer = $calculator->isSummerPeriod(Carbon::create(2024, 6, 15)); // true
$isSummer = $calculator->isSummer Period(Carbon::create(2024, 1, 15)); // false
```

### Calculation Methods

#### `calculateSummerGyvatukas(Building $building, Carbon $month): float`
Calculates circulation energy for summer months based on building characteristics.

**Parameters:**
- `$building`: Building model with `total_apartments` property
- `$month`: Carbon date within summer period

**Returns:** Float representing kWh circulation energy

**Example:**
```php
$building = Building::find(1);
$summerMonth = Carbon::create(2024, 6, 1);
$energy = $calculator->calculateSummerGyvatukas($building, $summerMonth);
// Returns: 142.5 (kWh)
```

**Calculation Logic:**
1. Base calculation: `apartments × default_circulation_rate`
2. Apply building size factors:
   - Large buildings (>50 apartments): 5% efficiency gain
   - Small buildings (<10 apartments): 10% penalty
3. Ensure minimum value (prevents negative results)

#### `calculateWinterGyvatukas(Building $building, Carbon $month): float`
Calculates circulation energy for heating season months using summer average baseline.

**Parameters:**
- `$building`: Building model with summer average data
- `$month`: Carbon date within heating season

**Returns:** Float representing kWh circulation energy

**Example:**
```php
$building = Building::find(1);
$winterMonth = Carbon::create(2024, 12, 1);
$energy = $calculator->calculateWinterGyvatukas($building, $winterMonth);
// Returns: 185.25 (kWh with winter adjustments)
```

**Calculation Logic:**
1. Get summer average for building
2. Apply winter adjustment factors:
   - Peak winter (Dec, Jan, Feb): 30% increase
   - Shoulder months (Oct, Nov, Mar, Apr): 15% increase
   - Other heating months: 20% increase
3. Apply building size factors
4. Ensure minimum value

#### `calculate(Building $building, Carbon $month): float`
Convenience method that automatically determines summer or winter calculation.

```php
$energy = $calculator->calculate($building, $month);
// Automatically uses summer or winter calculation based on month
```

### Summer Average Management

#### `getSummerAverage(Building $building): float`
Retrieves or calculates the summer average for a building.

**Caching Logic:**
- Returns cached value if valid (within 12 months)
- Calculates new average if cache is stale or missing
- Stores result in database for future use

#### `calculateAndStoreSummerAverage(Building $building): float`
Forces recalculation of summer average across the most recent complete summer period.

**Process:**
1. Identifies last complete summer period
2. Calculates monthly circulation energy for each summer month
3. Computes average across all months
4. Stores in database with timestamp
5. Returns calculated average

### Cost Distribution

#### `distributeCirculationCost(Building $building, float $totalCost, string $method = 'equal'): array`
Distributes total circulation costs among properties in a building.

**Parameters:**
- `$building`: Building with related properties
- `$totalCost`: Total cost to distribute
- `$method`: Distribution method ('equal' or 'area')

**Returns:** Array mapping property IDs to cost shares

**Example:**
```php
$distribution = $calculator->distributeCirculationCost($building, 1000.0, 'area');
// Returns: [1 => 250.0, 2 => 300.0, 3 => 450.0] (based on property areas)

$distribution = $calculator->distributeCirculationCost($building, 1000.0, 'equal');
// Returns: [1 => 333.33, 2 => 333.33, 3 => 333.34] (equal distribution)
```

## Cache Management

### Performance Optimization
- All calculations are cached for 24 hours by default
- Cache keys include building ID and calculation period
- Graceful fallback to direct calculation if cache fails

### Cache Methods

#### `clearBuildingCache(Building $building): void`
Clears all cached calculations for a specific building.

```php
$calculator->clearBuildingCache($building);
// Clears both summer and winter calculations for the building
```

#### `clearAllCache(): void`
Clears all gyvatukas calculation cache (use with caution).

```php
$calculator->clearAllCache();
// Forces recalculation for all buildings
```

## Configuration

### Environment Variables
```env
GYVATUKAS_SUMMER_START_MONTH=5
GYVATUKAS_SUMMER_END_MONTH=9
GYVATUKAS_DEFAULT_RATE=15.0
GYVATUKAS_CACHE_TTL=86400
```

### Configuration File (`config/gyvatukas.php`)
```php
return [
    'summer_months' => [5, 6, 7, 8, 9],
    'default_circulation_rate' => 15.0, // kWh per apartment per month
    'peak_winter_adjustment' => 1.3,    // 30% increase
    'shoulder_adjustment' => 1.15,      // 15% increase
    'large_building_threshold' => 50,   // apartments
    'small_building_threshold' => 10,   // apartments
    'cache_ttl' => 86400,              // 24 hours
];
```

## Error Handling

### Validation
The service validates building data before calculations:

```php
// Throws InvalidArgumentException for:
- Buildings with zero or negative apartments
- Buildings exceeding maximum apartment limit (1000)
- Invalid year ranges in summer period calculations
```

### Graceful Degradation
- Cache failures fall back to direct calculation
- Logs errors but continues operation
- Returns minimum values to prevent negative results

### Logging
All significant events are logged:
- Calculation warnings (wrong season requests)
- Cache failures and fallbacks
- Summer average calculations
- Validation errors

## Usage Examples

### Basic Usage in Controllers
```php
class BillingController extends Controller
{
    public function __construct(
        private readonly GyvatukasCalculatorInterface $calculator
    ) {}
    
    public function calculateMonthlyBill(Building $building, string $month): JsonResponse
    {
        $date = Carbon::parse($month);
        $circulation = $this->calculator->calculate($building, $date);
        
        return response()->json([
            'building_id' => $building->id,
            'month' => $month,
            'circulation_kwh' => $circulation,
            'season' => $this->calculator->isSummerPeriod($date) ? 'summer' : 'winter'
        ]);
    }
}
```

### Usage in Billing Services
```php
class BillingService
{
    public function __construct(
        private readonly GyvatukasCalculatorInterface $calculator
    ) {}
    
    public function generateMonthlyBills(Building $building, Carbon $month): Collection
    {
        $totalCirculation = $this->calculator->calculate($building, $month);
        $costPerKwh = $this->getEnergyRate($month);
        $totalCost = $totalCirculation * $costPerKwh;
        
        $distribution = $this->calculator->distributeCirculationCost(
            $building, 
            $totalCost, 
            'area'
        );
        
        return collect($distribution)->map(function ($cost, $propertyId) use ($month) {
            return [
                'property_id' => $propertyId,
                'month' => $month->format('Y-m'),
                'circulation_cost' => $cost,
                'type' => 'gyvatukas'
            ];
        });
    }
}
```

### Usage in Artisan Commands
```php
class RecalculateSummerAveragesCommand extends Command
{
    public function handle(GyvatukasCalculatorInterface $calculator): int
    {
        Building::chunk(100, function ($buildings) use ($calculator) {
            foreach ($buildings as $building) {
                $average = $calculator->calculateAndStoreSummerAverage($building);
                $this->info("Building {$building->id}: {$average} kWh average");
            }
        });
        
        return Command::SUCCESS;
    }
}
```

## Testing

### Unit Tests
The service includes comprehensive unit tests covering:
- Season detection logic
- Summer and winter calculations
- Cache behavior and fallbacks
- Validation and error handling
- Cost distribution methods

### Test Examples
```php
test('calculates summer gyvatukas correctly', function () {
    $building = Building::factory()->create(['total_apartments' => 10]);
    $summerMonth = Carbon::create(2024, 6, 15);
    
    $result = $this->calculator->calculateSummerGyvatukas($building, $summerMonth);
    
    expect($result)->toBeFloat()
        ->and($result)->toBeGreaterThan(0);
});

test('applies winter adjustments correctly', function () {
    $building = Building::factory()->create([
        'total_apartments' => 10,
        'gyvatukas_summer_average' => 150.0
    ]);
    
    $peakWinter = Carbon::create(2024, 1, 15);
    $result = $this->calculator->calculateWinterGyvatukas($building, $peakWinter);
    
    expect($result)->toBeGreaterThan(150.0); // Should be higher than summer average
});
```

## Performance Considerations

### Caching Strategy
- 24-hour cache TTL balances accuracy with performance
- Building-specific cache keys prevent cross-contamination
- Graceful fallback ensures service availability

### Database Optimization
- Summer averages stored in building table for quick access
- Calculation timestamps track data freshness
- Batch operations supported for bulk recalculations

### Memory Usage
- Service uses readonly properties for immutability
- Minimal object creation during calculations
- Efficient array operations for cost distribution

## Related Components

### Models
- `Building`: Contains apartment count and summer average data
- `Property`: Used for cost distribution calculations

### Value Objects
- `SummerPeriod`: Encapsulates summer period logic and validation

### Configuration
- `config/gyvatukas.php`: All calculation parameters and thresholds

### Interfaces
- `GyvatukasCalculatorInterface`: Service contract for dependency injection

## Changelog Notes

### Recent Changes
- Fixed syntax error in `DEFAULT_CIRCULATION_RATE` constant
- Enhanced error handling for cache failures
- Improved validation for building data
- Added cost distribution functionality

### Breaking Changes
None in current version. Service maintains backward compatibility.

### Future Enhancements
- Support for custom circulation rates per building type
- Integration with external energy pricing APIs
- Advanced building efficiency calculations
- Historical trend analysis capabilities