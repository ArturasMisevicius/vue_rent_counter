# Tariff Manual Mode - Design Document

## Architecture Overview

### System Context

The Tariff Manual Mode feature extends the existing tariff management system to support two operational modes:

1. **Provider Mode** (Default): Tariffs linked to provider integrations with optional external system IDs
2. **Manual Mode**: Tariffs created independently without provider linkage

```
┌─────────────────────────────────────────────────────────────┐
│                     Filament UI Layer                        │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  TariffResource (Form Definition)                      │ │
│  │  ├─ BuildsTariffFormFields (Trait)                     │ │
│  │  │  ├─ manual_mode Toggle (UI-only)                    │ │
│  │  │  ├─ provider_id Select (Conditional)                │ │
│  │  │  ├─ remote_id TextInput (Conditional)               │ │
│  │  │  └─ name TextInput (Always visible)                 │ │
│  │  └─ Conditional Validation Rules                       │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                         │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  TariffPolicy (Authorization)                          │ │
│  │  ├─ viewAny() - SUPERADMIN/ADMIN only                  │ │
│  │  ├─ create() - SUPERADMIN/ADMIN only                   │ │
│  │  ├─ update() - SUPERADMIN/ADMIN only                   │ │
│  │  └─ delete() - SUPERADMIN/ADMIN only                   │ │
│  └────────────────────────────────────────────────────────┘ │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  TariffObserver (Audit Logging)                        │ │
│  │  ├─ created() - Log tariff creation                    │ │
│  │  ├─ updated() - Log tariff updates                     │ │
│  │  └─ deleted() - Log tariff deletion                    │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                      Model Layer                             │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Tariff Model                                          │ │
│  │  ├─ provider_id (nullable)                             │ │
│  │  ├─ remote_id (nullable, indexed)                      │ │
│  │  ├─ isManual() - Check if provider_id is null         │ │
│  │  └─ provider() - BelongsTo relationship (nullable)    │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Database Layer                            │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  tariffs table                                         │ │
│  │  ├─ provider_id (nullable, foreign key, indexed)      │ │
│  │  ├─ remote_id (nullable, string(255), indexed)        │ │
│  │  └─ Multi-tenant scoping via tenant_id                │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Design Decisions

### Decision 1: UI-Only Toggle vs Database Field

**Problem:** How to control manual vs provider mode?

**Options Considered:**
1. Store mode as database field (e.g., `mode` enum)
2. Use UI-only toggle that controls field visibility
3. Infer mode from provider_id presence

**Decision:** UI-only toggle (Option 2)

**Rationale:**
- Mode is derivable from provider_id (null = manual, set = provider)
- Avoids data redundancy and potential inconsistency
- Simpler database schema
- Mode can be computed via `isManual()` method
- Reduces migration complexity

**Trade-offs:**
- Cannot directly query by mode (must use `WHERE provider_id IS NULL`)
- Mode must be computed for API responses
- Slightly more complex queries for mode-based filtering

### Decision 2: Nullable Provider ID vs Separate Tables

**Problem:** How to store manual and provider tariffs?

**Options Considered:**
1. Single table with nullable provider_id
2. Separate tables (manual_tariffs, provider_tariffs)
3. Single table inheritance with type discriminator

**Decision:** Single table with nullable provider_id (Option 1)

**Rationale:**
- Simpler schema and queries
- Easier to convert manual tariffs to provider tariffs
- Maintains existing relationships and scopes
- Reduces code duplication
- Follows Laravel conventions

**Trade-offs:**
- Nullable foreign key (less strict referential integrity)
- Must handle null checks in queries
- Slightly more complex validation logic

### Decision 3: Remote ID Field Design

**Problem:** How to store external system identifiers?

**Options Considered:**
1. String field with max length 255
2. UUID field
3. JSON field for multiple external IDs
4. Separate external_identifiers table

**Decision:** String field with max length 255 (Option 1)

**Rationale:**
- Flexible enough for most external system IDs
- Simple to query and index
- No overhead of UUID generation
- Sufficient for single external system integration
- Can be extended later if needed

**Trade-offs:**
- Limited to single external ID per tariff
- No built-in validation of ID format
- 255 character limit may be restrictive for some systems

### Decision 4: Conditional Validation Strategy

**Problem:** How to validate fields based on mode?

**Options Considered:**
1. Separate FormRequests for each mode
2. Conditional validation rules using closures
3. Custom validation rule classes
4. JavaScript-only validation

**Decision:** Conditional validation rules using closures (Option 2)

**Rationale:**
- Leverages Filament's built-in validation
- Single source of truth for validation logic
- Real-time validation feedback
- Consistent with Laravel patterns
- Easy to maintain and test

**Trade-offs:**
- Slightly more complex validation rule definitions
- Must ensure consistency between Filament and FormRequest validation
- Closure-based rules harder to reuse

### Decision 5: Field Visibility Control

**Problem:** How to show/hide fields based on mode?

**Options Considered:**
1. CSS display:none with JavaScript
2. Livewire conditional rendering
3. Filament's ->visible() with closures
4. Separate forms for each mode

**Decision:** Filament's ->visible() with closures (Option 3)

**Rationale:**
- Native Filament feature
- Reactive updates via ->live()
- No custom JavaScript required
- Maintains form state when toggling
- Accessible by default

**Trade-offs:**
- Requires Livewire round-trip for visibility changes
- All fields must be defined even if hidden
- Slightly larger initial payload

## Component Design

### BuildsTariffFormFields Trait

**Responsibility:** Construct tariff form fields with conditional visibility

**Key Methods:**

```php
protected static function buildBasicInformationFields(): array
{
    return [
        // UI-only toggle
        Forms\Components\Toggle::make('manual_mode')
            ->dehydrated(false)  // Don't save to database
            ->live()             // Enable reactive updates
            ->default(false),    // Provider mode by default
        
        // Conditional provider field
        Forms\Components\Select::make('provider_id')
            ->visible(fn (Get $get): bool => !$get('manual_mode'))
            ->required(fn (Get $get): bool => !$get('manual_mode')),
        
        // Conditional remote_id field
        Forms\Components\TextInput::make('remote_id')
            ->visible(fn (Get $get): bool => !$get('manual_mode'))
            ->maxLength(255),
        
        // Always visible name field
        Forms\Components\TextInput::make('name')
            ->required()
            ->maxLength(255),
    ];
}
```

**Design Patterns:**
- **Trait Pattern**: Extracts form logic from resource class
- **Builder Pattern**: Constructs complex form schemas
- **Strategy Pattern**: Conditional validation based on mode

### Tariff Model

**Responsibility:** Data representation and business logic

**Key Methods:**

```php
public function isManual(): bool
{
    return is_null($this->provider_id);
}

