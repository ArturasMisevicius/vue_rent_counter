# DistributionMethod Quick Reference

## Enum Cases

| Case | Value | Requirements | Use Case |
|------|-------|--------------|----------|
| `EQUAL` | `'equal'` | None | Simple equal distribution |
| `AREA` | `'area'` | Area data | Proportional by property size |
| `BY_CONSUMPTION` | `'by_consumption'` | Consumption data | Based on actual usage |
| `CUSTOM_FORMULA` | `'custom_formula'` | Formula definition | Flexible hybrid models |

## Quick Usage

### Check Requirements
```php
use App\Enums\DistributionMethod;

$method = DistributionMethod::AREA;

// Check what data is needed
$method->requiresAreaData();        // true
$method->requiresConsumptionData(); // false
$method->supportsCustomFormulas();  // false
```

### Get Area Types
```php
$areaTypes = DistributionMethod::AREA->getSupportedAreaTypes();
// ['total_area' => 'Total Area', 'heated_area' => 'Heated Area', ...]
```

### Use in Distribution
```php
use App\Services\hot water circulationCalculator;

$calculator = app(hot water circulationCalculator::class);

// Equal distribution
$costs = $calculator->distributeCirculationCost(
    $building, 
    1000.0, 
    DistributionMethod::EQUAL->value
);

// Area-based with heated area
$costs = $calculator->distributeCirculationCost(
    $building, 
    1000.0, 
    DistributionMethod::AREA->value,
    ['area_type' => 'heated_area']
);

// Consumption-based
$costs = $calculator->distributeCirculationCost(
    $building, 
    1000.0, 
    DistributionMethod::BY_CONSUMPTION->value,
    ['consumption_period_months' => 12]
);

// Custom formula
$costs = $calculator->distributeCirculationCost(
    $building, 
    1000.0, 
    DistributionMethod::CUSTOM_FORMULA->value,
    ['formula' => 'area * 0.7 + consumption * 0.3']
);
```

### Use in ServiceConfiguration
```php
use App\Models\ServiceConfiguration;
use App\Enums\DistributionMethod;

$config = ServiceConfiguration::create([
    'property_id' => 1,
    'utility_service_id' => 2,
    'distribution_method' => DistributionMethod::BY_CONSUMPTION,
    'area_type' => 'heated_area', // Only for AREA method
    // ...
]);

// Check requirements
if ($config->requiresConsumptionData()) {
    $consumption = $property->getHistoricalConsumption(12);
}
```

## Method Reference

| Method | Returns | Description |
|--------|---------|-------------|
| `getLabel()` | `string` | Translated label |
| `getDescription()` | `string` | Translated description |
| `requiresAreaData()` | `bool` | Needs area data? |
| `requiresConsumptionData()` | `bool` | Needs consumption data? |
| `supportsCustomFormulas()` | `bool` | Supports formulas? |
| `getSupportedAreaTypes()` | `array` | Available area types |

## Translation Keys

```php
// Labels
'enums.distribution_method.equal'
'enums.distribution_method.area'
'enums.distribution_method.by_consumption'
'enums.distribution_method.custom_formula'

// Descriptions
'enums.distribution_method.equal_description'
'enums.distribution_method.area_description'
'enums.distribution_method.by_consumption_description'
'enums.distribution_method.custom_formula_description'

// Area types
'enums.area_type.total_area'
'enums.area_type.heated_area'
'enums.area_type.commercial_area'
```

## Testing

```bash
# Run all tests
php artisan test --filter=DistributionMethodTest

# Run specific group
php artisan test --filter="DistributionMethod.*area"
```

## See Also

- [Full Documentation](./DISTRIBUTION_METHOD.md)
- [Test Coverage](../testing/DISTRIBUTION_METHOD_TEST_COVERAGE.md)
- [Changelog](../CHANGELOG_DISTRIBUTION_METHOD_ENHANCEMENT.md)
