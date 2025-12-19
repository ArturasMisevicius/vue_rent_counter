# Filament Authorization Guide

## Overview

This guide explains how authorization works in Filament v4 resources within the Vilnius Utilities Billing Platform, focusing on the integration between Filament's authorization layer and Laravel's policy system.

## Authorization Architecture

### Two-Layer System

Filament resources implement a two-layer authorization system:

1. **Resource Layer** (`can*()` methods)
   - Quick role-based checks
   - Navigation visibility control
   - UI element gating
   - Performance-optimized

2. **Policy Layer** (Laravel Policies)
   - Granular authorization logic
   - Tenant boundary enforcement
   - Audit logging
   - Business rule enforcement

### Authorization Flow

```
User Action
    ↓
Resource::can*() [Fast role check]
    ↓
Policy::*() [Detailed authorization]
    ↓
Tenant Scope Check
    ↓
Audit Log
    ↓
Action Executed
```

## Implementing Authorization in Resources

### Standard Pattern

All Filament resources should implement these authorization methods:

```php
/**
 * Control access to the resource list/index page.
 */
public static function canViewAny(): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->hasPermission('view_resource');
}

/**
 * Control ability to create new records.
 */
public static function canCreate(): bool
{
    return static::canViewAny();
}

/**
 * Control ability to edit specific records.
 */
public static function canEdit(Model $record): bool
{
    return static::canViewAny();
}

/**
 * Control ability to delete specific records.
 */
public static function canDelete(Model $record): bool
{
    return static::canViewAny();
}

/**
 * Control navigation visibility.
 */
public static function shouldRegisterNavigation(): bool
{
    return static::canViewAny();
}
```

### Method Responsibilities

#### canViewAny()

**Purpose**: Primary authorization checkpoint for the resource.

**Responsibilities**:
- Check if user can access the resource at all
- Control navigation visibility
- Gate list/index page access

**Best Practices**:
- Keep logic simple and fast
- Use role-based checks
- Avoid database queries
- Cache results if needed

**Example**:
```php
public static function canViewAny(): bool
{
    $user = auth()->user();
    
    // Fast role check
    return $user instanceof User && in_array($user->role, [
        UserRole::SUPERADMIN,
        UserRole::ADMIN,
        UserRole::MANAGER,
    ], true);
}
```

#### canCreate()

**Purpose**: Control ability to create new records.

**Responsibilities**:
- Check if user can create records
- Usually delegates to `canViewAny()`
- Policy handles detailed checks

**Best Practices**:
- Delegate to `canViewAny()` for consistency
- Let policy handle complex logic
- Keep resource method simple

**Example**:
```php
public static function canCreate(): bool
{
    // If user can view the resource, they can create
    // Policy enforces tenant boundaries
    return static::canViewAny();
}
```

#### canEdit(Model $record)

**Purpose**: Control ability to edit specific records.

**Responsibilities**:
- Resource-level authorization check
- Policy handles record-specific logic
- Tenant boundary enforcement in policy

**Best Practices**:
- Accept `Model $record` parameter
- Delegate to `canViewAny()` for resource access
- Let policy check record ownership
- Policy handles tenant boundaries

**Example**:
```php
public static function canEdit(Model $record): bool
{
    // Resource-level check
    // Policy::update() handles record-specific authorization
    return static::canViewAny();
}
```

#### canDelete(Model $record)

**Purpose**: Control ability to delete specific records.

**Responsibilities**:
- Resource-level authorization check
- Policy handles deletion rules
- Audit logging in policy

**Best Practices**:
- Accept `Model $record` parameter
- Delegate to `canViewAny()` for resource access
- Let policy handle deletion rules
- Policy logs sensitive operations

**Example**:
```php
public static function canDelete(Model $record): bool
{
    // Resource-level check
    // Policy::delete() handles deletion authorization
    return static::canViewAny();
}
```

#### shouldRegisterNavigation()

**Purpose**: Control whether resource appears in navigation.

