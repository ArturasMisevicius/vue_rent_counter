# Gyvatukas Summer Average API Reference

## Overview

This document provides a complete API reference for the gyvatukas summer average calculation system, including console commands, service methods, and value objects.

## Console Command API

### gyvatukas:calculate-summer-average

Calculates and stores summer average gyvatukas for buildings.

**Signature**:
```bash
php artisan gyvatukas:calculate-summer-average [options]
```

**Options**:

| Option | Type | Required | Default | Description |
|--------|------|----------|---------|-------------|
| `--year` | integer | No | Previous year | Year to calculate for |
| `--building` | integer | No | All buildings | Specific building ID |
| `--force` | flag | No | false | Force recalculation |

**Exit Codes**:
- `0`: Success - all buildings processed
- `1`: Failure - one or more errors occurred

**Examples**:

```bash
# Calculate for all buildings (previous year)
php artisan gyvatukas:calculate-summer-average

# Calculate for specific year
php artisan gyvatukas:calculate-summer-average --year=2023

# Calculate for single building
php artisan gyvatukas:calculate-summer-average --building=42

# Force recalculation
php artisan gyvatukas:calculate-summer-average --force

# Combined options
php artisan gyvatukas:calculate-summer-average --year=2023 --building=42 --force
```

**Output Format**:

```
Starting summer average gyvatukas calculation...
Calculating for period: 2023-05-01 to 2023-09-30
Processing 150 building(s)...

  ✓ Building #1 (Main Street Apartments): 245.67 kWh
  ✓ Building #2 (Oak Tower): 312.45 kWh
  ⊘ Building #3 (Pine Complex): Skipped - Already calculated for 2023
  ✗ Building #4 (Elm Residence): Failed - Division by zero

=== Summary ===
Total buildings: 150
Successfully calculated: 147
Skipped (already calculated): 2
Errors: 1
```

**Error Messages**:

| Error | Cause | Solution |
|-------|-------|----------|
| "Invalid input: Year must be a numeric value" | Non-numeric year provided | Use numeric year: `--year=2023` |
| "Invalid input: Building ID must be a positive integer" | Invalid building ID | Use positive integer: `--building=42` |
| "Year must be between 2020 and 2024, got 2019" | Year out of range | Use valid year range |
| "Building #42 not found" | Building doesn't exist | Verify building ID |

## Service API

### GyvatukasSummerAverageService

**Namespace**: `App\Services\GyvatukasSummerAverageService`

#### calculateForBuilding()

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

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `$building` | Building | Yes | - | Building to calculate for |
| `$period` | SummerPeriod | Yes | - | Summer period (May-Sep) |
| `$force` | bool | No | false | Force recalculation |

**Returns**: `CalculationResult` with status and data

**Example**:
```php
use App\Services\GyvatukasSummerAverageService;
use App\ValueObjects\SummerPeriod;
use App\Models\Building;

$service = new GyvatukasSummerAverageService();
$building = Building::find(42);
$period = new SummerPeriod(2023);

$result = $service->calculateForBuilding($building, $period);

if ($result->isSuccess()) {
    echo "Average: {$result->average} kWh";
}
```

**Behavior**:
- Checks if already calculated (unless `$force` is true)
- Wraps calculation in database transaction
- Logs success or failure
- Returns typed result object

#### calculateForBuildings()

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

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `$buildings` | Collection<Building> | Yes | - | Buildings to process |
| `$period` | SummerPeriod | Yes | - | Summer period |
| `$force` | bool | No | false | Force recalculation |

**Returns**: `Collection<CalculationResult>` with result for each building

**Example**:
```php
$buildings = Building::where('tenant_id', 1)->get();
$period = new SummerPeriod(2023);

$results = $service->calculateForBuildings($buildings, $period);

foreach ($results as $result) {
    if ($result->isSuccess()) {
        echo "{$result->building->display_name}: {$result->average} kWh\n";
    }
}
```

#### calculateForAllBuildings()

