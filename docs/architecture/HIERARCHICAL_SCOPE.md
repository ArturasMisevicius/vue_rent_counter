# HierarchicalScope Documentation

## Overview

`HierarchicalScope` is a global Eloquent scope that enforces multi-tenant data isolation by automatically filtering queries based on the authenticated user's role and tenant assignment. It's a critical security component that prevents cross-tenant data leakage.

## Purpose

- **Data Isolation**: Ensures users only see data belonging to their tenant
- **Role-Based Filtering**: Applies different filtering rules based on user roles
- **Property-Level Access**: Restricts tenant users to their assigned property
- **Performance Optimization**: Caches column existence checks to minimize database queries

## Architecture

### Class Location
```
app/Scopes/HierarchicalScope.php
```

### Dependencies
- `App\Enums\UserRole` - User role enumeration
- `App\Models\User` - User model with role and tenant information
- `App\Services\TenantContext` - Explicit tenant context management
- `Illuminate\Support\Facades\Cache` - Column existence caching
- `Illuminate\Support\Facades\Schema` - Database schema inspection

### Related Components
- `App\Traits\BelongsToTenant` - Trait that applies this scope to models
- `App\Scopes\TenantScope` - Legacy tenant scope (being replaced)
- `App\Services\TenantContext` - Service for explicit tenant switching

## Filtering Rules

### Superadmin (No Filtering)
```php
// Superadmins see ALL data across ALL tenants
User::all(); // Returns all users in the system
```

**Requirement 12.2**: WHEN a Superadmin queries data THEN the System SHALL bypass tenant_id filtering

### Admin/Manager (Tenant Filtering)
```php
// Admins/Managers see only their tenant's data
Property::all(); // Returns only properties where tenant_id = user's tenant_id
```

**Requirement 12.3**: WHEN an Admin queries data THEN the System SHALL filter to their tenant_id

### Tenant (Tenant + Property Filtering)
```php
// Tenants see only their tenant's data AND their assigned property
Property::all(); // Returns only the property where id = user's property_id
Meter::all(); // Returns only meters where tenant_id = user's tenant_id AND property_id = user's property_id
```

**Requirement 12.4**: WHEN a User queries data THEN the System SHALL filter to their tenant_id and assigned property

## Special Table Handling

### Properties Table
For tenant users querying the `properties` table, the scope filters by `id` instead of `property_id`:

```php
// For tenant users
Property::all(); // WHERE id = user's property_id
```

### Buildings Table
Buildings are filtered via their relationship to properties:

```php
// For tenant users
Building::all(); // WHERE EXISTS (SELECT * FROM properties WHERE buildings.id = properties.building_id AND properties.id = user's property_id)
```

### Standard Tables
Tables with `property_id` column are filtered directly:

```php
// For tenant users
Meter::all(); // WHERE tenant_id = user's tenant_id AND property_id = user's property_id
```

## Query Builder Macros

The scope registers three macros for advanced use cases:

### withoutHierarchicalScope()
Bypass the scope entirely (use with caution):

```php
// Superadmin-only operation
$allProperties = Property::withoutHierarchicalScope()->get();
```

### forTenant($tenantId)
Query data for a specific tenant:

```php
// Superadmin viewing another tenant's data
$tenantProperties = Property::forTenant(123)->get();
```

### forProperty($propertyId)
Query data for a specific property:

```php
// Admin viewing a specific property's meters
$propertyMeters = Meter::forProperty(456)->get();
```

## Performance Optimization

**See also**: [HierarchicalScope Performance Optimization](../performance/HIERARCHICAL_SCOPE_OPTIMIZATION.md) for detailed performance analysis and metrics.

### Column Existence Caching

The scope caches column existence checks to avoid repeated schema queries:

```php
// First query: checks schema and caches result
Property::all(); // Schema::hasColumn('properties', 'tenant_id') -> cached

// Subsequent queries: uses cached result
Property::where('address', 'like', '%Vilnius%')->get(); // Uses cache
```

**Cache Configuration**:
- **Cache Key Prefix**: `hierarchical_scope:columns:`
- **Cache TTL**: 86400 seconds (24 hours)
- **Cache Driver**: Uses default Laravel cache driver

### Cache Management

Clear cache after migrations or schema changes:

```php
// Clear cache for specific table
HierarchicalScope::clearColumnCache('properties');

// Clear all column caches
HierarchicalScope::clearAllColumnCaches();
```

## Integration with TenantContext

The scope integrates with `TenantContext` for explicit tenant switching:

```php
// Superadmin switching to view another tenant's data
TenantContext::set(123);
$properties = Property::all(); // Filtered to tenant_id = 123

// Reset to user's own tenant
TenantContext::clear();
$properties = Property::all(); // Filtered to user's tenant_id
```

## Usage Examples

### Basic Model Query
```php
// In a controller
public function index()
{
    // Automatically filtered by HierarchicalScope
    $properties = Property::all();
    
    return view('properties.index', compact('properties'));
}
```

