# Tariff Manual Mode - Developer Guide

## Quick Start

This guide helps developers understand and work with the Tariff Manual Mode feature.

## 5-Minute Overview

### What is Manual Mode?

Manual Mode allows creating tariffs without linking to a provider integration. It's controlled by a UI-only toggle that shows/hides provider-related fields.

### Key Files

```
app/
├── Filament/Resources/
│   └── TariffResource/
│       └── Concerns/
│           └── BuildsTariffFormFields.php  ← Form field logic
├── Models/
│   └── Tariff.php                          ← Model with isManual() method
database/
└── migrations/
    └── 2025_12_05_163137_add_remote_id_to_tariffs_table.php
tests/
└── Feature/Filament/
    └── TariffManualModeTest.php            ← Test coverage
```

### Database Schema

```sql
-- New column for external system integration
ALTER TABLE tariffs ADD COLUMN remote_id VARCHAR(255) NULL;
ALTER TABLE tariffs ADD INDEX idx_remote_id (remote_id);

-- Made nullable for manual mode support
ALTER TABLE tariffs MODIFY COLUMN provider_id BIGINT UNSIGNED NULL;
```

## Implementation Details

### Form Field Structure

```php
// UI-only toggle (not saved to database)
Forms\Components\Toggle::make('manual_mode')
    ->dehydrated(false)  // Don't save to DB
    ->live()             // Real-time updates
    ->default(false);    // Provider mode by default

// Conditional provider field
Forms\Components\Select::make('provider_id')
    ->visible(fn (Get $get): bool => !$get('manual_mode'))
    ->required(fn (Get $get): bool => !$get('manual_mode'));

// New remote_id field
Forms\Components\TextInput::make('remote_id')
    ->visible(fn (Get $get): bool => !$get('manual_mode'))
    ->maxLength(255);
```

### Model Method

```php
// Check if tariff is manual
public function isManual(): bool
{
    return is_null($this->provider_id);
}
```

## Common Development Tasks

### 1. Adding a New Conditional Field

```php
protected static function buildBasicInformationFields(): array
{
    return [
        // ... existing fields ...
        
        Forms\Components\TextInput::make('your_new_field')
            ->visible(fn (Get $get): bool => !$get('manual_mode'))
            ->required(fn (Get $get): bool => !$get('manual_mode')),
    ];
}
```

### 2. Querying Manual Tariffs

```php
// Get all manual tariffs
$manualTariffs = Tariff::whereNull('provider_id')->get();

// Get all provider-linked tariffs
$providerTariffs = Tariff::whereNotNull('provider_id')->get();

// Check if specific tariff is manual
if ($tariff->isManual()) {
    // Handle manual tariff
}
```

### 3. Creating Tariffs Programmatically

```php
// Create manual tariff
$manualTariff = Tariff::create([
    'provider_id' => null,
    'name' => 'Manual Historical Rate',
    'configuration' => [
        'type' => 'flat',
        'rate' => 0.12,
        'currency' => 'EUR',
    ],
    'active_from' => '2024-01-01',
]);

// Create provider-linked tariff
$providerTariff = Tariff::create([
    'provider_id' => 5,
    'remote_id' => 'EXT-12345',
    'name' => 'Provider Standard Rate',
    'configuration' => [
        'type' => 'flat',
        'rate' => 0.15,
        'currency' => 'EUR',
    ],
    'active_from' => '2025-01-01',
]);
```

### 4. Validating Tariff Data

```php
// Validation rules adapt based on manual mode
$rules = [
    'provider_id' => 'nullable|exists:providers,id',
    'remote_id' => 'nullable|string|max:255',
    'name' => 'required|string|max:255',
    'configuration' => 'required|array',
    'active_from' => 'required|date',
];

// Additional validation for provider mode
if ($request->has('provider_id')) {
    $rules['provider_id'] = 'required|exists:providers,id';
}
```

### 5. Testing Manual Mode

```php
it('can create a manual tariff', function () {
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
    
    expect($tariff->isManual())->toBeTrue();
});
```

## Debugging Tips

### 1. Check Field Visibility

```php
// In Filament form, add this to debug visibility
Forms\Components\TextInput::make('debug_manual_mode')
    ->default(fn (Get $get) => $get('manual_mode') ? 'Manual' : 'Provider')
    ->disabled();
```

### 2. Verify Validation Rules

```php
// Log validation rules being applied
Log::info('Validation rules', [
    'manual_mode' => $get('manual_mode'),
    'provider_required' => !$get('manual_mode'),
]);
```

### 3. Check Database State

```sql
-- Find manual tariffs
SELECT * FROM tariffs WHERE provider_id IS NULL;

-- Find tariffs with remote_id
SELECT * FROM tariffs WHERE remote_id IS NOT NULL;

-- Check for orphaned remote_ids (should not exist)
SELECT * FROM tariffs WHERE remote_id IS NOT NULL AND provider_id IS NULL;
```

