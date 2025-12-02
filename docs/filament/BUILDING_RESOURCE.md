# BuildingResource Documentation

## Overview

The `BuildingResource` is a Filament 4.x admin resource that manages building entities in the multi-tenant utilities billing platform. It provides role-based CRUD operations with automatic tenant scoping, localized validation, and a comprehensive properties relation manager.

## Architecture

### Component Structure

```
app/Filament/Resources/
├── BuildingResource.php                    # Main resource definition
└── BuildingResource/
    ├── Pages/
    │   ├── ListBuildings.php              # Index page with create action
    │   ├── CreateBuilding.php             # Create page with auto tenant_id
    │   └── EditBuilding.php               # Edit page with delete action
    └── RelationManagers/
        └── PropertiesRelationManager.php  # Manages building→properties relationship
```

### Dependencies

- **Model**: `App\Models\Building`
- **Policy**: `App\Policies\BuildingPolicy`
- **Trait**: `App\Filament\Concerns\HasTranslatedValidation`
- **Enums**: `App\Enums\UserRole`
- **Scopes**: `App\Scopes\TenantScope` (via `BelongsToTenant` trait)

## Features

### 1. Role-Based Authorization

The resource delegates all authorization to `BuildingPolicy`, which implements the following rules:

| Role | View Any | View | Create | Update | Delete |
|------|----------|------|--------|--------|--------|
| **Superadmin** | ✅ All | ✅ All | ✅ | ✅ All | ✅ All |
| **Admin** | ✅ All | ✅ All | ✅ | ✅ All | ✅ All |
| **Manager** | ✅ Tenant-scoped | ✅ Tenant-scoped | ✅ | ✅ Tenant-scoped | ❌ |
| **Tenant** | ❌ | ✅ Property's building only | ❌ | ❌ | ❌ |

**Navigation Visibility**: Hidden from tenant users via `shouldRegisterNavigation()`.

### 2. Form Schema

The resource provides a clean, validated form with three fields:

#### Name Field
```php
Forms\Components\TextInput::make('name')
    ->label(__('buildings.labels.name'))
    ->required()
    ->maxLength(255)
```

- **Validation**: Required, max 255 characters
- **Purpose**: Human-friendly building identifier
- **Fallback**: If empty, `display_name` attribute falls back to `address`

#### Address Field
```php
Forms\Components\TextInput::make('address')
    ->label(__('buildings.labels.address'))
    ->required()
    ->maxLength(255)
    ->columnSpanFull()
```

- **Validation**: Required, max 255 characters
- **Layout**: Spans full form width for long addresses
- **Localization**: Uses `buildings.labels.address` translation key

#### Total Apartments Field
```php
Forms\Components\TextInput::make('total_apartments')
    ->label(__('buildings.labels.total_apartments'))
    ->required()
    ->numeric()
    ->minValue(1)
    ->maxValue(1000)
    ->integer()
```

- **Validation**: Required, numeric, integer, 1-1000 range
- **Purpose**: Tracks building capacity for gyvatukas calculations
- **Business Rule**: Used by `GyvatukasCalculator` for circulation fee distribution

### 3. Table Configuration

#### Columns

1. **Name** - Searchable, sortable
2. **Address** - Searchable, sortable (default sort column, ascending)
3. **Total Apartments** - Numeric display, sortable
4. **Properties Count** - Counts related properties via `counts('properties')`
5. **Created At** - DateTime, sortable, hidden by default (toggleable)

#### Actions

- **Bulk Delete**: Available in toolbar via `BulkActionGroup`
- **Row Actions**: Removed in favor of page header actions (view/edit/delete on detail pages)

#### Default Sort

```php
->defaultSort('address', 'asc')
```

Buildings are sorted alphabetically by address for easy navigation.

### 4. Tenant Scoping

#### Automatic Assignment

The `CreateBuilding` page automatically assigns `tenant_id` from the authenticated user:

```php
protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['tenant_id'] = auth()->user()->tenant_id;
    return $data;
}
```

#### Query Filtering

The `Building` model uses the `BelongsToTenant` trait, which applies a global `TenantScope`:

- **Superadmin**: Sees all buildings across all tenants
- **Admin**: Sees all buildings (policy allows cross-tenant access)
- **Manager**: Sees only buildings where `tenant_id` matches their own
- **Tenant**: Cannot access building list (navigation hidden)

### 5. Localization

All UI strings are externalized via Laravel's translation system:

#### Translation Keys

```php
// Navigation
__('app.nav.buildings')              // "Buildings"
__('app.nav_groups.operations')      // "Operations"

// Form Labels
__('buildings.labels.name')          // "Name"
__('buildings.labels.address')       // "Address"
__('buildings.labels.total_apartments') // "Total Apartments"
__('buildings.labels.property_count')   // "Properties"
__('buildings.labels.created_at')       // "Created"

// Validation Messages
__('buildings.validation.name.required')
__('buildings.validation.address.max')
// ... etc
```

#### Validation Integration

The resource uses the `HasTranslatedValidation` trait to pull validation messages from translation files:

```php
->validationMessages(self::getValidationMessages('name'))
```

