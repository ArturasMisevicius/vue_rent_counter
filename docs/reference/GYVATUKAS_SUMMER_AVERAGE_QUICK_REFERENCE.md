# Gyvatukas Summer Average - Quick Reference

## TL;DR

Calculate summer average gyvatukas (circulation fees) for buildings to establish baseline for winter billing.

**Command**: `php artisan gyvatukas:calculate-summer-average`

**Period**: May 1 - September 30

**Scheduled**: October 1st at 2:00 AM

## Quick Commands

```bash
# Calculate for all buildings (previous year)
php artisan gyvatukas:calculate-summer-average

# Calculate for specific year
php artisan gyvatukas:calculate-summer-average --year=2023

# Calculate for single building
php artisan gyvatukas:calculate-summer-average --building=42

# Force recalculation
php artisan gyvatukas:calculate-summer-average --force
```

## Quick Code Examples

### Service Usage

```php
use App\Services\GyvatukasSummerAverageService;
use App\ValueObjects\SummerPeriod;

$service = new GyvatukasSummerAverageService();
$period = new SummerPeriod(2023);

// Single building
$result = $service->calculateForBuilding($building, $period);

if ($result->isSuccess()) {
    echo "Average: {$result->average} kWh";
}
```

### Value Objects

```php
// Create summer period
$period = new SummerPeriod(2023);
$period = SummerPeriod::forPreviousYear();
$period = SummerPeriod::forCurrentYear();

// Check result
if ($result->isSuccess()) { /* ... */ }
if ($result->isSkipped()) { /* ... */ }
if ($result->isFailed()) { /* ... */ }
```

## Configuration

```php
// config/gyvatukas.php
return [
    'summer_start_month' => 5,  // May
    'summer_end_month' => 9,    // September
    'validation' => [
        'min_year' => 2020,
    ],
    'audit' => [
        'enabled' => true,
    ],
];
```

## Database

```sql
-- Tables modified
buildings.gyvatukas_summer_average  -- decimal(10,2)
buildings.gyvatukas_last_calculated -- date

-- Indexes
CREATE INDEX idx_buildings_last_calculated ON buildings(gyvatukas_last_calculated);
```

## Testing

```bash
# Run all tests
php artisan test --filter=GyvatukasSummerAverage

# Run specific tests
php artisan test --filter=GyvatukasSummerAverageServiceTest
php artisan test --filter=SummerPeriodTest
php artisan test --filter=CalculationResultTest
```

## Common Issues

| Issue | Solution |
|-------|----------|
| "Building not found" | Verify building ID exists |
| "Year must be numeric" | Use `--year=2023` |
| "Already calculated" | Use `--force` to recalculate |
| Division by zero | Check meter readings exist |

## Logging

```php
// Success
Log::info('Summer average calculated', [
    'building_id' => 42,
    'year' => 2023,
    'average' => 245.67,
]);

// Error
Log::error('Failed to calculate', [
    'building_id' => 42,
    'error' => 'Division by zero',
]);
```

## Performance

- **Memory**: Constant (chunked processing)
- **Speed**: ~0.1-1 second per building
- **Scalability**: Tested with 10,000+ buildings

## Architecture

```
Console Command (I/O)
    ↓
Service (Business Logic)
    ↓
Building Model (Data)
    ↓
GyvatukasCalculator (Calculation)
```

## Full Documentation

- [Command Guide](../commands/CALCULATE_SUMMER_AVERAGE_COMMAND.md)
- [Service API](../services/GYVATUKAS_SUMMER_AVERAGE_SERVICE.md)
- [API Reference](../api/GYVATUKAS_SUMMER_AVERAGE_API.md)
- [Value Objects](../value-objects/SUMMER_PERIOD.md)
