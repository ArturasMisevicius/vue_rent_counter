# Assign Super Admin Script API

## Overview

The `assign-super-admin.php` script provides a programmatic interface for assigning super admin privileges in the Vilnius Utilities Billing Platform. This document details the script's API, data structures, and integration points.

## Script Interface

### Command Line Interface

```bash
php assign-super-admin.php
```

**Parameters**: None (script operates on latest user automatically)

**Exit Codes**:
- `0` - Success: Super admin privileges assigned
- `1` - Failure: User not found, database error, or assignment failed

### Output Format

The script provides structured console output with emoji indicators:

```
🔍 Found latest user: {name} ({email}) - ID: {id}
✅ Assigned '{role_name}' role to user {name}
✅ Updated user role enum to SUPERADMIN
🎉 Super admin access granted successfully!
🔗 User can now access /admin panel with full privileges
⚠️  Remember: Super admin has unrestricted access to all tenants and data
```

## Data Structures

### User Selection

```php
/**
 * User discovery query
 * 
 * @return \App\Models\User|null Latest created user
 */
$user = User::latest()->first();
```

**Selection Criteria**:
- Orders by `created_at` descending
- Returns single most recent user
- Returns `null` if no users exist

### Role Resolution

```php
/**
 * Role resolution with fallback naming
 * 
 * @return \Spatie\Permission\Models\Role Super admin role
 */
$superAdminRole = Role::where('name', 'super_admin')->first()
    ?? Role::where('name', 'superadmin')->first()
    ?? Role::create(['name' => 'super_admin']);
```

**Role Naming Conventions**:
- Primary: `super_admin` (with underscore)
- Fallback: `superadmin` (without underscore)
- Auto-creation: Creates `super_admin` if neither exists

## Integration Points

### Spatie Permission Integration

#### Role Assignment

```php
/**
 * Assign role with duplicate check
 * 
 * @param \App\Models\User $user Target user
 * @param string $roleName Role to assign
 * @return bool True if assigned, false if already assigned
 */
if (!$user->hasRole($roleName)) {
    $user->assignRole($roleName);
    return true;
}
return false;
```

#### Permission Inheritance

Super admin role inherits all permissions through Spatie Permission's role system:

```php
// Super admin can perform any action
$user->can('any_permission'); // Returns true for super admin
```

### UserRole Enum Integration

#### Enum Field Update

```php
/**
 * Update UserRole enum field for application consistency
 * 
 * @param \App\Models\User $user User to update
 * @return bool True if field exists and was updated
 */
if (isset($user->role)) {
    $user->role = \App\Enums\UserRole::SUPERADMIN;
    $user->save();
    return true;
}
return false;
```

#### Enum Values

```php
enum UserRole: string
{
    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case MANAGER = 'manager';
    case TENANT = 'tenant';
}
```

### Multi-Tenant System Integration

#### Tenant Boundary Bypass

Super admin users bypass all tenant scoping:

```php
// In TenantBoundaryService
public function canAccessTenant(User $user, int $tenantId): bool
{
    if ($user->hasRole('super_admin')) {
        return true; // Bypass tenant restrictions
    }
    
    return $user->tenant_id === $tenantId;
}
```

#### Cross-Tenant Operations

Super admin can perform operations across all tenants:

- View all tenant data
- Manage cross-tenant relationships
- Access system-wide analytics
- Perform platform administration

## Error Handling

### Exception Types

The script handles several exception scenarios:

#### Database Exceptions

```php
try {
    $user = User::latest()->first();
} catch (\Illuminate\Database\QueryException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
```

#### Permission Exceptions

```php
try {
    $user->assignRole($roleName);
} catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
    echo "❌ Role Error: " . $e->getMessage() . "\n";
    exit(1);
}
```

#### Model Exceptions

```php
try {
    $user->save();
} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    echo "❌ Model Error: " . $e->getMessage() . "\n";
    exit(1);
}
```

### Error Response Format

```
❌ Error: {error_message}
📍 File: {file_path}:{line_number}
🔧 Check database connection, migrations, and user existence
```

## Security Considerations

### Access Control

The script requires:
- File system read access to Laravel application
- Database write access for role assignment
- Proper Laravel environment configuration

### Audit Trail

Consider implementing audit logging:

```php
// Log super admin assignment
\Illuminate\Support\Facades\Log::critical('Super admin assigned', [
    'user_id' => $user->id,
    'user_email' => $user->email,
    'role_assigned' => $superAdminRole->name,
    'script_executed_at' => now(),
    'executed_by' => get_current_user(), // System user
]);
```

### Risk Mitigation

1. **Environment Checks**: Add environment validation
2. **Confirmation Prompts**: Require explicit confirmation
3. **Backup Verification**: Ensure database backup before execution
4. **Access Logging**: Log all script executions

## Testing

### Unit Test Structure

```php
<?php

namespace Tests\Unit\Scripts;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AssignSuperAdminTest extends TestCase
{
    public function test_assigns_super_admin_to_latest_user(): void
    {
        $user = User::factory()->create();
        
        // Execute script logic
        $this->artisan('app:assign-super-admin')
            ->expectsOutput("✅ Assigned 'super_admin' role to user {$user->name}")
            ->assertExitCode(0);
        
        $this->assertTrue($user->fresh()->hasRole('super_admin'));
    }
}
```

### Integration Testing

```php
public function test_script_handles_no_users_gracefully(): void
{
    // Ensure no users exist
    User::query()->delete();
    
    $this->artisan('app:assign-super-admin')
        ->expectsOutput('❌ No users found in database')
        ->assertExitCode(1);
}
```

## Performance Considerations

### Query Optimization

The script uses minimal queries:
1. One query to find latest user
2. One-two queries to find/create role
3. One query to assign role
4. One query to update enum (if applicable)

**Total**: 4-5 database queries maximum

### Memory Usage

Minimal memory footprint:
- Single user model loaded
- Single role model loaded
- No collection processing
- Immediate garbage collection

## Monitoring

### Execution Metrics

Track script usage:
- Execution frequency
- Success/failure rates
- User assignment patterns
- Environment usage

### Alerting

Set up alerts for:
- Production executions (should be rare)
- Failed assignments
- Multiple executions in short timeframe
- Unauthorized access attempts

## Related APIs

### Laravel Artisan Integration

Convert to Artisan command:

```php
php artisan make:command AssignSuperAdminCommand --command=app:assign-super-admin
```

### Filament Integration

Integrate with Filament admin panel:

```php
// In UserResource
Action::make('assignSuperAdmin')
    ->action(fn (User $record) => $record->assignRole('super_admin'))
    ->requiresConfirmation()
    ->visible(fn () => auth()->user()->hasRole('super_admin'));
```

## Changelog

### v1.0.0 (2025-01-07)
- Initial API documentation
- Comprehensive error handling specification
- Security considerations documented
- Integration points defined
- Testing patterns established

## References

- [Spatie Permission API](https://spatie.be/docs/laravel-permission/v6/basic-usage/basic-usage)
- [Laravel Console Commands](https://laravel.com/docs/artisan)
- [Multi-Tenant Security](../security/multi-tenant-security.md)
- [User Role Management](../models/user-role-management.md)