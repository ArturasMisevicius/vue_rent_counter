# UserPolicy API Reference

## Overview

The `UserPolicy` enforces hierarchical authorization for user management operations in the multi-tenant Vilnius Utilities Billing Platform. It implements role-based access control with strict tenant boundaries and comprehensive audit logging.

**Location**: `app/Policies/UserPolicy.php`  
**Requirements**: 13.1, 13.2, 13.3, 13.4  
**Related**: `HierarchicalScope`, `User` model, `UserRole` enum

---

## Authorization Matrix

| Action | Superadmin | Admin/Manager | Tenant | Notes |
|--------|-----------|---------------|--------|-------|
| `viewAny` | ✅ All users | ✅ Tenant users only | ❌ | List users |
| `view` | ✅ Any user | ✅ Same tenant | ✅ Self only | View user details |
| `create` | ✅ | ✅ | ❌ | Create new users |
| `update` | ✅ Any user | ✅ Same tenant | ✅ Self only | Update user |
| `delete` | ✅ Any user (not self) | ✅ Same tenant (not self) | ❌ | Soft delete |
| `restore` | ✅ Any user | ✅ Same tenant | ❌ | Restore soft-deleted |
| `forceDelete` | ✅ Any user (not self) | ❌ | ❌ | Permanent delete |
| `replicate` | ✅ | ❌ | ❌ | Duplicate user record |
| `impersonate` | ✅ Any user (not self) | ❌ | ❌ | Impersonate user |

---

## Public Methods

### viewAny()

Determine if the user can view the users list.

```php
public function viewAny(User $user): bool
```

**Parameters:**
- `$user` (User) - The authenticated user

**Returns:** `bool` - True if user can view users list

**Authorization:**
- ✅ Superadmin: Can view all users
- ✅ Admin/Manager: Can view users in their tenant
- ❌ Tenant: Cannot view users list

**Example:**
```php
// In controller
if (auth()->user()->can('viewAny', User::class)) {
    $users = User::all(); // Automatically scoped by HierarchicalScope
}
```

**Requirements:** 13.1

---

### view()

Determine if the user can view a specific user.

```php
public function view(User $user, User $model): bool
```

**Parameters:**
- `$user` (User) - The authenticated user
- `$model` (User) - The user being viewed

**Returns:** `bool` - True if user can view the model

**Authorization:**
- ✅ Superadmin: Can view any user
- ✅ Admin/Manager: Can view users in same tenant
- ✅ Any user: Can view themselves
- ❌ Tenant: Cannot view other users

**Example:**
```php
// In controller
$targetUser = User::find($id);

if (auth()->user()->can('view', $targetUser)) {
    return view('users.show', compact('targetUser'));
}

abort(403);
```

**Requirements:** 13.1, 13.3

---

### create()

Determine if the user can create new users.

```php
public function create(User $user): bool
```

**Parameters:**
- `$user` (User) - The authenticated user

**Returns:** `bool` - True if user can create users

**Authorization:**
- ✅ Superadmin: Can create admin users
- ✅ Admin/Manager: Can create tenant users
- ❌ Tenant: Cannot create users

**Example:**
```php
// In Filament UserResource
public static function canCreate(): bool
{
    return auth()->user()->can('create', User::class);
}
```

**Requirements:** 13.1, 13.2

---

### update()

Determine if the user can update a specific user.

```php
public function update(User $user, User $model): bool
```

**Parameters:**
- `$user` (User) - The authenticated user
- `$model` (User) - The user being updated

**Returns:** `bool` - True if user can update the model

**Authorization:**
- ✅ Superadmin: Can update any user (logged)
- ✅ Admin/Manager: Can update users in same tenant (logged)
- ✅ Any user: Can update themselves
- ❌ Tenant: Cannot update other users

**Audit Logging:** Logs superadmin and admin/manager updates to audit channel

**Example:**
```php
// In controller
$targetUser = User::find($id);

if (auth()->user()->can('update', $targetUser)) {
    $targetUser->update($request->validated());
    // Audit log automatically created if admin/superadmin
}
```

**Requirements:** 13.1, 13.3, 13.4

---

### delete()

Determine if the user can soft delete a specific user.

```php
public function delete(User $user, User $model): bool
```

**Parameters:**
- `$user` (User) - The authenticated user
- `$model` (User) - The user being deleted

**Returns:** `bool` - True if user can delete the model

**Authorization:**
- ✅ Superadmin: Can delete any user except themselves (logged)
- ✅ Admin/Manager: Can delete users in same tenant except themselves (logged)
- ❌ Any user: Cannot delete themselves
- ❌ Tenant: Cannot delete users

