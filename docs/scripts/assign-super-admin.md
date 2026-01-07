# Super Admin Assignment Script

## Overview

The `assign-super-admin.php` script is a critical administrative utility for the Vilnius Utilities Billing Platform that assigns super admin privileges to users. This script handles both Spatie Permission role assignment and UserRole enum updates to ensure complete authorization coverage.

## Purpose

- **Initial Setup**: Grant super admin access during system deployment
- **Emergency Access**: Restore admin access when locked out
- **Development**: Quickly assign admin privileges in development environments
- **Testing**: Set up admin users for testing scenarios

## Security Considerations

⚠️ **CRITICAL SECURITY WARNING**: This script grants unrestricted access to all system data and functionality across all tenants.

### Security Best Practices

1. **Environment Restrictions**: Use only in development/staging or during initial production setup
2. **Access Control**: Restrict file system access to this script
3. **Audit Trail**: Log all executions of this script
4. **Temporary Use**: Remove or secure after initial setup
5. **Multi-Tenant Impact**: Super admin can access all tenant data

### Risk Assessment

| Risk Level | Impact | Mitigation |
|------------|--------|------------|
| **HIGH** | Unauthorized system access | Restrict file permissions, audit usage |
| **HIGH** | Cross-tenant data exposure | Document super admin responsibilities |
| **MEDIUM** | Privilege escalation | Monitor role assignments |

## Usage

### Basic Usage

```bash
# Run from project root
php assign-super-admin.php
```

### Prerequisites

1. **Laravel Environment**: Properly configured Laravel 12 application
2. **Database Access**: Active database connection with migrated tables
3. **User Existence**: At least one user must exist in the database
4. **Spatie Permission**: Package must be installed and configured
5. **File Permissions**: Script must have read access to Laravel files

### Expected Output

```
🔍 Found latest user: John Doe (john@example.com) - ID: 1
✅ Assigned 'super_admin' role to user John Doe
✅ Updated user role enum to SUPERADMIN
🎉 Super admin access granted successfully!
🔗 User can now access /admin panel with full privileges
⚠️  Remember: Super admin has unrestricted access to all tenants and data
```

## Technical Implementation

### Architecture

The script follows Laravel's bootstrap pattern and integrates with:

- **Spatie Permission**: Role-based access control
- **UserRole Enum**: Application-level role management
- **Multi-Tenant System**: Cross-tenant access capabilities
- **Filament Admin**: Admin panel access

### Code Flow

1. **Bootstrap Laravel**: Initialize application container and services
2. **User Discovery**: Find the most recently created user
3. **Role Resolution**: Locate or create super admin role
4. **Permission Assignment**: Assign Spatie Permission role
5. **Enum Update**: Update UserRole enum field
6. **Confirmation**: Display success status

### Error Handling

The script handles common failure scenarios:

- **No Users Found**: Provides guidance to create users first
- **Database Connection**: Clear error messages for connection issues
- **Role Creation**: Automatic role creation with fallback naming
- **Permission Errors**: Detailed error reporting with file/line information

## Integration Points

### Spatie Permission Integration

```php
// Role assignment with duplicate check
if ($user->hasRole($superAdminRole->name)) {
    // Already assigned - skip
} else {
    $user->assignRole($superAdminRole->name);
}
```

### UserRole Enum Integration

```php
// Update enum field for application consistency
if (isset($user->role)) {
    $user->role = \App\Enums\UserRole::SUPERADMIN;
    $user->save();
}
```

### Multi-Tenant Considerations

Super admin users bypass tenant scoping and can:
- Access all tenant data
- Manage cross-tenant operations
- View system-wide analytics
- Perform platform administration

## Troubleshooting

### Common Issues

#### "No users found in database"

**Cause**: Database is empty or users table not migrated

**Solution**:
```bash
# Create a user first
php artisan make:filament-user

# Or run seeders
php artisan db:seed --class=UsersSeeder
```

#### "Class 'App\Enums\UserRole' not found"

**Cause**: UserRole enum not properly defined or autoloaded

**Solution**:
```bash
# Clear autoload cache
composer dump-autoload

# Check enum exists
php artisan tinker
>>> App\Enums\UserRole::SUPERADMIN
```

#### Database connection errors

**Cause**: Invalid database configuration

**Solution**:
```bash
# Test database connection
php artisan migrate:status

# Check environment configuration
php artisan config:show database
```

### Debug Mode

For detailed debugging, modify the script to include:

```php
// Add after Laravel bootstrap
if (app()->environment('local')) {
    \Illuminate\Support\Facades\DB::enableQueryLog();
}

// Add before exit
if (app()->environment('local')) {
    dump(\Illuminate\Support\Facades\DB::getQueryLog());
}
```

## Alternative Methods

### Artisan Command Alternative

Consider creating an Artisan command for better integration:

```bash
php artisan make:command AssignSuperAdminCommand
```

### Filament User Creation

Use Filament's built-in user creation:

```bash
php artisan make:filament-user
```

### Database Seeder

Include in database seeders for automated setup:

```php
// In DatabaseSeeder.php
$user = User::factory()->create([
    'email' => 'admin@example.com',
]);
$user->assignRole('super_admin');
```

## Related Documentation

- [Spatie Permission Documentation](https://spatie.be/docs/laravel-permission)
- [Filament Shield Integration](../filament/filament-shield-integration.md)
- [User Role Management](../models/user-role-management.md)
- [Multi-Tenant Security](../security/multi-tenant-security.md)
- [Administrative Scripts](README.md)

## Changelog

### v1.0.0 (2025-01-07)
- Initial implementation with comprehensive error handling
- Support for both 'super_admin' and 'superadmin' role naming
- UserRole enum integration
- Enhanced console output with emojis and guidance
- Security warnings and best practices documentation

## Security Audit

**Last Reviewed**: 2025-01-07  
**Reviewer**: Development Team  
**Risk Level**: HIGH  
**Approved For**: Development, Staging, Initial Production Setup  
**Restrictions**: Production use requires security team approval