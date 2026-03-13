# UserPolicy Usage Guide

## Quick Start

The `UserPolicy` handles all authorization for user management operations. It automatically enforces tenant boundaries and logs sensitive operations.

### Basic Usage

```php
// Check if user can view users list
if (auth()->user()->can('viewAny', User::class)) {
    $users = User::all();
}

// Check if user can view specific user
if (auth()->user()->can('view', $targetUser)) {
    // Show user details
}

// Check if user can update
if (auth()->user()->can('update', $targetUser)) {
    $targetUser->update($data);
}
```

---

## Common Scenarios

### Scenario 1: Admin Managing Tenant Users

```php
// In AdminController
public function index()
{
    // Authorize viewAny
    $this->authorize('viewAny', User::class);
    
    // HierarchicalScope automatically filters to admin's tenant
    $tenants = User::where('role', UserRole::TENANT)->get();
    
    return view('admin.tenants.index', compact('tenants'));
}

public function create()
{
    // Authorize create
    $this->authorize('create', User::class);
    
    return view('admin.tenants.create');
}

public function store(Request $request)
{
    $this->authorize('create', User::class);
    
    $tenant = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'role' => UserRole::TENANT,
        'tenant_id' => auth()->user()->tenant_id, // Inherit tenant
        'property_id' => $request->property_id,
    ]);
    
    return redirect()->route('admin.tenants.show', $tenant);
}
```

### Scenario 2: Tenant Updating Their Profile

```php
// In TenantController
public function edit()
{
    $user = auth()->user();
    
    // User can always update themselves
    $this->authorize('update', $user);
    
    return view('tenant.profile.edit', compact('user'));
}

public function update(Request $request)
{
    $user = auth()->user();
    
    $this->authorize('update', $user);
    
    $user->update($request->only(['name', 'email', 'phone']));
    
    return redirect()->route('tenant.profile')
        ->with('success', 'Profile updated successfully');
}
```

### Scenario 3: Superadmin Managing All Users

```php
// In SuperadminController
public function index()
{
    $this->authorize('viewAny', User::class);
    
    // Superadmin sees all users (HierarchicalScope bypassed)
    $users = User::paginate(50);
    
    return view('superadmin.users.index', compact('users'));
}

public function impersonate(User $user)
{
    // Check impersonate permission (logged)
    $this->authorize('impersonate', $user);
    
    auth()->user()->impersonate($user);
    
    return redirect()->route('dashboard')
        ->with('info', "Now impersonating {$user->name}");
}
```

---

## Filament Integration

### UserResource Example

```php
use App\Models\User;
use Filament\Resources\Resource;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    // Control who can see the resource in navigation
    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', User::class);
    }
    
    // Control who can create records
    public static function canCreate(): bool
    {
        return auth()->user()->can('create', User::class);
    }
    
    // Control who can edit specific records
    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('update', $record);
    }
    
    // Control who can delete specific records
    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete', $record);
    }
    
    // Control who can force delete
    public static function canForceDelete(Model $record): bool
    {
        return auth()->user()->can('forceDelete', $record);
    }
    
    // Control who can restore soft-deleted records
    public static function canRestore(Model $record): bool
    {
        return auth()->user()->can('restore', $record);
    }
    
    // Hide actions based on permissions
    public static function getActions(): array
    {
        return [
 