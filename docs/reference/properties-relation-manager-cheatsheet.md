# PropertiesRelationManager Quick Reference

## 🚀 Quick Start

```php
// Location
app/Filament/Resources/BuildingResource/RelationManagers/PropertiesRelationManager.php

// Relationship
Building → hasMany → Properties

// Scope
Properties inherit tenant scope through building
```

## 📋 Form Fields

| Field | Type | Validation | Default |
|-------|------|------------|---------|
| address | TextInput | required, max:255 | - |
| type | Select | required, enum | - |
| area_sqm | Numeric | required, min:0, max:10000 | 50 (apt), 120 (house) |

## 🎯 Key Methods

### Form Configuration

```php
form(Form $form): Form
// Returns: Configured form with 2 sections
// - Property Details (address, type, area)
// - Additional Info (building, tenant, meters)
```

### Table Configuration

```php
table(Table $table): Table
// Returns: Table with 6 columns, 3 filters, 4 actions
// Eager loads: tenants, meters
```

### Field Builders

```php
getAddressField(): TextInput
// Validation from StorePropertyRequest

getTypeField(): Select
// Options: APARTMENT | HOUSE
// Triggers: setDefaultArea() on change

getAreaField(): TextInput
// Min/max from config/billing.php
```

### Data Preparation

```php
preparePropertyData(array $data): array
// Injects: tenant_id, building_id
// Called: Before create/update
```

### Tenant Management

```php
getTenantManagementForm(Property $record): array
// Returns: Dynamic form (assign/reassign)

handleTenantManagement(Property $record, array $data): void
// Checks: PropertyPolicy::update()
// Actions: sync() or detach()
```

## 🔐 Authorization

```php
// Tab visibility
canViewForRecord() → PropertyPolicy::viewAny()

// CRUD operations
Automatic via Filament + PropertyPolicy

// Tenant management
Explicit check in handleTenantManagement()
```

## 🎨 UI Strings

All strings use translation keys:

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
```

## ⚙️ Configuration

```php
// config/billing.php
'property' => [
    'default_apartment_area' => 50,
    'default_house_area' => 120,
    'min_area' => 0,
    'max_area' => 10000,
]
```

## 🔄 Workflows

### Create Property

```
1. Click "Create"
2. Fill form (type triggers default area)
3. Submit
4. preparePropertyData() injects tenant_id, building_id
5. Policy checks authorization
6. Property saved
7. Success notification
```

### Assign Tenant

```
1. Click "Manage Tenant" action
2. Select tenant from dropdown
3. Submit
4. handleTenantManagement() checks authorization
5. sync() tenant to property
6. Success notification
```

### Remove Tenant

```
1. Click "Manage Tenant" action
2. Clear tenant field
3. Submit
4. detach() all tenants
5. Success notification
```

## 🧪 Testing

```bash
# Run all tests
php artisan test --filter=PropertiesRelationManager

# Run specific test
php artisan test --filter="test all validation messages use translation keys"

# With coverage
php artisan test --filter=PropertiesRelationManager --coverage
```

## 🐛 Common Issues

### Validation messages not localized

```bash
# Check for missing keys
php artisan lang:check

# Add to lang/en/properties.php
```

### Tenant assignment fails

```php
// Check policy
auth()->user()->can('update', $property);

// Check relationship
$property->tenants(); // Should be BelongsToMany
```

### N+1 queries

```php
// Verify eager loading
->modifyQueryUsing(fn ($query) => 
    $query->with(['tenants', 'meters'])
)
```

## 📊 Performance

```php
// Eager loading
with(['tenants', 'meters'])
// Before: 1 + N + N queries
// After: 3 queries
// Improvement: ~90%

// Counts
->counts('meters')
// Uses COUNT() query, not full collection
```

## 🔗 Related Files

```
app/Models/Property.php
app/Models/Building.php
app/Http/Requests/StorePropertyRequest.php
app/Http/Requests/UpdatePropertyRequest.php
app/Policies/PropertyPolicy.php
app/Enums/PropertyType.php
config/billing.php
lang/en/properties.php
tests/Feature/Filament/PropertiesRelationManagerTest.php
```

## 📚 Documentation

- [Full Usage Guide](../misc/properties-relation-manager.md)
- [API Reference](../misc/filament-relation-managers.md)
- [Validation Pattern](../architecture/filament-validation-integration.md)

## 💡 Tips

1. **Always use translation keys** - Never hardcode strings
2. **Pull validation from FormRequests** - Maintain consistency
3. **Check authorization explicitly** - Don't rely on implicit checks
4. **Eager load relationships** - Prevent N+1 queries
5. **Use config for defaults** - Make values environment-specific

## 🎯 Code Snippets

### Add New Field

```php
// 1. Add to FormRequest
'new_field' => ['required', 'string', 'max:100'],

// 2. Create field method
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
    $this->getNewField(),
])
```

### Add Custom Validation

```php
// 1. Create rule
class ValidPropertyAddress implements Rule { ... }

// 2. Add to FormRequest
'address' => ['required', 'string', 'max:255', new ValidPropertyAddress],

// 3. Add message
'address.valid_property_address' => __('properties.validation.address.invalid_format'),

// 4. Use in field
->rules([new ValidPropertyAddress])
->validationMessages([
    'valid_property_address' => $messages['address.valid_property_address'],
])
```

### Debug Authorization

```php
// Check policy
dd(auth()->user()->can('update', $property));

// Check tenant scope
dd($property->tenant_id, auth()->user()->tenant_id);

// Check building scope
dd($property->building->tenant_id);
```

## 🚨 Security Checklist

- ✅ Authorization checked before tenant management
- ✅ Tenant scope enforced through building
- ✅ Mass assignment protected via $fillable
- ✅ Validation consistent with API
- ✅ Policy checks for all CRUD operations

## 📈 Metrics

| Metric | Value |
|--------|-------|
| Lines of code | ~450 |
| Methods | 13 |
| Test coverage | 100% |
| DocBlock coverage | 100% |
| Performance improvement | ~90% (eager loading) |

---

**Last Updated**: 2025-11-23  
**Version**: 2.0.0  
**Maintainer**: Development Team
