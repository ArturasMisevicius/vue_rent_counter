# PropertiesRelationManager Documentation

## Overview

The `PropertiesRelationManager` manages properties associated with buildings in the Filament admin panel. It provides a complete CRUD interface with tenant management, validation integration, and automatic scope isolation.

**Location**: `app/Filament/Resources/BuildingResource/RelationManagers/PropertiesRelationManager.php`

## Key Features

- **Validation Integration**: Pulls rules from `StorePropertyRequest` and `UpdatePropertyRequest`
- **Automatic Defaults**: Sets area based on property type (apartment: 50 m², house: 120 m²)
- **Tenant Management**: Assign/reassign/remove tenants with authorization checks
- **Scope Isolation**: Properties automatically scoped to current tenant via building
- **Performance**: Eager loads relationships to prevent N+1 queries
- **Localization**: All UI strings use translation keys from `lang/en/properties.php`

## Form Configuration

### Property Details Section

```php
// Address Field
- Label: Localized via __('properties.labels.address')
- Validation: required, max:255
- Full width field
- Helper text for guidance

// Type Field (Select)
- Options: PropertyType::APARTMENT | PropertyType::HOUSE
- Validation: required, enum
- Live updates: triggers default area on change
- Non-native select for better UX

// Area Field (Numeric)
- Label: Localized with m² suffix
- Validation: required, numeric, min:0, max:10000
- Step: 0.01 (supports decimals)
- Auto-populated when type changes
```

### Additional Info Section (Collapsed)

```php
// Read-only placeholders showing:
- Building name
- Current tenant (or "Vacant")
- Installed meters count
```

## Table Configuration

### Columns

| Column | Features | Notes |
|--------|----------|-------|
| Address | Searchable, sortable, copyable | Shows type as description |
| Type | Badge, color-coded | Blue (apartment), Green (house) |
| Area | Numeric, sortable, right-aligned | Displays with m² suffix |
| Current Tenant | Badge, searchable | Shows "Vacant" if empty |
| Meters Count | Badge, toggleable | Uses counts() for performance |
| Created At | DateTime, hidden by default | Toggleable |

### Filters

```php
// Type Filter
SelectFilter::make('type')
    ->options(PropertyType::class)

// Occupancy Filter (Ternary)
- All Properties (default)
- Occupied (has tenants)
- Vacant (no tenants)

// Large Properties Filter (Toggle)
- Shows properties > 100 m²
```

### Actions

#### Row Actions (Action Group)

1. **View**: Read-only view of property
2. **Edit**: Update property details
3. **Manage Tenant**: Assign/reassign/remove tenant
4. **Delete**: Soft delete with confirmation

#### Bulk Actions

1. **Delete**: Bulk delete with confirmation
2. **Export**: Stub for future CSV/Excel export

## Validation Integration

### How It Works

The relation manager pulls validation rules from FormRequest classes to maintain consistency:

```php
// In getAddressField()
$request = new StorePropertyRequest;
$messages = $request->messages();

return Forms\Components\TextInput::make('address')
    ->validationMessages([
        'required' => $messages['address.required'],
        'max' => $messages['address.max'],
    ]);
```

### Validation Rules

| Field | Rules | Source |
|-------|-------|--------|
| address | required, string, max:255 | StorePropertyRequest |
| type | required, enum(PropertyType) | StorePropertyRequest |
| area_sqm | required, numeric, min:0, max:10000 | StorePropertyRequest |
| building_id | nullable, exists:buildings,id | Auto-injected |
| tenant_id | sometimes, exists:tenants,id | Auto-injected |

## Tenant Management Workflow

### Assign Tenant (Vacant Property)

```php
// Form shows:
- Label: "Assign Tenant"
- Required: true
- Options: Available tenants (no current property)
- Scoped to current tenant_id

// On submit:
1. Check authorization (PropertyPolicy::update)
2. Sync tenant to property
3. Send success notification
```

### Reassign Tenant (Occupied Property)

```php
// Form shows:
- Label: "Reassign Tenant"
- Required: false (can clear)
- Options: Available tenants
- Helper text about reassignment

// On submit:
1. Check authorization
2. Sync new tenant (replaces old)
3. Send success notification
```

### Remove Tenant

```php
// User clears tenant field

// On submit:
1. Check authorization
2. Detach all tenants
3. Send "tenant removed" notification
```

## Authorization

### Policy Integration

```php
// View tab
canViewForRecord() → PropertyPolicy::viewAny()

// CRUD operations
- Create: PropertyPolicy::create()
- Edit: PropertyPolicy::update()
- Delete: PropertyPolicy::delete()

// Tenant management
handleTenantManagement() → Explicit PropertyPolicy::update() check
```

### Tenant Scope

Properties are automatically scoped through the building relationship:

```
User (tenant_id: 1)
  → Building (tenant_id: 1)
    → Properties (building_id: X)
      → Only properties of buildings owned by tenant_id: 1
```

