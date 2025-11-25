# CalculationResult Value Object

## Overview

The `CalculationResult` value object represents the outcome of a summer average gyvatukas calculation for a building. It encapsulates success, skip, or failure states with relevant data.

**Namespace**: `App\ValueObjects`

**Type**: Immutable Value Object

## Purpose

Provides a type-safe, expressive way to represent calculation outcomes, eliminating the need for error codes or boolean flags. Enables pattern matching and clear status handling.

## Class Definition

```php
final readonly class CalculationResult
{
    public Building $building;
    public string $status;
    public ?float $average;
    public ?string $errorMessage;

    public function __construct(
        Building $building,
        string $status,
        ?float $average = null,
        ?string $errorMessage = null,
    );

    public static function success(Building $building, float $average): self;
    public static function skipped(Building $building, string $reason): self;
    public static function failed(Building $building, string $errorMessage): self;
    
    public function isSuccess(): bool;
    public function isSkipped(): bool;
    public function isFailed(): bool;
    public function getMessage(): string;
}
```

## Properties

### building

**Type**: `Building`

**Description**: The building this result is for

**Always Present**: Yes

### status

**Type**: `string`

**Description**: Result status

**Possible Values**:
- `'success'`: Calculation completed successfully
- `'skipped'`: Calculation was skipped (already calculated)
- `'failed'`: Calculation failed with error

### average

**Type**: `?float`

**Description**: Calculated average in kWh (only for success status)

**Present When**: `status === 'success'`

**Example**: `245.67`

### errorMessage

**Type**: `?string`

**Description**: Error or skip reason (only for skipped/failed status)

**Present When**: `status === 'skipped'` or `status === 'failed'`

**Example**: `"Already calculated for 2023"` or `"Division by zero"`

## Static Factory Methods

### success()

Creates a successful calculation result.

**Signature**:
```php
public static function success(Building $building, float $average): self
```

**Parameters**:
- `$building` (Building): The building
- `$average` (float): Calculated average in kWh

**Returns**: `CalculationResult` with success status

**Example**:
```php
$building = Building::find(42);
$result = CalculationResult::success($building, 245.67);

echo $result->status;   // 'success'
echo $result->average;  // 245.67
echo $result->errorMessage;  // null
```

### skipped()

Creates a skipped calculation result.

**Signature**:
```php
public static function skipped(Building $building, string $reason): self
```

**Parameters**:
- `$building` (Building): The building
- `$reason` (string): Reason for skipping

**Returns**: `CalculationResult` with skipped status

**Example**:
```php
$building = Building::find(42);
$result = CalculationResult::skipped($building, 'Already calculated for 2023');

echo $result->status;        // 'skipped'
echo $result->average;       // null
echo $result->errorMessage;  // 'Already calculated for 2023'
```

### failed()

Creates a failed calculation result.

**Signature**:
```php
public static function failed(Building $building, string $errorMessage): self
```

**Parameters**:
- `$building` (Building): The building
- `$errorMessage` (string): Error message

**Returns**: `CalculationResult` with failed status

**Example**:
```php
$building = Building::find(42);
$result = CalculationResult::failed($building, 'Division by zero');

echo $result->status;        // 'failed'
echo $result->average;       // null
echo $result->errorMessage;  // 'Division by zero'
```

## Instance Methods

### isSuccess()

Checks if the calculation was successful.

**Signature**:
```php
public function isSuccess(): bool
```

**Returns**: `true` if status is 'success', `false` otherwise

**Example**:
```php
$result = CalculationResult::success($building, 245.67);

if ($result->isSuccess()) {
    echo "Success! Average: {$result->average} kWh";
}
```

### isSkipped()

Checks if the calculation was skipped.

**Signature**:
```php
public function isSkipped(): bool
```

**Returns**: `true` if status is 'skipped', `false` otherwise

**Example**:
```php
$result = CalculationResult::skipped($building, 'Already calculated');

if ($result->isSkipped()) {
    echo "Skipped: {$result->errorMessage}";
}
```

### isFailed()

Checks if the calculation failed.

**Signature**:
```php
public function isFailed(): bool
```

**Returns**: `true` if status is 'failed', `false` otherwise

