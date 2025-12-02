# TariffResource API Documentation

## Overview

The `TariffResource` provides a Filament v4 admin interface for managing utility tariffs in the Vilnius Utilities Billing Platform. It implements comprehensive CRUD operations with role-based access control, tenant scoping, and extensive validation.

## Resource Information

- **Namespace**: `App\Filament\Resources`
- **Model**: `App\Models\Tariff`
- **Policy**: `App\Policies\TariffPolicy`
- **Observer**: `App\Observers\TariffObserver`
- **Navigation Group**: Configuration
- **Navigation Icon**: `heroicon-o-currency-dollar`
- **Navigation Sort**: 1

## Authorization

### Role-Based Access

| Role | View | Create | Edit | Delete | Navigation Visible |
|------|------|--------|------|--------|-------------------|
| SUPERADMIN | ✅ | ✅ | ✅ | ✅ | ✅ |
| ADMIN | ✅ | ✅ | ✅ | ✅ | ✅ |
| MANAGER | ❌ | ❌ | ❌ | ❌ | ❌ |
| TENANT | ❌ | ❌ | ❌ | ❌ | ❌ |

### Authorization Methods

#### `canViewAny(): bool`

Determines if the current user can view any tariffs.

**Returns**: `bool` - True if user has viewAny permission

**Policy Method**: `TariffPolicy::viewAny()`

**Example**:
```php
if (TariffResource::canViewAny()) {
    // User can access tariff list
}
```

#### `canCreate(): bool`

Determines if the current user can create tariffs.

**Returns**: `bool` - True if user has create permission

**Policy Method**: `TariffPolicy::create()`

#### `canEdit($record): bool`

Determines if the current user can edit a specific tariff.

**Parameters**:
- `$record` (`Tariff`) - The tariff record to check

**Returns**: `bool` - True if user has update permission

**Policy Method**: `TariffPolicy::update()`

#### `canDelete($record): bool`

Determines if the current user can delete a specific tariff.

**Parameters**:
- `$record` (`Tariff`) - The tariff record to check

**Returns**: `bool` - True if user has delete permission

**Policy Method**: `TariffPolicy::delete()`

#### `shouldRegisterNavigation(): bool`

Determines if the resource should appear in the navigation menu.

**Returns**: `bool` - True if resource should be visible in navigation

**Logic**:
- Returns `true` for SUPERADMIN and ADMIN roles
- Returns `false` for MANAGER and TENANT roles
- Uses explicit `instanceof` check to prevent null pointer exceptions
- Uses strict type checking in `in_array()` for security

**Example**:
```php
// Navigation visibility is automatically handled by Filament
// This method is called internally during panel registration
```

## Form Schema

### Sections

The form is organized into three main sections:

1. **Basic Information** (2 columns)
   - Provider selection
   - Tariff name
   - Service type
   - Tariff type (flat/time-of-use)

2. **Effective Period** (2 columns)
   - Active from date
   - Active until date

3. **Configuration** (2 columns)
   - Flat rate (for flat tariffs)
   - Zone configuration (for time-of-use tariffs)
   - Weekend logic
   - Fixed fee

### Field Validation

All fields include explicit validation rules that mirror `StoreTariffRequest` and `UpdateTariffRequest`:

#### Provider ID
```php
->rules(['required', 'exists:providers,id'])
->validationMessages([
    'required' => __('tariffs.validation.provider_id.required'),
    'exists' => __('tariffs.validation.provider_id.exists'),
])
```

#### Name
```php
->rules(['required', 'string', 'max:255'])
->validationMessages([
    'required' => __('tariffs.validation.name.required'),
    'max' => __('tariffs.validation.name.max'),
])
```

#### Dates
```php
->rules(['required', 'date', 'after_or_equal:today'])
->validationMessages([
    'required' => __('tariffs.validation.active_from.required'),
    'date' => __('tariffs.validation.active_from.date'),
    'after_or_equal' => __('tariffs.validation.active_from.after_or_equal'),
])
```

