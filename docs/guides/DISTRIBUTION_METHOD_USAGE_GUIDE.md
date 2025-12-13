# DistributionMethod Enum - Developer Usage Guide

## Overview

The `DistributionMethod` enum provides flexible cost distribution strategies for shared utility services in multi-property buildings. It supports four distribution methods with automatic detection of data requirements.

## Available Methods

### 1. EQUAL - Equal Distribution
**Value:** `'equal'`  
**Use Case:** Distribute costs equally among all properties regardless of size or consumption.

```php
use App\Enums\DistributionMethod;

$method = DistributionMethod::EQUAL;
$method->requiresAreaData();        // false
$method->requiresConsumptionData(); // false
$method->supportsCustomFormulas();  // false
```

**Example:**
```php
// 3 apartments, total cost €300
// Each apartment pays: €300 / 3 = €100
```

### 2. AREA - Area-Based Distribution
**Value:** `'area'`  
**Use Case:** Distribute costs proportionally based on property area (total, heated, or commercial).

```php
$method = DistributionMethod::AREA;
$method->requiresAreaData();        // true
$method->requiresConsumptionData(); // false
$method->supportsCustomFormulas();  // false

// Get supported area types
$areaTypes = $method->getSupportedAreaTypes();
// [
//     'total_area' => 'Total Area',
//     'heated_area' => 'Heated Area',
//     'commercial_area' => 'Commercial Area'
// ]
```

**Example:**
```php
// Apartment A: 50 m², Apartment B: 75 m², Apartment C: 25 m²
// Total area: 150 m², Total cost: €300
// Apartment A pays: (50/150) * €300 = €100
// Apartment B pays: (75/150) * €300 = €150
// Apartment C pays: (25/150) * €300 = €50
```

### 3. BY_CONSUMPTION - Consumption-Based Distribution
**Value:** `'by_consumption'`  
**Use Case:** Distribute costs based on actual consumption ratios.

```php
$method = DistributionMethod::BY_CONSUMPTION;
$method->requiresAreaData();        // false
$method->requiresConsumptionData(); // true
$method->supportsCustomFormulas();  // false
```

**Example:**
```php
// Apartment A: 100 kWh, Apartment B: 150 kWh, Apartment C: 50 kWh
// Total consumption: 300 kWh, Total cost: €300
// Apartment A pays: (100/300) * €300 = €100
// Apartment B pays: (150/300) * €300 = €150
// Apartment C pays: (50/300) * €300 = €50
```

### 4. CUSTOM_FORMULA - Custom Formula Distribution
**Value:** `'custom_formula'`  
**Use Case:** Use custom mathematical formulas for complex distribution scenarios.

```php
$method = DistributionMethod::CUSTOM_FORMULA;
$method->requiresAreaData();        // false
$method->requiresConsumptionData(); // false
$method->supportsCustomFormulas();  // true
```

**Example:**
```php
// Custom formula: (area * 0.6) + (consumption * 0.4)
// Combines area and consumption with custom weights
```

## Common Usage Patterns

### 1. Conditional Data Loading

```php
use App\Enums\DistributionMethod;

function prepareDistributionData(DistributionMethod $method, Building $building)
{
    $data = [];
    
    if ($method->requiresAreaData()) {
        $areaTypes = $method->getSupportedAreaTypes();
        $data['area_types'] = $areaTypes;
        $data['properties_with_area'] = $building->properties()
            ->select('id', 'area_sqm', 'heated_area_sqm', 'commercial_area_sqm')
            ->get();
    }
    
    if ($method->requiresConsumptionData()) {
        $data['consumption_history'] = $building->properties()
            ->with(['meters.readings' => function ($query) {
                $query->whereBetween('reading_date', [
                    now()->subMonths(3),
                    now()
                ]);
            }])
            ->get();
    }
    
    if ($method->supportsCustomFormulas()) {
        $data['formula_editor'] = true;
        $data['available_variables'] = [
            'area', 'consumption', 'tenant_count', 'property_type'
        ];
    }
    
    return $data;
}
```

### 2. Filament Form Integration

