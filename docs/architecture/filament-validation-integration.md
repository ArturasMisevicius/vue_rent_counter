# Filament Validation Integration Pattern

## Overview

This document describes the pattern used to integrate Laravel FormRequest validation rules into Filament form components, ensuring consistency between API and admin panel validation.

## Problem Statement

Without integration, validation rules would be duplicated:

```php
// ❌ BAD: Duplicated validation
// In FormRequest
'address' => ['required', 'max:255']

// In Filament form
Forms\Components\TextInput::make('address')
    ->required()
    ->maxLength(255)
    ->validationMessages([
        'required' => 'The address is required.',
        'max' => 'The address may not exceed 255 characters.',
    ])
```

**Issues**:
- Validation drift (rules change in one place but not the other)
- Message inconsistency
- Maintenance burden
- Potential security gaps

## Solution: FormRequest Integration

### Pattern Implementation

```php
protected function getAddressField(): Forms\Components\TextInput
{
    // 1. Instantiate the FormRequest
    $request = new StorePropertyRequest;
    
    // 2. Pull validation messages
    $messages = $request->messages();

    // 3. Configure field with messages
    return Forms\Components\TextInput::make('address')
        ->label(__('properties.labels.address'))
        ->required()
        ->maxLength(255)
        ->validationAttribute('address')
        ->validationMessages([
            'required' => $messages['address.required'],
            'max' => $messages['address.max'],
        ]);
}
```

### Benefits

✅ **Single Source of Truth**: Validation messages defined once in FormRequest  
✅ **Consistency**: API and admin panel show same messages  
✅ **Localization**: Messages use translation keys  
✅ **Maintainability**: Update rules in one place  
✅ **Type Safety**: IDE autocomplete for message keys

## Implementation in PropertiesRelationManager

### Address Field

```php
protected function getAddressField(): Forms\Components\TextInput
{
    $request = new StorePropertyRequest;
    $messages = $request->messages();

    return Forms\Components\TextInput::make('address')
        ->label(__('properties.labels.address'))
        ->placeholder(__('properties.placeholders.address'))
        ->required()
        ->maxLength(255)
        ->validationAttribute('address')
        ->validationMessages([
            'required' => $messages['address.required'],
            'max' => $messages['address.max'],
        ])
        ->helperText(__('properties.helper_text.address'))
        ->columnSpanFull();
}
```

**FormRequest Source**:
```php
// app/Http/Requests/StorePropertyRequest.php
public function rules(): array
{
    return [
        'address' => ['required', 'string', 'max:255'],
    ];
}

public function messages(): array
{
    return [
        'address.required' => __('properties.validation.address.required'),
        'address.max' => __('properties.validation.address.max'),
    ];
}
```

### Type Field (Enum Validation)

```php
protected function getTypeField(): Forms\Components\Select
{
    $request = new StorePropertyRequest;
    $messages = $request->messages();

    return Forms\Components\Select::make('type')
        ->label(__('properties.labels.type'))
        ->options(PropertyType::class)
        ->required()
        ->native(false)
        ->validationAttribute('type')
        ->rules([Rule::enum(PropertyType::class)])
        ->validationMessages([
            'required' => $messages['type.required'],
            'enum' => $messages['type.enum'],
        ])
        ->helperText(__('properties.helper_text.type'))
        ->live()
        ->afterStateUpdated(fn (string $state, Forms\Set $set) => 
            $this->setDefaultArea($state, $set)
        );
}
```

**FormRequest Source**:
```php
public function rules(): array
{
    return [
        'type' => ['required', Rule::enum(PropertyType::class)],
    ];
}

public function messages(): array
{
    return [
        'type.required' => __('properties.validation.type.required'),
        'type.enum' => __('properties.validation.type.enum'),
    ];
}
```

### Area Field (Config-Based Validation)

```php
protected function getAreaField(): Forms\Components\TextInput
{
    $request = new StorePropertyRequest;
    $messages = $request->messages();
    $config = config('billing.property');

    return Forms\Components\TextInput::make('area_sqm')
        ->label(__('properties.labels.area'))
        ->placeholder(__('properties.placeholders.area'))
        ->required()
        ->numeric()
        ->minValue($config['min_area'])
        ->maxValue($config['max_area'])
        ->suffix('m²')
        ->step(0.01)
        ->validationAttribute('area_sqm')
        ->validationMessages([
            'required' => $messages['area_sqm.required'],
            'numeric' => $messages['area_sqm.numeric'],
            'min' => $messages['area_sqm.min'],
            'max' => $messages['area_sqm.max'],
        ])
        ->helperText(__('properties.helper_text.area'));
}
```

