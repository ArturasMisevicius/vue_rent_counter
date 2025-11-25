# GyvatukasSummerAverageService

## Overview

The `GyvatukasSummerAverageService` handles the business logic for calculating and storing summer average gyvatukas (circulation fees) for buildings. This service is used during the heating season to establish baseline circulation costs.

**Namespace**: `App\Services`

**Requirements**: 4.4

## Purpose

During the heating season (October-April), buildings need a baseline for circulation energy costs. This service calculates the average circulation energy from the summer months (May-September) when heating is not active, providing a fair baseline for winter billing.

## Architecture

### Service Layer Pattern

```
Console Command / API Controller
    ↓
GyvatukasSummerAverageService (Business Logic)
    ↓
Building Model (Data Access)
    ↓
GyvatukasCalculator (Calculation Logic)
```

### Dependencies

- **Building Model**: Data access and persistence
- **GyvatukasCalculator**: Circulation energy calculations
- **SummerPeriod**: Date range encapsulation
- **CalculationResult**: Result encapsulation
- **Database Transactions**: Atomic operations
- **Logging**: Audit trail

## Class Definition

```php
final class GyvatukasSummerAverageService
{
    /**
     * Calculate summer average for a single building.
     */
    public function calculateForBuilding(
        Building $building,
        SummerPeriod $period,
        bool $force = false
    ): CalculationResult;

    /**
     * Calculate summer average for multiple buildings.
     */
    public function calculateForBuildings(
        Collection $buildings,
        SummerPeriod $period,
        bool $force = false
    ): Collection;

    /**
     * Calculate summer average for all buildings using chunked processing.
     */
    public function calculateForAllBuildings(
        SummerPeriod $period,
        bool $force = false,
        int $chunkSize = 100,
        ?callable $callback = null
    ): array;

    /**
     * Calculate for a specific building by ID.
     */
    public function calculateForBuildingId(
        int $buildingId,
        SummerPeriod $period,
        bool $force = false
    ): ?CalculationResult;
}
```

## Methods

### calculateForBuilding()

Calculates summer average for a single building.

**Signature**:
```php
public function calculateForBuilding(
    Building $building,
    SummerPeriod $period,
    bool $force = false
): CalculationResult
```

**Parameters**:
- `$building` (Building): The building to calculate for
- `$period` (SummerPeriod): The summer period (May-September)
- `$force` (bool): Force recalculation even if already calculated

**Returns**: `CalculationResult` with status (success/skipped/failed)

**Example**:
```php
$service = new GyvatukasSummerAverageService();
$building = Building::find(42);
$period = new SummerPeriod(2023);

$result = $service->calculateForBuilding($building, $period);

if ($result->isSuccess()) {
    echo "Average: {$result->average} kWh";
}
```

**Skip Logic**:
- Checks if building already has calculation for the specified year
- Skips if `$force` is false and calculation exists
- Returns `CalculationResult::skipped()` with reason

**Transaction Safety**:
- Wraps calculation in `DB::transaction()`
- Rolls back on any exception
- Ensures atomic updates

### calculateForBuildings()

Calculates summer average for multiple buildings.

**Signature**:
```php
public function calculateForBuildings(
    Collection $buildings,
    SummerPeriod $period,
    bool $force = false
): Collection
```

**Parameters**:
- `$buildings` (Collection<Building>): Buildings to calculate for
- `$period` (SummerPeriod): The summer period
- `$force` (bool): Force recalculation

**Returns**: `Collection<CalculationResult>` with results for each building

**Example**:
```php
$buildings = Building::where('tenant_id', 1)->get();
$period = new SummerPeriod(2023);

$results = $service->calculateForBuildings($buildings, $period);

foreach ($results as $result) {
    echo $result->getMessage() . "\n";
}
```

### calculateForAllBuildings()

Calculates summer average for all buildings using chunked processing.

**Signature**:
```php
public function calculateForAllBuildings(
    SummerPeriod $period,
    bool $force = false,
    int $chunkSize = 100,
    ?callable $callback = null
): array
```

**Parameters**:
- `$period` (SummerPeriod): The summer period
- `$force` (bool): Force recalculation
- `$chunkSize` (int): Number of buildings per chunk (default: 100)
- `$callback` (callable|null): Optional progress callback

