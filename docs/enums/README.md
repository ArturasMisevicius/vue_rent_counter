# Enums Documentation

## Overview

This directory contains documentation for all enums used in the CFlow application. Enums provide type-safe, validated values for various domain concepts.

## Available Enums

### DistributionMethod
**Location:** `app/Enums/DistributionMethod.php`  
**Purpose:** Defines how shared utility costs are distributed among properties

**Cases:**
- `EQUAL` - Equal distribution among all properties
- `AREA` - Proportional distribution based on property area
- `BY_CONSUMPTION` - Distribution based on actual consumption ratios
- `CUSTOM_FORMULA` - Custom mathematical formula distribution

**Documentation:**
- [Full Documentation](DISTRIBUTION_METHOD.md)
- [Quick Reference](DISTRIBUTION_METHOD_QUICK_REFERENCE.md)
- [Test Coverage](../testing/DISTRIBUTION_METHOD_TEST_COVERAGE.md)
- [Changelog](../CHANGELOG_DISTRIBUTION_METHOD_ENHANCEMENT.md)

### Other Enums

For documentation on other enums, see:
- [PricingModel](./PRICING_MODEL.md) - Utility pricing models
- [MeterType](./METER_TYPE.md) - Meter types (electricity, water, heating, gas)
- [ServiceType](./SERVICE_TYPE.md) - Utility service types
- [TariffType](./TARIFF_TYPE.md) - Tariff calculation types
- [UserRole](./USER_ROLE.md) - User roles and permissions
- [SubscriptionStatus](./SUBSCRIPTION_STATUS.md) - Subscription states
- [InvoiceStatus](./INVOICE_STATUS.md) - Invoice states

## Enum Patterns

### Basic Usage
```php
use App\Enums\DistributionMethod;

// Get enum case
$method = DistributionMethod::AREA;

// Get value
$value = $method->value; // 'area'

// Get label (translated)
$label = $method->getLabel(); // 'Area-Based Distribution'

// Get description
$description = $method->getDescription();
```

### Database Storage
```php
// Migration
$table->string('distribution_method')->default('equal');

// Model casting
protected function casts(): array
{
    return [
        'distribution_method' => DistributionMethod::class,
    ];
}
```

### Validation
```php
use Illuminate\Validation\Rules\Enum;

$request->validate([
    'distribution_method' => ['required', new Enum(DistributionMethod::class)],
]);
```

### Filament Forms
```php
use Filament\Forms\Components\Select;

Select::make('distribution_method')
    ->label(__('app.labels.distribution_method'))
    ->options(DistributionMethod::class)
    ->required();
```

## Testing Enums

### Unit Tests
```php
use App\Enums\DistributionMethod;

it('has all expected cases', function () {
    $cases = DistributionMethod::cases();
    expect($cases)->toHaveCount(4);
});

it('provides labels for all cases', function () {
    foreach (DistributionMethod::cases() as $method) {
        expect($method->getLabel())->toBeString();
    }
});
```

### Property-Based Tests
```php
it('ensures methods have mutually exclusive characteristics', function () {
    foreach (DistributionMethod::cases() as $method) {
        $characteristics = array_filter([
            $method->requiresAreaData(),
            $method->requiresConsumptionData(),
            $method->supportsCustomFormulas(),
        ]);
        
        expect(count($characteristics))->toBeLessThanOrEqual(1);
    }
});
```

## Localization

All enum labels and descriptions should be translated:

```php
// lang/en/enums.php
return [
    'distribution_method' => [
        'equal' => 'Equal Distribution',
        'equal_description' => 'Distribute costs equally among all properties',
        // ...
    ],
];

// lang/lt/enums.php
return [
    'distribution_method' => [
        'equal' => 'Vienodas paskirstymas',
        'equal_description' => 'Paskirstyti išlaidas vienodai tarp visų patalpų',
        // ...
    ],
];
```

## Best Practices

1. **Use Enums for Fixed Sets** - Only use enums for values that won't change frequently
2. **Provide Labels** - Always implement `getLabel()` for UI display
3. **Add Descriptions** - Include `getDescription()` for tooltips and help text
4. **Test Thoroughly** - Write comprehensive unit tests for all enum methods
5. **Document Well** - Provide clear documentation with usage examples
6. **Localize** - Translate all user-facing strings
7. **Version Carefully** - Adding cases is safe, removing cases requires migration

## Related Documentation

- [Testing Guide](../testing/overview.md)
- [Localization Guide](../features/translations/)
- [Database Schema](../database/overview.md)
- [Filament Resources](../filament/overview.md)

## Contributing

When adding new enums:

1. Create the enum class with proper docblocks
2. Implement `getLabel()` and `getDescription()` methods
3. Add translations for all supported locales
4. Write comprehensive unit tests
5. Create documentation in this directory
6. Update this README with the new enum
