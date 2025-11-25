# TariffController API Reference

## Overview

The `TariffController` provides CRUD operations for tariff management, supporting both flat-rate and time-of-use pricing structures with tariff versioning capabilities.

**Namespace**: `App\Http\Controllers\Admin`  
**Requirements**: 2.1, 2.2, 11.1, 11.2  
**Status**: ✅ Production Ready

---

## Routes

| Method | URI | Name | Action | Auth |
|--------|-----|------|--------|------|
| GET | `/admin/tariffs` | `admin.tariffs.index` | List tariffs | Admin only* |
| GET | `/admin/tariffs/create` | `admin.tariffs.create` | Show create form | Admin only |
| POST | `/admin/tariffs` | `admin.tariffs.store` | Store new tariff | Admin only |
| GET | `/admin/tariffs/{tariff}` | `admin.tariffs.show` | Show tariff details | Admin only* |
| GET | `/admin/tariffs/{tariff}/edit` | `admin.tariffs.edit` | Show edit form | Admin only |
| PUT/PATCH | `/admin/tariffs/{tariff}` | `admin.tariffs.update` | Update tariff | Admin only |
| DELETE | `/admin/tariffs/{tariff}` | `admin.tariffs.destroy` | Delete tariff | Admin only |

**Note**: *While TariffPolicy allows all authenticated users to view tariffs, the route middleware (`role:admin`) restricts these admin routes to admin/superadmin only. Managers and tenants access tariffs through Filament resources or API endpoints.

---

## Methods

### `index(Request $request): View`

Lists all tariffs with pagination and sorting.

**Authorization**: `viewAny` policy allows all authenticated users, but route middleware restricts to admin/superadmin only  
**Requirements**: 11.1, 11.2

**Authorization Architecture**:
- **Route Middleware**: `role:admin` restricts access to admin/superadmin
- **Controller Authorization**: `$this->authorize('viewAny', Tariff::class)` 
- **Policy Layer**: `TariffPolicy::viewAny()` returns true for all authenticated users

**Note**: Managers access tariffs through Filament resources, not these admin routes.

#### Query Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `sort` | string | `active_from` | Column to sort by |
| `direction` | string | `desc` | Sort direction (`asc`, `desc`) |
| `page` | integer | `1` | Page number |

**Allowed Sort Columns**: `name`, `active_from`, `active_until`, `created_at`

#### Response

Returns `admin.tariffs.index` view with:
- `$tariffs` - Paginated collection of tariffs with provider relationship

#### Example

```php
// GET /admin/tariffs?sort=name&direction=asc&page=2

// Controller
$tariffs = Tariff::with('provider')
    ->orderBy('name', 'asc')
    ->paginate(20)
    ->withQueryString();
```

---

### `create(): View`

Shows the tariff creation form.

**Authorization**: `create` (Admin only)  
**Requirements**: 11.1, 11.2

#### Response

Returns `admin.tariffs.create` view with:
- `$providers` - Collection of all providers ordered by name

#### Example

```php
// GET /admin/tariffs/create

// View receives
$providers = Provider::orderBy('name')->get();
```

---

### `store(StoreTariffRequest $request): RedirectResponse`

Creates a new tariff with validated configuration.

**Authorization**: `create` (Admin only)  
**Requirements**: 2.1, 2.2, 11.1, 11.2

#### Request Body

**Flat Rate Tariff**:
```json
{
  "provider_id": 1,
  "name": "Standard Electricity Rate",
  "configuration": {
    "type": "flat",
    "currency": "EUR",
    "rate": 0.20,
    "fixed_fee": 5.00
  },
  "active_from": "2025-01-01",
  "active_until": "2025-12-31"
}
```

**Time-of-Use Tariff**:
```json
{
  "provider_id": 1,
  "name": "Day/Night Electricity",
  "configuration": {
    "type": "time_of_use",
    "currency": "EUR",
    "zones": [
      {
        "id": "day",
        "start": "07:00",
        "end": "23:00",
        "rate": 0.25
      },
      {
        "id": "night",
        "start": "23:00",
        "end": "07:00",
        "rate": 0.15
      }
    ],
    "weekend_logic": "apply_night_rate",
    "fixed_fee": 5.00
  },
  "active_from": "2025-01-01",
  "active_until": null
}
```

#### Validation Rules