This ensures consistency between Filament forms and FormRequest validation.

### 6. Properties Relation Manager

The resource includes a comprehensive `PropertiesRelationManager` that provides:

- **CRUD Operations**: Create, view, edit, delete properties within a building
- **Tenant Management**: Assign/reassign tenants to properties
- **Dynamic Defaults**: Auto-populates area based on property type (apartment: 50m², house: 120m²)
- **Performance Optimization**: Eager loads relationships to prevent N+1 queries
- **Validation**: Integrates with `StorePropertyRequest` and `UpdatePropertyRequest`

See [PropertiesRelationManager Documentation](./PROPERTIES_RELATION_MANAGER.md) for details.

## Usage Examples

### Creating a Building (Manager Role)

```php
// User navigates to /admin/buildings/create
// Form displays with three fields: name, address, total_apartments
// On submit:
// 1. Validation runs (required, max lengths, numeric ranges)
// 2. tenant_id is automatically injected from auth()->user()->tenant_id
// 3. BuildingPolicy::create() checks authorization
// 4. Record is saved with tenant scope
// 5. User redirected to edit page with properties relation manager
```

### Viewing Buildings (Admin Role)

```php
// User navigates to /admin/buildings
// Table displays all buildings across all tenants (admin privilege)
// Columns: name, address, total_apartments, properties_count, created_at
// Default sort: address ascending
// Bulk actions: delete selected buildings
```

### Managing Properties (Manager Role)

```php
// User navigates to /admin/buildings/{id}/edit
// Properties relation manager displays in a tab
// Manager can:
// - Create new properties (auto-assigned to this building)
// - Edit existing properties (tenant-scoped)
// - Assign/reassign tenants to properties
// - View meters and tenant information
// - Delete properties (if authorized)
```

## API Reference

### Resource Methods

#### `canViewAny(): bool`

Determines if the authenticated user can view the building list.

**Authorization**: Delegates to `BuildingPolicy::viewAny()`

**Returns**: `true` for superadmin/admin/manager, `false` for tenant/guest

#### `canCreate(): bool`

Determines if the authenticated user can create buildings.

**Authorization**: Delegates to `BuildingPolicy::create()`

**Returns**: `true` for superadmin/admin/manager, `false` for tenant/guest

#### `canEdit($record): bool`

Determines if the authenticated user can edit a specific building.

**Parameters**:
- `$record` (Building): The building to check

**Authorization**: Delegates to `BuildingPolicy::update()`

**Returns**: 
- `true` for superadmin/admin (all buildings)
- `true` for manager (tenant-scoped buildings)
- `false` for tenant/guest

#### `canDelete($record): bool`

Determines if the authenticated user can delete a specific building.

**Parameters**:
- `$record` (Building): The building to check

**Authorization**: Delegates to `BuildingPolicy::delete()`

**Returns**: 
- `true` for superadmin/admin (all buildings)
- `false` for manager/tenant/guest

#### `shouldRegisterNavigation(): bool`

Determines if the building navigation item should be visible.

**Business Rule**: Hidden from tenant users (Requirements 9.1, 9.2, 9.3)

**Returns**: `true` for superadmin/admin/manager, `false` for tenant/guest

### Form Field Builders

#### `buildNameField(): Forms\Components\TextInput`

Creates the name input field with validation.

**Validation Rules**:
- Required
- Max length: 255 characters
- Localized error messages

**Returns**: Configured TextInput component

#### `buildAddressField(): Forms\Components\TextInput`

Creates the address input field with validation.

**Validation Rules**:
- Required
- Max length: 255 characters
- Full-width column span

**Returns**: Configured TextInput component

#### `buildTotalApartmentsField(): Forms\Components\TextInput`

Creates the total apartments input field with validation.

**Validation Rules**:
- Required
- Numeric
- Integer
- Min value: 1
- Max value: 1000

**Returns**: Configured TextInput component

### Table Configuration

#### `getTableColumns(): array<Tables\Columns\Column>`

Returns the table column configuration.

**Columns**:
1. Name (searchable, sortable)
2. Address (searchable, sortable)
3. Total Apartments (numeric, sortable)
4. Properties Count (counts relationship, sortable)
5. Created At (datetime, sortable, hidden by default)

**Returns**: Array of configured column components

## Data Flow

### Create Flow

```
User submits form
    ↓
Filament validates fields (required, max, numeric)
    ↓
CreateBuilding::mutateFormDataBeforeCreate()
    ├─ Injects tenant_id from auth()->user()
    └─ Returns mutated data
    ↓
BuildingPolicy::create() checks authorization
    ├─ Superadmin: ✅ Allow
    ├─ Admin: ✅ Allow
    ├─ Manager: ✅ Allow
    └─ Tenant: ❌ Deny
    ↓
Building model saves with tenant_id
    ↓
TenantScope applies (global scope from BelongsToTenant)
    ↓
User redirected to edit page
    ↓
PropertiesRelationManager loads
```

### Update Flow