```php
use Filament\Forms\Components\Select;
use App\Enums\DistributionMethod;

Select::make('distribution_method')
    ->label(__('app.labels.distribution_method'))
    ->options(fn () => collect(DistributionMethod::cases())
        ->mapWithKeys(fn ($case) => [
            $case->value => $case->getLabel()
        ])
    )
    ->descriptions(fn () => collect(DistributionMethod::cases())
        ->mapWithKeys(fn ($case) => [
            $case->value => $case->getDescription()
        ])
    )
    ->required()
    ->reactive()
    ->afterStateUpdated(function ($state, callable $set) {
        $method = DistributionMethod::from($state);
        
        // Show/hide area type selector
        $set('show_area_types', $method->requiresAreaData());
        
        // Show/hide consumption settings
        $set('show_consumption_settings', $method->requiresConsumptionData());
        
        // Show/hide formula editor
        $set('show_formula_editor', $method->supportsCustomFormulas());
    })
```

### 3. Validation Logic

```php
use App\Enums\DistributionMethod;
use Illuminate\Validation\Rule;

public function rules(): array
{
    $method = DistributionMethod::from($this->distribution_method);
    
    $rules = [
        'distribution_method' => ['required', Rule::enum(DistributionMethod::class)],
    ];
    
    if ($method->requiresAreaData()) {
        $rules['area_type'] = ['required', 'in:total_area,heated_area,commercial_area'];
    }
    
    if ($method->requiresConsumptionData()) {
        $rules['consumption_period_months'] = ['required', 'integer', 'min:1', 'max:12'];
    }
    
    if ($method->supportsCustomFormulas()) {
        $rules['custom_formula'] = ['required', 'string', 'max:500'];
    }
    
    return $rules;
}
```

### 4. Cost Distribution Calculation

```php
use App\Enums\DistributionMethod;

class CostDistributor
{
    public function distribute(
        float $totalCost,
        Collection $properties,
        DistributionMethod $method,
        array $options = []
    ): array {
        return match ($method) {
            DistributionMethod::EQUAL => $this->distributeEqually($totalCost, $properties),
            DistributionMethod::AREA => $this->distributeByArea($totalCost, $properties, $options['area_type'] ?? 'total_area'),
            DistributionMethod::BY_CONSUMPTION => $this->distributeByConsumption($totalCost, $properties, $options['period'] ?? 3),
            DistributionMethod::CUSTOM_FORMULA => $this->distributeByFormula($totalCost, $properties, $options['formula']),
        };
    }
    
    private function distributeEqually(float $totalCost, Collection $properties): array
    {
        $count = $properties->count();
        $costPerProperty = $totalCost / $count;
        
        return $properties->mapWithKeys(fn ($property) => [
            $property->id => $costPerProperty
        ])->toArray();
    }
    
    private function distributeByArea(float $totalCost, Collection $properties, string $areaType): array
    {
        $areaField = match ($areaType) {
            'heated_area' => 'heated_area_sqm',
            'commercial_area' => 'commercial_area_sqm',
            default => 'area_sqm',
        };
        
        $totalArea = $properties->sum($areaField);
        
        return $properties->mapWithKeys(fn ($property) => [
            $property->id => ($property->$areaField / $totalArea) * $totalCost
        ])->toArray();
    }
    
    private function distributeByConsumption(float $totalCost, Collection $properties, int $months): array
    {
        $consumptionData = $this->getConsumptionData($properties, $months);
        $totalConsumption = array_sum($consumptionData);
        
        return collect($consumptionData)->mapWithKeys(fn ($consumption, $propertyId) => [
            $propertyId => ($consumption / $totalConsumption) * $totalCost
        ])->toArray();
    }
    
    private function distributeByFormula(float $totalCost, Collection $properties, string $formula): array
    {
        // Custom formula evaluation logic
        // This would use a formula parser/evaluator
        return [];
    }
}
```

## Testing Examples

### Unit Tests

