---
inclusion_mode: "conditional"
file_patterns:
  - "app/Policies/**/*.php"
  - "app/Filament/Resources/**/*.php"
  - "database/seeders/*Role*.php"
  - "tests/Feature/**/*Authorization*.php"
  - "tests/Feature/**/*Permission*.php"
---

# Filament Shield - Role-Based Access Control

## Core Principles
- Filament Shield provides RBAC for Filament v4.3+ using Spatie Laravel Permission.
- All authorization flows through Laravel policies; Shield generates and manages permissions.
- Permissions follow pattern: `{action}::{Resource}` (e.g., `view_any::Company`, `create::Task`).
- Roles are team-scoped in multi-tenant applications; use `$user->assignRole('role', $team)`.
- Super admin role (`super_admin`) bypasses all permission checks when enabled.

## Permission Generation
- Run `php artisan shield:generate --all` after creating new resources to generate permissions.
- Run `php artisan shield:generate --resource=ResourceName` for specific resources.
- Permissions are automatically created for: resources, pages, widgets, and custom permissions.
- Policy methods generated: `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`, `replicate`, `reorder`.

## Resource Authorization
- Resources automatically check permissions via policies; no manual `can()` checks needed in most cases.
- Shield generates policies in `app/Policies/` that check permissions using Spatie's `can()` method.
- Custom authorization logic can be added to policies alongside Shield's permission checks.
- Hide unauthorized navigation items with `shouldRegisterNavigation()` or `canViewAny()`.

## Role Management
- Access role management UI at `/app/shield/roles` (within Settings cluster).
- Create roles with descriptive names: `admin`, `manager`, `viewer`, `editor`.
- Assign permissions via checkboxes in the Shield UI or programmatically.
- Roles are automatically scoped to current team in multi-tenant setups.

## Multi-Tenancy
- Configure tenant model in `config/filament-shield.php`: `'tenant_model' => \App\Models\Team::class`.
- Roles and permissions are scoped to teams automatically.
- Assign roles within team context: `$user->assignRole('manager', $team)`.
- Check permissions within tenant context: `$user->can('view_any::Company')` respects current team.
- Prevent cross-tenant leakage by ensuring all queries are tenant-scoped.

## Custom Permissions
- Define non-resource permissions in `config/filament-shield.php` under `custom_permissions`:
  ```php
  'custom_permissions' => [
      'export_reports',
      'import_data',
      'manage_integrations',
      'view_analytics',
  ],
  ```
- Use custom permissions for features that don't map to resources (exports, imports, analytics).
- Check custom permissions in actions: `->visible(fn () => auth()->user()->can('export_reports'))`.

## Policy Patterns

### Basic Policy
```php
public function viewAny(User $user): bool
{
    return $user->can('view_any::Company');
}

public function create(User $user): bool
{
    return $user->can('create::Company');
}
```

### Policy with Custom Logic
```php
public function update(User $user, Company $company): bool
{
    // Shield permission check
    if (! $user->can('update::Company')) {
        return false;
    }
    
    // Custom logic
    return $company->team_id === $user->currentTeam->id;
}
```

### Policy with Super Admin
```php
public function delete(User $user, Company $company): bool
{
    // Super admin bypass (handled by Shield automatically)
    // Custom logic
    return $user->can('delete::Company') && ! $company->hasChildren();
}
```

## Seeding Roles

### Role Seeder Pattern
```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

public function run(): void
{
    // Create roles
    $admin = Role::create(['name' => 'admin']);
    $manager = Role::create(['name' => 'manager']);
    $viewer = Role::create(['name' => 'viewer']);
    
    // Generate permissions first
    Artisan::call('shield:generate --all');
    
    // Assign all permissions to admin
    $admin->givePermissionTo(Permission::all());
    
    // Assign specific permissions to manager
    $manager->givePermissionTo([
        'view_any::Company',
        'view::Company',
        'create::Company',
        'update::Company',
    ]);
    
    // Assign read-only permissions to viewer
    $viewer->givePermissionTo(
        Permission::where('name', 'like', 'view%')->get()
    );
}
```

