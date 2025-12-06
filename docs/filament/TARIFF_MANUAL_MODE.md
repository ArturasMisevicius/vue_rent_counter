# Tariff Manual Entry Mode

## Overview

The Tariff Manual Entry Mode feature allows administrators to create and manage tariffs without requiring integration with external provider systems. This is essential for scenarios where tariff data must be entered manually from paper documents, historical records, or custom configurations.

## Feature Summary

- **Manual Mode Toggle**: UI-only toggle that controls field visibility
- **Conditional Fields**: Provider and Remote ID fields hidden in manual mode
- **Flexible Validation**: Validation rules adapt based on selected mode
- **Provider Integration**: Optional external system synchronization via Remote ID

## Use Cases

### Manual Entry Mode (provider_id = null)

1. **Historical Data Entry**: Import tariff rates from paper records or legacy systems
2. **Custom Configurations**: Create tariff structures not available via provider APIs
3. **Testing & Development**: Quick tariff creation without provider setup
4. **Temporary Rates**: Enter short-term or promotional rates manually

### Provider Integration Mode (provider_id set)

1. **Automated Sync**: Link tariffs to provider integrations for automatic updates
2. **External System Integration**: Use remote_id for bidirectional synchronization
3. **Provider-Managed Rates**: Leverage provider API for rate updates
4. **Audit Trail**: Track tariff source and external references

## Database Schema

### Migration: `2025_12_05_163137_add_remote_id_to_tariffs_table.php`

```php
// Added columns
$table->string('remote_id', 255)->nullable()->after('provider_id');
$table->index('remote_id'); // For external system lookups

// Modified columns
$table->foreignId('provider_id')->nullable()->change(); // Now optional
```

### Tariff Model Changes

```php
/**
 * Check if this is a manual tariff (not linked to a provider).
 */
public function isManual(): bool
{
    return is_null($this->provider_id);
}
```

## Form Implementation

### Field Structure

The form uses Filament's reactive fields with conditional visibility:

```php
Forms\Components\Toggle::make('manual_mode')
    ->label(__('tariffs.forms.manual_mode'))
    ->helperText(__('tariffs.forms.manual_mode_helper'))
    ->default(false)
    ->live()
    ->columnSpanFull()
    ->dehydrated(false), // Not saved to database

Forms\Components\Select::make('provider_id')
    ->visible(fn (Get $get): bool => !$get('manual_mode'))
    ->required(fn (Get $get): bool => !$get('manual_mode'))
    // ... additional configuration

Forms\Components\TextInput::make('remote_id')
    ->visible(fn (Get $get): bool => !$get('manual_mode'))
    ->maxLength(255)
    // ... additional configuration
```

### Key Implementation Details

1. **manual_mode Field**:
   - UI-only toggle (not persisted to database)
   - Uses `->dehydrated(false)` to prevent saving
   - `->live()` enables real-time field updates
   - Default: `false` (provider mode)

2. **provider_id Field**:
   - Conditionally visible based on manual_mode
   - Conditionally required based on manual_mode
   - Uses cached provider options for performance
   - Searchable for large provider lists

3. **remote_id Field**:
   - New field for external system integration
   - Optional even when provider is selected
   - Max length: 255 characters
   - Indexed for fast lookups

## Validation Rules

### Conditional Validation

The validation rules adapt based on the manual_mode state:

```php
// Provider ID validation
->rules([
    'nullable',
    'exists:providers,id',
])

// Remote ID validation
->rules([
    'nullable',
    'string',
    'max:255',
    fn (Get $get): string => $get('remote_id') && !$get('provider_id') 
        ? 'required_with:provider_id' 
        : '',
])
```

### Validation Messages

All validation messages are localized in `lang/en/tariffs.php`:

```php
'validation' => [
    'provider_id' => [
        'required' => 'Provider is required',
        'exists' => 'Selected provider does not exist',
        'required_with' => 'Provider is required when external ID is provided',
    ],
    'remote_id' => [
        'max' => 'External ID may not be greater than 255 characters',
    ],
],
```

