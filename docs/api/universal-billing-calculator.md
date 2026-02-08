# UniversalBillingCalculator API Documentation

## Overview

The UniversalBillingCalculator provides a unified interface for calculating utility bills across all service types while maintaining compatibility with existing heating calculator logic.

## Class Definition

```php
namespace App\Services;

final readonly class UniversalBillingCalculator
{
    public function __construct(
        private HeatingCalculatorService $heatingCalculator,
        private SharedServiceCostDistributor $costDistributor,
        private TariffSnapshotService $snapshotService,
    ) {}
}
```

## Public Methods

### calculateBill()

Calculates utility bill for a service configuration and consumption data.

```php
public function calculateBill(
    ServiceConfiguration $config,
    UniversalConsumptionData $consumption,
    BillingPeriod $period
): UniversalBillingResult
```

**Parameters**:
- `$config` - Service configuration with pricing model and rates
- `$consumption` - Consumption data with total and zone breakdowns
- `$period` - Billing period for calculation

**Returns**: `UniversalBillingResult` with calculated amounts and metadata

**Example**:
```php
$calculator = app(UniversalBillingCalculator::class);

$result = $calculator->calculateBill(
    $serviceConfiguration,
    new UniversalConsumptionData([
        'total' => 1500.0,
        'zones' => ['day' => 900.0, 'night' => 600.0],
    ]),
    BillingPeriod::forMonth(2024, 1)
);

echo $result->getTotalAmount(); // Total bill amount
echo $result->getFixedAmount(); // Fixed charges
echo $result->getConsumptionAmount(); // Usage-based charges
```

### calculateSharedServiceBill()

Calculates and distributes shared service costs across multiple properties.

```php
public function calculateSharedServiceBill(
    ServiceConfiguration $config,
    Collection $properties,
    BillingPeriod $period
): SharedServiceBillingResult
```

**Parameters**:
- `$config` - Shared service configuration
- `$properties` - Collection of properties to distribute costs across
- `$period` - Billing period for calculation

**Returns**: `SharedServiceBillingResult` with per-property allocations

**Example**:
```php
$result = $calculator->calculateSharedServiceBill(
    $sharedHeatingConfig,
    $buildingProperties,
    BillingPeriod::currentMonth()
);

foreach ($result->getPropertyAllocations() as $propertyId => $amount) {
    echo "Property {$propertyId}: €{$amount}";
}
```

## Service Type Handling

### Heating Services
When `ServiceConfiguration::utility_service::service_type === ServiceType::HEATING`:

1. **Configuration Translation**: Universal config → heating calculator format
2. **Calculation Delegation**: Uses existing `HeatingCalculatorService`
3. **Result Wrapping**: Heating result → universal result format
4. **Enhancement**: Adds snapshots, audit trails, multi-tenancy

### Standard Services
For non-heating services (electricity, water, gas):

1. **Direct Calculation**: Uses universal pricing models
2. **Zone Support**: Handles time-of-use and multi-zone billing
3. **Tiered Rates**: Supports consumption-based tier structures
4. **Custom Formulas**: Evaluates mathematical expressions

## Result Objects

### UniversalBillingResult

```php
final readonly class UniversalBillingResult
{
    public function getTotalAmount(): float;
    public function getFixedAmount(): float;
    public function getConsumptionAmount(): float;
    public function getTariffSnapshot(): array;
    public function getCalculationDetails(): array;
    public function toArray(): array;
}
```

**Properties**:
- `total_amount` - Total bill amount including all charges
- `fixed_amount` - Fixed monthly/periodic charges
- `consumption_amount` - Usage-based variable charges
- `tariff_snapshot` - Historical rate preservation for invoices
- `calculation_details` - Detailed breakdown of calculation steps

### SharedServiceBillingResult

```php
final readonly class SharedServiceBillingResult
{
    public function getTotalAmount(): float;
    public function getPropertyAllocations(): Collection;
    public function getDistributionMethod(): DistributionMethod;
    public function getCalculationDetails(): array;
}
```

## Pricing Models

### FIXED_MONTHLY
Fixed amount per billing period regardless of consumption.

```php
// Configuration
'rate_schedule' => [
    'monthly_rate' => 125.0,
]

// Calculation: monthly_rate
```

### CONSUMPTION_BASED
Variable rate based on total consumption.

```php
// Configuration
'rate_schedule' => [
    'unit_rate' => 0.15,
    'fixed_fee' => 25.0,
]

// Calculation: (consumption * unit_rate) + fixed_fee
```

### TIME_OF_USE
Different rates for different time periods (zones).

```php
// Configuration
'rate_schedule' => [
    'zone_rates' => [
        'day' => 0.18,
        'night' => 0.09,
    ],
    'fixed_fee' => 30.0,
]

// Calculation: sum(zone_consumption * zone_rate) + fixed_fee
```

### TIERED_RATES
Progressive rates based on consumption levels.

```php
// Configuration
'rate_schedule' => [
    'tiers' => [
        ['limit' => 100, 'rate' => 0.12],
        ['limit' => 500, 'rate' => 0.15],
        ['limit' => null, 'rate' => 0.18],
    ],
    'fixed_fee' => 20.0,
]

// Calculation: tiered consumption + fixed_fee
```

### HYBRID
Combination of fixed and variable components with seasonal adjustments.