**Example**:
```php
$result = CalculationResult::failed($building, 'Error occurred');

if ($result->isFailed()) {
    echo "Failed: {$result->errorMessage}";
}
```

### getMessage()

Returns a formatted message for display.

**Signature**:
```php
public function getMessage(): string
```

**Returns**: Human-readable message with building info and result

**Format**:
- **Success**: `"Building #42 (Main Street Apartments): 245.67 kWh"`
- **Skipped**: `"Building #42 (Main Street Apartments): Skipped - Already calculated for 2023"`
- **Failed**: `"Building #42 (Main Street Apartments): Failed - Division by zero"`

**Example**:
```php
$result = CalculationResult::success($building, 245.67);

echo $result->getMessage();
// Output: "Building #42 (Main Street Apartments): 245.67 kWh"
```

## Immutability

The class is marked as `readonly`, making all properties immutable:

```php
final readonly class CalculationResult
{
    public Building $building;
    public string $status;
    public ?float $average;
    public ?string $errorMessage;
}
```

**Benefits**:
- Thread-safe
- Predictable behavior
- No side effects
- Safe to pass between methods

## Usage Examples

### Basic Success Handling

```php
use App\ValueObjects\CalculationResult;
use App\Models\Building;

$building = Building::find(42);
$result = CalculationResult::success($building, 245.67);

if ($result->isSuccess()) {
    echo "Calculated average: {$result->average} kWh\n";
    echo "Building: {$result->building->display_name}\n";
}
```

### Pattern Matching with Match Expression

```php
$message = match ($result->status) {
    'success' => "✓ {$result->getMessage()}",
    'skipped' => "⊘ {$result->getMessage()}",
    'failed' => "✗ {$result->getMessage()}",
    default => "Unknown status",
};

echo $message;
```

### Service Layer Usage

```php
use App\Services\GyvatukasSummerAverageService;
use App\ValueObjects\SummerPeriod;

$service = new GyvatukasSummerAverageService();
$building = Building::find(42);
$period = new SummerPeriod(2023);

$result = $service->calculateForBuilding($building, $period);

if ($result->isSuccess()) {
    // Store result, send notification, etc.
    $this->notifySuccess($result->building, $result->average);
} elseif ($result->isSkipped()) {
    // Log skip reason
    Log::info("Calculation skipped: {$result->errorMessage}");
} else {
    // Handle error
    Log::error("Calculation failed: {$result->errorMessage}");
}
```

### Console Command Display

```php
private function displayResult(CalculationResult $result): void
{
    $this->newLine();

    match ($result->status) {
        'success' => $this->line("  ✓ {$result->getMessage()}"),
        'skipped' => $this->line("  ⊘ {$result->getMessage()}"),
        'failed' => $this->error("  ✗ {$result->getMessage()}"),
        default => null,
    };
}
```

### Batch Processing

```php
$results = $service->calculateForBuildings($buildings, $period);

$successful = $results->filter(fn($r) => $r->isSuccess());
$skipped = $results->filter(fn($r) => $r->isSkipped());
$failed = $results->filter(fn($r) => $r->isFailed());

echo "Success: {$successful->count()}\n";
echo "Skipped: {$skipped->count()}\n";
echo "Failed: {$failed->count()}\n";

// Process failures
foreach ($failed as $result) {
    Log::error("Building {$result->building->id} failed", [
        'error' => $result->errorMessage,
    ]);
}
```

### API Response

```php
use Illuminate\Http\JsonResponse;

public function calculateSummerAverage(Request $request): JsonResponse
{
    $building = Building::findOrFail($request->building_id);
    $period = new SummerPeriod($request->year);
    
    $result = $this->service->calculateForBuilding($building, $period);
    
    return response()->json([
        'status' => $result->status,
        'building_id' => $result->building->id,
        'building_name' => $result->building->display_name,
        'average' => $result->average,
        'message' => $result->getMessage(),
        'error' => $result->errorMessage,
    ], $result->isSuccess() ? 200 : 422);
}
```

### Error Recovery

```php
$result = $service->calculateForBuilding($building, $period);

if ($result->isFailed()) {
    // Attempt recovery
    if (str_contains($result->errorMessage, 'Division by zero')) {
        // Handle missing data
        $this->createDefaultMeterReadings($building);
        
        // Retry
        $result = $service->calculateForBuilding($building, $period, force: true);
    }
}
```