**Audit Logging:** All delete operations are logged to audit channel

**Example:**
```php
// In controller
$targetUser = User::find($id);

if (auth()->user()->can('delete', $targetUser)) {
    $targetUser->delete(); // Soft delete
    // Audit log automatically created
}
```

**Requirements:** 13.1, 13.3, 13.4

---

### restore()

Determine if the user can restore a soft-deleted user.

```php
public function restore(User $user, User $model): bool
```

**Parameters:**
- `$user` (User) - The authenticated user
- `$model` (User) - The user being restored

**Returns:** `bool` - True if user can restore the model

**Authorization:**
- ✅ Superadmin: Can restore any user (logged)
- ✅ Admin/Manager: Can restore users in same tenant (logged)
- ❌ Tenant: Cannot restore users

**Audit Logging:** All restore operations are logged to audit channel

**Example:**
```php
// In controller
$deletedUser = User::onlyTrashed()->find($id);

if (auth()->user()->can('restore', $deletedUser)) {
    $deletedUser->restore();
    // Audit log automatically created
}
```

**Requirements:** 13.1, 13.3

---

### forceDelete()

Determine if the user can permanently delete a user.

```php
public function forceDelete(User $user, User $model): bool
```

**Parameters:**
- `$user` (User) - The authenticated user
- `$model` (User) - The user being force deleted

**Returns:** `bool` - True if user can force delete the model

**Authorization:**
- ✅ Superadmin: Can force delete any user except themselves (logged)
- ❌ Admin/Manager: Cannot force delete
- ❌ Tenant: Cannot force delete

**Audit Logging:** All force delete operations are logged to audit channel

**Example:**
```php
// In controller (superadmin only)
$targetUser = User::withTrashed()->find($id);

if (auth()->user()->can('forceDelete', $targetUser)) {
    $targetUser->forceDelete(); // Permanent deletion
    // Audit log automatically created
}
```

**Requirements:** 13.1

---

### replicate()

Determine if the user can duplicate a user record (Filament feature).

```php
public function replicate(User $user, User $model): bool
```

**Parameters:**
- `$user` (User) - The authenticated user
- `$model` (User) - The user being replicated

**Returns:** `bool` - True if user can replicate the model

**Authorization:**
- ✅ Superadmin: Can replicate users
- ❌ Admin/Manager: Cannot replicate
- ❌ Tenant: Cannot replicate

**Example:**
```php
// In Filament UserResource
public static function canReplicate(Model $record): bool
{
    return auth()->user()->can('replicate', $record);
}
```

---

### impersonate()

Determine if the user can impersonate another user (support/debugging).

```php
public function impersonate(User $user, User $model): bool
```

**Parameters:**
- `$user` (User) - The authenticated user
- `$model` (User) - The user to impersonate

**Returns:** `bool` - True if user can impersonate the model

**Authorization:**
- ✅ Superadmin: Can impersonate any user except themselves (logged)
- ❌ Admin/Manager: Cannot impersonate
- ❌ Tenant: Cannot impersonate
- ❌ Any user: Cannot impersonate themselves

**Audit Logging:** All impersonation attempts are logged to audit channel

**Example:**
```php
// In controller (superadmin only)
$targetUser = User::find($id);

if (auth()->user()->can('impersonate', $targetUser)) {
    auth()->user()->impersonate($targetUser);
    // Audit log automatically created
}
```

---

## Private Helper Methods

### isSameTenant()

Check if two users belong to the same tenant.

```php
private function isSameTenant(User $user, User $model): bool
```

**Parameters:**
- `$user` (User) - The first user
- `$model` (User) - The second user

**Returns:** `bool` - True if both users have the same non-null tenant_id

**Logic:**
- Both users must have `tenant_id` set (not null)
- Both `tenant_id` values must match

---

### logSensitiveOperation()

Log sensitive user management operations for audit compliance.

```php
private function logSensitiveOperation(
    string $operation, 
    User $user, 
    User $model
): void
```

**Parameters:**
- `$operation` (string) - The operation being performed
- `$user` (User) - The authenticated user performing the operation
- `$model` (User) - The target user

**Returns:** void

**Logged Data:**
- Operation type
- Actor ID, email, role, tenant_id
- Target ID, email, role, tenant_id
- IP address
- User agent
- ISO 8601 timestamp

**Log Channel:** `audit` (configured in `config/logging.php`)

