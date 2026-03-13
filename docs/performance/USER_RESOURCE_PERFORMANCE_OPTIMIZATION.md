# UserResource Performance Optimization

**Date**: 2025-11-26  
**Status**: ✅ IMPLEMENTED

## Summary

Comprehensive performance optimizations for the UserResource Filament admin panel, addressing N+1 queries, missing indexes, and unnecessary repeated computations.

## Performance Issues Identified

### CRITICAL Issues (High Impact)

#### 1. N+1 Query on parentUser Relationship
**Severity**: CRITICAL  
**Impact**: 1 + N queries for N users in table  
**Location**: `app/Filament/Resources/UserResource.php:310` (parentUser.name column)

**Before**:
```php
// No eager loading - causes N+1 queries
Tables\Columns\TextColumn::make('parentUser.name')
```

**After**:
```php
// In getEloquentQuery()
public static function getEloquentQuery(): Builder
{
    $query = parent::getEloquentQuery();
    
    // Eager load parentUser to prevent N+1 queries
    $query->with('parentUser:id,name');
    
    // ... tenant scoping logic
}
```

**Expected Impact**:
- Query count: From 1 + N to 2 queries (1 for users, 1 for all parent users)
- For 100 users: 101 queries → 2 queries (98% reduction)
- Page load time: ~500ms → ~50ms (estimated 90% improvement)

#### 2. Navigation Badge Query on Every Request
**Severity**: CRITICAL  
**Impact**: COUNT query on every page load  
**Location**: `app/Filament/Resources/UserResource.php:420`

**Before**:
```php
public static function getNavigationBadge(): ?string
{
    $count = static::getModel()::query()
        ->where('tenant_id', $user->tenant_id)
        ->count();
    
    return $count > 0 ? (string) $count : null;
}
```

**After**:
```php
public static function getNavigationBadge(): ?string
{
    $cacheKey = sprintf(
        'user_resource_badge_%s_%s',
        $user->role->value,
        $user->tenant_id ?? 'all'
    );

    // Cache for 5 minutes
    $count = cache()->remember($cacheKey, 300, function () use ($user) {
        // ... query logic
    });
    
    return $count > 0 ? (string) $count : null;
}
```

**Expected Impact**:
- Query count: From 1 per page load to 1 per 5 minutes
- For 1000 page loads/hour: 1000 queries → 12 queries (99% reduction)
- Latency: ~10ms → ~0.1ms (cached reads)

### HIGH Issues (Medium Impact)

#### 3. Duplicate getEloquentQuery() Methods
**Severity**: HIGH (Bug + Performance)  
**Impact**: Code duplication, potential inconsistency  
**Location**: `app/Filament/Resources/UserResource.php:215-250`

**Fixed**: Removed duplicate method, consolidated into single optimized version.

#### 4. Missing Database Indexes
**Severity**: HIGH  
**Impact**: Full table scans on filtered queries  
**Location**: Database schema

**Indexes Added**:
```sql
-- Single column indexes
CREATE INDEX users_tenant_id_index ON users(tenant_id);
CREATE INDEX users_role_index ON users(role);
CREATE INDEX users_is_active_index ON users(is_active);

-- Composite indexes for common query patterns
CREATE INDEX users_tenant_id_role_index ON users(tenant_id, role);
CREATE INDEX users_tenant_id_is_active_index ON users(tenant_id, is_active);
```

**Expected Impact**:
- Query execution time: ~100ms → ~5ms (95% improvement for filtered queries)
- Especially beneficial for large user tables (>1000 users)
- Composite indexes optimize common filter combinations

### MEDIUM Issues (Low-Medium Impact)

#### 5. UserRole::labels() Not Memoized
**Severity**: MEDIUM  
**Impact**: Repeated translation lookups on every filter render  
**Location**: `app/Enums/UserRole.php`

**Before**:
```php
// Called on every filter render, translates all roles each time
->options(UserRole::labels())
```

**After**:
```php
enum UserRole: string implements HasLabel
{
    private static ?array $cachedLabels = null;

    public static function labels(): array
    {
        if (self::$cachedLabels === null) {
            self::$cachedLabels = collect(self::cases())
                ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
                ->all();
        }

        return self::$cachedLabels;
    }
}
```

**Expected Impact**:
- Translation lookups: From 4 per render to 4 total (first render only)
- Latency: ~2ms → ~0.01ms (cached)
- Memory: Negligible (~1KB for cached array)

## Implementation Steps

### 1. Apply Code Changes

```bash
# Changes already applied to:
# - app/Filament/Resources/UserResource.php
# - app/Enums/UserRole.php
```

### 2. Run Database Migration

```bash
php artisan migrate
```

### 3. Clear Caches

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Verify Indexes

```bash
php artisan tinker
```

```php
// Check indexes exist
DB::select("SHOW INDEX FROM users WHERE Key_name LIKE 'users_%'");
```

## Cache Invalidation Strategy

### Navigation Badge Cache

**Cache Key Pattern**: `user_resource_badge_{role}_{tenant_id}`

**Invalidation Triggers**:
1. User created → Clear cache for that tenant
2. User deleted → Clear cache for that tenant
3. User role changed → Clear cache for old and new tenant
4. User tenant changed → Clear cache for old and new tenant

**Implementation** (add to User model observer):