**FormRequest Source**:
```php
public function rules(): array
{
    return [
        'area_sqm' => ['required', 'numeric', 'min:0', 'max:10000'],
    ];
}

public function messages(): array
{
    return [
        'area_sqm.required' => __('properties.validation.area_sqm.required'),
        'area_sqm.numeric' => __('properties.validation.area_sqm.numeric'),
        'area_sqm.min' => __('properties.validation.area_sqm.min'),
        'area_sqm.max' => __('properties.validation.area_sqm.max'),
    ];
}
```

## Data Flow

```
User Input
    ↓
Filament Form Component
    ↓
Validation (using FormRequest messages)
    ↓
preparePropertyData() (inject tenant_id, building_id)
    ↓
Policy Authorization Check
    ↓
Model Save
    ↓
Success Notification
```

## Testing Strategy

### Test Validation Integration

```php
test('form uses FormRequest validation messages', function () {
    $manager = new PropertiesRelationManager;

    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('getAddressField');
    $method->setAccessible(true);

    $field = $method->invoke($manager);

    // Verify field has validation messages configured
    expect($field->getValidationMessages())->toBeArray();
    expect($field->getValidationMessages())->toHaveKey('required');
});
```

### Test Config Integration

```php
test('area field uses config values', function () {
    config(['billing.property.min_area' => 10]);
    config(['billing.property.max_area' => 5000]);

    $manager = new PropertiesRelationManager;

    $reflection = new ReflectionClass($manager);
    $method = $reflection->getMethod('getAreaField');
    $method->setAccessible(true);

    $field = $method->invoke($manager);

    expect($field->getMinValue())->toBe(10);
    expect($field->getMaxValue())->toBe(5000);
});
```

## Best Practices

### ✅ DO

1. **Instantiate FormRequest in field methods**
   ```php
   $request = new StorePropertyRequest;
   $messages = $request->messages();
   ```

2. **Use validationAttribute() for clarity**
   ```php
   ->validationAttribute('address')
   ```

3. **Pull config values for dynamic constraints**
   ```php
   $config = config('billing.property');
   ->minValue($config['min_area'])
   ```

4. **Use translation keys in FormRequest**
   ```php
   'address.required' => __('properties.validation.address.required')
   ```

5. **Test validation integration**
   ```php
   test('form uses FormRequest validation messages')
   ```

### ❌ DON'T

1. **Don't hardcode validation messages**
   ```php
   // ❌ BAD
   ->validationMessages([
       'required' => 'The address is required.',
   ])
   ```

2. **Don't duplicate rules**
   ```php
   // ❌ BAD: Rules in both places
   // FormRequest: 'address' => ['required', 'max:255']
   // Filament: ->required()->maxLength(255)
   ```

3. **Don't skip validationAttribute()**
   ```php
   // ❌ BAD: Error messages won't match field name
   ->validationMessages([...])
   ```

4. **Don't use different validation logic**
   ```php
   // ❌ BAD: API allows 500 chars, Filament allows 255
   ```

## Extending the Pattern

### Adding New Fields

```php
// 1. Add validation to FormRequest
public function rules(): array
{
    return [
        'new_field' => ['required', 'string', 'max:100'],
    ];
}

public function messages(): array
{
    return [
        'new_field.required' => __('properties.validation.new_field.required'),
        'new_field.max' => __('properties.validation.new_field.max'),
    ];
}

// 2. Create field method in RelationManager
protected function getNewField(): Forms\Components\TextInput
{
    $request = new StorePropertyRequest;
    $messages = $request->messages();

    return Forms\Components\TextInput::make('new_field')
        ->label(__('properties.labels.new_field'))
        ->required()
        ->maxLength(100)
        ->validationAttribute('new_field')
        ->validationMessages([
            'required' => $messages['new_field.required'],
            'max' => $messages['new_field.max'],
        ]);
}

// 3. Add to form schema
->schema([
    $this->getAddressField(),
    $this->getTypeField(),
    $this->getAreaField(),
    $this->getNewField(), // ← New field
])
```

### Custom Validation Rules

```php
// 1. Add custom rule to FormRequest
use App\Rules\ValidPropertyAddress;

public function rules(): array
{
    return [
        'address' => ['required', 'string', 'max:255', new ValidPropertyAddress],
    ];
}

// 2. Add message for custom rule
public function messages(): array
{
    return [
        'address.valid_property_address' => __('properties.validation.address.invalid_format'),
    ];
}

// 3. Use in Filament field
->rules([new ValidPropertyAddress])
->validationMessages([
    'valid_property_address' => $messages['address.valid_property_address'],
])
```

## Related Patterns

- [Automatic Data Injection](./automatic-data-injection.md)
- [Tenant Scope Isolation](./tenant-scope-isolation.md)
- [Localization Strategy](./localization-strategy.md)

## References

- [Filament Forms Documentation](https://filamentphp.com/docs/forms)
- [Laravel FormRequest Validation](https://laravel.com/docs/validation#form-request-validation)
- [PropertiesRelationManager Implementation](../../app/Filament/Resources/BuildingResource/RelationManagers/PropertiesRelationManager.php)
