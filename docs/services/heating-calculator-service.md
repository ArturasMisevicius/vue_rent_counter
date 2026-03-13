# HeatingCalculatorService Documentation

## Overview

The HeatingCalculatorService handles Lithuanian heating system calculations with seasonal adjustments, building factors, and cost distribution methods. This service is integrated with the universal utility management system through a bridge pattern.

## Service Definition

```php
namespace App\Services;

final readonly class HeatingCalculatorService
{
    public function __construct(
        private TariffResolver $tariffResolver,
        private CacheManager $cache,
    ) {}
}
```

## Core Methods

### calculateHeatingCharges()

Calculates heating charges for a property and billing period.

```php
public function calculateHeatingCharges(
    Property $property,
    BillingPeriod $period
): array
```

**Parameters**:
- `$property` - Property with building and meter relationships
- `$period` - Billing period for calculation

**Returns**: Array with calculation breakdown
```php
[
    'total_charge' => 245.50,
    'base_charge' => 125.00,
    'consumption_charge' => 120.50,
    'seasonal_multiplier' => 1.5,
    'building_factor' => 1.1,
    'calculation_details' => [...],
]
```

**Example**:
```php
$heatingCalculator = app(HeatingCalculatorService::class);

$result = $heatingCalculator->calculateHeatingCharges(
    $property,
    BillingPeriod::forMonth(2024, 1)
);

echo "Total heating cost: €{$result['total_charge']}";
```

### distributeCirculationCost()

Distributes shared heating circulation costs across multiple properties.

```php
public function distributeCirculationCost(
    float $totalCost,
    Collection $properties,
    DistributionMethod $method
): array
```

**Parameters**:
- `$totalCost` - Total cost to distribute
- `$properties` - Collection of properties to distribute across
- `$method` - Distribution method (EQUAL, AREA, BY_CONSUMPTION)

**Returns**: Array with distribution breakdown
```php
[
    'total_cost' => 2000.00,
    'distribution_method' => 'area',
    'distributed_amounts' => [
        1 => 450.00,  // Property ID => Amount
        2 => 650.00,
        3 => 900.00,
    ],
    'distribution_factors' => [...],
]
```

**Example**:
```php
$distribution = $heatingCalculator->distributeCirculationCost(
    2000.00,
    $building->properties,
    DistributionMethod::AREA
);

foreach ($distribution['distributed_amounts'] as $propertyId => $amount) {
    echo "Property {$propertyId}: €{$amount}";
}
```

## Calculation Logic

### Seasonal Adjustments

**Winter Months** (Oct-Apr):
- Base multiplier: 1.5x
- Higher consumption expected
- Full heating system operation

**Summer Months** (May-Sep):
- Base multiplier: 0.3x
- Reduced consumption
- Minimal heating operation

**Implementation**:
```php
private function getSeasonalMultiplier(BillingPeriod $period): float
{
    $month = $period->getStartDate()->month;
    
    return in_array($month, [5, 6, 7, 8, 9], true) 
        ? 0.3  // Summer
        : 1.5; // Winter
}
```

### Building Factors

**Age-Based Efficiency**:
- Buildings before 1990: 1.2x multiplier (poor insulation)
- Buildings 1990-2005: 1.1x multiplier (moderate insulation)
- Buildings after 2005: 1.0x multiplier (modern insulation)

**Size-Based Economies**:
- Small buildings (<1000 sqm): 1.1x multiplier
- Medium buildings (1000-3000 sqm): 1.0x multiplier
- Large buildings (>3000 sqm): 0.9x multiplier

**Implementation**:
```php
private function getBuildingFactor(Building $building): float
{
    $ageFactor = $building->built_year < 1990 ? 1.2 : 
                ($building->built_year < 2005 ? 1.1 : 1.0);
    
    $sizeFactor = $building->total_area < 1000 ? 1.1 :
                 ($building->total_area > 3000 ? 0.9 : 1.0);
    
    return $ageFactor * $sizeFactor;
}
```

### Distribution Methods

#### Equal Distribution
```php
private function distributeEqually(float $totalCost, Collection $properties): array
{
    $perPropertyCost = $totalCost / $properties->count();
    
    return $properties->mapWithKeys(fn($property) => [
        $property->id => $perPropertyCost
    ])->toArray();
}
```

#### Area-Based Distribution
```php
private function distributeByArea(float $totalCost, Collection $properties): array
{
    $totalArea = $properties->sum('area_sqm');
    
    return $properties->mapWithKeys(fn($property) => [
        $property->id => ($property->area_sqm / $totalArea) * $totalCost
    ])->toArray();
}
```