**Example Log Entry:**
```json
{
  "operation": "delete",
  "actor_id": 1,
  "actor_email": "admin@example.com",
  "actor_role": "admin",
  "target_id": 42,
  "target_email": "tenant@example.com",
  "target_role": "tenant",
  "actor_tenant_id": 1,
  "target_tenant_id": 1,
  "ip": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "timestamp": "2024-11-26T10:30:00+00:00"
}
```

---

## Usage Examples

### Controller Usage

```php
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        // Check viewAny permission
        $this->authorize('viewAny', User::class);
        
        // HierarchicalScope automatically filters by tenant
        $users = User::paginate(20);
        
        return view('users.index', compact('users'));
    }
    
    public function show(User $user)
    {
        // Check view permission
        $this->authorize('view', $user);
        
        return view('users.show', compact('user'));
    }
    
    public function update(Request $request, User $user)
    {
        // Check update permission (logs if admin/superadmin)
        $this->authorize('update', $user);
        
        $user->update($request->validated());
        
        return redirect()->route('users.show', $user)
            ->with('success', 'User updated successfully');
    }
    
    public function destroy(User $user)
    {
        // Check delete permission (logs operation)
        $this->authorize('delete', $user);
        
        $user->delete();
        
        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully');
    }
}
```

### Filament Resource Usage

```php
use App\Models\User;
use Filament\Resources\Resource;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', User::class);
    }
    
    public static function canCreate(): bool
    {
        return auth()->user()->can('create', User::class);
    }
    
    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update', $record);
    }
    
    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete', $record);
    }
    
    public static function canForceDelete(Model $record): bool
    {
        return auth()->user()->can('forceDelete', $record);
    }
    
    public static function canRestore(Model $record): bool
    {
        return auth()->user()->can('restore', $record);
    }
}
```

### Blade Template Usage

```blade
@can('viewAny', App\Models\User::class)
    <a href="{{ route('users.index') }}">Manage Users</a>
@endcan

@can('update', $user)
    <a href="{{ route('users.edit', $user) }}">Edit User</a>
@endcan

@can('delete', $user)
    <form action="{{ route('users.destroy', $user) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit">Delete User</button>
    </form>
@endcan
```

---

## Security Considerations

### Tenant Isolation

The policy enforces strict tenant boundaries:

1. **Admin/Manager Restrictions**: Can only manage users within their `tenant_id`
2. **Tenant Restrictions**: Can only view/update their own profile
3. **Superadmin Bypass**: Has unrestricted access but all operations are logged

### Self-Protection

Users cannot perform destructive operations on themselves:

- ❌ Cannot delete themselves
- ❌ Cannot force delete themselves
- ❌ Cannot impersonate themselves

### Audit Logging

All sensitive operations are logged to the `audit` channel:

- Superadmin operations (update, delete, restore, forceDelete, impersonate)
- Admin/Manager operations on other users (update, delete, restore)

**Audit Log Location**: `storage/logs/audit.log` (configured in `config/logging.php`)

### Integration with HierarchicalScope

The policy works in conjunction with `HierarchicalScope`:

1. **Policy**: Checks if user has permission to perform action
2. **Scope**: Filters query results to only include accessible records

```php
// Policy checks permission
$this->authorize('viewAny', User::class);

// Scope filters results by tenant
$users = User::all(); // Only returns users in same tenant
```

---

## Testing

**Test File**: `tests/Feature/Policies/UserPolicyTest.php`

**Coverage**: 100% of policy methods

**Test Categories**:
- viewAny permissions (4 tests)
- view permissions (5 tests)
- create permissions (4 tests)
- update permissions (5 tests)
- delete permissions (6 tests)
- restore permissions (3 tests)
- forceDelete permissions (3 tests)
- impersonate permissions (4 tests)
- replicate permissions (2 tests)

**Running Tests**:
```bash
php artisan test --filter=UserPolicyTest
```

---

## Related Documentation

- **Architecture**: `docs/architecture/HIERARCHICAL_SCOPE.md`
- **Security**: `docs/security/HIERARCHICAL_SCOPE_SECURITY_AUDIT.md`
- **Requirements**: `.kiro/specs/3-hierarchical-user-management/requirements.md`
- **User Model**: `app/Models/User.php`
- **UserRole Enum**: `app/Enums/UserRole.php`
- **HierarchicalScope**: `app/Scopes/HierarchicalScope.php`

---

## Changelog

### 2024-11-26
- ✅ Comprehensive documentation created
- ✅ All methods documented with examples
- ✅ Security considerations documented
- ✅ Audit logging fully documented
- ✅ Integration patterns documented

---

**Document Version**: 1.0  
**Last Updated**: 2024-11-26  
**Status**: ✅ Complete
