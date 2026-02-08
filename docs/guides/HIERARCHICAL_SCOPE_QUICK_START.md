# HierarchicalScope Quick Start Guide

## 5-Minute Overview

The `HierarchicalScope` automatically filters your Eloquent queries based on the authenticated user's role and tenant assignment. No manual filtering required!

## Basic Usage

### 1. Apply to Your Model

```php
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use BelongsToTenant; // This applies HierarchicalScope
    
    protected $fillable = ['tenant_id', 'address', 'type'];
}
```

### 2. Query Normally

```php
// In your controller
public function index()
{
    // Automatically filtered by user's tenant!
    $properties = Property::all();
    
    return view('properties.index', compact('properties'));
}
```

That's it! The scope handles everything automatically.

---

## What Gets Filtered?

### For Superadmin Users
```php
Property::all(); // Returns ALL properties (no filtering)
```

### For Admin/Manager Users
```php
Property::all(); // Returns only properties where tenant_id = user's tenant_id
```

### For Tenant Users
```php
Property::all(); // Returns only the property where id = user's property_id
Meter::all(); // Returns only meters where tenant_id = user's tenant_id AND property_id = user's property_id
```

---

## Common Scenarios

### Scenario 1: Admin Viewing Their Properties

```php
// In AdminController
public function dashboard()
{
    // Automatically filtered to admin's tenant
    $properties = Property::with('meters')->get();
    $totalMeters = Meter::count(); // Only counts admin's tenant's meters
    
    return view('admin.dashboard', compact('properties', 'totalMeters'));
}
```

### Scenario 2: Tenant Viewing Their Data

```php
// In TenantController
public function myProperty()
{
    // Automatically filtered to tenant's property
    $property = Property::first(); // Their assigned property
    $meters = Meter::all(); // Only their property's meters
    $invoices = Invoice::latest()->get(); // Only their invoices
    
    return view('tenant.property', compact('property', 'meters', 'invoices'));
}
```

### Scenario 3: Superadmin Viewing All Data

```php
// In SuperadminController
public function systemReport()
{
    // No filtering - sees everything
    $allProperties = Property::count();
    $allTenants = User::where('role', 'tenant')->count();
    
    return view('superadmin.report', compact('allProperties', 'allTenants'));
}
```

---

## Advanced Usage

### Bypass the Scope (Superadmin Only)

```php
// Check authorization first!
if (auth()->user()->isSuperadmin()) {
    $allProperties = Property::withoutHierarchicalScope()->get();
}
```

### Query Another Tenant's Data

```php
// Superadmin viewing specific tenant
$tenantProperties = Property::forTenant(123)->get();
```

### Query Specific Property

```php
// Admin viewing specific property's meters
$propertyMeters = Meter::forProperty(456)->get();
```

---

## Troubleshooting

### Problem: Users See Data from Other Tenants

**Solution:** Ensure your model uses the `BelongsToTenant` trait:

```php
class YourModel extends Model
{
    use BelongsToTenant; // Add this!
}
```

### Problem: Scope Not Working

**Checklist:**
- ✅ Model uses `BelongsToTenant` trait
- ✅ User is authenticated (`auth()->check()`)
- ✅ User has `tenant_id` set
- ✅ Table has `tenant_id` column

### Problem: Performance Issues

**Solution:** Clear cache after migrations:

```bash
php artisan migrate
php artisan tinker
>>> HierarchicalScope::clearAllColumnCaches();
```

---

## Testing Your Scope

```php
use Tests\TestCase;

class PropertyTest extends TestCase
{
    /** @test */
    public function admin_only_sees_their_properties()
    {
        $admin1 = User::factory()->admin()->create(['tenant_id' => 1]);
        $admin2 = User::factory()->admin()->create(['tenant_id' => 2]);
        
        $property1 = Property::factory()->create(['tenant_id' => 1]);
        $property2 = Property::factory()->create(['tenant_id' => 2]);
        
        $this->actingAs($admin1);
        
        $properties = Property::all();
        
        $this->assertCount(1, $properties);
        $this->assertEquals($property1->id, $properties->first()->id);
    }
}
```

---

## Best Practices

### ✅ DO

```php
// Use Eloquent queries (scope applied automatically)
$properties = Property::where('status', 'active')->get();

// Check authorization before bypassing scope
if (auth()->user()->isSuperadmin()) {
    $allData = Model::withoutHierarchicalScope()->get();
}

// Use policies for authorization
$this->authorize('viewAny', Property::class);
```

### ❌ DON'T

```php
// Don't use raw queries (bypasses scope)
DB::table('properties')->get(); // DANGEROUS!

// Don't bypass scope without authorization
Model::withoutHierarchicalScope()->get(); // DANGEROUS!

// Don't forget to apply trait to models
class Property extends Model {
    // Missing: use BelongsToTenant;
}
```

---

## Integration with Filament

```php
use Filament\Resources\Resource;

class PropertyResource extends Resource
{
    // Scope automatically applied!
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
        // No manual filtering needed
    }
}
```

---

## Next Steps

- 📖 Read the [full documentation](../architecture/HIERARCHICAL_SCOPE.md)
- 🔧 Check the [API reference](../api/HIERARCHICAL_SCOPE_API.md)
- 🧪 Review [test examples](../../tests/Feature/HierarchicalScopeTest.php)
- 🏗️ Learn about [multi-tenancy architecture](../architecture/MULTI_TENANCY.md)

---

## Quick Reference

| User Role | Filtering Applied |
|-----------|------------------|
| Superadmin | None (sees all data) |
| Admin/Manager | `tenant_id = user's tenant_id` |
| Tenant | `tenant_id = user's tenant_id AND property_id = user's property_id` |

| Macro | Purpose | Authorization |
|-------|---------|---------------|
| `withoutHierarchicalScope()` | Bypass scope | Superadmin only |
| `forTenant($id)` | Query specific tenant | Superadmin typically |
| `forProperty($id)` | Query specific property | Admin/Superadmin |

---

## Support

- 🐛 Found a bug? Check [tests/Feature/HierarchicalScopeTest.php](../../tests/Feature/HierarchicalScopeTest.php)
- 📝 Need more examples? See [docs/architecture/HIERARCHICAL_SCOPE.md](../architecture/HIERARCHICAL_SCOPE.md)
- 🔒 Security concerns? Review [docs/security/MULTI_TENANCY_SECURITY.md](../security/MULTI_TENANCY_SECURITY.md)
