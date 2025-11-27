# UserResource API Documentation

**Date**: 2025-11-26  
**Version**: Filament v4  
**Status**: ✅ PRODUCTION READY

## Overview

The UserResource provides a complete CRUD interface for managing users in the Vilnius Utilities Billing Platform. It implements role-based access control, tenant scoping, and conditional field visibility based on user roles.

## Resource Configuration

### Model
- **Class**: `App\Models\User`
- **Policy**: `App\Policies\UserPolicy`
- **Navigation Group**: System
- **Navigation Sort**: 8
- **Navigation Icon**: `heroicon-o-users`

### Access Control

**Navigation Visibility**:
- Superadmin: ✅ Full access
- Admin: ✅ Full access
- Manager: ✅ Full access
- Tenant: ❌ Hidden

**Authorization**: All operations are gated by `UserPolicy` methods:
- `viewAny()`: List users
- `view()`: View user details
- `create()`: Create new users
- `update()`: Edit user details
- `delete()`: Delete users

## Form Schema

### Section 1: User Details

```php
Forms\Components\Section::make(__('users.sections.user_details'))
    ->description(__('users.sections.user_details_description'))
    ->schema([...])
    ->columns(2)
```

**Fields**:

#### Name
- **Type**: TextInput
- **Validation**: Required, max 255 characters
- **Localization**: `users.labels.name`, `users.placeholders.name`
- **Messages**: `users.validation.name.*`

#### Email
- **Type**: TextInput (email)
- **Validation**: Required, email format, unique (ignoring current record), max 255 characters
- **Localization**: `users.labels.email`, `users.placeholders.email`
- **Messages**: `users.validation.email.*`

#### Password
- **Type**: TextInput (password)
- **Validation**: 
  - Required on create
  - Min 8 characters
  - Must be confirmed
- **Behavior**:
  - Hashed with `Hash::make()` before storage
  - Only dehydrated if filled
  - Not required on edit (optional password change)
- **Localization**: `users.labels.password`, `users.placeholders.password`
- **Helper Text**: `users.helper_text.password`
- **Messages**: `users.validation.password.*`

#### Password Confirmation
- **Type**: TextInput (password)
- **Validation**: Required on create, must match password
- **Behavior**: Not dehydrated (validation only)
- **Localization**: `users.labels.password_confirmation`, `users.placeholders.password_confirmation`

### Section 2: Role and Access

```php
Forms\Components\Section::make(__('users.sections.role_and_access'))
    ->description(__('users.sections.role_and_access_description'))
    ->schema([...])
    ->columns(2)
```

**Fields**:

#### Role
- **Type**: Select (enum)
- **Options**: `UserRole` enum (Superadmin, Admin, Manager, Tenant)
- **Validation**: Required
- **Behavior**: 
  - Live updates trigger tenant field visibility
  - Native select disabled for better UX
- **Localization**: `users.labels.role`
- **Helper Text**: `users.helper_text.role`
- **Messages**: `users.validation.role.*`

#### Tenant (Organization)
- **Type**: Select (relationship)
- **Relationship**: `parentUser` (belongsTo User)
- **Query Scoping**: Filtered by authenticated user's tenant
- **Validation**: 
  - Required for Manager and Tenant roles
  - Optional for Admin and Superadmin roles
- **Visibility**:
  - Visible for Manager, Tenant, and Admin roles
  - Hidden for Superadmin role
- **Behavior**:
  - Searchable
  - Preloaded options
- **Localization**: `users.labels.tenant`
- **Helper Text**: `users.helper_text.tenant`
- **Messages**: `users.validation.tenant_id.*`

#### Is Active
- **Type**: Toggle
- **Default**: `true`
- **Localization**: `users.labels.is_active`
- **Helper Text**: `users.helper_text.is_active`

## Table Schema

### Columns

| Column | Type | Searchable | Sortable | Toggleable | Default Visible |
|--------|------|------------|----------|------------|-----------------|
| Name | TextColumn | ✅ | ✅ | ❌ | ✅ |
| Email | TextColumn (copyable) | ✅ | ✅ | ❌ | ✅ |
| Role | TextColumn (badge) | ❌ | ✅ | ❌ | ✅ |
| Tenant | TextColumn | ✅ | ✅ | ✅ | ✅ |
| Is Active | IconColumn (boolean) | ❌ | ✅ | ✅ | ✅ |
| Created At | TextColumn (datetime) | ❌ | ✅ | ✅ | ❌ |