#### Flat Rate (Conditional)
```php
->rules(function (Get $get) {
    return $get('tariff_type') === TariffType::FLAT->value
        ? ['required', 'numeric', 'min:0', 'max:999999.99']
        : ['nullable'];
})
```

#### Zone Configuration (Conditional)
```php
->rules(function (Get $get) {
    return $get('tariff_type') === TariffType::TIME_OF_USE->value
        ? ['required', 'array', 'min:1']
        : ['nullable'];
})
```

### Security Features

1. **XSS Prevention**
   - Regex validation for time formats (HH:MM)
   - HTML sanitization for text inputs
   - Strict type validation for numeric fields

2. **Numeric Overflow Protection**
   - Max value validation: 999999.99 for rates
   - Prevents database overflow errors
   - Ensures reasonable tariff values

3. **Zone ID Injection Prevention**
   - Validates zone IDs against TariffZone enum
   - Prevents arbitrary zone creation
   - Ensures data integrity

4. **Tenant Scope Protection**
   - Provider loading scoped to current tenant
   - Prevents cross-tenant data access
   - Enforced via TenantScope

## Table Schema

### Columns

The table displays the following columns:

1. **Provider** - Provider name with service type badge
2. **Name** - Tariff name (searchable, sortable)
3. **Service Type** - Service type badge with color coding
4. **Tariff Type** - Flat or Time-of-Use badge
5. **Active From** - Start date (sortable)
6. **Active Until** - End date (sortable)
7. **Status** - Active/Inactive badge based on dates

### Query Optimization

```php
->modifyQueryUsing(fn ($query) => $query->with('provider:id,name,service_type'))
```

Eager loads provider relationship to prevent N+1 queries.

### Default Sorting

```php
->defaultSort('active_from', 'desc')
```

Sorts by most recent tariffs first.

## Pages

### List Page

**Route**: `/admin/tariffs`

**Class**: `App\Filament\Resources\TariffResource\Pages\ListTariffs`

**Features**:
- Searchable table
- Sortable columns
- Tenant-scoped data
- Edit action per row

### Create Page

**Route**: `/admin/tariffs/create`

**Class**: `App\Filament\Resources\TariffResource\Pages\CreateTariff`

**Features**:
- Multi-section form
- Conditional field visibility
- Real-time validation
- Automatic tenant_id assignment

### Edit Page

**Route**: `/admin/tariffs/{record}/edit`

**Class**: `App\Filament\Resources\TariffResource\Pages\EditTariff`

**Features**:
- Pre-populated form
- Conditional field visibility
- Real-time validation
- Audit logging on save

## Audit Logging

All tariff operations are logged via `TariffObserver`:

### Events Logged

1. **Creating** - Before tariff creation
2. **Created** - After tariff creation
3. **Updating** - Before tariff update
4. **Updated** - After tariff update
5. **Deleting** - Before tariff deletion
6. **Deleted** - After tariff deletion

### Audit Log Fields

- User ID
- Action type
- Tariff ID
- Old values (for updates)
- New values (for updates)
- Timestamp
- IP address
- User agent

## Usage Examples

### Creating a Flat Rate Tariff

```php
// Navigate to /admin/tariffs/create
// Fill in the form:
[
    'provider_id' => 1,
    'name' => 'Standard Electricity Rate',
    'service_type' => ServiceType::ELECTRICITY,
    'tariff_type' => TariffType::FLAT,
    'active_from' => '2024-01-01',
    'active_until' => '2024-12-31',
    'rate' => 0.15,
    'fixed_fee' => 5.00,
]
// Submit form
// Tariff is created with automatic tenant_id assignment
```

### Creating a Time-of-Use Tariff