#### Consumption-Based Distribution
```php
private function distributeByConsumption(
    float $totalCost, 
    Collection $properties, 
    BillingPeriod $period
): array {
    $consumptionData = $this->getPropertyConsumption($properties, $period);
    $totalConsumption = array_sum($consumptionData);
    
    return collect($consumptionData)->mapWithKeys(fn($consumption, $propertyId) => [
        $propertyId => ($consumption / $totalConsumption) * $totalCost
    ])->toArray();
}
```

## Caching Strategy

### Calculation Caching
```php
private function getCachedCalculation(Property $property, BillingPeriod $period): ?array
{
    $cacheKey = "heating_calculation:{$property->id}:{$period->getCacheKey()}";
    
    return $this->cache->remember($cacheKey, 3600, function () use ($property, $period) {
        return $this->performCalculation($property, $period);
    });
}
```

### Cache Invalidation
- Property changes: Clear property-specific cache
- Tariff updates: Clear all calculations for affected period
- Building modifications: Clear building-related calculations
- Seasonal transitions: Clear seasonal calculation cache

## Integration with Universal System

### Bridge Pattern
The heating calculator integrates with the universal system through a bridge pattern:

1. **Detection**: Universal calculator detects heating service type
2. **Translation**: Universal configuration → heating calculator format
3. **Delegation**: Calculation delegated to heating calculator
4. **Wrapping**: Heating result → universal result format
5. **Enhancement**: Snapshots, audits, multi-tenancy added

### Configuration Mapping
```php
// Universal ServiceConfiguration
$config = ServiceConfiguration::create([
    'pricing_model' => PricingModel::HYBRID,
    'rate_schedule' => [
        'fixed_fee' => 125.0,
        'unit_rate' => 0.08,
        'seasonal_adjustments' => [
            'summer_multiplier' => 0.3,
            'winter_multiplier' => 1.5,
        ],
    ],
]);

// Translated to heating calculator format
$heatingConfig = [
    'base_rate' => 125.0,
    'consumption_rate' => 0.08,
    'seasonal_factors' => [
        'summer' => 0.3,
        'winter' => 1.5,
    ],
];
```

## Error Handling

### Validation Errors
```php
// Missing meter readings
if (!$property->hasHeatingMeter()) {
    throw new HeatingCalculationException(
        "Property {$property->id} has no heating meter"
    );
}

// Invalid consumption data
if ($consumption < 0) {
    throw new InvalidConsumptionException(
        "Negative consumption not allowed: {$consumption}"
    );
}
```

### Calculation Errors
```php
// Division by zero protection
$totalArea = max($properties->sum('area_sqm'), 0.01);

// Seasonal factor validation
if (!in_array($month, range(1, 12))) {
    throw new InvalidBillingPeriodException("Invalid month: {$month}");
}
```

## Testing

### Unit Tests
```php
it('calculates heating charges with seasonal adjustment', function () {
    $property = Property::factory()->withHeatingMeter()->create();
    $winterPeriod = BillingPeriod::forMonth(2024, 1);
    
    $result = $this->heatingCalculator->calculateHeatingCharges(
        $property,
        $winterPeriod
    );
    
    expect($result['seasonal_multiplier'])->toBe(1.5);
    expect($result['total_charge'])->toBeGreaterThan(0);
});
```

### Integration Tests
```php
it('integrates with universal billing calculator', function () {
    $heatingConfig = ServiceConfiguration::factory()->heating()->create();
    $consumption = new UniversalConsumptionData(['total' => 500.0]);
    
    $universalResult = $this->universalCalculator->calculateBill(
        $heatingConfig,
        $consumption,
        BillingPeriod::forMonth(2024, 1)
    );
    
    $heatingResult = $this->heatingCalculator->calculateHeatingCharges(
        $heatingConfig->property,
        BillingPeriod::forMonth(2024, 1)
    );
    
    expect($universalResult->getTotalAmount())
        ->toBe($heatingResult['total_charge']);
});
```

## Configuration

### Service Registration
```php
// In AppServiceProvider
$this->app->singleton(HeatingCalculatorService::class, function ($app) {
    return new HeatingCalculatorService(
        $app->make(TariffResolver::class),
        $app->make(CacheManager::class)
    );
});
```

### Cache Configuration
```php
// config/cache.php
'heating_calculations' => [
    'driver' => 'redis',
    'ttl' => 3600, // 1 hour
    'prefix' => 'heating_calc',
],
```

## Related Documentation
- [Universal Billing Calculator API](../api/universal-billing-calculator.md)
- [Heating Integration Bridge](../architecture/heating-integration-bridge.md)
- [Service Configuration Model](../models/service-configuration.md)
- [Distribution Methods](../enums/distribution-method.md)