### Column Details

#### Role Badge Colors
```php
->color(fn (UserRole $state): string => match ($state) {
    UserRole::SUPERADMIN => 'danger',
    UserRole::ADMIN => 'warning',
    UserRole::MANAGER => 'info',
    UserRole::TENANT => 'success',
})
```

#### Email Copyable
- Click to copy email to clipboard
- Shows toast notification: `users.tooltips.copy_email`

### Filters

#### Role Filter
- **Type**: SelectFilter
- **Options**: All UserRole enum values
- **Native**: Disabled (better UX)
- **Localization**: `users.filters.role`

#### Is Active Filter
- **Type**: TernaryFilter
- **Options**:
  - All users (default)
  - Active only
  - Inactive only
- **Localization**: `users.filters.is_active`, `users.filters.all_users`, `users.filters.active_only`, `users.filters.inactive_only`

### Table Configuration

```php
->defaultSort('name', 'asc')
->persistSortInSession()
->persistSearchInSession()
->persistFiltersInSession()
```

**Empty State**:
- Heading: `users.empty_state.heading`
- Description: `users.empty_state.description`

## Tenant Scoping

### Query Scoping

The resource implements hierarchical tenant scoping:

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
- Superadmin: Sees all users across all tenants
- Admin/Manager: Sees only users within their tenant
- Tenant: Cannot access UserResource (navigation hidden)

### Tenant Field Scoping

The tenant select field is scoped to the authenticated user's tenant:

```php
->relationship(
    name: 'parentUser',
    titleAttribute: 'name',
    modifyQueryUsing: fn (Builder $query) => self::scopeToUserTenant($query)
)
```

This ensures users can only assign other users to their own tenant.

## Navigation Badge

Displays the count of users visible to the authenticated user:

```php
public static function getNavigationBadge(): ?string
{
    $user = auth()->user();

    if (! $user instanceof User) {
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

**Badge Color**: `primary`

## Pages

### List Users
- **Route**: `/admin/users`
- **Class**: `App\Filament\Resources\UserResource\Pages\ListUsers`
- **Features**: Search, sort, filter, pagination

### Create User
- **Route**: `/admin/users/create`
- **Class**: `App\Filament\Resources\UserResource\Pages\CreateUser`
- **Validation**: All form validation rules apply
- **Behavior**: Password required, tenant_id auto-assigned based on role

### View User
- **Route**: `/admin/users/{record}`
- **Class**: `App\Filament\Resources\UserResource\Pages\ViewUser`
- **Features**: Read-only view with infolist, edit/delete actions

### Edit User
- **Route**: `/admin/users/{record}/edit`
- **Class**: `App\Filament\Resources\UserResource\Pages\EditUser`
- **Validation**: All form validation rules apply
- **Behavior**: Password optional (only updated if filled)

## Validation Messages

All validation messages are localized and loaded from `lang/{locale}/users.php`:

```php
protected static string $translationPrefix = 'users.validation';
```

### Translation Structure

```php
'validation' => [
    'name' => [
        'required' => 'Name is required',
        'string' => 'Name must be a valid text',
        'max' => 'Name cannot exceed 255 characters',
    ],
    'email' => [
        'required' => 'Email is required',
        'email' => 'Please provide a valid email address',
        'unique' => 'This email is already in use',
        'max' => 'Email cannot exceed 255 characters',
    ],
    'password' => [
        'required' => 'Password is required',
        'string' => 'Password must be valid text',
        'min' => 'Password must be at least 8 characters',
        'confirmed' => 'Password confirmation does not match',
    ],
    'role' => [
        'required' => 'Role is required',
        'enum' => 'Invalid role selected',
    ],
    'tenant_id' => [
        'required' => 'Organization is required for this role',
        'integer' => 'Organization must be a valid number',
        'exists' => 'Selected organization does not exist',
    ],
]
```

## Security Considerations

### Password Security
- Passwords are hashed using `Hash::make()` before storage
- Password confirmation field is not dehydrated (validation only)
- Passwords are never displayed in table or view pages

### Tenant Isolation
- All queries are scoped by tenant_id for non-superadmin users
- Tenant field options are filtered by authenticated user's tenant
- UserPolicy enforces tenant boundaries on all operations

### Authorization
- All CRUD operations are gated by UserPolicy
- Sensitive operations (update, delete, restore, forceDelete) are audit logged
- Self-deletion is prevented
- Impersonation is restricted to superadmins

### Audit Logging
UserPolicy logs all sensitive operations to the audit channel:
- Operation type
- Actor details (ID, email, role, tenant)
- Target details (ID, email, role, tenant)
- IP address and user agent
- Timestamp

## Usage Examples

### Creating a User

```php
// Via Filament UI
// 1. Navigate to /admin/users
// 2. Click "New User"
// 3. Fill in form:
//    - Name: "John Doe"
//    - Email: "john@example.com"
//    - Password: "SecurePass123"
//    - Password Confirmation: "SecurePass123"
//    - Role: "Manager"
//    - Tenant: Select from dropdown (filtered by your tenant)
//    - Is Active: Toggle on
// 4. Click "Create"

