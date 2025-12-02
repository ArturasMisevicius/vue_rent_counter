# HierarchicalScope API Reference

## Overview

The `HierarchicalScope` provides a programmatic API for managing multi-tenant data isolation in Laravel Eloquent queries.

## Public Methods

### apply()

Automatically applies role-based filtering to Eloquent queries.

```php
public function apply(Builder $builder, Model $model): void
```

**Parameters:**
- `$builder` (Builder) - The Eloquent query builder instance
- `$model` (Model) - The model being queried

**Returns:** void

**Behavior:**
- Exits early if model doesn't have `tenant_id` column
- Bypasses filtering for superadmin users
- Applies `tenant_id` filtering for admin/manager users
- Applies `tenant_id` + `property_id` filtering for tenant users

**Example:**
```php
// Automatically called by Eloquent
$properties = Property::all(); // Scope applied automatically
```

---

### extend()

Registers query builder macros for advanced scope control.

```php
public function extend(Builder $builder): void
```

**Parameters:**
- `$builder` (Builder) - The Eloquent query builder instance

**Returns:** void

**Registered Macros:**
1. `withoutHierarchicalScope()` - Bypass the scope
2. `forTenant($tenantId)` - Query specific tenant's data
3. `forProperty($propertyId)` - Query specific property's data

---

### clearColumnCache()

Clears cached column existence data for a specific table.

```php
public static function clearColumnCache(string $table): void
```

**Parameters:**
- `$table` (string) - The table name

**Returns:** void

**Use Case:** Call after migrations that modify table structure

**Example:**
```php
// After adding tenant_id column to a table
HierarchicalScope::clearColumnCache('new_table');
```

---

### clearAllColumnCaches()

Clears all cached column existence data.

```php
public static function clearAllColumnCaches(): void
```

**Parameters:** None

**Returns:** void

**Use Case:** Call after running multiple migrations

**Example:**
```php
// After running migrations
php artisan migrate
HierarchicalScope::clearAllColumnCaches();
```

---

## Query Builder Macros

### withoutHierarchicalScope()

Bypasses the hierarchical scope entirely.

```php
Model::withoutHierarchicalScope(): Builder
```

**Returns:** Builder - Query builder without the scope

**Authorization:** Should only be used by superadmin users

**Example:**
```php
// Superadmin viewing all properties across all tenants
if (auth()->user()->isSuperadmin()) {
    $allProperties = Property::withoutHierarchicalScope()->get();
}
```

**Security Warning:** ⚠️ Always check authorization before using this macro

---

### forTenant()

Queries data for a specific tenant, bypassing the authenticated user's tenant.

```php
Model::forTenant(int $tenantId): Builder
```

**Parameters:**
- `$tenantId` (int) - The tenant ID to query

**Returns:** Builder - Query builder filtered to the specified tenant

**Authorization:** Typically used by superadmin users

**Example:**
```php
// Superadmin viewing tenant 123's properties
$tenantProperties = Property::forTenant(123)->get();

// Can be combined with other query methods
$activeProperties = Property::forTenant(123)
    ->where('status', 'active')
    ->orderBy('created_at', 'desc')
    ->get();
```

---

### forProperty()

Queries data for a specific property.

```php
Model::forProperty(int $propertyId): Builder
```

**Parameters:**
- `$propertyId` (int) - The property ID to query

**Returns:** Builder - Query builder filtered to the specified property

**Special Handling:**
- For `properties` table: filters by `id`
- For tables with `property_id`: filters by `property_id`
- For other tables: returns unmodified builder

**Example:**
```php
// Admin viewing meters for property 456
$propertyMeters = Meter::forProperty(456)->get();

// Tenant viewing their own property's invoices
$invoices = Invoice::forProperty(auth()->user()->property_id)
    ->where('status', 'paid')
    ->get();
```

---

## Protected Methods

### applyPropertyFiltering()

Applies property-level filtering for tenant users.

```php
protected function applyPropertyFiltering(
    Builder $builder, 
    Model $model, 
    User $user
): void
```

**Parameters:**
- `$builder` (Builder) - The query builder instance
- `$model` (Model) - The model being queried
- `$user` (User) - The authenticated tenant user

**Returns:** void

**Internal Use:** Called automatically by `apply()` method

---

### hasTenantColumn()

Checks if a model has a `tenant_id` column.

```php
protected function hasTenantColumn(Model $model): bool
```

**Parameters:**
- `$model` (Model) - The model to check

**Returns:** bool - True if the model has a `tenant_id` column

**Performance:** Uses caching to avoid repeated schema queries

---

### hasPropertyColumn()

Checks if a model has a `property_id` column.