## Testing Patterns

### Feature Test
```php
use function Pest\Laravel\actingAs;

it('allows admin to manage companies', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');
    
    actingAs($user)
        ->get(CompanyResource::getUrl('index'))
        ->assertSuccessful();
});

it('prevents viewer from creating companies', function () {
    $user = User::factory()->create();
    $user->assignRole('viewer');
    
    actingAs($user)
        ->get(CompanyResource::getUrl('create'))
        ->assertForbidden();
});
```

### Policy Test
```php
it('checks company policy permissions', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    
    $user->givePermissionTo('view::Company');
    
    expect($user->can('view', $company))->toBeTrue();
    expect($user->can('update', $company))->toBeFalse();
});
```

## Configuration

### Shield Resource
```php
'shield_resource' => [
    'slug' => 'shield/roles',
    'cluster' => \App\Filament\Clusters\Settings::class,
    'tabs' => [
        'pages' => true,
        'widgets' => true,
        'resources' => true,
        'custom_permissions' => true,
    ],
],
```

### Super Admin
```php
'super_admin' => [
    'enabled' => true,
    'name' => 'super_admin',
    'define_via_gate' => false,
    'intercept_gate' => 'before',
],
```

### Permission Format
```php
'permissions' => [
    'separator' => '::',
    'case' => 'pascal',
    'generate' => true,
],
```

## Best Practices

### DO:
- ✅ Generate permissions after creating new resources
- ✅ Use Shield's role management UI for non-technical users
- ✅ Test authorization in feature tests
- ✅ Scope roles to teams in multi-tenant apps
- ✅ Use descriptive role names
- ✅ Document custom permissions
- ✅ Clear cache after permission changes: `php artisan permission:cache-reset`
- ✅ Seed default roles in database seeders

### DON'T:
- ❌ Skip permission checks in custom actions
- ❌ Hardcode authorization logic in resources
- ❌ Allow cross-tenant permission leakage
- ❌ Forget to assign roles to new users
- ❌ Mix Shield with custom authorization systems
- ❌ Create overly granular permissions
- ❌ Use super admin for regular users

## Troubleshooting

### Permissions Not Working
```bash
# Clear all caches
php artisan optimize:clear
php artisan permission:cache-reset

# Regenerate permissions
php artisan shield:generate --all

# Check user permissions
php artisan tinker
>>> User::find(1)->roles
>>> User::find(1)->permissions
>>> User::find(1)->can('view_any::Company')
```

### Policy Not Found
```bash
# Generate policy for resource
php artisan shield:generate --resource=CompanyResource

# Check if policy is registered
php artisan tinker
>>> Gate::getPolicyFor(Company::class)
```

### Cross-Tenant Access
- Ensure all queries use Filament v4.3's auto-tenancy scoping
- Check that roles are assigned with team context
- Verify policies check tenant ownership

## Integration with Existing Code

### Adding Shield to Existing Resource
1. Generate permissions: `php artisan shield:generate --resource=CompanyResource`
2. Policy is created/updated automatically
3. Resource authorization works immediately
4. Create roles and assign permissions via UI

### Custom Authorization Logic
Keep custom logic in policies alongside Shield checks:
```php
public function update(User $user, Company $company): bool
{
    // Shield permission
    if (! $user->can('update::Company')) {
        return false;
    }
    
    // Custom business logic
    if ($company->is_locked) {
        return false;
    }
    
    return true;
}
```

## Related Documentation
- `docs/filament-shield-integration.md` - Complete integration guide
- `.kiro/steering/filament-auth-tenancy.md` - Authorization patterns
- `docs/testing-infrastructure.md` - Testing guidelines
- [Filament Shield](https://github.com/bezhanSalleh/filament-shield)
- [Spatie Permission](https://spatie.be/docs/laravel-permission)
