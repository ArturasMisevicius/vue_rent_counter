# SummerPeriod Value Object

## Overview

The `SummerPeriod` value object encapsulates the logic for determining summer period dates (May 1 - September 30) based on configuration and year validation.

**Namespace**: `App\ValueObjects`

**Type**: Immutable Value Object

## Purpose

Provides a type-safe, self-validating representation of the summer calculation period used for gyvatukas baseline calculations. Ensures consistent date handling across the application.

## Class Definition

```php
final readonly class SummerPeriod
{
    public Carbon $startDate;
    public Carbon $endDate;
    public int $year;

    public function __construct(int $year);
    public static function forPreviousYear(): self;
    public static function forCurrentYear(): self;
    public function description(): string;
}
```

## Properties

### startDate

**Type**: `Carbon`

**Description**: Start date of the summer period (May 1st)

**Example**: `2023-05-01 00:00:00`

### endDate

**Type**: `Carbon`

**Description**: End date of the summer period (September 30th)

**Example**: `2023-09-30 23:59:59`

### year

**Type**: `int`

**Description**: The year for the summer period

**Example**: `2023`

## Constructor

### __construct()

Creates a new summer period instance with validation.

**Signature**:
```php
public function __construct(int $year)
```

**Parameters**:
- `$year` (int): The year for the summer period

**Throws**: `InvalidArgumentException` if year is invalid

**Validation Rules**:
- Year must be >= `gyvatukas.validation.min_year` (default: 2020)
- Year must be <= current year
- Year must be numeric

**Example**:
```php
$period = new SummerPeriod(2023);

echo $period->year;        // 2023
echo $period->startDate;   // 2023-05-01 00:00:00
echo $period->endDate;     // 2023-09-30 23:59:59
```

**Configuration**:

The start and end months are configurable in `config/gyvatukas.php`:

```php
return [
    'summer_start_month' => env('GYVATUKAS_SUMMER_START', 5),  // May
    'summer_end_month' => env('GYVATUKAS_SUMMER_END', 9),      // September
    
    'validation' => [
        'min_year' => 2020,
    ],
];
```

## Static Factory Methods

### forPreviousYear()

Creates a summer period for the previous year.

**Signature**:
```php
public static function forPreviousYear(): self
```

**Returns**: `SummerPeriod` for previous year

**Example**:
```php
// If current year is 2024
$period = SummerPeriod::forPreviousYear();

echo $period->year;        // 2023
echo $period->startDate;   // 2023-05-01 00:00:00
echo $period->endDate;     // 2023-09-30 23:59:59
```