public function provider(): BelongsTo
{
    return $this->belongsTo(Provider::class);
}

// Accessor for API responses
protected function isManual(): Attribute
{
    return Attribute::make(
        get: fn () => is_null($this->provider_id),
    );
}
```

**Design Patterns:**
- **Active Record**: Laravel Eloquent pattern
- **Null Object**: Nullable provider relationship
- **Accessor Pattern**: Computed attributes

## Data Flow

### Manual Tariff Creation Flow

```
1. User enables manual_mode toggle
   ↓
2. Filament hides provider_id and remote_id fields (reactive)
   ↓
3. User fills in name and configuration
   ↓
4. Form validation runs (provider_id not required)
   ↓
5. TariffPolicy::create() checks authorization
   ↓
6. Tariff model created with provider_id = null
   ↓
7. TariffObserver::created() logs the action
   ↓
8. User redirected to tariff list
```

### Provider-Linked Tariff Creation Flow

```
1. User keeps manual_mode toggle disabled (default)
   ↓
2. Filament shows provider_id and remote_id fields
   ↓
3. User selects provider and optionally enters remote_id
   ↓
4. Form validation runs (provider_id required)
   ↓
5. TariffPolicy::create() checks authorization
   ↓
6. Tariff model created with provider_id and remote_id
   ↓