```php
protected function hasPropertyColumn(Model $model): bool
```

**Parameters:**
- `$model` (Model) - The model to check

**Returns:** bool - True if the model has a `property_id` column

**Performance:** Uses caching to avoid repeated schema queries

---

### hasColumn()

Generic column existence checker with caching.

```php
protected function hasColumn(Model $model, string $column): bool
```

**Parameters:**
- `$model` (Model) - The model to check
- `$column` (string) - The column name to check

**Returns:** bool - True if the column exists

**Performance:**
1. First checks fillable array (fast, no DB query)
2. Falls back to schema inspection (cached for 24 hours)

---

## Constants

### CACHE_PREFIX

Cache key prefix for column existence checks.

```php
private const CACHE_PREFIX = 'hierarchical_scope:columns:';
```

---

### CACHE_TTL

Cache TTL for column existence checks (24 hours).

```php
private const CACHE_TTL = 86400;
```

---

### TABLE_PROPERTIES

Constant for properties table name.

```php
private const TABLE_PROPERTIES = 'properties';
```

---

### TABLE_BUILDINGS

Constant for buildings table name.

```php
private const TABLE_BUILDINGS = 'buildings';
```

---

## Integration Examples

### With TenantContext

```php
use App\Services\TenantContext;

// Superadmin switching context to view another tenant's data
TenantContext::set(123);
$properties = Property::all(); // Filtered to tenant_id = 123

// Reset context
TenantContext::clear();
$properties = Property::all(); // Filtered to user's tenant_id
```

### With Policies

```php
// In a policy
public function viewAny(User $user): bool
{
    // Scope automatically filters results
    // Policy just checks if user can access the resource type
    return $user->isAdmin() || $user->isManager();
}

// In a controller
public function index()
{
    $this->authorize('viewAny', Property::class);
    
    // Scope automatically filters to user's tenant
    return Property::all();
}
```

### With Filament Resources

```php
use Filament\Resources\Resource;

class PropertyResource extends Resource
{
    public static function getEloquentQuery(): Builder
    {
        // Scope automatically applied
        return parent::getEloquentQuery();
    }
    
    // For superadmin panel
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        if (auth()->user()->isSuperadmin()) {
            // Bypass scope for superadmin
            return $query->withoutHierarchicalScope();
        }
        
        return $query;
    }
}
```

---

## Error Handling

The scope handles errors gracefully:

### No Authenticated User
```php
// Returns early without filtering
Property::all(); // No error, returns all properties (no filtering)
```

### User Without tenant_id
```php
// Returns early without filtering
Property::all(); // No error, returns all properties (no filtering)
```

### Model Without tenant_id Column
```php
// Returns early without filtering
ModelWithoutTenantId::all(); // No error, no filtering applied
```

---

## Performance Considerations

### Column Existence Caching

The scope caches column existence checks to minimize database queries:

```php
// First query: checks schema and caches
Property::all(); // 1 schema query + 1 data query

// Subsequent queries: uses cache
Property::where('status', 'active')->get(); // 1 data query only
Meter::all(); // 1 data query only
```

**Cache Invalidation:**
```php
// After migrations
HierarchicalScope::clearColumnCache('properties');

// Or clear all
HierarchicalScope::clearAllColumnCaches();
```

### Query Optimization

The scope adds minimal overhead to queries:

```sql
-- Without scope
SELECT * FROM properties;

-- With scope (admin user)
SELECT * FROM properties WHERE tenant_id = 123;

-- With scope (tenant user)
SELECT * FROM properties WHERE id = 456;
```

---

## Testing API

### Test Helpers

```php
use Tests\TestCase;

class MyTest extends TestCase
{
    /** @test */
    public function it_filters_by_tenant()
    {
        $admin = User::factory()->admin()->create(['tenant_id' => 1]);
        $property = Property::factory()->create(['tenant_id' => 1]);
        
        $this->actingAs($admin);
        
        $properties = Property::all();
        
        $this->assertCount(1, $properties);
        $this->assertEquals($property->id, $properties->first()->id);
    }
    
    /** @test */
    public function superadmin_bypasses_scope()
    {
        $superadmin = User::factory()->superadmin()->create();
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $property2 = Property::factory()->create(['tenant_id' => 2]);
        
        $this->actingAs($superadmin);
        
        $properties = Property::all();
        
        $this->assertCount(2, $properties);
    }
}
```

---

## Related APIs

- [TenantContext Service API](./TENANT_CONTEXT_API.md)
- [BelongsToTenant Trait API](./BELONGS_TO_TENANT_API.md)
- [User Model API](USER_MODEL_API.md)
- [Authorization Policies API](./POLICIES_API.md)
