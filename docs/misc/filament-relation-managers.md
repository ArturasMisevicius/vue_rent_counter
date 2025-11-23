# Filament Relation Managers API Reference

## PropertiesRelationManager

**Namespace**: `App\Filament\Resources\BuildingResource\RelationManagers`  
**Extends**: `Filament\Resources\RelationManagers\RelationManager`  
**Final**: Yes (cannot be extended)

### Class Properties

```php
protected static string $relationship = 'properties';
protected static ?string $recordTitleAttribute = 'address';
protected static ?string $title = 'Properties';
protected static ?string $icon = 'heroicon-o-home';
```

### Public Methods

#### `form(Form $form): Form`

Configures the form schema for creating and editing properties.

**Parameters**:
- `$form` (Form): The Filament form instance

**Returns**: Form - Configured form with validation and live updates

**Features**:
- Two-section layout (Property Details, Additional Info)
- Validation from StorePropertyRequest/UpdatePropertyRequest
- Live updates for default area based on type
- Localized labels and helper text

**Example**:
```php
// Called automatically by Filament
$manager = new PropertiesRelationManager;
$form = $manager->form(Form::make());
```

---

#### `table(Table $table): Table`

Configures the table schema for displaying properties.

**Parameters**:
- `$table` (Table): The Filament table instance

**Returns**: Table - Configured table with columns, filters, and actions

**Features**:
- 6 columns (address, type, area, tenant, meters, created_at)
- 3 filters (type, occupancy, large properties)
- Row actions (view, edit, manage tenant, delete)
- Bulk actions (delete, export)
- Eager loading (tenants, meters)

**Example**:
```php
// Called automatically by Filament
$manager = new PropertiesRelationManager;
$table = $manager->table(Table::make());
```

---

#### `static canViewForRecord(Model $ownerRecord, string $pageClass): bool`

Determines if the relation manager tab should be visible.

**Parameters**:
- `$ownerRecord` (Model): The parent building record
- `$pageClass` (string): The Filament page class

**Returns**: bool - True if user can view properties

**Authorization**: Delegates to `PropertyPolicy::viewAny()`

**Example**:
```php
$canView = PropertiesRelationManager::canViewForRecord($building, ViewBuilding::class);
// Returns: true if user has viewAny permission
```

---

### Protected Methods

#### `getAddressField(): Forms\Components\TextInput`

Creates the address input field with validation.

**Returns**: TextInput - Configured address field

**Validation**:
- Required
- Max length: 255
- Messages from StorePropertyRequest

**Example**:
```php
$field = $this->getAddressField();
// Returns: TextInput with localized label and validation
```

---

#### `getTypeField(): Forms\Components\Select`

Creates the property type select field with live updates.

**Returns**: Select - Configured type field

**Features**:
- Options from PropertyType enum
- Live updates trigger default area
- Enum validation rule

**Example**:
```php
$field = $this->getTypeField();
// Returns: Select with APARTMENT and HOUSE options
```

---

#### `getAreaField(): Forms\Components\TextInput`

Creates the area numeric input field.

**Returns**: TextInput - Configured area field

**Validation**:
- Required
- Numeric
- Min: 0 (from config)
- Max: 10000 (from config)
- Step: 0.01

**Example**:
```php
$field = $this->getAreaField();
// Returns: Numeric input with m² suffix
```

---

#### `setDefaultArea(string $state, Forms\Set $set): void`

Sets default area based on property type selection.

**Parameters**:
- `$state` (string): The selected property type value
- `$set` (Forms\Set): Filament form state setter

**Returns**: void

**Behavior**:
- Apartment → 50 m² (from config)
- House → 120 m² (from config)

**Example**:
```php
// Called automatically via afterStateUpdated
$this->setDefaultArea(PropertyType::APARTMENT->value, $set);
// Sets area_sqm to 50
```

---

#### `preparePropertyData(array $data): array`

Prepares property data before saving.

**Parameters**:
- `$data` (array): Form data from user input

**Returns**: array - Data with tenant_id and building_id injected

**Injected Values**:
- `tenant_id`: From authenticated user
- `building_id`: From parent building record

**Example**:
```php
$data = [
    'address' => 'Apartment 12',
    'type' => 'apartment',
    'area_sqm' => 50,
];

$prepared = $this->preparePropertyData($data);
// Returns: [
//     'address' => 'Apartment 12',
//     'type' => 'apartment',
//     'area_sqm' => 50,
//     'tenant_id' => 1,
//     'building_id' => 5,
// ]
```

---

#### `getTenantManagementForm(Property $record): array`

Creates the tenant management form schema.

**Parameters**:
- `$record` (Property): The property being managed

**Returns**: array - Form components for tenant management modal

**Behavior**:
- If vacant: "Assign Tenant" (required)
- If occupied: "Reassign Tenant" (nullable)
- Only shows available tenants (no current property)

**Example**:
```php
$form = $this->getTenantManagementForm($property);
// Returns: [Select component with tenant options]
```

---

#### `handleTenantManagement(Property $record, array $data): void`

Processes tenant assignment/removal.

**Parameters**:
- `$record` (Property): The property being managed
- `$data` (array): Form data with tenant_id

**Returns**: void

**Authorization**: Checks `PropertyPolicy::update()`