7. TariffObserver::created() logs the action
   ↓
8. User redirected to tariff list
```

### Mode Switching Flow

```
1. User toggles manual_mode
   ↓
2. Livewire detects change (->live())
   ↓
3. Server-side visibility evaluation
   ↓
4. Conditional fields show/hide
   ↓
5. Validation rules update
   ↓
6. Form state preserved
   ↓
7. UI updates (no page reload)
```

## Validation Strategy

### Conditional Validation Rules

```php
// Provider ID validation
->rules([
    fn (Get $get): string => !$get('manual_mode') ? 'required' : 'nullable',
    fn (Get $get): string => !$get('manual_mode') ? 'exists:providers,id' : 'nullable',
])

// Remote ID validation
->rules([
    'nullable',
    'string',
    'max:255',
])

// Name validation (always required)
->rules([
    'required',
    'string',
    'max:255',
    'regex:/^[a-zA-Z0-9\s\-\_\.\,\(\)]+$/u',
])
```

### Validation Consistency

**Filament Form Validation:**
- Uses closure-based conditional rules
- Real-time validation on field blur
- Immediate user feedback

**FormRequest Validation:**
- Uses standard Laravel validation rules
- Server-side validation on submission
- Consistent error messages

**Consistency Approach:**
- Both use same validation logic
- Translation keys shared
- Error messages identical

## Security Architecture

### Authorization Layer

```
Request → Middleware → TariffPolicy → Resource Action
                           ↓
                    Check User Role
                           ↓
                  SUPERADMIN/ADMIN?
                    ↓         ↓
                  Yes       No
                    ↓         ↓
                 Allow    Deny (403)
```

**Policy Methods:**
- `viewAny()`: Check if user can view tariff list
- `create()`: Check if user can create tariffs
- `update()`: Check if user can update tariffs
- `delete()`: Check if user can delete tariffs

**Authorization Rules:**
- Only SUPERADMIN and ADMIN roles allowed
- Manual mode doesn't bypass authorization
- Multi-tenant scoping enforced via BelongsToTenant

### Input Sanitization

```
User Input → Validation → Sanitization → Database
                ↓              ↓
           Regex Check    XSS Prevention
                ↓              ↓
           Type Check     SQL Injection Prevention
```

**Sanitization Measures:**
- Name field: Regex validation + InputSanitizer service
- Remote ID: Max length + string type validation
- Configuration: JSON validation + type checking
- All fields: Eloquent parameterized queries

### Audit Trail

```
Action → Observer → Audit Log → Database
           ↓
    Capture Context
           ↓
    - User ID
    - Timestamp
    - Action Type
    - Old/New Values
    - Manual Mode Status
```

**Audit Events:**
- Tariff created (manual or provider)
- Tariff updated (including mode changes)
- Tariff deleted
- Provider added to manual tariff

## Performance Considerations

### Query Optimization

**Indexed Fields:**
- `provider_id`: Foreign key index (existing)
- `remote_id`: Standard index (new)
- `tenant_id`: Multi-tenant scoping index (existing)

**Query Patterns:**
```sql
-- Find manual tariffs
SELECT * FROM tariffs WHERE provider_id IS NULL;

-- Find provider tariffs
SELECT * FROM tariffs WHERE provider_id IS NOT NULL;

