# BuildingResource API Reference

## Overview

Complete API reference for the `BuildingResource` Filament resource, including all public methods, authorization rules, form fields, table columns, and configuration options.

## Class Definition

```php
namespace App\Filament\Resources;

class BuildingResource extends Resource
```

**Traits**: `HasTranslatedValidation`

**Model**: `App\Models\Building`

**Policy**: `App\Policies\BuildingPolicy`

## Static Properties

### `$model`

```php
protected static ?string $model = Building::class;
```

The Eloquent model class managed by this resource.

### `$translationPrefix`

```php
protected static string $translationPrefix = 'buildings.validation';
```

Prefix for validation message translation keys. Used by `HasTranslatedValidation` trait.

### `$navigationLabel`

```php
protected static ?string $navigationLabel = null;
```

Navigation label (dynamically loaded via `getNavigationLabel()`).

### `$navigationSort`

```php
protected static ?int $navigationSort = 4;
```

Sort order in navigation menu (4th position in Operations group).

## Public Static Methods

### `getNavigationIcon()`

Returns the navigation icon for the resource.

**Signature**:
```php
public static function getNavigationIcon(): string|BackedEnum|null
```

**Returns**: `'heroicon-o-building-office-2'`

**Usage**:
```php
$icon = BuildingResource::getNavigationIcon();
// Returns: 'heroicon-o-building-office-2'
```

---

### `getNavigationLabel()`

Returns the localized navigation label.

**Signature**:
```php
public static function getNavigationLabel(): string
```

**Returns**: Translated string from `app.nav.buildings`

**Usage**:
```php
$label = BuildingResource::getNavigationLabel();
// Returns: "Buildings" (or localized equivalent)
```

---

### `getNavigationGroup()`

Returns the localized navigation group name.

**Signature**:
```php
public static function getNavigationGroup(): string|UnitEnum|null
```

**Returns**: Translated string from `app.nav_groups.operations`

**Usage**:
```php
$group = BuildingResource::getNavigationGroup();
// Returns: "Operations" (or localized equivalent)
```

---

### `canViewAny()`

Determines if the authenticated user can view the building list.

**Signature**:
```php
public static function canViewAny(): bool
```

**Authorization**: Delegates to `BuildingPolicy::viewAny()`

**Returns**:
- `true` - Superadmin, Admin, Manager
- `false` - Tenant, Guest

**Usage**:
```php
if (BuildingResource::canViewAny()) {
    // User can access building list
}
```

**Policy Logic**:
```php
// BuildingPolicy::viewAny()
if ($user->role === UserRole::SUPERADMIN) return true;
return $user->role === UserRole::ADMIN || $user->role === UserRole::MANAGER;
```

---

### `canCreate()`

Determines if the authenticated user can create buildings.

**Signature**:
```php
public static function canCreate(): bool
```

**Authorization**: Delegates to `BuildingPolicy::create()`

**Returns**:
- `true` - Superadmin, Admin, Manager
- `false` - Tenant, Guest

**Usage**:
```php
if (BuildingResource::canCreate()) {
    // Show create button
}
```

**Note**: `tenant_id` is automatically assigned in `CreateBuilding::mutateFormDataBeforeCreate()`.

---

### `canEdit($record)`

Determines if the authenticated user can edit a specific building.

**Signature**:
```php
public static function canEdit($record): bool
```

**Parameters**:
- `$record` (Building): The building to check

**Authorization**: Delegates to `BuildingPolicy::update()`