## Common Pitfalls

### 1. Forgetting `dehydrated(false)`

❌ **Wrong:**
```php
Forms\Components\Toggle::make('manual_mode')
    ->live();
```

✅ **Correct:**
```php
Forms\Components\Toggle::make('manual_mode')
    ->live()
    ->dehydrated(false);  // Don't save to database
```

### 2. Not Using Closures for Conditional Rules

❌ **Wrong:**
```php
->required(!$get('manual_mode'))  // Won't work
```

✅ **Correct:**
```php
->required(fn (Get $get): bool => !$get('manual_mode'))
```

### 3. Hardcoding Validation Rules

❌ **Wrong:**
```php
->rules(['required', 'exists:providers,id'])
```

✅ **Correct:**
```php
->rules([
    'nullable',
    'exists:providers,id',
])
```

## Performance Considerations

### 1. Use Cached Provider Options

```php
// Good - uses cache
->options(fn () => Provider::getCachedOptions())

// Bad - queries every time
->options(fn () => Provider::pluck('name', 'id'))
```

### 2. Index Remote ID Field

```php
// Migration includes index
$table->index('remote_id');

// Use in queries
Tariff::where('remote_id', $externalId)->first();
```

### 3. Eager Load Relationships

```php
// Good - eager load
$tariffs = Tariff::with('provider')->get();

// Bad - N+1 queries
$tariffs = Tariff::all();
foreach ($tariffs as $tariff) {
    $provider = $tariff->provider; // N+1 query
}
```

## Security Checklist

- ✅ Authorization via TariffPolicy (SUPERADMIN/ADMIN only)
- ✅ XSS prevention via input sanitization
- ✅ SQL injection prevention via Eloquent
- ✅ Audit logging via TariffObserver
- ✅ Validation on both client and server
- ✅ Rate limiting on API endpoints

## Testing Checklist

- ✅ Manual tariff creation
- ✅ Provider tariff creation
- ✅ Field visibility toggling
- ✅ Validation rules enforcement
- ✅ Remote ID max length
- ✅ Mode switching (manual to provider)
- ✅ Authorization checks
- ✅ Database constraints

## API Integration Examples

### Create Manual Tariff via API

```bash
curl -X POST https://api.example.com/api/tariffs \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "provider_id": null,
    "name": "Manual Historical Rate",
    "configuration": {
      "type": "flat",
      "rate": 0.12,
      "currency": "EUR"
    },
    "active_from": "2024-01-01"
  }'
```

### Create Provider Tariff via API

```bash
curl -X POST https://api.example.com/api/tariffs \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "provider_id": 5,
    "remote_id": "EXT-12345",
    "name": "Provider Standard Rate",
    "configuration": {
      "type": "flat",
      "rate": 0.15,
      "currency": "EUR"
    },
    "active_from": "2025-01-01"
  }'
```

## Extending the Feature

### Adding a New Mode

1. Add new toggle field
2. Update conditional visibility logic
3. Add validation rules
4. Update model methods
5. Add tests
6. Update documentation

### External System Integration

```php
// Sync tariff with external system
public function syncWithExternalSystem(Tariff $tariff): void
{
    if ($tariff->remote_id) {
        $externalData = ExternalAPI::getTariff($tariff->remote_id);
        
        $tariff->update([
            'configuration' => $externalData['configuration'],
            'active_from' => $externalData['active_from'],
        ]);
    }
}
```

## Troubleshooting

### Issue: Provider field still showing in manual mode

**Solution:** Check that `->live()` is set on manual_mode toggle

### Issue: Validation errors on manual tariff

**Solution:** Ensure validation rules use closures, not static values

### Issue: Remote ID not saving

**Solution:** Check that field is not `dehydrated(false)`

### Issue: Can't query by remote_id efficiently

**Solution:** Verify index exists: `SHOW INDEX FROM tariffs WHERE Key_name = 'idx_remote_id'`

## Resources

- [Feature Documentation](../filament/TARIFF_MANUAL_MODE.md)
- [API Documentation](../api/TARIFF_API.md)
- [Architecture Documentation](../architecture/TARIFF_MANUAL_MODE_ARCHITECTURE.md)
- [Quick Reference](../filament/TARIFF_QUICK_REFERENCE.md)
- [Test File](../../tests/Feature/Filament/TariffManualModeTest.php)

## Getting Help

1. Check the comprehensive feature documentation
2. Review the test file for examples
3. Check the architecture documentation for design details
4. Review the API documentation for integration
5. Check logs for error details

## Contributing

When modifying this feature:

1. Update code-level DocBlocks
2. Update feature documentation
3. Add/update tests
4. Update API documentation if endpoints change
5. Update this developer guide
6. Add changelog entry

---

**Last Updated:** 2025-12-05  
**Maintained By:** Development Team