| Field | Rules | Description |
|-------|-------|-------------|
| `provider_id` | required, exists:providers | Provider ID |
| `name` | required, string, max:255 | Tariff name |
| `configuration` | required, array | Tariff configuration |
| `configuration.type` | required, in:flat,time_of_use | Tariff type |
| `configuration.currency` | required, in:EUR | Currency code |
| `configuration.rate` | required_if:type,flat, numeric, min:0 | Flat rate |
| `configuration.zones` | required_if:type,time_of_use, array, min:1 | Time zones |
| `configuration.zones.*.id` | required, string | Zone identifier |
| `configuration.zones.*.start` | required, regex:HH:MM | Start time |
| `configuration.zones.*.end` | required, regex:HH:MM | End time |
| `configuration.zones.*.rate` | required, numeric, min:0 | Zone rate |
| `configuration.weekend_logic` | nullable, in:apply_night_rate,apply_day_rate,apply_weekend_rate | Weekend handling |
| `configuration.fixed_fee` | nullable, numeric, min:0 | Fixed monthly fee |
| `active_from` | required, date | Start date |
| `active_until` | nullable, date, after:active_from | End date |

#### Time-of-Use Validation

**Property 6**: Time-of-use zones must:
1. Cover full 24-hour period (no gaps)
2. Not overlap
3. Have valid time format (HH:MM)
4. Have end time after start time (or wrap midnight)

#### Response

**Success** (302 Redirect):
```
Location: /admin/tariffs
Flash: success = "Tariff created successfully"
```

**Validation Error** (422):
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "configuration.zones": [
      "Time zones must cover full 24-hour period",
      "Time zones cannot overlap"
    ]
  }
}
```

#### Audit Logging

Creates log entry:
```php
Log::info('Tariff created', [
    'user_id' => auth()->id(),
    'tariff_id' => $tariff->id,
    'provider_id' => $tariff->provider_id,
    'name' => $tariff->name,
    'type' => $tariff->configuration['type'],
]);
```

---

### `show(Tariff $tariff): View`

Displays tariff details with version history.

**Authorization**: `view` policy allows all authenticated users, but route middleware restricts to admin/superadmin only  
**Requirements**: 11.1

**Note**: Managers and tenants access tariff details through Filament resources or API endpoints, not these admin routes.

#### Response

Returns `admin.tariffs.show` view with:
- `$tariff` - Tariff model with provider relationship
- `$versionHistory` - Collection of related tariff versions

#### Version History

Finds all tariffs with:
- Same `provider_id`
- Same `name`
- Different `id`
- Ordered by `active_from` descending

#### Example

```php
// GET /admin/tariffs/123

// View receives
$tariff = Tariff::with('provider')->find(123);
$versionHistory = Tariff::where('provider_id', $tariff->provider_id)
    ->where('name', $tariff->name)
    ->where('id', '!=', $tariff->id)
    ->orderBy('active_from', 'desc')
    ->get();