```
User navigates to edit page
    ↓
BuildingPolicy::view() checks authorization
    ├─ Superadmin: ✅ All buildings
    ├─ Admin: ✅ All buildings
    ├─ Manager: ✅ Tenant-scoped buildings
    └─ Tenant: ❌ Deny (navigation hidden)
    ↓
Form loads with existing data
    ↓
User modifies fields
    ↓
Filament validates changes
    ↓
BuildingPolicy::update() checks authorization
    ↓
Building model updates (tenant_id unchanged)
    ↓
Success notification displayed
```

### Delete Flow

```
User clicks delete action
    ↓
Confirmation modal displays
    ↓
User confirms deletion
    ↓
BuildingPolicy::delete() checks authorization
    ├─ Superadmin: ✅ All buildings
    ├─ Admin: ✅ All buildings
    └─ Manager/Tenant: ❌ Deny
    ↓
Building model soft deletes (if configured)
    ↓
Related properties cascade (check foreign key constraints)
    ↓
User redirected to list page
    ↓
Success notification displayed
```

## Testing

### Test Coverage

The `BuildingResourceTest` provides comprehensive coverage:

```php
tests/Feature/Filament/BuildingResourceTest.php
```

**Test Suites**:

1. **Navigation** (5 tests)
   - Superadmin can see navigation
   - Admin can see navigation
   - Manager can see navigation
   - Tenant cannot see navigation
   - Guest cannot see navigation

2. **Authorization - View Any** (5 tests)
   - Role-based access to building list

3. **Authorization - Create** (5 tests)
   - Role-based ability to create buildings

4. **Authorization - Edit** (6 tests)
   - Role-based editing with tenant scope checks
   - Manager cannot edit other tenant's buildings

5. **Authorization - Delete** (5 tests)
   - Only superadmin/admin can delete
   - Manager/tenant cannot delete

6. **Configuration** (5 tests)
   - Model class verification
   - Navigation icon verification
   - Navigation sort order verification
   - Navigation label localization
   - Navigation group localization

7. **Form Schema** (3 tests)
   - Name field presence and validation
   - Address field presence and validation
   - Total apartments field presence and validation

8. **Table Configuration** (4 tests)
   - Default sort verification
   - Column presence and configuration
   - Properties count column

9. **Relations** (1 test)
   - PropertiesRelationManager registration

10. **Pages** (3 tests)
    - List page registration
    - Create page registration
    - Edit page registration

**Total**: 37 tests with 100% coverage of authorization, configuration, and UI components

### Running Tests

```bash
# Run all building resource tests
php artisan test --filter=BuildingResourceTest

# Run specific test suite
php artisan test --filter="BuildingResource Navigation"

# Run with coverage
php artisan test --filter=BuildingResourceTest --coverage
```

## Configuration

### Navigation

```php
protected static ?int $navigationSort = 4;
```

Buildings appear 4th in the navigation menu within the "Operations" group.

### Icon

```php
public static function getNavigationIcon(): string
{
    return 'heroicon-o-building-office-2';
}
```

Uses Heroicon's building office icon for visual consistency.

### Translation Prefix

```php
protected static string $translationPrefix = 'buildings.validation';
```

Validation messages are loaded from `lang/{locale}/buildings.php` under the `validation` key.

## Related Documentation

- [Building Model](../../app/Models/Building.php) - Eloquent model with gyvatukas calculations
- [BuildingPolicy](../../app/Policies/BuildingPolicy.php) - Authorization rules
- [PropertiesRelationManager](./PROPERTIES_RELATION_MANAGER.md) - Managing building properties
- [HasTranslatedValidation Trait](../../app/Filament/Concerns/HasTranslatedValidation.php) - Validation message loading
- [Multi-Tenant Architecture](../architecture/MULTI_TENANT_ARCHITECTURE.md) - Tenant scoping patterns
- [Filament V4 Compatibility Guide](FILAMENT_V4_COMPATIBILITY_GUIDE.md) - Framework upgrade notes

## Changelog

### Laravel 12 / Filament 4 Upgrade

- ✅ Updated to Filament 4.x API (Schema, Table, Actions)
- ✅ Replaced `->reactive()` with `->live()` for form fields
- ✅ Updated action syntax (BulkActionGroup, DeleteBulkAction)
- ✅ Migrated to new column/field builder syntax
- ✅ Updated navigation registration methods
- ✅ Comprehensive test suite with 37 tests passing
- ✅ Full authorization coverage via BuildingPolicy
- ✅ Localized validation messages via HasTranslatedValidation trait

### Future Enhancements

- [ ] Add bulk export action for buildings
- [ ] Implement building archival (soft delete UI)
- [ ] Add gyvatukas calculation dashboard widget
- [ ] Implement building comparison reports
- [ ] Add building photo upload capability
- [ ] Implement building document management

## Support

For issues or questions:

1. Check the [Filament V4 Compatibility Guide](FILAMENT_V4_COMPATIBILITY_GUIDE.md)
2. Review [Multi-Tenant Architecture](../architecture/MULTI_TENANT_ARCHITECTURE.md)
3. Run tests: `php artisan test --filter=BuildingResourceTest`
4. Check logs: `php artisan pail` or `storage/logs/laravel.log`
5. Verify policies: `php artisan tinker` → `Gate::inspect('view', $building)`