Calculates summer average for all buildings with chunked processing.

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

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `$period` | SummerPeriod | Yes | - | Summer period |
| `$force` | bool | No | false | Force recalculation |
| `$chunkSize` | int | No | 100 | Buildings per chunk |
| `$callback` | callable\|null | No | null | Progress callback |

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
    chunkSize: 50,
    callback: function ($result) {
        echo "Processed: {$result->building->display_name}\n";
    }
);

echo "Success: {$stats['success']}\n";
echo "Failed: {$stats['failed']}\n";
```

**Performance**:
- Constant memory usage (chunked processing)
- Suitable for 10,000+ buildings
- Progress callback for monitoring

#### calculateForBuildingId()

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

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `$buildingId` | int | Yes | - | Building ID |
| `$period` | SummerPeriod | Yes | - | Summer period |
| `$force` | bool | No | false | Force recalculation |

**Returns**: `CalculationResult` or `null` if building not found

**Example**:
```php
$result = $service->calculateForBuildingId(42, new SummerPeriod(2023));

if ($result === null) {
    echo "Building not found\n";
} elseif ($result->isSuccess()) {
    echo "Average: {$result->average} kWh\n";
}
```

## Value Object API

### SummerPeriod

**Namespace**: `App\ValueObjects\SummerPeriod`

#### Constructor

**Signature**:
```php
public function __construct(int $year)
```

**Parameters**:

| Parameter | Type | Required | Validation |
|-----------|------|----------|------------|
| `$year` | int | Yes | 2020 - current year |

**Throws**: `InvalidArgumentException` if year is invalid

**Example**:
```php
$period = new SummerPeriod(2023);