```

---

### `edit(Tariff $tariff): View`

Shows the tariff edit form.

**Authorization**: `update` (Admin only)  
**Requirements**: 11.1, 11.2

#### Response

Returns `admin.tariffs.edit` view with:
- `$tariff` - Tariff model to edit
- `$providers` - Collection of all providers

---

### `update(UpdateTariffRequest $request, Tariff $tariff): RedirectResponse`

Updates an existing tariff or creates a new version.

**Authorization**: `update` (Admin only)  
**Requirements**: 2.1, 2.2, 11.1, 11.2

#### Update Modes

**1. Direct Update** (default):
- Modifies existing tariff
- Preserves tariff ID
- Updates configuration in place

**2. Version Creation** (`create_new_version=true`):
- Closes current tariff (`active_until` = new version start - 1 day)
- Creates new tariff with new configuration
- Maintains version history

#### Request Body

**Direct Update**:
```json
{
  "name": "Updated Tariff Name",
  "configuration": {
    "type": "flat",
    "currency": "EUR",
    "rate": 0.22
  },
  "active_from": "2025-01-01"
}
```

**Version Creation**:
```json
{
  "provider_id": 1,
  "name": "Standard Electricity Rate",
  "configuration": {
    "type": "flat",
    "currency": "EUR",
    "rate": 0.25
  },
  "active_from": "2025-07-01",
  "create_new_version": true
}
```

#### Validation Rules

Same as `store()` but all fields are optional (partial updates allowed).

#### Response

**Direct Update** (302 Redirect):
```
Location: /admin/tariffs/{tariff}
Flash: success = "Tariff updated successfully"
```

**Version Creation** (302 Redirect):
```
Location: /admin/tariffs/{newTariff}
Flash: success = "New tariff version created successfully"
```

#### Audit Logging

**Direct Update**:
```php
Log::info('Tariff updated', [
    'user_id' => auth()->id(),
    'tariff_id' => $tariff->id,
    'provider_id' => $tariff->provider_id,
    'name' => $tariff->name,
]);
```

**Version Creation**:
```php
Log::info('Tariff version created', [
    'user_id' => auth()->id(),
    'old_tariff_id' => $tariff->id,
    'new_tariff_id' => $newTariff->id,
    'provider_id' => $newTariff->provider_id,
    'name' => $newTariff->name,
]);
```

---

### `destroy(Tariff $tariff): RedirectResponse`

Soft deletes a tariff.

**Authorization**: `delete` (Admin only)  
**Requirements**: 11.1, 11.2

#### Response

**Success** (302 Redirect):
```
Location: /admin/tariffs
Flash: success = "Tariff deleted successfully"
```

#### Audit Logging

```php
Log::info('Tariff deleted', [
    'user_id' => auth()->id(),
    'tariff_id' => $tariff->id,
    'provider_id' => $tariff->provider_id,
    'name' => $tariff->name,
]);
```

#### Restoration

Soft-deleted tariffs can be restored:
```php
$tariff = Tariff::withTrashed()->find($id);
$tariff->restore();
```

---

## Authorization Matrix

### Admin Routes (TariffController)

| Action | SUPERADMIN | ADMIN | MANAGER | TENANT |
|--------|------------|-------|---------|--------|
| index | ✅ | ✅ | ❌* | ❌* |
| create | ✅ | ✅ | ❌ | ❌ |
| store | ✅ | ✅ | ❌ | ❌ |
| show | ✅ | ✅ | ❌* | ❌* |
| edit | ✅ | ✅ | ❌ | ❌ |
| update | ✅ | ✅ | ❌ | ❌ |
| destroy | ✅ | ✅ | ❌ | ❌ |

**Note**: *Managers and tenants are blocked by route middleware (`role:admin`), even though the policy would allow viewing. They access tariffs through Filament resources or API endpoints instead.

### Policy Permissions (TariffPolicy)

| Action | SUPERADMIN | ADMIN | MANAGER | TENANT |
|--------|------------|-------|---------|--------|
| viewAny | ✅ | ✅ | ✅ | ✅ |
| view | ✅ | ✅ | ✅ | ✅ |
| create | ✅ | ✅ | ❌ | ❌ |
| update | ✅ | ✅ | ❌ | ❌ |
| delete | ✅ | ✅ | ❌ | ❌ |
| restore | ✅ | ✅ | ❌ | ❌ |
| forceDelete | ✅ | ❌ | ❌ | ❌ |

### Authorization Architecture

The authorization system has three layers:

1. **Route Middleware** (`role:admin`): Blocks non-admins at route level
2. **Controller Authorization** (`$this->authorize()`): Policy check
3. **Policy Layer** (`TariffPolicy`): Returns true/false based on role

**Design Decision**: Admin routes are restricted to admin-only access via route middleware, while managers access tariffs through Filament resources or API endpoints. This separation keeps admin and manager interfaces distinct.

---

## Usage Examples

### Example 1: Creating Flat Rate Tariff

```php
// POST /admin/tariffs
$response = $this->actingAs($admin)->post(route('admin.tariffs.store'), [
    'provider_id' => 1,
    'name' => 'Standard Water Rate',
    'configuration' => [
        'type' => 'flat',
        'currency' => 'EUR',
        'rate' => 2.50,
        'fixed_fee' => 10.00,
    ],
    'active_from' => '2025-01-01',
    'active_until' => '2025-12-31',
]);

$response->assertRedirect(route('admin.tariffs.index'));
$response->assertSessionHas('success');
```

### Example 2: Creating Time-of-Use Tariff

```php
// POST /admin/tariffs
$response = $this->actingAs($admin)->post(route('admin.tariffs.store'), [
    'provider_id' => 1,
    'name' => 'Day/Night Electricity',
    'configuration' => [
        'type' => 'time_of_use',
        'currency' => 'EUR',
        'zones' => [
            [
                'id' => 'day',
                'start' => '07:00',
                'end' => '23:00',
                'rate' => 0.25,
            ],
            [
                'id' => 'night',
                'start' => '23:00',
                'end' => '07:00',
                'rate' => 0.15,
            ],
        ],
        'weekend_logic' => 'apply_night_rate',
    ],
    'active_from' => '2025-01-01',
]);

$response->assertRedirect(route('admin.tariffs.index'));
```

### Example 3: Creating New Tariff Version

```php
// PUT /admin/tariffs/123
$response = $this->actingAs($admin)->put(route('admin.tariffs.update', $tariff), [
    'provider_id' => $tariff->provider_id,
    'name' => $tariff->name,
    'configuration' => [
        'type' => 'flat',
        'currency' => 'EUR',
        'rate' => 0.30, // New rate
    ],
    'active_from' => '2025-07-01',
    'create_new_version' => true,
]);

