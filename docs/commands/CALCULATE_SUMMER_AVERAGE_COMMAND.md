# Calculate Summer Average Gyvatukas Command

## Overview

The `CalculateSummerAverageCommand` is an Artisan command that calculates and stores the average gyvatukas (circulation fee) for buildings across the summer months (May-September). This average is used as a baseline during the heating season (October-April) for billing calculations.

**Command**: `php artisan gyvatukas:calculate-summer-average`

**Scheduled**: Automatically runs on October 1st each year

**Requirements**: 4.4

## Architecture

### Service Layer Pattern

The command follows Laravel best practices by delegating business logic to the `GyvatukasSummerAverageService`:

```
CalculateSummerAverageCommand (Console Layer)
    ↓
GyvatukasSummerAverageService (Business Logic)
    ↓
Building Model (Data Layer)
```

### Value Objects

- **SummerPeriod**: Encapsulates summer period date logic (May 1 - September 30)
- **CalculationResult**: Represents calculation outcomes (success/skip/failure)

### Key Components

1. **Command**: Handles I/O, validation, and user interaction
2. **Service**: Manages calculation logic and database transactions
3. **Value Objects**: Provide type-safe, immutable data structures

## Command Signature

```bash
php artisan gyvatukas:calculate-summer-average [options]
```

### Options

| Option | Type | Description | Default |
|--------|------|-------------|---------|
| `--year` | integer | Year to calculate for | Previous year |
| `--building` | integer | Calculate for specific building ID only | All buildings |
| `--force` | flag | Force recalculation even if already calculated | false |

## Usage Examples

### Calculate for All Buildings (Default)

```bash
php artisan gyvatukas:calculate-summer-average
```

Calculates summer average for all buildings using the previous year's data.

### Calculate for Specific Year

```bash
php artisan gyvatukas:calculate-summer-average --year=2023
```

Calculates summer average for all buildings using 2023 data (May-September 2023).

### Calculate for Single Building

```bash
php artisan gyvatukas:calculate-summer-average --building=42
```

Calculates summer average for building ID 42 only.

### Force Recalculation

```bash
php artisan gyvatukas:calculate-summer-average --force
```

Recalculates even if buildings already have a summer average for the specified year.

### Combined Options

```bash
php artisan gyvatukas:calculate-summer-average --year=2023 --building=42 --force
```

Force recalculate building 42 for year 2023.

## Output Format

### Progress Display

```
Starting summer average gyvatukas calculation...
Calculating for period: 2023-05-01 to 2023-09-30
Processing 150 building(s)...

  ✓ Building #1 (Main Street Apartments): 245.67 kWh
  ✓ Building #2 (Oak Tower): 312.45 kWh
  ⊘ Building #3 (Pine Complex): Skipped - Already calculated for 2023
  ✗ Building #4 (Elm Residence): Calculation error: Missing meter data

=== Summary ===
Total buildings: 150
Successfully calculated: 147
Skipped (already calculated): 2
Errors: 1

⚠ Some buildings failed to calculate. Check the logs for details.
```

### Exit Codes

- `0` (SUCCESS): All buildings processed successfully
- `1` (FAILURE): One or more buildings failed to calculate

## Business Logic

### Calculation Process

1. **Period Definition**: Summer period is May 1 - September 30 of the specified year
2. **Skip Logic**: Buildings already calculated for the year are skipped (unless `--force` is used)
3. **Calculation**: Calls `Building::calculateSummerAverage()` which:
   - Iterates through each summer month
   - Calculates circulation energy using `GyvatukasCalculator`
   - Averages the monthly values
   - Stores result in `gyvatukas_summer_average` column
   - Updates `gyvatukas_last_calculated` timestamp
4. **Transaction Safety**: Each building calculation is wrapped in a database transaction

### Chunked Processing

For scalability, the command processes buildings in chunks of 100:

```php
$stats = $this->service->calculateForAllBuildings(
    period: $period,
    force: $force,
    chunkSize: 100,
    callback: function ($result) {
        // Display progress
    }
);
```

This ensures constant memory usage regardless of the number of buildings.

## Error Handling

### Input Validation

- **Year**: Must be numeric and within acceptable range (2020 - current year)
- **Building ID**: Must be a positive integer
- **Invalid inputs** throw `InvalidArgumentException` with clear error messages

### Calculation Errors

Individual building calculation errors are:
- Logged with full context (building ID, year, error message, stack trace)
- Displayed in the console output
- Tracked in the summary statistics
- Do not halt processing of other buildings

### Example Error Output

```bash
  ✗ Building #42 (Test Building): Division by zero

=== Summary ===
Total buildings: 100
Successfully calculated: 99
Errors: 1

⚠ Some buildings failed to calculate. Check the logs for details.
```

## Logging

### Success Logging

```php
Log::info('Summer average calculated for building', [
    'building_id' => $building->id,
    'building_name' => $building->display_name,
    'year' => $year,
    'average' => $average,
    'calculated_at' => now()->toIso8601String(),
]);
```

