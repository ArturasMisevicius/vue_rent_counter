# DistributionMethod Enum Documentation

## Overview

The `DistributionMethod` enum defines how shared utility costs (particularly circulation energy costs) are allocated among properties in a building. This enum is central to the Universal Utility Management System and supports flexible cost distribution strategies for multi-tenant buildings.

**Location:** `app/Enums/DistributionMethod.php`  
**Test Coverage:** `tests/Unit/Enums/DistributionMethodTest.php` (22 tests, 70 assertions)  
**Namespace:** `App\Enums`

## Enum Cases

### EQUAL
**Value:** `'equal'`  
**Description:** Distributes costs equally among all properties regardless of size or consumption.

**Use Cases:**
- Small buildings where equal distribution is fair
- Situations where consumption data is unavailable
- Simplified billing scenarios

**Requirements:**
- None (simplest distribution method)

**Example:**
```php
use App\Enums\DistributionMethod;

$method = DistributionMethod::EQUAL;
$method->requiresAreaData(); // false
$method->requiresConsumptionData(); // false
$method->supportsCustomFormulas(); // false
```

### AREA
**Value:** `'area'`  
**Description:** Distributes costs proportionally based on property area (square meters).

**Use Cases:**
- Residential buildings where area is a fair proxy for usage
- Heating cost distribution
- Common area maintenance costs

**Requirements:**
- Property area data (area_sqm field)
- Supports multiple area types: total_area, heated_area, commercial_area

**Supported Area Types:**
```php
$areaTypes = DistributionMethod::AREA->getSupportedAreaTypes();
// Returns:
// [
//     'total_area' => 'Total Area',
//     'heated_area' => 'Heated Area',
//     'commercial_area' => 'Commercial Area',
// ]
```

**Example:**
```php
$method = DistributionMethod::AREA;
$method->requiresAreaData(); // true
$method->getSupportedAreaTypes(); // ['total_area' => '...', ...]
```

### BY_CONSUMPTION
**Value:** `'by_consumption'`  
**Description:** Distributes costs based on actual consumption ratios from historical data.

**Use Cases:**
- Water distribution based on actual usage
- Electricity costs in shared meter scenarios
- Fair allocation based on actual consumption patterns

**Requirements:**
- Historical consumption data (typically last 12 months)
- Meter readings for all properties

**Fallback Behavior:**
- Falls back to EQUAL distribution if no consumption data available

**Example:**
```php
$method = DistributionMethod::BY_CONSUMPTION;
$method->requiresConsumptionData(); // true
```

### CUSTOM_FORMULA
**Value:** `'custom_formula'`  
**Description:** Uses custom mathematical formulas for distribution calculations.

**Use Cases:**
- Complex distribution scenarios
- Hybrid models (e.g., 70% area + 30% consumption)
- Special contractual arrangements

**Requirements:**
- Custom formula definition
- Variables: area, consumption, property attributes

**Fallback Behavior:**
- Falls back to EQUAL distribution if formula evaluation fails

**Example:**
```php
$method = DistributionMethod::CUSTOM_FORMULA;
$method->supportsCustomFormulas(); // true

// Formula example: 'area * 0.7 + consumption * 0.3'
```

## Methods

### getLabel(): string
Returns the translated label for the distribution method.

**Translation Keys:**
- `enums.distribution_method.equal`
- `enums.distribution_method.area`
- `enums.distribution_method.by_consumption`
- `enums.distribution_method.custom_formula`

**Example:**
```php
$label = DistributionMethod::AREA->getLabel();
// Returns: "Area-Based Distribution" (in current locale)
```

### getDescription(): string
Returns the translated description explaining the distribution method.

**Translation Keys:**
- `enums.distribution_method.equal_description`
- `enums.distribution_method.area_description`
- `enums.distribution_method.by_consumption_description`
- `enums.distribution_method.custom_formula_description`

**Example:**
```php
$description = DistributionMethod::AREA->getDescription();
// Returns: "Distribute costs proportionally based on property area"
```

### requiresAreaData(): bool
Checks if the distribution method requires property area data.

**Returns:** `true` only for AREA method

**Example:**
```php
DistributionMethod::AREA->requiresAreaData(); // true
DistributionMethod::EQUAL->requiresAreaData(); // false
```

### requiresConsumptionData(): bool
Checks if the distribution method requires historical consumption data.

**Returns:** `true` only for BY_CONSUMPTION method

**Example:**
```php
DistributionMethod::BY_CONSUMPTION->requiresConsumptionData(); // true
DistributionMethod::AREA->requiresConsumptionData(); // false
```

### supportsCustomFormulas(): bool
Checks if the distribution method supports custom formula definitions.