// Old tariff: active_until = 2025-06-30
// New tariff: active_from = 2025-07-01

$response->assertRedirect(route('admin.tariffs.show', $newTariff));
$response->assertSessionHas('success', 'New tariff version created successfully');
```

### Example 4: Listing with Sorting

```php
// GET /admin/tariffs?sort=name&direction=asc
$response = $this->actingAs($admin)->get(route('admin.tariffs.index', [
    'sort' => 'name',
    'direction' => 'asc',
]));

$response->assertOk();
$response->assertViewHas('tariffs');
```

---

## Error Handling

### Validation Errors

**Time Zone Overlap**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "configuration.zones": [
      "Time zones cannot overlap: day (07:00-23:00) overlaps with evening (22:00-02:00)"
    ]
  }
}
```

**Incomplete Coverage**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "configuration.zones": [
      "Time zones must cover full 24-hour period. Missing: 02:00-07:00"
    ]
  }
}
```

**Invalid Time Format**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "configuration.zones.0.start": [
      "The configuration.zones.0.start format is invalid."
    ]
  }
}
```

### Authorization Errors

**403 Forbidden**:
```
This action is unauthorized.
```

Occurs when:
- Manager attempts to access admin tariff routes (blocked by route middleware)
- Tenant attempts to access admin tariff routes (blocked by route middleware)
- Manager attempts to create/update/delete tariff (blocked by policy)
- Tenant attempts to create/update/delete tariff (blocked by policy)
- Unauthenticated user attempts any action

**Note**: Managers and tenants should use Filament resources or API endpoints to view tariffs, not admin routes.

---

## Integration Points

### Models
- **Tariff**: Main model with JSON configuration
- **Provider**: Related provider entity
- **InvoiceItem**: References tariff snapshots

### Policies
- **TariffPolicy**: Authorization rules
  - `viewAny()` - All authenticated users
  - `view()` - All authenticated users
  - `create()` - Admin only
  - `update()` - Admin only
  - `delete()` - Admin only

### Services
- **TimeRangeValidator**: Validates time-of-use zones
- **TariffResolver**: Selects appropriate tariff for billing
- **BillingService**: Uses tariff snapshots in invoices

### Requests
- **StoreTariffRequest**: Create validation
- **UpdateTariffRequest**: Update validation (extends Store)

---

## Performance Considerations

### Query Optimization

**Index Usage**:
```sql
-- Efficient queries use these indexes
INDEX idx_tariffs_provider_name (provider_id, name)
INDEX idx_tariffs_active_dates (active_from, active_until)
INDEX idx_tariffs_created_at (created_at)
```

**Eager Loading**:
```php
// Always eager-load provider
$tariffs = Tariff::with('provider')->paginate(20);
```

### Caching Opportunities

```php
// Cache active tariffs
$activeTariffs = Cache::remember('tariffs.active', 3600, function () {
    return Tariff::where('active_from', '<=', now())
        ->where(function ($query) {
            $query->whereNull('active_until')
                ->orWhere('active_until', '>=', now());
        })
        ->with('provider')
        ->get();
});
```

---

## Security Considerations

### Authorization
- All mutations require admin role
- Policy checks enforced via `$this->authorize()`
- Filament resources respect same policies

### Audit Logging
- All CRUD operations logged
- Includes user ID, tariff ID, provider ID
- Version creation tracked separately

### Input Validation
- JSON configuration validated
- Time zones checked for overlaps
- Rates must be non-negative
- Dates validated for logical order

### Data Integrity
- Soft deletes preserve history
- Version creation maintains continuity
- Foreign key constraints enforced

---

## Related Documentation

- **Policy**: `docs/api/TARIFF_POLICY_API.md`
- **Implementation**: `docs/controllers/TARIFF_CONTROLLER_COMPLETE.md`
- **Testing**: `tests/Feature/Http/Controllers/Admin/TariffControllerTest.php`
- **Specification**: `.kiro/specs/2-vilnius-utilities-billing/requirements.md`

---

## Changelog

### 2025-11-26 - Initial Documentation
- ✅ Documented all CRUD methods
- ✅ Added authorization matrix
- ✅ Included usage examples
- ✅ Documented validation rules
- ✅ Added error handling guide

---

## Status

✅ **PRODUCTION READY**

All methods documented, authorization enforced, validation comprehensive, audit logging complete.

---

**Last Updated**: November 26, 2025  
**Maintained By**: Development Team  
**Version**: 1.0.0