**Responsibilities**:
- Hide navigation from unauthorized users
- Reduce UI clutter
- Improve UX

**Best Practices**:
- Delegate to `canViewAny()`
- Keep consistent with resource access
- Consider role-based visibility

**Example**:
```php
public static function shouldRegisterNavigation(): bool
{
    return static::canViewAny();
}
```

## Policy Integration

### Policy Methods

Each resource authorization method should have a corresponding policy method:

| Resource Method | Policy Method | Purpose |
|----------------|---------------|---------|
| `canViewAny()` | `viewAny()` | List access |
| `canCreate()` | `create()` | Creation authorization |
| `canEdit()` | `update()` | Edit authorization |
| `canDelete()` | `delete()` | Deletion authorization |
| N/A | `view()` | Individual record view |
| N/A | `restore()` | Soft delete restoration |
| N/A | `forceDelete()` | Permanent deletion |

### Policy Example

```php
final class ResourcePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin() 
            || $user->isAdmin() 
            || $user->isManager();
    }

    public function view(User $user, Model $model): bool
    {
        if ($user->isSuperadmin()) {
            return true;
        }

        return $this->isSameTenant($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->isSuperadmin() 
            || $user->isAdmin() 
            || $user->isManager();
    }

    public function update(User $user, Model $model): bool
    {
        if ($user->isSuperadmin()) {
            $this->logSensitiveOperation('update', $user, $model);
            return true;
        }

        if ($this->canManageTenantRecord($user, $model)) {
            $this->logSensitiveOperation('update', $user, $model);
            return true;
        }

        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        // Prevent self-deletion if applicable
        if ($this->isSelfReference($user, $model)) {
            return false;
        }

        if ($user->isSuperadmin()) {
            $this->logSensitiveOperation('delete', $user, $model);
            return true;
        }

        if ($this->canManageTenantRecord($user, $model)) {
            $this->logSensitiveOperation('delete', $user, $model);
            return true;
        }

        return false;
    }

    private function canManageTenantRecord(User $user, Model $model): bool
    {
        return ($user->isAdmin() || $user->isManager()) 
            && $this->isSameTenant($user, $model);
    }

    private function isSameTenant(User $user, Model $model): bool
    {
        return $user->tenant_id !== null 
            && $model->tenant_id !== null
            && $user->tenant_id === $model->tenant_id;
    }

    private function logSensitiveOperation(string $operation, User $user, Model $model): void
    {
        Log::channel('audit')->info("Resource {$operation} operation", [
            'operation' => $operation,
            'actor_id' => $user->id,
            'target_id' => $model->id,
            'tenant_id' => $user->tenant_id,
            'ip' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
```

## Tenant Isolation

### Query Scoping

All resources must implement tenant scoping:

```php
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    $user = auth()->user();

    // Superadmins see all records
    if ($user instanceof User && $user->isSuperadmin()) {
        return $query;
    }

    // Apply tenant scope for other roles
    if ($user instanceof User && $user->tenant_id) {
        $query->where('tenant_id', $user->tenant_id);
    }

    return $query;
}
```

### Form Scoping

Relationship fields must be scoped to user's tenant:

```php
Forms\Components\Select::make('related_id')
    ->relationship(
        name: 'related',
        titleAttribute: 'name',
        modifyQueryUsing: fn (Builder $query) => self::scopeToUserTenant($query)
    )
```

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

## Table Actions Authorization

### Record Actions

```php
->recordActions([
    Tables\Actions\ViewAction::make(),
    Tables\Actions\EditAction::make(),
    Tables\Actions\DeleteAction::make(),
])
```

**Authorization**:
- View: Controlled by `Policy::view()`
- Edit: Controlled by `Policy::update()`
- Delete: Controlled by `Policy::delete()`
- Actions automatically hidden if unauthorized

### Bulk Actions

```php
->bulkActions([
    Tables\Actions\BulkActionGroup::make([
        Tables\Actions\DeleteBulkAction::make(),
    ]),
])
```