-- Find by remote_id
SELECT * FROM tariffs WHERE remote_id = 'EXT-12345';
```

### Caching Strategy

**Provider Options:**
```php
Provider::getCachedOptions()
```
- Cache provider dropdown options
- Reduce database queries on form load
- Invalidate cache on provider changes

**Form State:**
- Livewire maintains form state in session
- No database queries for field visibility changes
- Reactive updates use Livewire's wire:model

### Performance Targets

- Form initial render: <300ms
- Manual mode toggle: <100ms
- Form submission: <500ms
- Provider dropdown: <200ms (cached)
- Database queries: <100ms

## Extensibility Points

### Adding New Modes

To add additional entry modes:

1. Add new toggle field in `buildBasicInformationFields()`
2. Update conditional visibility logic
3. Add corresponding validation rules
4. Update model methods if needed
5. Add tests for new mode

### External System Integration

The `remote_id` field enables integration with external systems:

**Sync Service:**
```php
class TariffSyncService
{
    public function syncFromExternalSystem(string $remoteId): void
    {
        $externalData = $this->externalApi->getTariff($remoteId);
        
        $tariff = Tariff::where('remote_id', $remoteId)->first();
        
        if ($tariff) {
            $tariff->update([
                'configuration' => $externalData['configuration'],
                'active_from' => $externalData['active_from'],
            ]);
        }
    }
}
```

### Custom Validation Rules

To add custom validation:

1. Create custom validation rule class
2. Add to `->rules()` array in form field
3. Add localized error message
4. Mirror in FormRequest validation

## Testing Strategy

### Unit Tests

**Model Tests:**
```php
it('identifies manual tariffs correctly', function () {
    $manual = Tariff::factory()->create(['provider_id' => null]);
    expect($manual->isManual())->toBeTrue();
});
```

**Validation Tests:**
```php
it('validates provider required in provider mode', function () {
    $data = ['manual_mode' => false, 'name' => 'Test'];
    // Assert validation fails without provider_id
});
```

### Feature Tests

**Filament Integration:**
```php
it('can create manual tariff', function () {
    Livewire::test(CreateTariff::class)
        ->fillForm(['name' => 'Manual Tariff'])
        ->call('create')
        ->assertHasNoFormErrors();
});
```

**Authorization Tests:**
```php
it('prevents non-admin from creating tariffs', function () {
    actingAs(User::factory()->create(['role' => 'tenant']));
    // Assert 403 response
});
```

### Integration Tests

**End-to-End Flow:**
```php
it('completes manual tariff creation flow', function () {
    // 1. Navigate to create form
    // 2. Enable manual mode
    // 3. Fill in fields
    // 4. Submit form
    // 5. Verify tariff created
    // 6. Verify audit log entry
});
```

## Deployment Considerations

### Migration Strategy

**Zero-Downtime Migration:**
1. Add nullable columns first
2. Deploy code changes
3. No data migration required

**Rollback Plan:**
1. Revert migration if needed
2. Check for manual tariffs before rollback
3. Assign providers to manual tariffs if necessary

### Monitoring

**Metrics to Track:**
- Manual tariff creation rate
- Provider tariff creation rate
- Validation error frequency
- Form performance metrics

**Alerts:**
- High validation error rate (>10%)
- Slow query performance (>100ms)
- Authorization failures

## Related Documentation

- Requirements: `.kiro/specs/tariff-manual-mode/requirements.md`
- API Documentation: `docs/api/TARIFF_API.md`
- Architecture: `docs/architecture/TARIFF_MANUAL_MODE_ARCHITECTURE.md`
- Developer Guide: `docs/guides/TARIFF_MANUAL_MODE_DEVELOPER_GUIDE.md`
- Quick Reference: `docs/filament/TARIFF_QUICK_REFERENCE.md`

## References

- Migration: `database/migrations/2025_12_05_163137_add_remote_id_to_tariffs_table.php`
- Form Builder: `app/Filament/Resources/TariffResource/Concerns/BuildsTariffFormFields.php`
- Model: `app/Models/Tariff.php`
- Tests: `tests/Feature/Filament/TariffManualModeTest.php`