**Returns:** `true` only for CUSTOM_FORMULA method

**Example:**
```php
DistributionMethod::CUSTOM_FORMULA->supportsCustomFormulas(); // true
DistributionMethod::EQUAL->supportsCustomFormulas(); // false
```

### getSupportedAreaTypes(): array
Returns available area types for area-based distribution.

**Returns:** 
- Array of area types with translated labels (for AREA method)
- Empty array for non-area-based methods

**Example:**
```php
$areaTypes = DistributionMethod::AREA->getSupportedAreaTypes();
// [
//     'total_area' => 'Total Area',
//     'heated_area' => 'Heated Area',
//     'commercial_area' => 'Commercial Area',
// ]

DistributionMethod::EQUAL->getSupportedAreaTypes(); // []
```

## Usage in System

### ServiceConfiguration Model
The enum is used in the `ServiceConfiguration` model to define how shared service costs are distributed:

```php
use App\Enums\DistributionMethod;
use App\Models\ServiceConfiguration;

$config = ServiceConfiguration::create([
    'property_id' => 1,
    'utility_service_id' => 2,
    'distribution_method' => DistributionMethod::AREA,
    'area_type' => 'heated_area',
    // ...
]);

// Check requirements
if ($config->distribution_method->requiresAreaData()) {
    // Ensure area data is available
}
```

### GyvatukasCalculator Service
The enum is used in the `GyvatukasCalculator` service for circulation cost distribution:

```php
use App\Services\GyvatukasCalculator;
use App\Enums\DistributionMethod;

$calculator = app(GyvatukasCalculator::class);

// Equal distribution
$costs = $calculator->distributeCirculationCost(
    $building, 
    1000.0, 
    DistributionMethod::EQUAL->value
);

// Area-based distribution with heated area
$costs = $calculator->distributeCirculationCost(
    $building, 
    1000.0, 
    DistributionMethod::AREA->value,
    ['area_type' => 'heated_area']
);

// Consumption-based distribution
$costs = $calculator->distributeCirculationCost(
    $building, 
    1000.0, 
    DistributionMethod::BY_CONSUMPTION->value,
    ['consumption_period_months' => 12]
);
```

### UniversalBillingCalculator Service
The enum integrates with the universal billing system:

```php
use App\Services\UniversalBillingCalculator;

$calculator = app(UniversalBillingCalculator::class);

// Distribution method is read from ServiceConfiguration
$serviceConfig = ServiceConfiguration::find(1);
$distributionMethod = $serviceConfig->distribution_method;

if ($distributionMethod->requiresConsumptionData()) {
    // Fetch consumption data
}
```

## Validation Rules

### Mutually Exclusive Characteristics
Each distribution method has at most one primary characteristic:
- EQUAL: No special requirements (simplest)
- AREA: Requires area data
- BY_CONSUMPTION: Requires consumption data
- CUSTOM_FORMULA: Supports custom formulas

This design ensures clear, predictable behavior and prevents conflicting requirements.

## Localization

### Translation Files
Translations are defined in `lang/{locale}/enums.php`:

**English (en):**
```php
'distribution_method' => [
    'equal' => 'Equal Distribution',
    'area' => 'Area-Based Distribution',
    'by_consumption' => 'Consumption-Based Distribution',
    'custom_formula' => 'Custom Formula Distribution',
    'equal_description' => 'Distribute costs equally among all properties',
    'area_description' => 'Distribute costs proportionally based on property area',
    'by_consumption_description' => 'Distribute costs based on actual consumption ratios',
    'custom_formula_description' => 'Use custom mathematical formula for distribution',
],
```

**Lithuanian (lt):**
```php
'distribution_method' => [
    'equal' => 'Vienodas paskirstymas',
    'area' => 'Paskirstymas pagal plotą',
    'by_consumption' => 'Paskirstymas pagal suvartojimą',
    'custom_formula' => 'Paskirstymas pagal formulę',
    // descriptions...
],
```

**Russian (ru):**
```php
'distribution_method' => [
    'equal' => 'Равное распределение',
    'area' => 'Распределение по площади',
    'by_consumption' => 'Распределение по потреблению',
    'custom_formula' => 'Распределение по формуле',
    // descriptions...
],
```

## Database Storage

The enum is stored as a string in the database:

```php
// Migration
$table->string('distribution_method')->default('equal');

// Cast in model
protected function casts(): array
{
    return [
        'distribution_method' => DistributionMethod::class,
    ];
}
```

## Backward Compatibility

### Legacy Support
The enum maintains full backward compatibility with the existing gyvatukas system:

**Original Methods (Preserved):**
- `EQUAL` - Existed in original implementation
- `AREA` - Existed in original implementation
- `requiresAreaData()` - Original method preserved

**New Enhancements:**
- `BY_CONSUMPTION` - New case for consumption-based distribution
- `CUSTOM_FORMULA` - New case for flexible formulas
- `requiresConsumptionData()` - New method
- `supportsCustomFormulas()` - New method
- `getSupportedAreaTypes()` - New method with area type support

### Migration Path
Existing code using string values continues to work:

```php
// Old code (still works)
$method = 'equal';
$costs = $calculator->distributeCirculationCost($building, 1000.0, $method);

// New code (recommended)
$method = DistributionMethod::EQUAL;
$costs = $calculator->distributeCirculationCost($building, 1000.0, $method->value);
```

## Testing

### Test Coverage
Comprehensive test suite in `tests/Unit/Enums/DistributionMethodTest.php`:

**Test Categories:**
1. **Enum Structure** (2 tests)
   - All expected cases exist
   - Correct case count

2. **Area Data Requirements** (2 tests)
   - AREA requires area data
   - Other methods don't require area data

3. **Consumption Data Requirements** (2 tests)
   - BY_CONSUMPTION requires consumption data
   - Other methods don't require consumption data

4. **Custom Formula Support** (2 tests)
   - CUSTOM_FORMULA supports formulas
   - Other methods don't support formulas

5. **Supported Area Types** (3 tests)
   - AREA returns area types
   - Non-area methods return empty array
   - Area types have translated labels

6. **Labels and Descriptions** (3 tests)
   - All cases have labels
   - All cases have descriptions
   - Labels are unique

7. **Backward Compatibility** (2 tests)
   - EQUAL and AREA preserved
   - requiresAreaData() behavior preserved

8. **New Capabilities** (5 tests)
   - BY_CONSUMPTION case added
   - CUSTOM_FORMULA case added
   - New methods exist

9. **Method Combinations** (2 tests)
   - Mutually exclusive characteristics
   - EQUAL has no special requirements

**Test Statistics:**
- Total Tests: 22
- Total Assertions: 70
- Coverage: 100% of enum methods

### Running Tests
```bash
# Run all enum tests
php artisan test --filter=DistributionMethodTest

# Run with coverage
php artisan test --filter=DistributionMethodTest --coverage

# Run specific test group
php artisan test --filter="DistributionMethod.*area data"
```

## Performance Considerations

### Caching
Distribution calculations are cached for 5 minutes:

```php
// In GyvatukasCalculator
$cacheKey = $this->buildDistributionCacheKey($building->id, $method, $totalCost, $options);

return $this->cache->remember(
    $cacheKey,
    300, // 5 minutes
    fn () => $this->performDistributionCalculation(...)
);
```

### Query Optimization
Area-based and consumption-based methods use optimized queries:

```php
// Selective column loading
$properties = $this->buildingRepository->getBuildingPropertiesForDistribution(
    $building->id,
    $method // Only loads required columns based on method
);
```

## Related Documentation

- [GyvatukasCalculator Service](../services/gyvatukas-calculator.md)
- [ServiceConfiguration Model](../models/SERVICE_CONFIGURATION.md)
- [UniversalBillingCalculator Service](../services/UNIVERSAL_BILLING_CALCULATOR.md)
- [Distribution Method Usage Guide](../guides/DISTRIBUTION_METHOD_USAGE_GUIDE.md)
- [Universal Utility Management Spec](../../.kiro/specs/universal-utility-management/)

## Changelog

### 2024-12-13 - Enhancement Complete
- ✅ Added `BY_CONSUMPTION` case for consumption-based distribution
- ✅ Added `CUSTOM_FORMULA` case for flexible formula support
- ✅ Added `requiresConsumptionData()` method
- ✅ Added `supportsCustomFormulas()` method
- ✅ Enhanced `getSupportedAreaTypes()` with multiple area types
- ✅ Added comprehensive test suite (22 tests, 70 assertions)
- ✅ Added translations for EN, LT, RU locales
- ✅ Maintained full backward compatibility

### Original Implementation
- ✅ `EQUAL` case for equal distribution
- ✅ `AREA` case for area-based distribution
- ✅ `requiresAreaData()` method
- ✅ `getLabel()` and `getDescription()` methods

## See Also

- [Task 2.2 Implementation](./.kiro/specs/universal-utility-management/tasks.md#22-extend-distributionmethod-enum)
- [Distribution Method Enhancement Complete](./DISTRIBUTION_METHOD_ENHANCEMENT_COMPLETE.md)
- [Enum Testing Guide](../testing/enum-testing-guide.md)
