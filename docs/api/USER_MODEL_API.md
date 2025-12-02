# User Model API Documentation

**Model**: `App\Models\User`  
**Purpose**: Hierarchical user management with role-based access control  
**Laravel Version**: 12.x  
**Last Updated**: 2025-12-02

---

## Overview

The User model implements a three-tier hierarchical system for managing users across the Vilnius Utilities Billing platform:

- **SUPERADMIN**: System owner with full access across all organizations
- **ADMIN**: Property owner managing their portfolio within tenant_id scope
- **MANAGER**: Legacy role with admin-level permissions (deprecated, use ADMIN)
- **TENANT**: Apartment resident with access limited to their assigned property

---

## Model Properties

### Core Attributes

| Property | Type | Description | Nullable |
|----------|------|-------------|----------|
| `id` | int | Primary key | No |
| `tenant_id` | int | Organization identifier for data isolation (null for Superadmin) | Yes |
| `property_id` | int | Assigned property for Tenant role | Yes |
| `parent_user_id` | int | Admin who created this user (for Tenant role) | Yes |
| `name` | string | User's full name | No |
| `email` | string | Unique email address | No |
| `password` | string | Hashed password | No |
| `role` | UserRole | User role enum (superadmin, admin, manager, tenant) | No |
| `is_active` | boolean | Account activation status | No |
| `organization_name` | string | Organization name (for Admin role) | Yes |
| `email_verified_at` | Carbon | Email verification timestamp | Yes |
| `created_at` | Carbon | Creation timestamp | No |
| `updated_at` | Carbon | Last update timestamp | No |

### Relationships

| Relationship | Type | Description |
|--------------|------|-------------|
| `property` | BelongsTo | Assigned property (for Tenant role) |
| `parentUser` | BelongsTo | Admin who created this user |
| `childUsers` | HasMany | Tenants created by this Admin |
| `subscription` | HasOne | Subscription (for Admin role) |
| `properties` | HasMany | Properties managed by this Admin |
| `buildings` | HasMany | Buildings managed by this Admin |
| `invoices` | HasMany | Invoices for this Admin's organization |
| `meterReadings` | HasMany | Meter readings entered by this user |
| `meterReadingAudits` | HasMany | Meter reading audits created by this user |
| `tenant` | HasOne | Tenant (renter) associated with this user |

---

## Authorization Methods

### `canAccessPanel(Panel $panel): bool`

**Purpose**: Primary authorization gate for Filament panel access

**Parameters**:
- `$panel` (Panel): The Filament panel being accessed

**Returns**: `bool` - True if user can access the panel, false otherwise

**Authorization Rules**:
- Admin Panel (`admin`): ADMIN, MANAGER, SUPERADMIN roles only
- Other Panels: SUPERADMIN only
- TENANT role: Explicitly denied access to all panels
- Inactive users: Denied access to all panels

**Requirements**: 9.1, 9.2, 9.3

**Example**:
```php
use Filament\Facades\Filament;

$user = auth()->user();
$panel = Filament::getPanel('admin');

if ($user->canAccessPanel($panel)) {
    // User can access admin panel
} else {
    // Access denied
}
```

**Security Notes**:
- Works in conjunction with `EnsureUserIsAdminOrManager` middleware
- Provides defense-in-depth security
- Always checks `is_active` status first
- Uses strict comparison (`===`) for role checks

---

## Role Helper Methods

### `isSuperadmin(): bool`

**Purpose**: Check if user has SUPERADMIN role

**Returns**: `bool` - True if user is superadmin

**Example**:
```php
if ($user->isSuperadmin()) {
    // Superadmin-only logic
}
```

### `isAdmin(): bool`

**Purpose**: Check if user has ADMIN role

**Returns**: `bool` - True if user is admin

**Example**:
```php
if ($user->isAdmin()) {
    // Admin-only logic
}
```

### `isManager(): bool`

**Purpose**: Check if user has MANAGER role

**Returns**: `bool` - True if user is manager

**Example**:
```php
if ($user->isManager()) {
    // Manager-only logic
}
```

### `isTenantUser(): bool`

**Purpose**: Check if user has TENANT role

**Returns**: `bool` - True if user is tenant

**Example**:
```php
if ($user->isTenantUser()) {
    // Tenant-only logic
}
```

---

## Query Scopes

### `scopeOrderedByRole($query)`

**Purpose**: Order users by role priority (superadmin → admin → manager → tenant)

**Example**:
```php
$users = User::orderedByRole()->get();
```

### `scopeActive($query)`

**Purpose**: Filter only active users

**Example**:
```php
$activeUsers = User::active()->get();
```

---

## Usage Examples

### Creating Users

#### Create Superadmin