**Authorization**:
- Each record checked individually
- Unauthorized records excluded
- Partial success possible

### Custom Actions

```php
Tables\Actions\Action::make('approve')
    ->visible(fn (Model $record): bool => auth()->user()->can('approve', $record))
    ->action(function (Model $record) {
        // Action logic
    })
```

## Performance Considerations

### Caching

Cache expensive authorization checks:

```php
public static function getNavigationBadge(): ?string
{
    $user = auth()->user();

    if (! $user instanceof User) {
        return null;
    }

    $cacheKey = sprintf(
        'resource_badge_%s_%s',
        $user->role->value,
        $user->tenant_id ?? 'all'
    );

    $count = cache()->remember($cacheKey, 300, function () use ($user) {
        $query = static::getModel()::query();

        if ($user->role !== UserRole::SUPERADMIN && $user->tenant_id) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query->count();
    });

    return $count > 0 ? (string) $count : null;
}
```

### Early Returns

Optimize policy methods with early returns:

```php
public function update(User $user, Model $model): bool
{
    // Fastest path first
    if ($user->id === $model->user_id) {
        return true;
    }

    // Next fastest
    if ($user->isSuperadmin()) {
        return true;
    }

    // Most expensive last
    return $this->canManageTenantRecord($user, $model);
}
```

### Eager Loading

Prevent N+1 queries in table queries:

```php
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    
    // Eager load relationships
    $query->with('parentUser:id,name');
    
    return $query;
}
```

## Testing Authorization

### Resource Tests

```php
test('only authorized users can view resource', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);

    expect(ResourceResource::canViewAny())->toBeTrue()
        ->when(actingAs($admin))
        ->and(ResourceResource::canViewAny())->toBeFalse()
        ->when(actingAs($tenant));
});
```

### Policy Tests

```php
test('users can only edit records in their tenant', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN, 'tenant_id' => 1]);
    $record1 = Resource::factory()->create(['tenant_id' => 1]);
    $record2 = Resource::factory()->create(['tenant_id' => 2]);

    expect($admin->can('update', $record1))->toBeTrue()
        ->and($admin->can('update', $record2))->toBeFalse();
});
```

### Integration Tests

```php
test('unauthorized users cannot access resource pages', function () {
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);

    actingAs($tenant)
        ->get(ResourceResource::getUrl('index'))
        ->assertForbidden();
});
```

## Common Patterns

### Admin-Only Resources

```php
public static function canViewAny(): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->isAdmin();
}
```

### Tenant-Scoped Resources

```php
public static function canViewAny(): bool
{
    $user = auth()->user();
    return $user instanceof User && $user->tenant_id !== null;
}
```

### Role-Based Resources

```php
public static function canViewAny(): bool
{
    $user = auth()->user();
    return $user instanceof User && in_array($user->role, [
        UserRole::SUPERADMIN,
        UserRole::ADMIN,
        UserRole::MANAGER,
    ], true);
}
```

## Best Practices

1. **Keep Resource Methods Simple**
   - Fast role checks only
   - Delegate complex logic to policies
   - Avoid database queries

2. **Use Policies for Business Logic**
   - Tenant boundary enforcement
   - Record ownership checks
   - Audit logging

3. **Implement Tenant Scoping**
   - Query scoping in `getEloquentQuery()`
   - Form scoping in relationship fields
   - Policy checks for cross-tenant access

4. **Cache Expensive Operations**
   - Navigation badge counts
   - Permission checks
   - Role lookups

5. **Test Thoroughly**
   - Resource authorization
   - Policy authorization
   - Tenant isolation
   - Integration tests

## Related Documentation

- [UserResource Authorization](USER_RESOURCE_AUTHORIZATION.md)
- [Multi-Tenancy Architecture](../architecture/MULTI_TENANCY.md)
- [Authorization Testing Guide](../testing/AUTHORIZATION_TESTING.md)
- [Filament Resources Guide](./FILAMENT_RESOURCES.md)