**Use Case**: Default behavior for scheduled calculations (calculate previous summer's average at start of heating season).

### forCurrentYear()

Creates a summer period for the current year.

**Signature**:
```php
public static function forCurrentYear(): self
```

**Returns**: `SummerPeriod` for current year

**Example**:
```php
// If current year is 2024
$period = SummerPeriod::forCurrentYear();

echo $period->year;        // 2024
echo $period->startDate;   // 2024-05-01 00:00:00
echo $period->endDate;     // 2024-09-30 23:59:59
```

**Use Case**: Mid-year calculations or testing.

## Instance Methods

### description()

Returns a human-readable description of the period.

**Signature**:
```php
public function description(): string
```

**Returns**: Formatted date range string

**Example**:
```php
$period = new SummerPeriod(2023);

echo $period->description();
// Output: "2023-05-01 to 2023-09-30"
```

**Use Case**: Display in console output, logs, or user interfaces.

## Validation

### Year Range Validation

The constructor validates the year against configured constraints:

```php
private function validateYear(int $year): void
{
    $minYear = config('gyvatukas.validation.min_year', 2020);
    $maxYear = now()->year;

    if ($year < $minYear || $year > $maxYear) {
        throw new InvalidArgumentException(
            "Year must be between {$minYear} and {$maxYear}, got {$year}"
        );
    }
}
```

**Valid Years**: 2020 - current year (configurable)

**Invalid Examples**:
```php
new SummerPeriod(2019);  // Throws: Year must be between 2020 and 2024, got 2019
new SummerPeriod(2025);  // Throws: Year must be between 2020 and 2024, got 2025
```

## Immutability

The class is marked as `readonly`, making all properties immutable after construction:

```php
final readonly class SummerPeriod
{
    public Carbon $startDate;
    public Carbon $endDate;
    public int $year;
}
```

**Benefits**:
- Thread-safe
- Predictable behavior
- No side effects
- Safe to pass between methods

**Example**:
```php
$period = new SummerPeriod(2023);

// This would cause a compile error:
// $period->year = 2024;  // Error: Cannot modify readonly property
```

## Usage Examples

### Basic Usage

```php
use App\ValueObjects\SummerPeriod;

$period = new SummerPeriod(2023);

echo "Year: {$period->year}\n";
echo "Start: {$period->startDate->toDateString()}\n";
echo "End: {$period->endDate->toDateString()}\n";
echo "Description: {$period->description()}\n";
```

**Output**:
```
Year: 2023
Start: 2023-05-01
End: 2023-09-30
Description: 2023-05-01 to 2023-09-30
```

### With Service Layer

```php
use App\Services\GyvatukasSummerAverageService;
use App\ValueObjects\SummerPeriod;
use App\Models\Building;

$service = new GyvatukasSummerAverageService();
$building = Building::find(42);
$period = new SummerPeriod(2023);

$result = $service->calculateForBuilding($building, $period);
```

### In Console Command

```php
use App\ValueObjects\SummerPeriod;
use Illuminate\Console\Command;

class CalculateSummerAverageCommand extends Command
{
    public function handle(): int
    {
        $year = $this->option('year') ?? now()->subYear()->year;
        
        try {
            $period = new SummerPeriod($year);
            $this->info("Calculating for: {$period->description()}");
            
            // Process calculations...
        } catch (\InvalidArgumentException $e) {
            $this->error("Invalid year: {$e->getMessage()}");
            return self::FAILURE;
        }
        
        return self::SUCCESS;
    }
}
```

### Factory Method Usage

```php
// For scheduled calculations (previous year)
$period = SummerPeriod::forPreviousYear();

// For current year calculations
$period = SummerPeriod::forCurrentYear();

// For specific year
$period = new SummerPeriod(2023);
```

### Error Handling

```php
use App\ValueObjects\SummerPeriod;
use InvalidArgumentException;

try {
    $period = new SummerPeriod(2019);
} catch (InvalidArgumentException $e) {
    echo "Error: {$e->getMessage()}\n";
    // Output: Error: Year must be between 2020 and 2024, got 2019
}
```

## Configuration Examples

### Custom Summer Period

To change the summer period months (e.g., June-August):

```php
// config/gyvatukas.php
return [
    'summer_start_month' => 6,  // June
    'summer_end_month' => 8,    // August
];
```

```php
$period = new SummerPeriod(2023);

echo $period->startDate;  // 2023-06-01 00:00:00
echo $period->endDate;    // 2023-08-31 23:59:59
```

### Custom Minimum Year

```php
// config/gyvatukas.php
return [
    'validation' => [
        'min_year' => 2015,
    ],
];
```

```php
$period = new SummerPeriod(2015);  // Now valid
```

## Testing

### Unit Tests

```php
use App\ValueObjects\SummerPeriod;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class SummerPeriodTest extends TestCase
{
    public function test_creates_summer_period_with_correct_dates(): void
    {
        $period = new SummerPeriod(2023);

        $this->assertEquals(2023, $period->year);
        $this->assertEquals('2023-05-01', $period->startDate->toDateString());
        $this->assertEquals('2023-09-30', $period->endDate->toDateString());
    }

    public function test_throws_exception_for_invalid_year(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        new SummerPeriod(2019);
    }

    public function test_description_returns_formatted_string(): void
    {
        $period = new SummerPeriod(2023);

        $this->assertEquals('2023-05-01 to 2023-09-30', $period->description());
    }
}
```

Run tests:
```bash
php artisan test --filter=SummerPeriodTest
```

## Design Patterns

### Value Object Pattern

- **Immutable**: Properties cannot be changed after construction
- **Self-Validating**: Validates input in constructor
- **Equality by Value**: Two periods with same year are equal
- **No Identity**: No database ID or unique identifier

### Factory Pattern

Static factory methods provide convenient construction:
- `forPreviousYear()`: Common use case
- `forCurrentYear()`: Testing and mid-year calculations

## Benefits

1. **Type Safety**: Compile-time checking of period usage
2. **Validation**: Automatic year range validation
3. **Consistency**: Single source of truth for summer period logic
4. **Testability**: Easy to test with predictable behavior
5. **Immutability**: Thread-safe and predictable
6. **Configuration**: Flexible period definition via config

## Related Documentation

- [CalculateSummerAverageCommand](../commands/CALCULATE_SUMMER_AVERAGE_COMMAND.md)
- [GyvatukasSummerAverageService](../services/GYVATUKAS_SUMMER_AVERAGE_SERVICE.md)
- [CalculationResult Value Object](./CALCULATION_RESULT.md)
- [Building Model](../models/BUILDING.md)

## Changelog

### v1.0 (2024-11-25) - Initial Release

- Created immutable value object for summer period
- Implemented year validation
- Added factory methods for common use cases
- Configuration-driven date ranges
- Comprehensive validation and error messages