```php
// Configuration (Heating-specific)
'rate_schedule' => [
    'fixed_fee' => 125.0,
    'unit_rate' => 0.08,
    'seasonal_adjustments' => [
        'summer_multiplier' => 0.3,
        'winter_multiplier' => 1.5,
    ],
]

// Calculation: (fixed_fee + consumption * unit_rate) * seasonal_multiplier
```

### CUSTOM_FORMULA
Mathematical expression evaluation for complex pricing.

```php
// Configuration
'rate_schedule' => [
    'formula' => 'base_rate + (consumption * unit_rate * efficiency_factor)',
    'variables' => [
        'base_rate' => 50.0,
        'unit_rate' => 0.12,
        'efficiency_factor' => 1.2,
    ],
]

// Calculation: Evaluates mathematical expression
```

## Distribution Methods

### EQUAL
Equal cost allocation across all properties.

```php
// Each property pays: total_cost / property_count
```

### AREA
Cost allocation based on property square meters.

```php
// Each property pays: (property_area / total_area) * total_cost
```

### BY_CONSUMPTION
Cost allocation based on actual consumption.

```php
// Each property pays: (property_consumption / total_consumption) * total_cost
```

### CUSTOM_FORMULA
Mathematical formula for distribution.

```php
// Configuration
'distribution_formula' => 'base_allocation + (consumption_factor * property_consumption)'
```

## Error Handling

### Exceptions

```php
// Service configuration errors
ServiceConfigurationException::invalidPricingModel($model);
ServiceConfigurationException::missingRateSchedule($config);

// Calculation errors
BillingCalculationException::invalidConsumptionData($data);
BillingCalculationException::calculationFailed($reason);

// Distribution errors
DistributionException::invalidDistributionMethod($method);
DistributionException::noPropertiesProvided();
```

### Validation

```php
// Input validation
if ($consumption->getTotal() < 0) {
    throw new InvalidArgumentException('Consumption cannot be negative');
}

if ($config->rate_schedule === null) {
    throw new ServiceConfigurationException('Rate schedule is required');
}
```

## Performance Considerations

### Caching
- Calculation results cached per tenant/period
- Tariff snapshots cached for invoice generation
- Distribution calculations cached for shared services

### Optimization
- Batch processing for multiple properties
- Eager loading of related models
- Query optimization for large datasets

### Memory Management
- Efficient handling of large property collections
- Streaming results for bulk calculations
- Garbage collection optimization

## Integration Examples

### Basic Service Calculation
```php
// Create service configuration
$electricityConfig = ServiceConfiguration::create([
    'utility_service_id' => $electricityService->id,
    'property_id' => $property->id,
    'pricing_model' => PricingModel::TIME_OF_USE,
    'rate_schedule' => [
        'zone_rates' => ['day' => 0.18, 'night' => 0.09],
        'fixed_fee' => 30.0,
    ],
]);

// Calculate bill
$result = $calculator->calculateBill(
    $electricityConfig,
    new UniversalConsumptionData([
        'total' => 850.0,
        'zones' => ['day' => 520.0, 'night' => 330.0],
    ]),
    BillingPeriod::forMonth(2024, 3)
);

// Use result
$invoice = Invoice::create([
    'property_id' => $property->id,
    'total_amount' => $result->getTotalAmount(),
    'tariff_snapshot' => $result->getTariffSnapshot(),
]);
```

### Shared Service Distribution
```php
// Shared heating service
$sharedHeatingConfig = ServiceConfiguration::create([
    'utility_service_id' => $heatingService->id,
    'pricing_model' => PricingModel::FIXED_MONTHLY,
    'rate_schedule' => ['monthly_rate' => 2000.0],
    'distribution_method' => DistributionMethod::AREA,
    'is_shared_service' => true,
]);

// Calculate and distribute
$result = $calculator->calculateSharedServiceBill(
    $sharedHeatingConfig,
    $building->properties,
    BillingPeriod::currentMonth()
);

// Create individual invoices
foreach ($result->getPropertyAllocations() as $propertyId => $amount) {
    Invoice::create([
        'property_id' => $propertyId,
        'total_amount' => $amount,
        'shared_service_id' => $sharedHeatingConfig->id,
    ]);
}
```

## Testing

### Unit Tests
```php
it('calculates electricity bill with time-of-use rates', function () {
    $config = ServiceConfiguration::factory()->timeOfUse()->create();
    $consumption = new UniversalConsumptionData([
        'total' => 1000.0,
        'zones' => ['day' => 600.0, 'night' => 400.0],
    ]);
    
    $result = $this->calculator->calculateBill(
        $config,
        $consumption,
        BillingPeriod::forMonth(2024, 1)
    );
    
    expect($result->getTotalAmount())->toBe(138.0); // (600*0.18 + 400*0.09) + 30
});
```

### Integration Tests
```php
it('integrates with heating calculator for heating services', function () {
    $heatingConfig = ServiceConfiguration::factory()->heating()->create();
    $consumption = new UniversalConsumptionData(['total' => 500.0]);
    
    $result = $this->calculator->calculateBill(
        $heatingConfig,
        $consumption,
        BillingPeriod::forMonth(2024, 1)
    );
    
    expect($result->getTariffSnapshot())->toHaveKey('service_configuration_id');
    expect($result->getCalculationDetails())->toHaveKey('heating_specific_data');
});
```

## Related Documentation
- [Heating Integration Bridge](../architecture/heating-integration-bridge.md)
- [Service Configuration Model](../models/service-configuration.md)
- [Property-Based Testing Guide](../testing/property-based-testing-guide.md)
- [Universal Utility Management](../architecture/universal-utility-management.md)