```php
// app/Observers/UserObserver.php
class UserObserver
{
    public function created(User $user): void
    {
        $this->clearNavigationBadgeCache($user);
    }

    public function updated(User $user): void
    {
        $this->clearNavigationBadgeCache($user);
        
        // If tenant_id changed, clear old tenant cache too
        if ($user->isDirty('tenant_id')) {
            $oldTenantId = $user->getOriginal('tenant_id');
            $this->clearNavigationBadgeCacheForTenant($oldTenantId);
        }
    }

    public function deleted(User $user): void
    {
        $this->clearNavigationBadgeCache($user);
    }

    private function clearNavigationBadgeCache(User $user): void
    {
        // Clear for all roles in this tenant
        foreach (UserRole::cases() as $role) {
            $cacheKey = sprintf(
                'user_resource_badge_%s_%s',
                $role->value,
                $user->tenant_id ?? 'all'
            );
            cache()->forget($cacheKey);
        }
    }

    private function clearNavigationBadgeCacheForTenant(?int $tenantId): void
    {
        foreach (UserRole::cases() as $role) {
            $cacheKey = sprintf(
                'user_resource_badge_%s_%s',
                $role->value,
                $tenantId ?? 'all'
            );
            cache()->forget($cacheKey);
        }
    }
}
```

## Testing & Validation

### 1. Query Count Test

```php
// tests/Feature/Performance/UserResourcePerformanceTest.php
use Illuminate\Support\Facades\DB;

test('user resource table does not have N+1 queries', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    // Create 50 users with parent users
    User::factory()->count(50)->create(['tenant_id' => 1]);
    
    actingAs($admin);
    
    // Enable query log
    DB::enableQueryLog();
    
    // Render the user resource table
    Livewire::test(ListUsers::class);
    
    $queries = DB::getQueryLog();
    
    // Should be ~3 queries: 1 for users, 1 for parentUsers, 1 for count
    expect(count($queries))->toBeLessThan(10);
});
```

### 2. Cache Test

```php
test('navigation badge is cached', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    User::factory()->count(10)->create(['tenant_id' => 1]);
    
    actingAs($admin);
    
    // First call - should query database
    DB::enableQueryLog();
    $badge1 = UserResource::getNavigationBadge();
    $queriesFirst = count(DB::getQueryLog());
    
    // Second call - should use cache
    DB::flushQueryLog();
    $badge2 = UserResource::getNavigationBadge();
    $queriesSecond = count(DB::getQueryLog());
    
    expect($badge1)->toBe($badge2)
        ->and($queriesFirst)->toBeGreaterThan(0)
        ->and($queriesSecond)->toBe(0); // No queries on cached call
});
```

### 3. Index Verification Test

```php
test('users table has required performance indexes', function () {
    $indexes = DB::select("SHOW INDEX FROM users");
    $indexNames = collect($indexes)->pluck('Key_name')->unique();
    
    expect($indexNames)->toContain('users_tenant_id_index')
        ->toContain('users_role_index')
        ->toContain('users_is_active_index')
        ->toContain('users_tenant_id_role_index')
        ->toContain('users_tenant_id_is_active_index');
});
```

## Monitoring & Rollback

### Performance Monitoring

Add to `config/logging.php`:

```php
'channels' => [
    'performance' => [
        'driver' => 'daily',
        'path' => storage_path('logs/performance.log'),
        'level' => 'info',
        'days' => 14,
    ],
],
```

Log slow queries:

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Support\Facades\DB;

public function boot(): void
{
    DB::listen(function ($query) {
        if ($query->time > 100) { // Log queries > 100ms
            Log::channel('performance')->warning('Slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ]);
        }
    });
}
```

### Rollback Plan

If issues arise:

1. **Revert Code Changes**:
```bash
git revert <commit-hash>
```

2. **Remove Indexes** (if causing issues):
```bash
php artisan migrate:rollback --step=1
```

3. **Clear Caches**:
```bash
php artisan cache:clear
php artisan optimize:clear
```

4. **Monitor**:
```bash
tail -f storage/logs/performance.log
```

## Expected Overall Impact

### Query Performance

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| User list page (100 users) | 101 queries | 2 queries | 98% reduction |
| Navigation badge | 1 query/page | 1 query/5min | 99% reduction |
| Filtered queries (no index) | ~100ms | ~5ms | 95% faster |
| Role filter render | 4 translations | 0 (cached) | 100% reduction |

### Page Load Times (Estimated)

| Page | Before | After | Improvement |
|------|--------|-------|-------------|
| User list (100 users) | ~800ms | ~100ms | 87% faster |
| User list (1000 users) | ~5000ms | ~300ms | 94% faster |
| Any page with nav badge | +10ms | +0.1ms | 99% faster |

### Database Load

- **Query count reduction**: ~99% for navigation badge
- **Query execution time**: ~95% for filtered queries
- **Index storage**: ~5MB for 10,000 users (negligible)

## Additional Recommendations

### Future Optimizations

1. **Query Result Caching**: Cache common filter combinations
2. **Pagination Optimization**: Consider cursor pagination for very large tables
3. **Column Selection**: Add `select()` to limit columns fetched
4. **Lazy Loading**: Implement lazy loading for less critical columns

### Monitoring Metrics

Track these metrics in production:

1. Average query count per user list page load
2. 95th percentile page load time
3. Cache hit rate for navigation badge
4. Slow query frequency (>100ms)

## Related Documentation

- [UserResource API Documentation](../filament/USER_RESOURCE_API.md)
- [UserResource Architecture](../filament/USER_RESOURCE_ARCHITECTURE.md)
- [Laravel Query Optimization](https://laravel.com/docs/12.x/queries#optimizing-queries)
- [Filament Performance](https://filamentphp.com/docs/4.x/support/performance)

## Changelog

### 2025-11-26
- ✅ Fixed duplicate `getEloquentQuery()` methods
- ✅ Added eager loading for `parentUser` relationship
- ✅ Implemented navigation badge caching (5-minute TTL)
- ✅ Added memoization for `UserRole::labels()`
- ✅ Created database migration for performance indexes
- ✅ Documented cache invalidation strategy
- ✅ Created performance test suite
- ✅ Added monitoring and rollback procedures