echo $period->year;        // 2023
echo $period->startDate;   // 2023-05-01 00:00:00
echo $period->endDate;     // 2023-09-30 23:59:59
```

#### Static Factory Methods

**forPreviousYear()**:
```php
public static function forPreviousYear(): self
```

Returns summer period for previous year.

**forCurrentYear()**:
```php
public static function forCurrentYear(): self
```

Returns summer period for current year.

**Example**:
```php
$period = SummerPeriod::forPreviousYear();  // Previous year
$period = SummerPeriod::forCurrentYear();   // Current year
```

#### Instance Methods

**description()**:
```php
public function description(): string
```

Returns formatted date range string.

**Example**:
```php
$period = new SummerPeriod(2023);
echo $period->description();  // "2023-05-01 to 2023-09-30"
```

#### Properties

| Property | Type | Description | Example |
|----------|------|-------------|---------|
| `year` | int | Year of period | 2023 |
| `startDate` | Carbon | Start date (May 1) | 2023-05-01 00:00:00 |
| `endDate` | Carbon | End date (Sep 30) | 2023-09-30 23:59:59 |

### CalculationResult

**Namespace**: `App\ValueObjects\CalculationResult`

#### Static Factory Methods

**success()**:
```php
public static function success(Building $building, float $average): self
```

Creates successful result.

**skipped()**:
```php
public static function skipped(Building $building, string $reason): self
```

Creates skipped result.

**failed()**:
```php
public static function failed(Building $building, string $errorMessage): self
```

Creates failed result.

**Example**:
```php
$result = CalculationResult::success($building, 245.67);
$result = CalculationResult::skipped($building, 'Already calculated');
$result = CalculationResult::failed($building, 'Division by zero');
```

#### Instance Methods

**Status Checks**:
```php
public function isSuccess(): bool
public function isSkipped(): bool
public function isFailed(): bool
```

**Message Formatting**:
```php
public function getMessage(): string
```

Returns formatted message with building info and result.

**Example**:
```php
if ($result->isSuccess()) {
    echo $result->getMessage();
    // "Building #42 (Main Street Apartments): 245.67 kWh"
}
```

#### Properties

| Property | Type | Description | Present When |
|----------|------|-------------|--------------|
| `building` | Building | The building | Always |
| `status` | string | Result status | Always |
| `average` | ?float | Calculated average | Success only |
| `errorMessage` | ?string | Error/skip reason | Skipped/Failed |

**Status Values**:
- `'success'`: Calculation completed
- `'skipped'`: Already calculated
- `'failed'`: Error occurred

## Configuration API

### config/gyvatukas.php

```php
return [
    // Summer period months
    'summer_start_month' => env('GYVATUKAS_SUMMER_START', 5),  // May
    'summer_end_month' => env('GYVATUKAS_SUMMER_END', 9),      // September
    
    // Validation
    'validation' => [
        'min_year' => 2020,
    ],
    
    // Audit logging
    'audit' => [
        'enabled' => env('GYVATUKAS_AUDIT_ENABLED', true),
    ],
];
```

**Environment Variables**:

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `GYVATUKAS_SUMMER_START` | int | 5 | Summer start month (1-12) |
| `GYVATUKAS_SUMMER_END` | int | 9 | Summer end month (1-12) |
| `GYVATUKAS_AUDIT_ENABLED` | bool | true | Enable audit logging |

## Database API

### Tables Modified

**buildings**:

| Column | Type | Description |
|--------|------|-------------|
| `gyvatukas_summer_average` | decimal(10,2) | Calculated average in kWh |
| `gyvatukas_last_calculated` | date | Last calculation date |

**Indexes**:
```sql
CREATE INDEX idx_buildings_last_calculated ON buildings(gyvatukas_last_calculated);
CREATE INDEX idx_buildings_tenant_id ON buildings(tenant_id);
```

## Logging API

### Success Log

**Level**: `info`

**Message**: `"Summer average calculated for building"`

**Context**:
```php
[
    'building_id' => 42,
    'building_name' => 'Main Street Apartments',
    'year' => 2023,
    'average' => 245.67,
    'calculated_at' => '2024-11-25T14:30:00+00:00',
]
```

### Error Log

**Level**: `error`

**Message**: `"Failed to calculate summer average for building"`

**Context**:
```php
[
    'building_id' => 42,
    'building_name' => 'Main Street Apartments',
    'year' => 2023,
    'error' => 'Division by zero',
    'trace' => '...',
]
```

## HTTP API (Optional)

If you want to expose this via HTTP API:

### POST /api/gyvatukas/calculate-summer-average

**Request**:
```json
{
    "year": 2023,
    "building_id": 42,
    "force": false
}
```

**Validation Rules**:
```php
[
    'year' => 'required|integer|min:2020|max:' . now()->year,
    'building_id' => 'nullable|integer|exists:buildings,id',
    'force' => 'boolean',
]
```

**Response (Success)**:
```json
{
    "status": "success",
    "building_id": 42,
    "building_name": "Main Street Apartments",
    "average": 245.67,
    "message": "Building #42 (Main Street Apartments): 245.67 kWh"
}
```

**Response (Error)**:
```json
{
    "status": "failed",
    "building_id": 42,
    "building_name": "Main Street Apartments",
    "error": "Division by zero",
    "message": "Building #42 (Main Street Apartments): Failed - Division by zero"
}
```

**Status Codes**:
- `200`: Success
- `404`: Building not found
- `422`: Validation error or calculation failed

## Testing API

### Unit Tests

```bash
# Test service
php artisan test --filter=GyvatukasSummerAverageServiceTest

# Test value objects
php artisan test --filter=SummerPeriodTest
php artisan test --filter=CalculationResultTest

# Test all
php artisan test tests/Unit/Services/GyvatukasSummerAverageServiceTest.php
php artisan test tests/Unit/ValueObjects/
```

### Manual Testing

```bash
# Test command
php artisan gyvatukas:calculate-summer-average --year=2023

# Test with specific building
php artisan gyvatukas:calculate-summer-average --building=1

# Test force recalculation
php artisan gyvatukas:calculate-summer-average --force
```

## Related Documentation

- [Command Documentation](../commands/CALCULATE_SUMMER_AVERAGE_COMMAND.md)
- [Service Documentation](../services/GYVATUKAS_SUMMER_AVERAGE_SERVICE.md)
- [SummerPeriod Documentation](../value-objects/SUMMER_PERIOD.md)
- [CalculationResult Documentation](../value-objects/CALCULATION_RESULT.md)
- [Refactoring Summary](../refactoring/CALCULATE_SUMMER_AVERAGE_COMMAND_REFACTORING.md)