```php
use App\Enums\DistributionMethod;

it('correctly identifies data requirements', function () {
    expect(DistributionMethod::EQUAL->requiresAreaData())->toBeFalse();
    expect(DistributionMethod::AREA->requiresAreaData())->toBeTrue();
    expect(DistributionMethod::BY_CONSUMPTION->requiresConsumptionData())->toBeTrue();
    expect(DistributionMethod::CUSTOM_FORMULA->supportsCustomFormulas())->toBeTrue();
});

it('returns correct area types for area-based distribution', function () {
    $areaTypes = DistributionMethod::AREA->getSupportedAreaTypes();
    
    expect($areaTypes)->toHaveKey('total_area');
    expect($areaTypes)->toHaveKey('heated_area');
    expect($areaTypes)->toHaveKey('commercial_area');
    expect($areaTypes)->toHaveCount(3);
});

it('provides localized labels', function () {
    foreach (DistributionMethod::cases() as $method) {
        expect($method->getLabel())->toBeString()->not->toBeEmpty();
        expect($method->getDescription())->toBeString()->not->toBeEmpty();
    }
});
```

### Feature Tests

```php
use App\Enums\DistributionMethod;
use App\Models\Building;

it('distributes costs correctly using different methods', function () {
    $building = Building::factory()->create();
    $properties = Property::factory()->count(3)->create([
        'building_id' => $building->id,
        'area_sqm' => 50,
    ]);
    
    $totalCost = 300;
    
    // Equal distribution
    $distribution = (new CostDistributor)->distribute(
        $totalCost,
        $properties,
        DistributionMethod::EQUAL
    );
    
    expect($distribution)->toHaveCount(3);
    expect(array_sum($distribution))->toBe($totalCost);
    expect($distribution[$properties[0]->id])->toBe(100.0);
});
```

## Migration Guide

### From Existing Code

If you're currently using string values for distribution methods:

```php
// Old code
if ($distributionMethod === 'equal') {
    // ...
}

// New code
use App\Enums\DistributionMethod;

if ($distributionMethod === DistributionMethod::EQUAL) {
    // ...
}

// Or with match expression
$result = match ($distributionMethod) {
    DistributionMethod::EQUAL => $this->distributeEqually(),
    DistributionMethod::AREA => $this->distributeByArea(),
    // ...
};
```

### Database Migration

If storing distribution methods in database:

```php
// Migration
Schema::table('buildings', function (Blueprint $table) {
    $table->string('distribution_method')->default('equal')->change();
});

// Model
use App\Enums\DistributionMethod;

class Building extends Model
{
    protected $casts = [
        'distribution_method' => DistributionMethod::class,
    ];
}
```

## Best Practices

1. **Always check data requirements before loading data:**
   ```php
   if ($method->requiresAreaData()) {
       // Load area data
   }
   ```

2. **Use match expressions for cleaner code:**
   ```php
   $result = match ($method) {
       DistributionMethod::EQUAL => $this->handleEqual(),
       DistributionMethod::AREA => $this->handleArea(),
       // ...
   };
   ```

3. **Leverage translations for user-facing text:**
   ```php
   $label = $method->getLabel(); // Automatically translated
   ```

4. **Validate enum values in forms:**
   ```php
   use Illuminate\Validation\Rule;
   
   'distribution_method' => ['required', Rule::enum(DistributionMethod::class)],
   ```

5. **Use type hints for better IDE support:**
   ```php
   public function setDistributionMethod(DistributionMethod $method): void
   {
       $this->distributionMethod = $method;
   }
   ```

## Troubleshooting

### Issue: Translation keys not found
**Solution:** Clear translation cache
```bash
php artisan optimize:clear
```

### Issue: Enum value not recognized
**Solution:** Ensure you're using the correct case
```php
// Correct
DistributionMethod::EQUAL

// Incorrect
DistributionMethod::Equal
```

### Issue: Type error when storing in database
**Solution:** Add cast to model
```php
protected $casts = [
    'distribution_method' => DistributionMethod::class,
];
```

## Related Documentation

- [Universal Utility Management Spec](../.kiro/specs/universal-utility-management/requirements.md)
- [GyvatukasCalculator Service](../services/GYVATUKAS_CALCULATOR.md)
- [Enum Best Practices](./ENUM_BEST_PRACTICES.md)

---

**Last Updated:** December 13, 2025  
**Version:** 1.0.0