**Returns**: Array with statistics:
```php
[
    'success' => 147,
    'skipped' => 2,
    'failed' => 1,
    'results' => Collection<CalculationResult>
]
```

**Example**:
```php
$period = new SummerPeriod(2023);

$stats = $service->calculateForAllBuildings(
    period: $period,
    force: false,
    chunkSize: 100,
    callback: function ($result) {
        echo "Processed: {$result->building->display_name}\n";
    }
);

echo "Success: {$stats['success']}\n";
echo "Failed: {$stats['failed']}\n";
```

**Memory Efficiency**:
- Processes buildings in chunks to avoid memory exhaustion
- Constant memory usage regardless of total building count
- Suitable for large datasets (10,000+ buildings)

### calculateForBuildingId()

Calculates summer average for a specific building by ID.

**Signature**:
```php
public function calculateForBuildingId(
    int $buildingId,
    SummerPeriod $period,
    bool $force = false
): ?CalculationResult
```

**Parameters**:
- `$buildingId` (int): The building ID
- `$period` (SummerPeriod): The summer period
- `$force` (bool): Force recalculation

**Returns**: `CalculationResult` or `null` if building not found

**Example**:
```php
$result = $service->calculateForBuildingId(42, new SummerPeriod(2023));

if ($result === null) {
    echo "Building not found\n";
} elseif ($result->isSuccess()) {
    echo "Calculated: {$result->average} kWh\n";
}
```

## Private Methods

### isAlreadyCalculated()

Checks if a building already has a calculation for the given year.

```php
private function isAlreadyCalculated(Building $building, int $year): bool
```

### logCalculation()

Logs successful calculation with full context.

```php
private function logCalculation(Building $building, int $year, float $average): void
```

**Log Entry**:
```json
{
    "level": "info",
    "message": "Summer average calculated for building",
    "context": {
        "building_id": 42,
        "building_name": "Main Street Apartments",
        "year": 2023,
        "average": 245.67,
        "calculated_at": "2024-11-25T14:30:00+00:00"
    }
}
```

### logError()

Logs calculation errors with full context and stack trace.

```php
private function logError(Building $building, int $year, \Exception $exception): void
```

**Log Entry**:
```json
{
    "level": "error",
    "message": "Failed to calculate summer average for building",
    "context": {
        "building_id": 42,
        "building_name": "Main Street Apartments",
        "year": 2023,
        "error": "Division by zero",
        "trace": "..."
    }
}
```

## Configuration

The service respects configuration from `config/gyvatukas.php`:

```php
return [
    'audit' => [
        'enabled' => env('GYVATUKAS_AUDIT_ENABLED', true),
    ],
];
```

When `audit.enabled` is false, logging is suppressed.

## Error Handling

### Exception Handling

All exceptions are caught and converted to `CalculationResult::failed()`:

```php
try {
    $average = DB::transaction(function () use ($building, $period) {
        return $building->calculateSummerAverage(
            $period->startDate,
            $period->endDate
        );
    });
    
    return CalculationResult::success($building, $average);
} catch (\Exception $e) {
    $this->logError($building, $period->year, $e);
    return CalculationResult::failed($building, $e->getMessage());
}
```

### Common Exceptions

- **Division by zero**: No meter readings for summer period
- **Database errors**: Connection issues, constraint violations
- **Calculation errors**: Invalid data, missing relationships

## Usage Examples

### Basic Usage

```php
use App\Services\GyvatukasSummerAverageService;
use App\ValueObjects\SummerPeriod;
use App\Models\Building;

$service = new GyvatukasSummerAverageService();
$building = Building::find(42);
$period = new SummerPeriod(2023);

$result = $service->calculateForBuilding($building, $period);

if ($result->isSuccess()) {
    echo "Success! Average: {$result->average} kWh\n";
} elseif ($result->isSkipped()) {
    echo "Skipped: {$result->errorMessage}\n";
} else {
    echo "Failed: {$result->errorMessage}\n";
}
```

### Batch Processing with Progress