**Returns**:
- `true` - Superadmin (all), Admin (all), Manager (tenant-scoped)
- `false` - Tenant, Guest, Manager (other tenant's buildings)

**Usage**:
```php
$building = Building::find(1);
if (BuildingResource::canEdit($building)) {
    // Show edit button
}
```

**Policy Logic**:
```php
// BuildingPolicy::update()
if ($user->role === UserRole::SUPERADMIN) return true;
if ($user->role === UserRole::ADMIN) return true;
if ($user->role === UserRole::MANAGER) {
    return $building->tenant_id === $user->tenant_id;
}
return false;
```

---

### `canDelete($record)`

Determines if the authenticated user can delete a specific building.

**Signature**:
```php
public static function canDelete($record): bool
```

**Parameters**:
- `$record` (Building): The building to check

**Authorization**: Delegates to `BuildingPolicy::delete()`

**Returns**:
- `true` - Superadmin, Admin
- `false` - Manager, Tenant, Guest

**Usage**:
```php
$building = Building::find(1);
if (BuildingResource::canDelete($building)) {
    // Show delete button
}
```

**Policy Logic**:
```php
// BuildingPolicy::delete()
if ($user->role === UserRole::SUPERADMIN) return true;
if ($user->role === UserRole::ADMIN) return true;
return false; // Managers cannot delete
```

---

### `shouldRegisterNavigation()`

Determines if the navigation item should be visible.

**Signature**:
```php
public static function shouldRegisterNavigation(): bool
```

**Returns**:
- `true` - Superadmin, Admin, Manager
- `false` - Tenant, Guest

**Usage**:
```php
if (BuildingResource::shouldRegisterNavigation()) {
    // Show in navigation menu
}
```

**Business Rule**: Hides navigation from tenant users (Requirements 9.1, 9.2, 9.3).

---

### `form(Schema $schema)`

Configures the form schema for creating and editing buildings.

**Signature**:
```php
public static function form(Schema $schema): Schema
```

**Parameters**:
- `$schema` (Schema): Filament form instance

**Returns**: Configured Schema with three fields

**Form Fields**:
1. **Name** - `buildNameField()`
2. **Address** - `buildAddressField()`
3. **Total Apartments** - `buildTotalApartmentsField()`

**Usage**:
```php
$schema = BuildingResource::form(Schema::make());
// Returns schema with name, address, total_apartments fields
```

---

### `table(Table $table)`

Configures the table schema for displaying buildings.

**Signature**:
```php
public static function table(Table $table): Table
```

**Parameters**:
- `$table` (Table): Filament table instance

**Returns**: Configured Table with columns, filters, and actions

**Table Configuration**:
- **Columns**: name, address, total_apartments, properties_count, created_at
- **Filters**: None (can be added as needed)
- **Actions**: Bulk delete
- **Default Sort**: address ascending

**Usage**:
```php
$table = BuildingResource::table(Table::make(BuildingResource::class));
// Returns configured table
```

---

### `getRelations()`

Returns the relation managers for this resource.

**Signature**:
```php
public static function getRelations(): array
```

**Returns**: Array containing `PropertiesRelationManager::class`

**Usage**:
```php
$relations = BuildingResource::getRelations();
// Returns: [PropertiesRelationManager::class]
```

---

### `getPages()`

Returns the page configuration for this resource.

**Signature**:
```php
public static function getPages(): array
```

**Returns**: Array of page routes

**Pages**:
- `index` - `ListBuildings::route('/')`
- `create` - `CreateBuilding::route('/create')`
- `edit` - `EditBuilding::route('/{record}/edit')`

**Usage**:
```php
$pages = BuildingResource::getPages();
// Returns: ['index' => ..., 'create' => ..., 'edit' => ...]
```

## Private Static Methods

### `buildNameField()`

Creates the name input field with validation.

**Signature**:
```php
private static function buildNameField(): Forms\Components\TextInput
```

**Validation**:
- Required
- Max length: 255 characters
- Localized error messages

**Returns**: Configured TextInput component

---

### `buildAddressField()`

Creates the address input field with validation.

**Signature**:
```php
private static function buildAddressField(): Forms\Components\TextInput
```

**Validation**:
- Required
- Max length: 255 characters
- Full-width column span

**Returns**: Configured TextInput component

---

### `buildTotalApartmentsField()`

Creates the total apartments input field with validation.

**Signature**:
```php
private static function buildTotalApartmentsField(): Forms\Components\TextInput
```

**Validation**:
- Required
- Numeric (integer)
- Min value: 1
- Max value: 1000

**Returns**: Configured TextInput component

---

### `getAuthenticatedUser()`

Helper method to safely retrieve the authenticated user.

**Signature**:
```php
private static function getAuthenticatedUser(): ?User
```

**Returns**: User instance or null

**Usage**:
```php
$user = self::getAuthenticatedUser();
if ($user) {
    // User is authenticated
}
```

---

### `getTableColumns()`

Returns the table column configuration.

**Signature**:
```php
private static function getTableColumns(): array<Tables\Columns\Column>
```

**Returns**: Array of configured table columns

**Columns**:
1. Name (searchable, sortable)
2. Address (searchable, sortable)
3. Total Apartments (numeric, sortable)
4. Properties Count (relationship count, sortable)
5. Created At (datetime, sortable, hidden by default)

## Translation Keys

### Navigation

```php
'app.nav.buildings'              // "Buildings"
'app.nav_groups.operations'      // "Operations"
```

### Form Labels

```php
'buildings.labels.name'          // "Building Name"
'buildings.labels.address'       // "Address"
'buildings.labels.total_apartments' // "Total Apartments"
```

### Table Labels

```php
'buildings.labels.property_count'   // "Properties"
'buildings.labels.created_at'       // "Created"
```

### Validation Messages

```php
'buildings.validation.name.required'
'buildings.validation.name.max'
'buildings.validation.address.required'
'buildings.validation.address.max'
'buildings.validation.total_apartments.required'
'buildings.validation.total_apartments.numeric'
'buildings.validation.total_apartments.min'
'buildings.validation.total_apartments.max'
'buildings.validation.total_apartments.integer'
```

## Authorization Matrix

| Method | Superadmin | Admin | Manager | Tenant | Guest |
|--------|------------|-------|---------|--------|-------|
| `canViewAny()` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `canCreate()` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `canEdit()` | ✅ All | ✅ All | ✅ Scoped | ❌ | ❌ |
| `canDelete()` | ✅ All | ✅ All | ❌ | ❌ | ❌ |
| `shouldRegisterNavigation()` | ✅ | ✅ | ✅ | ❌ | ❌ |

**Scoped**: Manager can only access buildings where `tenant_id` matches their own.

## Related Documentation

- [BuildingResource Guide](BUILDING_RESOURCE.md)
- [Building Model](../../app/Models/Building.php)
- [BuildingPolicy](../../app/Policies/BuildingPolicy.php)
- [PropertiesRelationManager](./PROPERTIES_RELATION_MANAGER.md)
- [HasTranslatedValidation Trait](../../app/Filament/Concerns/HasTranslatedValidation.php)