## Testing

### Unit Tests

```php
use App\ValueObjects\CalculationResult;
use App\Models\Building;
use Tests\TestCase;

class CalculationResultTest extends TestCase
{
    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->building = new Building();
        $this->building->id = 1;
        $this->building->display_name = 'Test Building';
    }

    public function test_creates_success_result(): void
    {
        $result = CalculationResult::success($this->building, 123.45);

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isSkipped());
        $this->assertFalse($result->isFailed());
        $this->assertEquals('success', $result->status);
        $this->assertEquals(123.45, $result->average);
        $this->assertNull($result->errorMessage);
    }

    public function test_creates_skipped_result(): void
    {
        $result = CalculationResult::skipped($this->building, 'Already calculated');

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isSkipped());
        $this->assertFalse($result->isFailed());
        $this->assertEquals('skipped', $result->status);
        $this->assertNull($result->average);
        $this->assertEquals('Already calculated', $result->errorMessage);
    }

    public function test_creates_failed_result(): void
    {
        $result = CalculationResult::failed($this->building, 'Calculation error');

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isSkipped());
        $this->assertTrue($result->isFailed());
        $this->assertEquals('failed', $result->status);
        $this->assertNull($result->average);
        $this->assertEquals('Calculation error', $result->errorMessage);
    }

    public function test_get_message_formats_correctly(): void
    {
        $result = CalculationResult::success($this->building, 123.45);

        $message = $result->getMessage();

        $this->assertStringContainsString('Test Building', $message);
        $this->assertStringContainsString('123.45', $message);
    }
}
```

Run tests:
```bash
php artisan test --filter=CalculationResultTest
```

## Design Patterns

### Value Object Pattern

- **Immutable**: Properties cannot be changed after construction
- **Self-Contained**: Contains all data needed to represent the result
- **Equality by Value**: Two results with same data are equal
- **No Identity**: No database ID or unique identifier

### Factory Pattern

Static factory methods provide expressive construction:
- `success()`: Clear intent for successful calculation
- `skipped()`: Clear intent for skipped calculation
- `failed()`: Clear intent for failed calculation

### Result Pattern

Represents operation outcomes without exceptions:
- **Type-Safe**: Compile-time checking
- **Explicit**: Forces handling of all cases
- **Composable**: Easy to chain and transform

## Benefits

1. **Type Safety**: Compile-time checking of result handling
2. **Expressiveness**: Clear intent with factory methods
3. **Pattern Matching**: Works well with PHP 8+ match expressions
4. **Immutability**: Thread-safe and predictable
5. **No Exceptions**: Errors are values, not control flow
6. **Testability**: Easy to test with predictable behavior

## Common Patterns

### Guard Clauses

```php
$result = $service->calculateForBuilding($building, $period);

if ($result->isFailed()) {
    return response()->json(['error' => $result->errorMessage], 422);
}

// Continue with success case
return response()->json(['average' => $result->average]);
```

### Early Returns

```php
public function processCalculation(Building $building): void
{
    $result = $this->service->calculateForBuilding($building, $this->period);
    
    if (!$result->isSuccess()) {
        return;
    }
    
    // Process successful result
    $this->storeResult($result->average);
}
```

### Collection Operations

```php
$results = $service->calculateForBuildings($buildings, $period);

// Get all successful averages
$averages = $results
    ->filter(fn($r) => $r->isSuccess())
    ->map(fn($r) => $r->average);

// Get all error messages
$errors = $results
    ->filter(fn($r) => $r->isFailed())
    ->map(fn($r) => $r->errorMessage);
```

## Related Documentation

- [CalculateSummerAverageCommand](../commands/CALCULATE_SUMMER_AVERAGE_COMMAND.md)
- [GyvatukasSummerAverageService](../services/GYVATUKAS_SUMMER_AVERAGE_SERVICE.md)
- [SummerPeriod Value Object](./SUMMER_PERIOD.md)
- [Building Model](../models/BUILDING.md)

## Changelog

### v1.0 (2024-11-25) - Initial Release

- Created immutable value object for calculation results
- Implemented factory methods for all result types
- Added status checking methods
- Implemented formatted message generation
- Full type safety with readonly properties