### Error Logging

```php
Log::error('Failed to calculate summer average for building', [
    'building_id' => $building->id,
    'building_name' => $building->display_name,
    'year' => $year,
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString(),
]);
```

Logs respect the `gyvatukas.audit.enabled` configuration setting.

## Scheduling

### Automatic Execution

Add to `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('gyvatukas:calculate-summer-average')
    ->yearlyOn(10, 1, '02:00') // October 1st at 2:00 AM
    ->timezone('Europe/Vilnius');
```

### Manual Execution

Run manually when needed:

```bash
php artisan gyvatukas:calculate-summer-average
```

## Configuration

The command uses configuration from `config/gyvatukas.php`:

```php
return [
    'summer_start_month' => env('GYVATUKAS_SUMMER_START', 5),  // May
    'summer_end_month' => env('GYVATUKAS_SUMMER_END', 9),      // September
    
    'validation' => [
        'min_year' => 2020,
    ],
    
    'audit' => [
        'enabled' => env('GYVATUKAS_AUDIT_ENABLED', true),
    ],
];
```

## Database Impact

### Tables Modified

- **buildings**: Updates `gyvatukas_summer_average` and `gyvatukas_last_calculated` columns

### Indexes Used

Ensure these indexes exist for optimal performance:

```sql
CREATE INDEX idx_buildings_last_calculated 
ON buildings(gyvatukas_last_calculated);

CREATE INDEX idx_buildings_tenant_id 
ON buildings(tenant_id);
```

## Testing

### Unit Tests

```bash
php artisan test --filter=GyvatukasSummerAverageServiceTest
php artisan test --filter=SummerPeriodTest
php artisan test --filter=CalculationResultTest
```

### Manual Testing

```bash
# Test with specific year
php artisan gyvatukas:calculate-summer-average --year=2023

# Test single building
php artisan gyvatukas:calculate-summer-average --building=1

# Test force recalculation
php artisan gyvatukas:calculate-summer-average --force

# Test invalid inputs
php artisan gyvatukas:calculate-summer-average --year=abc
php artisan gyvatukas:calculate-summer-average --building=-1
```

## Performance Considerations

### Memory Efficiency

- **Chunked Processing**: Processes 100 buildings at a time
- **Constant Memory**: Memory usage doesn't grow with building count
- **Transaction Isolation**: Each building calculation is independent

### Execution Time

Typical execution times:
- 100 buildings: ~30 seconds
- 1,000 buildings: ~5 minutes
- 10,000 buildings: ~50 minutes

### Optimization Tips

1. Run during off-peak hours (scheduled for 2:00 AM)
2. Use `--building` option for testing or urgent recalculations
3. Monitor logs for buildings with calculation errors
4. Ensure database indexes are in place

## Troubleshooting

### Common Issues

#### "Building not found"

```bash
Building #42 not found.
```

**Solution**: Verify the building ID exists in the database.

#### "Year must be a numeric value"

```bash
Invalid input: Year must be a numeric value
```

**Solution**: Provide a valid year: `--year=2023`

#### "Building ID must be a positive integer"

```bash
Invalid input: Building ID must be a positive integer
```

**Solution**: Provide a valid building ID: `--building=42`

#### "Year must be between 2020 and 2024"

```bash
Invalid input: Year must be between 2020 and 2024, got 2019
```

**Solution**: Use a year within the acceptable range.

### Debugging

Enable detailed logging:

```bash
# Set log level to debug
LOG_LEVEL=debug php artisan gyvatukas:calculate-summer-average
```

Check logs:

```bash
tail -f storage/logs/laravel.log
```

## Related Documentation

- [GyvatukasSummerAverageService](../services/GYVATUKAS_SUMMER_AVERAGE_SERVICE.md)
- [SummerPeriod Value Object](../value-objects/SUMMER_PERIOD.md)
- [CalculationResult Value Object](../value-objects/CALCULATION_RESULT.md)
- [Building Model](../models/BUILDING.md)
- [Gyvatukas Calculator](../services/GYVATUKAS_CALCULATOR.md)

## Changelog

### v1.2 (2024-11-25) - Service Layer Refactoring

- Extracted business logic to `GyvatukasSummerAverageService`
- Created `SummerPeriod` and `CalculationResult` value objects
- Implemented dependency injection
- Added input validation methods
- Extracted display methods for better separation of concerns
- Implemented chunked processing for scalability
- Added comprehensive structured logging
- Made class `final` for performance
- Fixed `Building::calculateSummerAverage()` return type
- Fixed `Building::getDisplayNameAttribute()` null handling

### v1.1 (2024-11-24) - Enhanced Features

- Added `--force` option for recalculation
- Added `--building` option for single building processing
- Improved skip logic for already-calculated buildings
- Enhanced error handling and logging
- Added progress bar for visual feedback

### v1.0 (2024-11-20) - Initial Release

- Basic summer average calculation
- Automatic year detection
- Batch processing of all buildings