## Configuration

### Config Values (config/billing.php)

```php
'property' => [
    'default_apartment_area' => env('DEFAULT_APARTMENT_AREA', 50),
    'default_house_area' => env('DEFAULT_HOUSE_AREA', 120),
    'min_area' => 0,
    'max_area' => 10000,
],
```

### Environment Variables

```env
DEFAULT_APARTMENT_AREA=50
DEFAULT_HOUSE_AREA=120
```

## Localization

All UI strings use translation keys from `lang/en/properties.php`:

```php
// Labels
__('properties.labels.address')
__('properties.labels.type')
__('properties.labels.area')

// Validation
__('properties.validation.address.required')
__('properties.validation.type.enum')

// Notifications
__('properties.notifications.created.title')
__('properties.notifications.tenant_assigned.body')

// Actions
__('properties.actions.manage_tenant')
__('properties.actions.export_selected')
```

## Performance Optimizations

### Eager Loading

```php
->modifyQueryUsing(fn (Builder $query): Builder => 
    $query->with(['tenants', 'meters'])
)
```

**Before**: 1 + N + N queries (for N properties)  
**After**: 3 queries total (properties, tenants, meters)  
**Improvement**: ~90% reduction for 10+ properties

### Counts Instead of Collections

```php
Tables\Columns\TextColumn::make('meters_count')
    ->counts('meters')  // Uses COUNT() query, not full collection
```

## Usage Examples

### Creating a Property

```php
// User flow:
1. Click "Create" button
2. Fill form:
   - Address: "Apartment 12, Floor 3"
   - Type: Select "Apartment"
   - Area: Auto-filled to 50 m² (can edit)
3. Submit

// Behind the scenes:
1. Validation via StorePropertyRequest rules
2. preparePropertyData() injects:
   - tenant_id: auth()->user()->tenant_id
   - building_id: $this->getOwnerRecord()->id
3. PropertyPolicy::create() checks authorization
4. Property saved
5. Success notification shown
```

### Assigning a Tenant

```php
// User flow:
1. Click "Manage Tenant" action
2. Modal opens with tenant select
3. Choose tenant from dropdown
4. Submit

// Behind the scenes:
1. getTenantManagementForm() builds form
2. handleTenantManagement() processes:
   - Checks PropertyPolicy::update()
   - Syncs tenant: $record->tenants()->sync([$tenant_id])
3. Success notification shown
```

### Filtering Properties

```php
// Type filter
- Select "Apartment" → Shows only apartments
- Select "House" → Shows only houses

// Occupancy filter
- Select "Occupied" → whereHas('tenants')
- Select "Vacant" → whereDoesntHave('tenants')

// Large properties toggle
- Enable → where('area_sqm', '>', 100)
```

## Testing

### Test Coverage

See `tests/Feature/Filament/PropertiesRelationManagerTest.php`:

- ✅ Localization (all strings use translation keys)
- ✅ Model relationships (BelongsToMany for tenants)
- ✅ Authorization (policy checks enforced)
- ✅ Validation integration (FormRequest rules applied)
- ✅ Eager loading (N+1 prevention)
- ✅ Default area setting (config-based)
- ✅ Tenant management workflow
- ✅ Data preparation (auto-injection)
- ✅ Security (tenant scope isolation)

### Running Tests

```bash
# Run all PropertiesRelationManager tests
php artisan test --filter=PropertiesRelationManager

# Run with coverage
php artisan test --filter=PropertiesRelationManager --coverage

# Run specific test
php artisan test --filter="test all validation messages use translation keys"
```

## Troubleshooting

### Issue: Validation messages not localized

**Cause**: Missing translation keys in `lang/en/properties.php`

**Solution**:
```bash
# Check for missing keys
php artisan lang:check

# Add missing keys to lang/en/properties.php
```

### Issue: Tenant assignment fails

**Cause**: Authorization check failing or relationship misconfigured

**Debug**:
```php
// Check policy
auth()->user()->can('update', $property); // Should return true

// Check relationship
$property->tenants(); // Should be BelongsToMany, not HasMany
```

### Issue: N+1 queries on table load

**Cause**: Eager loading not configured

**Solution**: Verify `modifyQueryUsing()` includes `with(['tenants', 'meters'])`

## Related Documentation

- [Property Model](../models/property.md)
- [Building Resource](./building-resource.md)
- [StorePropertyRequest](../requests/store-property-request.md)
- [PropertyPolicy](../policies/property-policy.md)
- [Localization Guide](../localization.md)

## Changelog

### 2025-11-23
- ✅ Integrated validation from FormRequests
- ✅ Added explicit authorization checks
- ✅ Removed inline tenant field (moved to separate action)
- ✅ Added comprehensive DocBlocks
- ✅ Created documentation

### Previous
- Initial implementation with basic CRUD
- Tenant management workflow
- Eager loading optimization