```php
use App\Enums\UserRole;

$superadmin = User::create([
    'name' => 'System Administrator',
    'email' => 'admin@system.com',
    'password' => bcrypt('secure-password'),
    'role' => UserRole::SUPERADMIN,
    'is_active' => true,
    'tenant_id' => null, // No tenant isolation
]);
```

#### Create Admin (Property Owner)

```php
$admin = User::create([
    'name' => 'Property Owner',
    'email' => 'owner@example.com',
    'password' => bcrypt('secure-password'),
    'role' => UserRole::ADMIN,
    'is_active' => true,
    'tenant_id' => 1, // Unique organization ID
    'organization_name' => 'ABC Properties',
]);
```

#### Create Tenant

```php
$tenant = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('secure-password'),
    'role' => UserRole::TENANT,
    'is_active' => true,
    'tenant_id' => 1, // Inherited from Admin
    'property_id' => 5, // Assigned property
    'parent_user_id' => $admin->id, // Admin who created this tenant
]);
```

### Checking Authorization

```php
use Filament\Facades\Filament;

$user = auth()->user();
$panel = Filament::getPanel('admin');

// Check panel access
if ($user->canAccessPanel($panel)) {
    // Allow access
} else {
    abort(403, 'Unauthorized access to admin panel');
}

// Check role-specific permissions
if ($user->isAdmin() || $user->isManager()) {
    // Admin/Manager logic
} elseif ($user->isTenantUser()) {
    // Tenant logic
}
```

### Querying Users

```php
// Get all active admins
$admins = User::where('role', UserRole::ADMIN)
    ->active()
    ->orderedByRole()
    ->get();

// Get tenants for a specific admin
$tenants = User::where('parent_user_id', $admin->id)
    ->where('role', UserRole::TENANT)
    ->active()
    ->get();

// Get users with their properties
$users = User::with(['property', 'parentUser', 'subscription'])
    ->active()
    ->get();
```

---

## Security Considerations

### Multi-Tenancy

- **Tenant Isolation**: Admin and Tenant users are isolated by `tenant_id`
- **No Global Scope**: User model does NOT apply `BelongsToTenant` scope to avoid circular dependency during authentication
- **Controller-Level Filtering**: User filtering is handled through policies and controller-level authorization

### Authorization Layers

1. **Model Level**: `canAccessPanel()` method
2. **Middleware Level**: `EnsureUserIsAdminOrManager` middleware
3. **Policy Level**: User policies for CRUD operations
4. **Gate Level**: `access-admin-panel` gate definition

### Best Practices

1. ✅ Always check `is_active` status before granting access
2. ✅ Use role helper methods for cleaner code
3. ✅ Implement policies for all user operations
4. ✅ Log authorization failures for security monitoring
5. ✅ Never bypass authorization checks "temporarily"

---

## Testing

### Unit Tests

```php
use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRole;
use Filament\Facades\Filament;

class UserAuthorizationTest extends TestCase
{
    /** @test */
    public function superadmin_can_access_admin_panel()
    {
        $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);
        $panel = Filament::getPanel('admin');
        
        $this->assertTrue($user->canAccessPanel($panel));
    }
    
    /** @test */
    public function tenant_cannot_access_admin_panel()
    {
        $user = User::factory()->create(['role' => UserRole::TENANT]);
        $panel = Filament::getPanel('admin');
        
        $this->assertFalse($user->canAccessPanel($panel));
    }
    
    /** @test */
    public function inactive_user_cannot_access_admin_panel()
    {
        $user = User::factory()->create([
            'role' => UserRole::ADMIN,
            'is_active' => false,
        ]);
        $panel = Filament::getPanel('admin');
        
        $this->assertFalse($user->canAccessPanel($panel));
    }
}
```

### Feature Tests

```php
/** @test */
public function tenant_is_blocked_from_admin_routes()
{
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    
    $this->actingAs($tenant)
        ->get('/admin')
        ->assertForbidden();
}

/** @test */
public function admin_can_access_admin_routes()
{
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    
    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful();
}
```

---

## Related Documentation

- [Authorization Quick Reference](../security/AUTHORIZATION_QUICK_REFERENCE.md)
- [Security Incident Report](../security/SECURITY_INCIDENT_2025_12_02.md)
- [Multi-Tenancy Architecture](../architecture/MULTI_TENANCY.md)
- [User Policies](../policies/USER_POLICY.md)
- [Filament Admin Panel](../admin/ADMIN_PANEL_GUIDE.md)

---

## Changelog

### 2025-12-02
- ✅ Fixed critical security vulnerability in `canAccessPanel()`
- ✅ Added `is_active` check to prevent deactivated users from accessing panels
- ✅ Enhanced documentation with security notes and examples
- ✅ Added comprehensive test coverage

---

**Maintained by**: Development Team  
**Security Contact**: security@example.com  
**Last Security Audit**: 2025-12-02