```php
// Navigate to /admin/tariffs/create
// Fill in the form:
[
    'provider_id' => 1,
    'name' => 'Day/Night Electricity Rate',
    'service_type' => ServiceType::ELECTRICITY,
    'tariff_type' => TariffType::TIME_OF_USE,
    'active_from' => '2024-01-01',
    'active_until' => '2024-12-31',
    'zones' => [
        [
            'zone_id' => TariffZone::DAY,
            'rate' => 0.18,
            'start_time' => '07:00',
            'end_time' => '23:00',
        ],
        [
            'zone_id' => TariffZone::NIGHT,
            'rate' => 0.09,
            'start_time' => '23:00',
            'end_time' => '07:00',
        ],
    ],
    'weekend_logic' => WeekendLogic::SEPARATE_RATE,
    'fixed_fee' => 5.00,
]
// Submit form
// Tariff is created with zone configuration
```

### Editing a Tariff

```php
// Navigate to /admin/tariffs/{id}/edit
// Modify fields as needed
// Submit form
// Changes are logged via TariffObserver
// Old and new values are recorded in audit log
```

### Deleting a Tariff

```php
// Navigate to /admin/tariffs
// Click delete action on a tariff row
// Confirm deletion
// Tariff is soft-deleted (if using SoftDeletes)
// Deletion is logged via TariffObserver
```

## Error Handling

### Validation Errors

All validation errors are displayed inline with localized messages:

```php
// Example validation error response
[
    'provider_id' => ['The provider field is required.'],
    'name' => ['The name field is required.'],
    'rate' => ['The rate must be a number between 0 and 999999.99.'],
]
```

### Authorization Errors

Unauthorized access attempts return 403 Forbidden:

```php
// Example: MANAGER trying to access tariffs
abort(403, 'This action is unauthorized.');
```

### Not Found Errors

Invalid tariff IDs return 404 Not Found:

```php
// Example: Accessing non-existent tariff
abort(404, 'Tariff not found.');
```

## Testing

### Test Files

1. **Validation Tests**: `tests/Feature/Filament/FilamentTariffValidationConsistencyPropertyTest.php`
2. **Security Tests**: `tests/Feature/Security/TariffResourceSecurityTest.php`
3. **Navigation Tests**: `tests/Feature/Filament/FilamentNavigationVisibilityTest.php`
4. **Authorization Tests**: `tests/Feature/Filament/TariffResourceTest.php`

### Running Tests

```bash
# Run all tariff tests
php artisan test --filter=Tariff

# Run validation tests
php artisan test --filter=FilamentTariffValidationConsistencyPropertyTest

# Run security tests
php artisan test --filter=TariffResourceSecurityTest

# Run navigation tests
php artisan test --filter=FilamentNavigationVisibilityTest
```

## Related Documentation

- [Tariff Model Documentation](../models/TARIFF_MODEL.md)
- [Tariff Policy Documentation](../policies/TARIFF_POLICY.md)
- [Tariff Observer Documentation](../observers/TARIFF_OBSERVER.md)
- [Role-Based Navigation Visibility](role-based-navigation-visibility.md)
- [Tariff Resource Navigation Update](TARIFF_RESOURCE_NAVIGATION_UPDATE.md)
- [Tariff Security Implementation](../security/TARIFF_SECURITY_IMPLEMENTATION.md)
- [Tariff Validation Localization](tariff-resource-validation.md)

## Changelog

### 2024-11-27 - Navigation Visibility Update
- Updated `shouldRegisterNavigation()` to include SUPERADMIN role
- Added comprehensive PHPDoc documentation
- Enhanced code-level documentation for all authorization methods
- Improved consistency with ProviderResource pattern

### 2024-11-26 - Security Hardening
- Implemented XSS prevention with regex validation
- Added numeric overflow protection
- Implemented zone ID injection prevention
- Added comprehensive audit logging via TariffObserver

### 2024-11-25 - Validation Enhancement
- Added explicit validation rules to all form fields
- Implemented localized validation messages
- Added conditional validation for tariff types
- Enhanced zone configuration validation

## Support

For issues or questions regarding TariffResource:

1. Check the [Filament v4 Documentation](https://filamentphp.com/docs/4.x)
2. Review the [Laravel 12 Documentation](https://laravel.com/docs/12.x)
3. Consult the [Project README](../../README.md)
4. Review related test files for usage examples