```php
$period = new SummerPeriod(2023);
$processed = 0;

$stats = $service->calculateForAllBuildings(
    period: $period,
    force: false,
    chunkSize: 50,
    callback: function ($result) use (&$processed) {
        $processed++;
        echo "Processed {$processed}: {$result->getMessage()}\n";
    }
);

echo "\nSummary:\n";
echo "Success: {$stats['success']}\n";
echo "Skipped: {$stats['skipped']}\n";
echo "Failed: {$stats['failed']}\n";
```

### Force Recalculation

```php
$building = Building::find(42);
$period = new SummerPeriod(2023);

// Force recalculation even if already calculated
$result = $service->calculateForBuilding($building, $period, force: true);
```

### API Controller Integration

```php
use App\Services\GyvatukasSummerAverageService;
use App\ValueObjects\SummerPeriod;
use Illuminate\Http\JsonResponse;

class GyvatukasController extends Controller
{
    public function __construct(
        private readonly GyvatukasSummerAverageService $service
    ) {}

    public function calculateSummerAverage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:' . now()->year,
            'building_id' => 'nullable|integer|exists:buildings,id',
            'force' => 'boolean',
        ]);

        $period = new SummerPeriod($validated['year']);
        $force = $validated['force'] ?? false;

        if (isset($validated['building_id'])) {
            $result = $this->service->calculateForBuildingId(
                $validated['building_id'],
                $period,
                $force
            );

            if ($result === null) {
                return response()->json(['error' => 'Building not found'], 404);
            }

            return response()->json([
                'status' => $result->status,
                'average' => $result->average,
                'message' => $result->getMessage(),
            ]);
        }

        $stats = $this->service->calculateForAllBuildings($period, $force);

        return response()->json([
            'success' => $stats['success'],
            'skipped' => $stats['skipped'],
            'failed' => $stats['failed'],
        ]);
    }
}
```

## Testing

### Unit Tests

```php
use App\Services\GyvatukasSummerAverageService;
use App\ValueObjects\SummerPeriod;
use App\Models\Building;
use Tests\TestCase;

class GyvatukasSummerAverageServiceTest extends TestCase
{
    public function test_calculates_for_building_successfully(): void
    {
        $service = new GyvatukasSummerAverageService();
        $building = Building::factory()->create();
        $period = new SummerPeriod(2023);

        $result = $service->calculateForBuilding($building, $period);

        $this->assertTrue($result->isSuccess());
        $this->assertNotNull($result->average);
    }

    public function test_skips_already_calculated_building(): void
    {
        $service = new GyvatukasSummerAverageService();
        $building = Building::factory()->create([
            'gyvatukas_last_calculated' => '2023-10-01',
        ]);
        $period = new SummerPeriod(2023);

        $result = $service->calculateForBuilding($building, $period);

        $this->assertTrue($result->isSkipped());
    }
}
```

Run tests:
```bash
php artisan test --filter=GyvatukasSummerAverageServiceTest
```

## Performance Considerations

### Memory Usage

- **Chunked Processing**: Constant memory usage
- **Transaction Isolation**: Each building is independent
- **Lazy Loading**: Relationships loaded only when needed

### Execution Time

Typical execution times per building:
- Simple building: ~0.1 seconds
- Complex building: ~0.5 seconds
- With many meters: ~1 second

### Optimization Tips

1. Use appropriate chunk size (default: 100)
2. Run during off-peak hours
3. Monitor database query performance
4. Ensure proper indexes on `buildings` table

## Related Documentation

- [CalculateSummerAverageCommand](../commands/CALCULATE_SUMMER_AVERAGE_COMMAND.md)
- [SummerPeriod Value Object](../value-objects/SUMMER_PERIOD.md)
- [CalculationResult Value Object](../value-objects/CALCULATION_RESULT.md)
- [Building Model](../models/BUILDING.md)
- [GyvatukasCalculator](./GYVATUKAS_CALCULATOR.md)

## Changelog

### v1.0 (2024-11-25) - Initial Release

- Extracted from `CalculateSummerAverageCommand`
- Implemented service layer pattern
- Added chunked processing support
- Implemented skip logic
- Added comprehensive logging
- Created value objects for type safety
