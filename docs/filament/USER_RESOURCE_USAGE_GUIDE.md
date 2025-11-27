# UserResource Usage Guide

**Date**: 2025-11-26  
**Audience**: Developers, System Administrators  
**Status**: ✅ PRODUCTION READY

## Quick Start

### Accessing the User Management Interface

1. **Login** as an Admin, Manager, or Superadmin
2. **Navigate** to the admin panel: `/admin`
3. **Click** on "Users" in the System navigation group
4. You'll see a list of users filtered by your tenant (unless you're a Superadmin)

### Creating a New User

#### Via Filament UI

1. Click the **"New User"** button in the top-right corner
2. Fill in the **User Details** section:
   - **Name**: Full name of the user
   - **Email**: Valid email address (must be unique)
   - **Password**: Minimum 8 characters
   - **Password Confirmation**: Must match password
3. Fill in the **Role and Access** section:
   - **Role**: Select from Superadmin, Admin, Manager, or Tenant
   - **Tenant**: Select organization (required for Manager/Tenant, optional for Admin)
   - **Is Active**: Toggle to enable/disable the account
4. Click **"Create"**

#### Role-Based Tenant Field Behavior

| Role | Tenant Field Visibility | Tenant Field Required |
|------|------------------------|----------------------|
| Superadmin | Hidden | No |
| Admin | Visible | No (optional) |
| Manager | Visible | Yes |
| Tenant | Visible | Yes |

#### Programmatic Creation

```php
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;

// Create a manager
$manager = User::create([
    'name' => 'John Manager',
    'email' => 'john.manager@example.com',
    'password' => Hash::make('SecurePassword123'),
    'role' => UserRole::MANAGER,
    'tenant_id' => auth()->user()->tenant_id, // Required for managers
    'is_active' => true,
]);

// Create an admin (tenant optional)
$admin = User::create([
    'name' => 'Jane Admin',
    'email' => 'jane.admin@example.com',
    'password' => Hash::make('SecurePassword456'),
    'role' => UserRole::ADMIN,
    'tenant_id' => auth()->user()->tenant_id, // Optional for admins
    'is_active' => true,
]);

// Create a tenant user
$tenant = User::create([
    'name' => 'Bob Tenant',
    'email' => 'bob.tenant@example.com',
    'password' => Hash::make('SecurePassword789'),
    'role' => UserRole::TENANT,
    'tenant_id' => auth()->user()->tenant_id, // Required for tenants
    'is_active' => true,
]);
```

## Viewing Users

### List View

The user list displays:
- **Name**: User's full name
- **Email**: Clickable to copy to clipboard
- **Role**: Color-coded badge (Superadmin=red, Admin=yellow, Manager=blue, Tenant=green)
- **Tenant**: Organization name (if assigned)
- **Is Active**: Green checkmark or red X
- **Created At**: Hidden by default, toggle to show

### Filtering Users

#### By Role
1. Click the **filter icon** in the table header
2. Select **"Role"** filter
3. Choose one or more roles
4. Results update automatically

#### By Active Status
1. Click the **filter icon** in the table header
2. Select **"Is Active"** filter
3. Choose:
   - **All Users**: Show both active and inactive
   - **Active Only**: Show only active users
   - **Inactive Only**: Show only inactive users

#### Searching
1. Type in the **search box** at the top of the table
2. Searches across: Name, Email, Tenant name
3. Results update as you type

### Detail View

1. Click on a **user row** in the table
2. View comprehensive user information:
   - **User Details**: Name, Email (copyable)
   - **Role and Access**: Role badge, Tenant, Active status
   - **Metadata**: Created at, Updated at (collapsible)
3. Use header actions:
   - **Edit**: Modify user details
   - **Delete**: Remove user (with confirmation)

## Editing Users

### Via Filament UI

1. Navigate to the user list
2. Click on a user row to view details
3. Click **"Edit"** in the header
4. Modify fields as needed:
   - **Password**: Leave blank to keep existing password
   - **Role**: Changing role may show/hide tenant field
   - **Tenant**: Required based on role
5. Click **"Save"**

### Programmatic Updates

```php
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Hash;

$user = User::find(1);

// Update basic info
$user->update([
    'name' => 'Updated Name',
    'email' => 'updated@example.com',
]);

// Change role (may require tenant_id)
$user->update([
    'role' => UserRole::MANAGER,
    'tenant_id' => 1, // Required for manager role
]);

// Update password
$user->update([
    'password' => Hash::make('NewSecurePassword123'),
]);

// Deactivate account
$user->update([
    'is_active' => false,
]);
```

## Deleting Users

### Via Filament UI

1. Navigate to the user detail view
2. Click **"Delete"** in the header
3. Confirm the deletion in the modal
4. User is soft-deleted (can be restored by Superadmin)

### Programmatic Deletion

```php
use App\Models\User;

$user = User::find(1);

// Soft delete
$user->delete();

// Force delete (Superadmin only)
$user->forceDelete();

// Restore soft-deleted user
$user->restore();
```

### Deletion Rules

- ❌ Cannot delete yourself
- ✅ Admins can delete users in their tenant
- ✅ Managers can delete users in their tenant
- ✅ Superadmins can delete any user
- ✅ All deletions are audit logged

## Tenant Scoping

### How It Works

The UserResource automatically scopes queries based on the authenticated user's role:

```php
// Superadmin
// Sees: ALL users across ALL tenants

// Admin (tenant_id = 1)
// Sees: Only users with tenant_id = 1

// Manager (tenant_id = 1)
// Sees: Only users with tenant_id = 1

// Tenant
// Cannot access UserResource (navigation hidden)
```