### Bypassing the Scope (Superadmin Only)
```php
// In a superadmin controller
public function systemWideReport()
{
    // Get all properties across all tenants
    $allProperties = Property::withoutHierarchicalScope()->get();
    
    return view('superadmin.reports.properties', compact('allProperties'));
}
```

### Querying Another Tenant's Data
```php
// In a superadmin controller
public function viewTenantData($tenantId)
{
    // View specific tenant's properties
    $properties = Property::forTenant($tenantId)->get();
    
    return view('superadmin.tenant-data', compact('properties'));
}
```

### Querying Specific Property Data
```php
// In an admin controller
public function propertyDetails($propertyId)
{
    // Get meters for specific property
    $meters = Meter::forProperty($propertyId)->get();
    
    return view('admin.property-details', compact('meters'));
}
```

## Testing

### Test Coverage
The scope is tested in `tests/Feature/HierarchicalScopeTest.php`:

- ✅ Superadmin unrestricted access
- ✅ Admin tenant isolation
- ✅ Tenant property isolation
- ✅ Tenant meter filtering
- ✅ Admin building filtering
- ✅ Scope macros functionality
- ✅ Column cache performance

### Running Tests
```bash
php artisan test --filter=HierarchicalScopeTest
```

## Security Considerations

### Critical Security Component
This scope is a **critical security component** that prevents cross-tenant data leakage. Any modifications must be:

1. **Thoroughly tested** with property-based tests
2. **Reviewed by security team** before deployment
3. **Documented** with clear rationale
4. **Verified** in production-like environment

### Common Pitfalls

❌ **Don't bypass the scope without authorization checks**:
```php
// DANGEROUS: No authorization check
$allData = Model::withoutHierarchicalScope()->get();
```

✅ **Always check authorization first**:
```php
// SAFE: Authorization check before bypassing scope
if (auth()->user()->isSuperadmin()) {
    $allData = Model::withoutHierarchicalScope()->get();
}
```

❌ **Don't use raw queries without tenant filtering**:
```php
// DANGEROUS: Bypasses scope
DB::table('properties')->get();
```

✅ **Use Eloquent queries with scope**:
```php
// SAFE: Scope automatically applied
Property::all();
```

## Troubleshooting

### Scope Not Applied
**Symptom**: Users see data from other tenants

**Causes**:
1. Model doesn't use `BelongsToTenant` trait
2. User not authenticated
3. User's `tenant_id` is null

**Solution**:
```php
// Ensure model uses trait
class Property extends Model
{
    use BelongsToTenant; // This applies HierarchicalScope
}

// Ensure user is authenticated and has tenant_id
if (auth()->check() && auth()->user()->tenant_id) {
    // Scope will be applied
}
```

### Performance Issues
**Symptom**: Slow queries with many schema checks

**Causes**:
1. Cache not configured
2. Cache cleared too frequently
3. Many different models queried

**Solution**:
```php
// Ensure cache is configured in config/cache.php
'default' => env('CACHE_DRIVER', 'redis'),

// Clear cache only after migrations
php artisan migrate
HierarchicalScope::clearAllColumnCaches();
```

### Incorrect Filtering
**Symptom**: Tenant users see wrong properties

**Causes**:
1. User's `property_id` is null
2. Property assignment changed but session not refreshed
3. TenantContext set incorrectly

**Solution**:
```php
// Ensure user has property_id
$user = auth()->user();
if (!$user->property_id) {
    // Assign property or show error
}

// Clear TenantContext if needed
TenantContext::clear();
```

## Migration Guide

### From TenantScope to HierarchicalScope

If migrating from the legacy `TenantScope`:

1. **Update model trait**:
```php
// Old
use TenantScoped;

// New
use BelongsToTenant;
```

2. **Update scope bypass**:
```php
// Old
Model::withoutGlobalScope('tenant')->get();

// New
Model::withoutHierarchicalScope()->get();
```

3. **Update tests**:
```php
// Old
Model::withoutGlobalScopes()->get();

// New
Model::withoutHierarchicalScope()->get();
```

## Related Documentation

- [Multi-Tenancy Architecture](./MULTI_TENANCY.md)
- [TenantContext Service](../services/TENANT_CONTEXT.md)
- [BelongsToTenant Trait](../traits/BELONGS_TO_TENANT.md)
- [Authorization Policies](../policies/README.md)
- [Hierarchical User Management Spec](../../.kiro/specs/3-hierarchical-user-management/README.md)

## Changelog

### Version 2.0 (Current)
- Added `TenantContext` integration
- Implemented column existence caching
- Added query builder macros
- Improved property-level filtering
- Added special handling for buildings table
- Enhanced documentation and type hints

### Version 1.0 (Legacy)
- Basic tenant_id filtering
- Role-based access control
- Property-level filtering for tenants
