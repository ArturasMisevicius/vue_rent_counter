# MeterResource API Reference

**Resource**: `App\Filament\Resources\MeterResource`  
**Model**: `App\Models\Meter`  
**Policy**: `App\Policies\MeterPolicy`  
**Version**: Filament 4.x  
**Last Updated**: 2024-11-27

## Overview

The MeterResource provides a complete Filament admin interface for managing utility meters across properties. It implements tenant-scoped data access, role-based navigation visibility, and integrates with the StoreMeterRequest and UpdateMeterRequest validation.

## Table of Contents

- [Authorization](#authorization)
- [Navigation](#navigation)
- [Form Schema](#form-schema)
- [Table Configuration](#table-configuration)
- [Filters](#filters)
- [Relations](#relations)
- [Pages](#pages)
- [Validation](#validation)
- [Localization](#localization)
- [Usage Examples](#usage-examples)

## Authorization

### Policy Integration

The resource delegates all authorization to `MeterPolicy`:

```php
// Policy methods used:
- viewAny(User $user): bool
- view(User $user, Meter $meter): bool
- create(User $user): bool
- update(User $user, Meter $meter): bool
- delete(User $user, Meter $meter): bool
- restore(User $user, Meter $meter): bool
- forceDelete(User $user, Meter $meter): bool
```

### Authorization Matrix

| Role | View Any | View | Create | Update | Delete | Force Delete |
|------|----------|------|--------|--------|--------|--------------|
| Superadmin | ✅ All | ✅ All | ✅ | ✅ All | ✅ All | ✅ |
| Admin | ✅ All | ✅ All | ✅ | ✅ All | ✅ All | ❌ |
| Manager | ✅ Tenant | ✅ Tenant | ✅ | ✅ Tenant | ✅ Tenant | ❌ |
| Tenant | ❌ | ✅ Property | ❌ | ❌ | ❌ | ❌ |

### Tenant Scoping

The resource automatically scopes queries to the authenticated user's tenant:

```php
protected static function scopeToUserTenant(Builder $query): Builder
{
    $user = auth()->user();

    if ($user instanceof User && $user->tenant_id) {
        $table = $query->getModel()->getTable();
        $query->where("{$table}.tenant_id", $user->tenant_id);
    }

    return $query;
}
```

**Behavior**:
- Superadmin: Sees all meters across all tenants
- Admin/Manager: Sees only meters for their tenant
- Tenant: Resource is hidden from navigation

## Navigation

### Configuration

```php
protected static ?string $navigationLabel = null;
protected static ?int $navigationSort = 4;
protected static ?string $recordTitleAttribute = 'serial_number';

public static function getNavigationIcon(): string|BackedEnum|null
{
    return 'heroicon-o-bolt';
}

public static function getNavigationGroup(): string|UnitEnum|null
{
    return __('app.nav_groups.operations');
}
```

### Visibility Rules

```php
public static function shouldRegisterNavigation(): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->role !== UserRole::TENANT;
}
```

**Navigation Badge**: Shows count of meters for the user's tenant (or all meters for Superadmin)

```php
public static function getNavigationBadge(): ?string
{
    $user = auth()->user();
    
    if (!$user instanceof User) {
        return null;
    }
    
    $query = static::getModel()::query();
    
    // Apply tenant scope for non-superadmin users
    if ($user->role !== UserRole::SUPERADMIN && $user->tenant_id) {
        $query->where('tenant_id', $user->tenant_id);
    }
    
    $count = $query->count();
    return $count > 0 ? (string) $count : null;
}
```

## Form Schema

### Structure

The form is organized into a single section with two columns:

```php
Forms\Components\Section::make(__('meters.sections.meter_details'))
    ->description(__('meters.sections.meter_details_description'))
    ->schema([...])
    ->columns(2)
```

### Fields

#### Property Selection

```php
Forms\Components\Select::make('property_id')
    ->label(__('meters.labels.property'))
    ->relationship(
        name: 'property',
        titleAttribute: 'address',
        modifyQueryUsing: fn (Builder $query) => self::scopeToUserTenant($query)
    )
    ->searchable()
    ->preload()
    ->required()
    ->helperText(__('meters.helper_text.property'))
    ->validationMessages(self::getValidationMessages('property_id'))
```

**Features**:
- Tenant-scoped property list
- Searchable dropdown
- Preloaded options
- Helper text for guidance
- Localized validation messages

#### Meter Type

```php
Forms\Components\Select::make('type')
    ->label(__('meters.labels.type'))
    ->options(MeterType::class)
    ->required()
    ->native(false)
    ->helperText(__('meters.helper_text.type'))
    ->validationMessages(self::getValidationMessages('type'))
```

**Options** (from `MeterType` enum):
- `ELECTRICITY` - Electricity meter
- `WATER_COLD` - Cold water meter
- `WATER_HOT` - Hot water meter
- `HEATING` - Heating meter

#### Serial Number

```php
Forms\Components\TextInput::make('serial_number')
    ->label(__('meters.labels.serial_number'))
    ->placeholder(__('meters.placeholders.serial_number'))
    ->helperText(__('meters.helper_text.serial_number'))
    ->required()
    ->maxLength(255)
    ->unique(ignoreRecord: true)
    ->validationMessages(self::getValidationMessages('serial_number'))
```

**Validation**:
- Required
- Maximum 255 characters
- Must be unique across all meters
- Uniqueness check ignores current record on edit

#### Installation Date

```php
Forms\Components\DatePicker::make('installation_date')
    ->label(__('meters.labels.installation_date'))
    ->helperText(__('meters.helper_text.installation_date'))
    ->required()
    ->maxDate(now())
    ->native(false)
    ->displayFormat('Y-m-d')
    ->validationMessages(self::getValidationMessages('installation_date'))
```

**Validation**:
- Required
- Cannot be in the future
- Date format: Y-m-d

#### Supports Zones

```php
Forms\Components\Toggle::make('supports_zones')
    ->label(__('meters.labels.supports_zones'))
    ->helperText(__('meters.helper_text.supports_zones'))
    ->default(false)
    ->inline(false)
    ->validationMessages(self::getValidationMessages('supports_zones'))
```

**Purpose**: Indicates if the meter supports time-of-use zones (day/night rates)

## Table Configuration

### Columns

#### Property Address

```php
Tables\Columns\TextColumn::make('property.address')
    ->label(__('meters.labels.property'))
    ->searchable()
    ->sortable()
    ->weight('medium')
    ->tooltip(fn ($record): string => __('meters.tooltips.property_address', [
        'address' => $record->property->address,
    ]))
```

**Features**:
- Displays property address
- Searchable and sortable
- Tooltip shows full address
- Medium font weight for emphasis

#### Meter Type

```php
Tables\Columns\TextColumn::make('type')
    ->label(__('meters.labels.type'))
    ->badge()
    ->color(fn (MeterType $state): string => match ($state) {
        MeterType::ELECTRICITY => 'warning',
        MeterType::WATER_COLD => 'info',
        MeterType::WATER_HOT => 'danger',
        MeterType::HEATING => 'success',
    })
    ->formatStateUsing(fn (?MeterType $state): ?string => $state?->label())
    ->sortable()
```

**Features**:
- Displayed as colored badge
- Color-coded by meter type
- Uses enum labels
- Sortable

#### Serial Number

```php
Tables\Columns\TextColumn::make('serial_number')
    ->label(__('meters.labels.serial_number'))
    ->searchable()
    ->sortable()
    ->copyable()
    ->copyMessage(__('meters.tooltips.copy_serial'))
    ->weight('medium')
```

**Features**:
- Searchable and sortable
- Click to copy functionality
- Copy confirmation message
- Medium font weight

#### Installation Date

```php
Tables\Columns\TextColumn::make('installation_date')
    ->label(__('meters.labels.installation_date'))
    ->date()
    ->sortable()
    ->toggleable()
```

**Features**:
- Formatted as date
- Sortable
- Can be hidden by user

#### Supports Zones

```php
Tables\Columns\IconColumn::make('supports_zones')
    ->label(__('meters.labels.supports_zones'))
    ->boolean()
    ->trueIcon('heroicon-o-check-circle')
    ->falseIcon('heroicon-o-x-circle')
    ->trueColor('success')
    ->falseColor('gray')
    ->tooltip(fn ($record): string => $record->supports_zones
        ? __('meters.tooltips.supports_zones_yes')
        : __('meters.tooltips.supports_zones_no')
    )
    ->toggleable()
```

**Features**:
- Icon-based display
- Green check for yes, gray X for no
- Tooltip explains status
- Can be hidden by user

#### Readings Count

```php
Tables\Columns\TextColumn::make('readings_count')
    ->label(__('meters.labels.readings_count'))
    ->counts('readings')
    ->badge()
    ->color('gray')
    ->tooltip(__('meters.tooltips.readings_count'))
    ->toggleable()
```

**Features**:
- Shows count of meter readings
- Displayed as badge
- Tooltip provides context
- Can be hidden by user

#### Created At

```php
Tables\Columns\TextColumn::make('created_at')
    ->label(__('meters.labels.created'))
    ->dateTime()
    ->sortable()
    ->toggleable(isToggledHiddenByDefault: true)
```

**Features**:
- Formatted as datetime
- Sortable
- Hidden by default

### Table Features

```php
->defaultSort('serial_number', 'asc')
->persistSortInSession()
->persistSearchInSession()
->persistFiltersInSession()
->emptyStateHeading(__('meters.empty_state.heading'))
->emptyStateDescription(__('meters.empty_state.description'))
```

**Features**:
- Default sort by serial number
- Persistent state across sessions
- Custom empty state messages

## Filters

### Meter Type Filter

```php
Tables\Filters\SelectFilter::make('type')
    ->label(__('meters.filters.type'))
    ->options(MeterType::labels())
    ->native(false)
```

**Options**: All meter types from `MeterType` enum

### Property Filter

```php
Tables\Filters\SelectFilter::make('property_id')
    ->label(__('meters.filters.property'))
    ->relationship('property', 'address')
    ->searchable()
    ->preload()
    ->native(false)
```

**Features**:
- Searchable property list
- Preloaded options
- Tenant-scoped automatically

### Supports Zones Filter

```php
Tables\Filters\Filter::make('supports_zones')
    ->label(__('meters.filters.supports_zones'))
    ->query(fn (Builder $query): Builder => $query->where('supports_zones', true))
    ->toggle()
```

**Behavior**: Toggle to show only meters that support zones

### No Readings Filter

```php
Tables\Filters\Filter::make('no_readings')
    ->label(__('meters.filters.no_readings'))
    ->query(fn (Builder $query): Builder => $query->doesntHave('readings'))
    ->toggle()
```

**Behavior**: Toggle to show only meters without any readings

## Relations

### Readings Relation Manager

```php
public static function getRelations(): array
{
    return [
        RelationManagers\ReadingsRelationManager::class,
    ];
}
```

**Features**:
- Displays all meter readings
- Supports zone-based readings (day/night)
- Date range filtering
- Full CRUD operations
- Conditional field visibility based on meter's supports_zones

See [ReadingsRelationManager documentation](./METER_READINGS_RELATION_MANAGER.md) for details.

## Pages

```php
public static function getPages(): array
{
    return [
        'index' => Pages\ListMeters::route('/'),
        'create' => Pages\CreateMeter::route('/create'),
        'view' => Pages\ViewMeter::route('/{record}'),
        'edit' => Pages\EditMeter::route('/{record}/edit'),
    ];
}
```

### Available Pages

- **List**: Browse and filter meters
- **Create**: Add new meter
- **View**: View meter details and readings
- **Edit**: Update meter information

## Validation

### Integration with FormRequests

The resource uses the `HasTranslatedValidation` trait to integrate with `StoreMeterRequest` and `UpdateMeterRequest`:

```php
use HasTranslatedValidation;

protected static string $translationPrefix = 'meters.validation';
```

### Validation Rules

| Field | Rules | Description |
|-------|-------|-------------|
| tenant_id | required, integer | Auto-set from authenticated user |
| property_id | required, exists:properties,id | Must be valid property |
| type | required, enum:MeterType | Must be valid meter type |
| serial_number | required, string, max:255, unique | Unique identifier |
| installation_date | required, date, before_or_equal:today | Cannot be future date |
| supports_zones | boolean | Optional, defaults to false |

### Custom Validation Messages

All validation messages are localized via `lang/{locale}/meters.php`:

```php
'validation' => [
    'tenant_id' => [
        'required' => 'Tenant is required.',
        'integer' => 'Tenant identifier must be a valid number.',
    ],
    'serial_number' => [
        'required' => 'The serial number is required.',
        'unique' => 'This serial number is already in use.',
        'string' => 'The serial number must be text.',
        'max' => 'The serial number may not be greater than 255 characters.',
    ],
    // ... more validation messages
]
```

## Localization

### Translation Keys

All UI strings use Laravel's translation system with the `meters` namespace:

#### Labels
```php
'labels' => [
    'meter' => 'Meter',
    'meters' => 'Meters',
    'property' => 'Property',
    'type' => 'Meter Type',
    'serial_number' => 'Serial Number',
    'installation_date' => 'Installation Date',
    'supports_zones' => 'Supports Zones',
    'readings_count' => 'Readings',
    'readings' => 'Meter Readings',
    'created' => 'Created At',
    'updated' => 'Updated At',
]
```

#### Helper Text
```php
'helper_text' => [
    'property' => 'Select the property where this meter is installed',
    'type' => 'Select the type of utility this meter measures',
    'serial_number' => 'Unique identifier for this meter (must be unique)',
    'installation_date' => 'Date when the meter was installed (cannot be in the future)',
    'supports_zones' => 'Enable if this meter supports time-of-use zones (day/night rates)',
]
```

#### Tooltips
```php
'tooltips' => [
    'property_address' => 'Installed at: :address',
    'copy_serial' => 'Click to copy serial number',
    'supports_zones_yes' => 'This meter supports time-of-use zones',
    'supports_zones_no' => 'This meter does not support time-of-use zones',
    'readings_count' => 'Number of recorded readings',
]
```

#### Filters
```php
'filters' => [
    'type' => 'Meter Type',
    'property' => 'Property',
    'supports_zones' => 'Supports Zones',
    'no_readings' => 'No Readings',
]
```

#### Empty State
```php
'empty_state' => [
    'heading' => 'No Meters',
    'description' => 'Get started by creating your first meter.',
]
```

## Usage Examples

### Creating a Meter

```php
// Via Filament UI:
// 1. Navigate to Meters resource
// 2. Click "New Meter" button
// 3. Fill in form:
//    - Select property from dropdown
//    - Select meter type (Electricity, Water Cold, Water Hot, Heating)
//    - Enter unique serial number
//    - Select installation date
//    - Toggle "Supports Zones" if applicable
// 4. Click "Create"

// Programmatically:
use App\Models\Meter;
use App\Enums\MeterType;

$meter = Meter::create([
    'tenant_id' => auth()->user()->tenant_id,
    'property_id' => $property->id,
    'type' => MeterType::ELECTRICITY,
    'serial_number' => 'ELEC-2024-001',
    'installation_date' => now()->subMonths(6),
    'supports_zones' => true,
]);
```

### Filtering Meters

```php
// Via Filament UI:
// 1. Navigate to Meters resource
// 2. Use filter dropdowns:
//    - Filter by meter type
//    - Filter by property
//    - Toggle "Supports Zones" filter
//    - Toggle "No Readings" filter
// 3. Results update automatically

// Programmatically:
use App\Models\Meter;
use App\Enums\MeterType;

// Get all electricity meters with zones
$meters = Meter::where('type', MeterType::ELECTRICITY)
    ->where('supports_zones', true)
    ->get();

// Get meters without readings
$metersWithoutReadings = Meter::doesntHave('readings')->get();
```

### Viewing Meter Details

```php
// Via Filament UI:
// 1. Navigate to Meters resource
// 2. Click on a meter row
// 3. View meter details and readings
// 4. Use "Meter Readings" tab to see all readings

// Programmatically:
use App\Models\Meter;

$meter = Meter::with(['property', 'readings'])
    ->findOrFail($meterId);

// Access meter details
$property = $meter->property;
$readingsCount = $meter->readings->count();
$latestReading = $meter->readings()->latest('reading_date')->first();
```

### Updating a Meter

```php
// Via Filament UI:
// 1. Navigate to Meters resource
// 2. Click on a meter row
// 3. Click "Edit" button
// 4. Update fields as needed
// 5. Click "Save changes"

// Programmatically:
use App\Models\Meter;

$meter = Meter::findOrFail($meterId);
$meter->update([
    'serial_number' => 'ELEC-2024-001-UPDATED',
    'supports_zones' => true,
]);
```

### Tenant Scoping Example

```php
// Manager viewing meters
$manager = User::where('role', UserRole::MANAGER)
    ->where('tenant_id', 1)
    ->first();

auth()->login($manager);

// This query automatically scopes to tenant_id = 1
$meters = Meter::all(); // Only returns meters for tenant 1

// Superadmin viewing all meters
$superadmin = User::where('role', UserRole::SUPERADMIN)->first();
auth()->login($superadmin);

// This query returns all meters across all tenants
$allMeters = Meter::all();
```

## Testing

### Test Coverage

The MeterResource has comprehensive test coverage in `tests/Feature/Filament/MeterResourceTest.php`:

- ✅ Tenant scope filtering
- ✅ Navigation visibility by role
- ✅ Badge counting (tenant-scoped)
- ✅ Policy integration
- ✅ Localization
- ✅ Resource configuration

### Running Tests

```bash
# Run all MeterResource tests
php artisan test --filter=MeterResourceTest

# Run specific test
php artisan test --filter=MeterResourceTest::test_scope_to_user_tenant_filters_by_tenant_id

# Run with coverage
php artisan test --filter=MeterResourceTest --coverage
```

## Related Documentation

- [Meter Model](../../app/Models/Meter.php)
- [MeterPolicy](../../app/Policies/MeterPolicy.php)
- [StoreMeterRequest](../../app/Http/Requests/StoreMeterRequest.php)
- [UpdateMeterRequest](../../app/Http/Requests/UpdateMeterRequest.php)
- [MeterResource Tests](../../tests/Feature/Filament/MeterResourceTest.php)
- [Meters Translations](../../lang/en/meters.php)
- [ReadingsRelationManager](./METER_READINGS_RELATION_MANAGER.md)

## Troubleshooting

### Issue: Meters not showing in list

**Possible causes**:
1. User doesn't have permission (check MeterPolicy)
2. No meters exist for user's tenant
3. Filters are active

**Solution**:
```bash
# Check user permissions
php artisan tinker
>>> $user = User::find(1);
>>> $user->can('viewAny', App\Models\Meter::class);

# Check meter count
>>> Meter::where('tenant_id', $user->tenant_id)->count();

# Clear filters in UI
```

### Issue: Cannot create meter

**Possible causes**:
1. User doesn't have create permission
2. Serial number already exists
3. Property doesn't belong to user's tenant

**Solution**:
```bash
# Check create permission
php artisan tinker
>>> $user = User::find(1);
>>> $user->can('create', App\Models\Meter::class);

# Check serial number uniqueness
>>> Meter::where('serial_number', 'ELEC-001')->exists();

# Verify property tenant
>>> Property::find($propertyId)->tenant_id === $user->tenant_id;
```

### Issue: Navigation badge not showing

**Possible causes**:
1. No meters exist for user's tenant
2. User is tenant role (resource hidden)

**Solution**:
```bash
# Check meter count
php artisan tinker
>>> $user = User::find(1);
>>> Meter::where('tenant_id', $user->tenant_id)->count();

# Check user role
>>> $user->role->value;
```

## Changelog

### 2024-11-27
- ✅ Enhanced form with sections and helper text
- ✅ Improved table with tooltips and badges
- ✅ Added navigation badge with tenant-scoped count
- ✅ Implemented advanced filtering (type, property, zones, no readings)
- ✅ Added copyable serial numbers
- ✅ Improved localization with comprehensive translation keys
- ✅ Enhanced documentation with PHPDoc blocks
- ✅ Updated navigation sort order to 4
- ✅ Changed navigation icon to heroicon-o-bolt
- ✅ Added persistent table state (sort, search, filters)
- ✅ Implemented role-based navigation visibility
- ✅ Added comprehensive test coverage

### Initial Release
- ✅ Basic CRUD operations
- ✅ Tenant scoping
- ✅ Policy integration
- ✅ Form validation
- ✅ Table configuration
- ✅ Readings relation manager