### Tenant Field Scoping

When selecting a tenant for a user, the dropdown is automatically filtered:

```php
// Admin (tenant_id = 1) creating a Manager
// Tenant dropdown shows: Only users with tenant_id = 1

// Superadmin creating a Manager
// Tenant dropdown shows: ALL users (no filtering)
```

This ensures users can only assign other users to their own tenant.

## Authorization

All operations are gated by the `UserPolicy`:

| Operation | Superadmin | Admin | Manager | Tenant |
|-----------|-----------|-------|---------|--------|
| View List | ✅ All | ✅ Tenant | ✅ Tenant | ❌ |
| View User | ✅ All | ✅ Tenant | ✅ Tenant | ✅ Self |
| Create User | ✅ | ✅ | ✅ | ❌ |
| Edit User | ✅ All | ✅ Tenant | ✅ Tenant | ✅ Self |
| Delete User | ✅ All | ✅ Tenant | ✅ Tenant | ❌ |
| Impersonate | ✅ | ❌ | ❌ | ❌ |

### Self-Management

All users can:
- ✅ View their own profile
- ✅ Edit their own profile
- ❌ Delete their own account
- ❌ Change their own role

## Common Workflows

### Onboarding a New Manager

1. **Login** as Admin or Superadmin
2. **Navigate** to Users
3. **Click** "New User"
4. **Fill in**:
   - Name: "Sarah Manager"
   - Email: "sarah@example.com"
   - Password: Generate secure password
   - Role: "Manager"
   - Tenant: Select your organization
   - Is Active: Toggle on
5. **Click** "Create"
6. **Send** login credentials to Sarah via secure channel

### Deactivating a User Account

1. **Navigate** to Users
2. **Find** the user to deactivate
3. **Click** on the user row
4. **Click** "Edit"
5. **Toggle off** "Is Active"
6. **Click** "Save"
7. User can no longer log in

### Reactivating a User Account

1. **Navigate** to Users
2. **Filter** by "Inactive Only"
3. **Find** the user to reactivate
4. **Click** on the user row
5. **Click** "Edit"
6. **Toggle on** "Is Active"
7. **Click** "Save"
8. User can now log in again

### Changing a User's Role

1. **Navigate** to Users
2. **Find** the user
3. **Click** on the user row
4. **Click** "Edit"
5. **Change** the role
6. **Update** tenant field if required (based on new role)
7. **Click** "Save"

**Important**: Changing from Admin to Manager/Tenant requires selecting a tenant.

### Resetting a User's Password

1. **Navigate** to Users
2. **Find** the user
3. **Click** on the user row
4. **Click** "Edit"
5. **Enter** new password in "Password" field
6. **Enter** same password in "Password Confirmation" field
7. **Click** "Save"
8. **Notify** user of new password via secure channel

## Troubleshooting

### "Organization is required for this role"

**Problem**: Trying to create/edit a Manager or Tenant without selecting a tenant.

**Solution**: Select an organization from the "Tenant" dropdown. If the dropdown is empty, you may not have permission to assign users to any tenant.

### "This email is already in use"

**Problem**: Trying to create a user with an email that already exists.

**Solution**: Use a different email address or edit the existing user.

### "Cannot delete yourself"

**Problem**: Trying to delete your own account.

**Solution**: Have another admin delete your account, or deactivate it instead.

### User not appearing in list

**Problem**: Created a user but can't see them in the list.

**Possible Causes**:
1. User belongs to a different tenant (check tenant filter)
2. User is inactive (check active status filter)
3. You don't have permission to view that user

**Solution**: Check filters, verify tenant assignment, or contact a Superadmin.

### Tenant dropdown is empty

**Problem**: Cannot select a tenant when creating a Manager or Tenant user.

**Possible Causes**:
1. You don't have a tenant assigned (Admins need tenant_id)
2. No users exist in your tenant to select from

**Solution**: Contact a Superadmin to assign you to a tenant or create tenant users.

## Best Practices

### Password Management

- ✅ Use strong passwords (min 8 characters, mix of letters, numbers, symbols)
- ✅ Generate passwords using a password manager
- ✅ Send passwords via secure channel (encrypted email, password manager)
- ❌ Don't share passwords via plain text email or chat
- ❌ Don't reuse passwords across accounts

### Account Management

- ✅ Deactivate accounts instead of deleting when possible
- ✅ Review inactive accounts regularly
- ✅ Assign appropriate roles based on job function
- ✅ Keep tenant assignments up to date
- ❌ Don't create multiple accounts for the same person
- ❌ Don't share accounts between multiple people

### Security

- ✅ Review user list regularly for unauthorized accounts
- ✅ Deactivate accounts immediately when users leave
- ✅ Use role-based access control (don't make everyone Admin)
- ✅ Monitor audit logs for suspicious activity
- ❌ Don't give Superadmin access unless absolutely necessary
- ❌ Don't leave test accounts active in production

## Related Documentation

- [UserResource API Documentation](./USER_RESOURCE_API.md)
- [User Model Documentation](../../app/Models/User.php)
- [UserPolicy Documentation](../../app/Policies/UserPolicy.php)
- [User Translations](../../lang/en/users.php)
- [Authentication Documentation](../authentication/README.md)

## Support

For issues or questions:
1. Check this guide and the API documentation
2. Review the troubleshooting section
3. Check audit logs for authorization issues
4. Contact your system administrator or Superadmin
