# Tariff Manual Mode Architecture

## Overview

This document describes the architectural design and implementation of the Tariff Manual Entry Mode feature, which enables creation of tariffs without provider integration.

## Architecture Diagram

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
│  │  ├─ name (required)                                    │ │
│  │  ├─ configuration (JSON)                               │ │
│  │  ├─ active_from (date)                                 │ │
│  │  ├─ active_until (nullable date)                       │ │
│  │  └─ Methods:                                           │ │
│  │     ├─ isManual() - Check if provider_id is null      │ │
│  │     ├─ isActiveOn($date) - Check active status        │ │
│  │     └─ provider() - BelongsTo relationship            │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│                    Database Layer                            │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  tariffs table                                         │ │
│  │  ├─ id (primary key)                                   │ │
│  │  ├─ provider_id (nullable, foreign key, indexed)      │ │
│  │  ├─ remote_id (nullable, string(255), indexed)        │ │
│  │  ├─ name (string(255))                                 │ │
│  │  ├─ configuration (JSON)                               │ │
│  │  ├─ active_from (date)                                 │ │
│  │  ├─ active_until (nullable date)                       │ │
│  │  ├─ created_at (timestamp)                             │ │
│  │  └─ updated_at (timestamp)                             │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## Component Responsibilities

### UI Layer: BuildsTariffFormFields Trait

**Responsibility:** Form field construction with conditional visibility

**Key Methods:**
- `buildBasicInformationFields()`: Constructs manual mode toggle and conditional fields
- `buildEffectivePeriodFields()`: Date range fields
- `buildConfigurationFields()`: Tariff type and rate configuration

**Design Patterns:**
- **Trait Pattern**: Extracts form logic from resource class
- **Builder Pattern**: Constructs complex form schemas
- **Strategy Pattern**: Conditional validation based on mode

**Dependencies:**
- Filament Forms components
- Provider model (for cached options)
- InputSanitizer service (for XSS prevention)

### Application Layer: TariffPolicy

**Responsibility:** Authorization enforcement

**Authorization Rules:**
- Only SUPERADMIN and ADMIN can manage tariffs
- Manual mode doesn't bypass authorization
- All CRUD operations require policy checks

**Integration Points:**
- TariffResource::canViewAny()
- TariffResource::canCreate()
- TariffResource::canEdit()
- TariffResource::canDelete()

### Application Layer: TariffObserver

**Responsibility:** Audit logging and event handling

**Events Captured:**
- Tariff creation (including manual tariffs)
- Tariff updates (including mode changes)
- Tariff deletion

**Audit Data:**
- User who performed action
- Timestamp of action
- Old and new values
- Manual mode status

### Model Layer: Tariff

**Responsibility:** Data representation and business logic

**Key Methods:**
- `isManual()`: Determines if tariff is provider-independent
- `isActiveOn($date)`: Checks if tariff is active on given date
- `provider()`: BelongsTo relationship (nullable)

**Computed Attributes:**
- `is_currently_active`: Boolean indicating current active status
- `is_manual`: Boolean indicating manual mode (via accessor)

**Scopes:**
- `active($date)`: Filter to active tariffs
- `forProvider($providerId)`: Filter by provider
- `flatRate()`: Filter to flat rate tariffs
- `timeOfUse()`: Filter to time-of-use tariffs

### Database Layer: tariffs Table

**Schema Design:**
- `provider_id`: Nullable foreign key (supports manual mode)
- `remote_id`: Nullable indexed string (external system integration)
- Both fields indexed for query performance

**Indexes:**
- Primary key on `id`
- Foreign key index on `provider_id`
- Index on `remote_id` for external lookups
- Composite indexes for common queries

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

## Validation Strategy

### Conditional Validation Rules

The validation strategy uses Filament's closure-based validation to adapt rules based on form state:

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

### Validation Consistency

Form validation mirrors FormRequest validation to ensure consistency:
- **Filament UI**: Uses closure-based conditional rules
- **API Requests**: Uses Laravel validation rules
- **Both**: Produce identical validation errors

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

### Input Sanitization

```
User Input → Validation → Sanitization → Database
                ↓              ↓
           Regex Check    XSS Prevention
                ↓              ↓
           Type Check     SQL Injection Prevention
```

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

## Performance Considerations

### Query Optimization

1. **Indexed Fields:**
   - `provider_id`: Foreign key index
   - `remote_id`: Standard index
   - Composite indexes for common queries

2. **Cached Data:**
   - Provider options cached via `Provider::getCachedOptions()`
   - Navigation visibility memoized per request

3. **Eager Loading:**
   - Provider relationship eager-loaded in table queries
   - Prevents N+1 query problems

### Form Performance

1. **Conditional Loading:**
   - Fields only rendered when visible
   - Reduces DOM complexity

2. **Reactive Updates:**
   - Filament's `->live()` enables real-time updates
   - Minimal server round-trips

3. **Validation Efficiency:**
   - Client-side validation for immediate feedback
   - Server-side validation for security

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

1. **Sync Service:** Create service to sync tariffs via remote_id
2. **Webhook Handler:** Handle updates from external systems
3. **Batch Import:** Import tariffs with remote_id mapping
4. **Export API:** Export tariffs with remote_id for external use

### Custom Validation Rules

To add custom validation:

1. Create custom validation rule class
2. Add to `->rules()` array in form field
3. Add localized error message
4. Mirror in FormRequest validation

## Testing Strategy

### Unit Tests

- Model method tests (`isManual()`, `isActiveOn()`)
- Validation rule tests
- Scope tests

### Feature Tests

- Manual tariff creation flow
- Provider tariff creation flow
- Mode switching scenarios
- Validation error scenarios
- Authorization tests

### Integration Tests

- Filament form interaction
- Database persistence
- Observer event handling
- Policy enforcement

## Deployment Considerations

### Migration Strategy

1. **Zero-Downtime Migration:**
   - Add nullable columns first
   - Deploy code changes
   - No data migration required

2. **Rollback Plan:**
   - Revert migration if needed
   - Check for manual tariffs before rollback
   - Assign providers to manual tariffs if necessary

### Monitoring

1. **Metrics to Track:**
   - Manual tariff creation rate
   - Provider tariff creation rate
   - Validation error frequency
   - Performance metrics (query time, form load time)

2. **Alerts:**
   - High validation error rate
   - Slow query performance
   - Authorization failures

## Related Documentation

- [Tariff Manual Mode Feature Guide](../filament/TARIFF_MANUAL_MODE.md)
- [Tariff API Documentation](../api/TARIFF_API.md)
- [TariffResource Documentation](../filament/TARIFF_RESOURCE.md)
- [Validation Consistency](../testing/VALIDATION_CONSISTENCY.md)

## References

- Migration: `database/migrations/2025_12_05_163137_add_remote_id_to_tariffs_table.php`
- Trait: `app/Filament/Resources/TariffResource/Concerns/BuildsTariffFormFields.php`
- Model: `app/Models/Tariff.php`
- Tests: `tests/Feature/Filament/TariffManualModeTest.php`