// Programmatically
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('SecurePass123'),
    'role' => UserRole::MANAGER,
    'tenant_id' => auth()->user()->tenant_id,
    'is_active' => true,
]);
```

### Filtering Users

```php
// Via Filament UI
// 1. Navigate to /admin/users
// 2. Use filters:
//    - Role: Select "Manager"
//    - Is Active: Select "Active Only"
// 3. Results are automatically filtered

// Programmatically
$users = User::query()
    ->where('role', UserRole::MANAGER)
    ->where('is_active', true)
    ->where('tenant_id', auth()->user()->tenant_id)
    ->get();
```

### Updating a User

```php
// Via Filament UI
// 1. Navigate to /admin/users
// 2. Click on user row or view icon
// 3. Click "Edit" in header
// 4. Update fields (password optional)
// 5. Click "Save"

// Programmatically
$user->update([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com',
    'role' => UserRole::ADMIN,
    'is_active' => false,
]);

// Update password
$user->update([
    'password' => Hash::make('NewSecurePass456'),
]);
```

## Performance Considerations

### Query Optimization
- Tenant scoping applied at query level (indexed column)
- Relationship preloading for tenant select field
- Session persistence for sort, search, and filters

### Caching
- Navigation badge count is calculated on each request
- Consider caching for high-traffic scenarios

### Indexes
Ensure these database indexes exist:
- `users.tenant_id`
- `users.role`
- `users.is_active`
- `users.email` (unique)

## Testing

### Feature Tests

```php
use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Enums\UserRole;

test('admin can view users in their tenant', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    $users = User::factory()->count(3)->create(['tenant_id' => 1]);
    $otherUsers = User::factory()->count(2)->create(['tenant_id' => 2]);
    
    actingAs($admin);
    
    $query = UserResource::getEloquentQuery();
    
    expect($query->count())->toBe(4); // 3 users + admin
});

test('superadmin can view all users', function () {
    $superadmin = User::factory()->create(['role' => UserRole::SUPERADMIN]);
    $users = User::factory()->count(5)->create();
    
    actingAs($superadmin);
    
    $query = UserResource::getEloquentQuery();
    
    expect($query->count())->toBe(6); // 5 users + superadmin
});

test('tenant field is required for manager role', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    
    actingAs($admin);
    
    $response = $this->post(route('filament.admin.resources.users.store'), [
        'name' => 'Test Manager',
        'email' => 'manager@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => UserRole::MANAGER->value,
        // tenant_id missing
    ]);
    
    $response->assertSessionHasErrors('tenant_id');
});
```

## Related Documentation

- [User Model](../../app/Models/User.php)
- [UserPolicy](../../app/Policies/UserPolicy.php)
- [User Translations](../../lang/en/users.php)
- [ViewUser Page](../../app/Filament/Resources/UserResource/Pages/ViewUser.php)
- [User Resource Review](./USER_RESOURCE_REVIEW.md)
- [User Resource Implementation](./USER_RESOURCE_IMPLEMENTATION_COMPLETE.md)

## Changelog

### 2025-11-26
- ✅ Refactored form schema with sections and improved UX
- ✅ Added ViewUser page with comprehensive infolist
- ✅ Implemented tenant scoping for queries and relationships
- ✅ Added role and is_active filters
- ✅ Improved table columns with badges and copyable email
- ✅ Added navigation badge with user count
- ✅ Enhanced documentation and helper text
- ✅ Removed duplicate methods and improved code organization
- ✅ Added session persistence for sort, search, and filters