## User Interface

### Manual Mode Enabled

When manual mode is enabled:
- ✅ Manual Mode toggle is ON
- ❌ Provider field is hidden
- ❌ Remote ID field is hidden
- ✅ Name field is visible and required
- ✅ All other tariff configuration fields remain visible

### Provider Mode (Default)

When manual mode is disabled:
- ❌ Manual Mode toggle is OFF
- ✅ Provider field is visible and required
- ✅ Remote ID field is visible (optional)
- ✅ Name field is visible and required
- ✅ All other tariff configuration fields remain visible

## Testing

### Test Coverage

The feature includes comprehensive test coverage in `tests/Feature/Filament/TariffManualModeTest.php`:

1. **Manual Tariff Creation**: Verify tariffs can be created without provider
2. **Provider Tariff Creation**: Verify tariffs with provider and remote_id
3. **Validation Rules**: Ensure provider required when remote_id provided
4. **Field Length Validation**: Test remote_id max length constraint
5. **Mode Switching**: Test editing manual tariff to add provider later

### Example Test Cases

```php
it('can create a manual tariff without provider', function () {
    $data = [
        'name' => 'Manual Test Tariff',
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
            'currency' => 'EUR',
        ],
        'active_from' => now()->toDateString(),
    ];

    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasNoFormErrors();

    $tariff = Tariff::where('name', 'Manual Test Tariff')->first();
    
    expect($tariff->provider_id)->toBeNull()
        ->and($tariff->isManual())->toBeTrue();
});
```

## API Consistency

The Filament form validation mirrors the FormRequest validation to ensure consistency:

- **StoreTariffRequest**: Handles tariff creation validation
- **UpdateTariffRequest**: Handles tariff update validation
- Both support manual mode via nullable provider_id

## Security Considerations

1. **XSS Prevention**: Name field uses regex validation and sanitization
2. **SQL Injection**: Remote ID uses parameterized queries via Eloquent
3. **Authorization**: TariffPolicy enforces SUPERADMIN/ADMIN access only
4. **Audit Logging**: TariffObserver tracks all tariff changes

## Performance Optimization

1. **Cached Provider Options**: `Provider::getCachedOptions()` reduces queries
2. **Indexed Remote ID**: Database index on remote_id for fast lookups
3. **Conditional Loading**: Fields only loaded when visible
4. **Memoized Navigation**: Navigation visibility cached per request

## Migration Guide

### Upgrading Existing Tariffs

All existing tariffs remain unchanged:
- Existing provider_id values are preserved
- No data migration required
- Backward compatible with existing code

### Creating New Manual Tariffs

```php
// Via Filament UI
1. Navigate to Tariffs → Create
2. Enable "Manual Entry Mode" toggle
3. Enter tariff name and configuration
4. Save (provider_id will be null)

// Via API/Code
Tariff::create([
    'provider_id' => null, // Manual mode
    'name' => 'Manual Tariff',
    'configuration' => [...],
    'active_from' => now(),
]);
```

## Related Documentation

- [TariffResource Documentation](./TARIFF_RESOURCE.md)
- [Tariff Model Documentation](../models/TARIFF_MODEL.md)
- [Provider Integration](./PROVIDER_INTEGRATION.md)
- [Validation Consistency](../testing/VALIDATION_CONSISTENCY.md)

## Changelog

### 2025-12-05: Manual Mode Implementation

- Added `remote_id` column to tariffs table
- Made `provider_id` nullable
- Implemented manual mode toggle in Filament form
- Added conditional field visibility and validation
- Created comprehensive test coverage
- Updated documentation

## Support

For questions or issues related to tariff manual mode:
1. Check test coverage in `tests/Feature/Filament/TariffManualModeTest.php`
2. Review validation rules in `BuildsTariffFormFields.php`
3. Consult migration file for schema details
4. See `Tariff::isManual()` method for model logic