**Behavior**:
- Empty tenant_id → Detach all tenants
- Valid tenant_id → Sync to new tenant
- Sends success/error notifications

**Example**:
```php
// Assign tenant
$this->handleTenantManagement($property, ['tenant_id' => 5]);
// Result: Property assigned to tenant 5

// Remove tenant
$this->handleTenantManagement($property, ['tenant_id' => null]);
// Result: Property marked as vacant
```

---

#### `handleExport(): void`

Handles bulk export action (stub).

**Returns**: void

**Current Behavior**: Sends info notification

**Future**: Integrate with Laravel Excel

**Example**:
```php
$this->handleExport();
// Sends: "Export started" notification
```

---

#### `applyTenantScoping(Builder $query): Builder`

Applies tenant scope to relation query.

**Parameters**:
- `$query` (Builder): The Eloquent query builder

**Returns**: Builder - Unmodified query (scoping via building)

**Note**: No additional scoping needed as properties inherit scope through building relationship.

**Example**:
```php
$query = Property::query();
$scoped = $this->applyTenantScoping($query);
// Returns: Same query (building already scoped)
```

---

## Validation Rules

### Address Field

```php
[
    'required' => true,
    'string' => true,
    'max' => 255,
]
```

**Messages**:
- `required`: "The property address is required."
- `max`: "The property address may not be greater than 255 characters."

---

### Type Field

```php
[
    'required' => true,
    'enum' => PropertyType::class,
]
```

**Messages**:
- `required`: "The property type is required."
- `enum`: "The property type must be either apartment or house."

---

### Area Field

```php
[
    'required' => true,
    'numeric' => true,
    'min' => 0,
    'max' => 10000,
]
```

**Messages**:
- `required`: "The property area is required."
- `numeric`: "The property area must be a number."
- `min`: "The property area must be at least 0 square meters."
- `max`: "The property area cannot exceed 10,000 square meters."

---

## Events & Hooks

### Form Events

```php
// Type field change
->afterStateUpdated(fn (string $state, Forms\Set $set) => 
    $this->setDefaultArea($state, $set)
)
```

### Table Events

```php
// Create action
->mutateFormDataUsing(fn (array $data) => 
    $this->preparePropertyData($data)
)

// Edit action
->mutateFormDataUsing(fn (array $data) => 
    $this->preparePropertyData($data)
)
```

---

## Error Handling

### Authorization Errors

```php
// In handleTenantManagement()
if (! auth()->user()->can('update', $record)) {
    Notification::make()
        ->danger()
        ->title(__('Error'))
        ->body(__('You are not authorized...'))
        ->send();
    return;
}
```

### Validation Errors

Handled automatically by Filament using FormRequest validation rules.

---

## Performance Considerations

### Query Optimization

```php
// Eager loading configured
->modifyQueryUsing(fn (Builder $query) => 
    $query->with(['tenants', 'meters'])
)

// Counts instead of collections
->counts('meters')
```

**Impact**:
- Before: 1 + N + N queries
- After: 3 queries total
- Improvement: ~90% for 10+ properties

---

## Security

### Authorization Checks

1. **Tab visibility**: `canViewForRecord()` → `PropertyPolicy::viewAny()`
2. **CRUD operations**: Automatic via Filament + Policy
3. **Tenant management**: Explicit check in `handleTenantManagement()`

### Tenant Scope

Properties automatically scoped through building relationship:
```
User (tenant_id: 1)
  → Building (tenant_id: 1)
    → Properties (building_id: X)
```

### Mass Assignment Protection

```php
// In Property model
protected $fillable = [
    'tenant_id',
    'address',
    'type',
    'area_sqm',
    'building_id',
];
```

---

## Configuration Dependencies

### Required Config

```php
// config/billing.php
'property' => [
    'default_apartment_area' => 50,
    'default_house_area' => 120,
    'min_area' => 0,
    'max_area' => 10000,
],
```

### Required Translations

```php
// lang/en/properties.php
'labels' => [...],
'validation' => [...],
'notifications' => [...],
'actions' => [...],
```

---

## Related Classes

- `App\Models\Property` - Property model
- `App\Models\Building` - Building model
- `App\Models\Tenant` - Tenant model
- `App\Http\Requests\StorePropertyRequest` - Create validation
- `App\Http\Requests\UpdatePropertyRequest` - Update validation
- `App\Policies\PropertyPolicy` - Authorization
- `App\Enums\PropertyType` - Type enum

---

## Migration Requirements

### Database Schema

```sql
CREATE TABLE properties (
    id BIGINT UNSIGNED PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    building_id BIGINT UNSIGNED NOT NULL,
    address VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    area_sqm DECIMAL(8,2) NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id),
    FOREIGN KEY (building_id) REFERENCES buildings(id)
);

CREATE TABLE property_tenant (
    id BIGINT UNSIGNED PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    tenant_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY (property_id, tenant_id),
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
```

---

## Testing

### Test File

`tests/Feature/Filament/PropertiesRelationManagerTest.php`

### Coverage

- ✅ Localization
- ✅ Model relationships
- ✅ Authorization
- ✅ Validation integration
- ✅ Eager loading
- ✅ Default area setting
- ✅ Tenant management
- ✅ Data preparation
- ✅ Security

### Run Tests

```bash
php artisan test --filter=PropertiesRelationManager
```
