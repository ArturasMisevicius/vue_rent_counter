# SubscriptionChecker Service Documentation

## Overview

The `SubscriptionChecker` service provides optimized, cached subscription lookups for the Vilnius Utilities Billing Platform. It reduces database queries by ~95% through intelligent caching with automatic invalidation.

**Class Type**: Non-final (extensible)  
**Interface**: `App\Contracts\SubscriptionCheckerInterface`  
**Binding**: Singleton in `AppServiceProvider`

## Architecture

### Interface-Based Design

The service implements `SubscriptionCheckerInterface`, enabling:
- Dependency inversion for better testability
- Easy mocking in tests
- Potential for alternative implementations
- Clear contract definition

### Dependency Injection

```php
use App\Contracts\SubscriptionCheckerInterface;

class YourController extends Controller
{
    public function __construct(
        private readonly SubscriptionCheckerInterface $subscriptionChecker
    ) {
    }
    
    public function index(User $user)
    {
        if ($this->subscriptionChecker->isActive($user)) {
            // User has active subscription
        }
    }
}
```

## Features

### 1. Cached Subscription Retrieval

```php
$subscription = $subscriptionChecker->getSubscription($user);
```

- **First call**: Queries database and caches result
- **Subsequent calls**: Returns cached data (5-minute TTL)
- **Fallback**: Direct database query if cache fails
- **Security**: Validates user ID to prevent cache poisoning

### 2. Active Status Check

```php
$isActive = $subscriptionChecker->isActive($user);
```

- Returns `true` if user has an active subscription
- Returns `false` if subscription is expired or doesn't exist
- Cached separately for optimal performance

### 3. Expiry Status Check

```php
$isExpired = $subscriptionChecker->isExpired($user);
```

- Returns `true` if subscription is expired or doesn't exist
- Returns `false` if subscription is active

### 4. Days Until Expiry

```php
$days = $subscriptionChecker->daysUntilExpiry($user);
```

- Returns number of days until expiry (positive)
- Returns negative number if already expired
- Returns `null` if no subscription exists

### 5. Cache Invalidation

```php
// Single user
$subscriptionChecker->invalidateCache($user);

// Multiple users
$subscriptionChecker->invalidateMany([$user1, $user2, $user3]);
```

- Clears all cached data for specified user(s)
- Automatically called when subscriptions change (via observer)
- Dispatches `SubscriptionCacheInvalidated` event

### 6. Cache Warming

```php
$subscriptionChecker->warmCache($user);
```

- Pre-loads subscription data into cache
- Useful for batch operations or scheduled tasks
- Dispatches `SubscriptionCacheWarmed` event

## Configuration

### Cache TTL

Configure cache duration in `config/subscription.php`:

```php
return [
    'cache_ttl' => env('SUBSCRIPTION_CACHE_TTL', 300), // 5 minutes default
];
```

### Cache Driver

Uses Laravel's default cache driver. For optimal performance with cache tags:
- Redis (recommended)
- Memcached
- Database (supports tags in Laravel 12)

**Note**: File and array drivers don't support tags and will fall back to individual key management.

## Automatic Cache Invalidation

The `SubscriptionObserver` automatically invalidates cache when:
- New subscription is created
- Existing subscription is updated
- Subscription is deleted

No manual invalidation needed in most cases!

## Events

### SubscriptionCacheInvalidated

Dispatched when cache is invalidated:

```php
use App\Events\SubscriptionCacheInvalidated;

Event::listen(SubscriptionCacheInvalidated::class, function ($event) {
    Log::info('Cache invalidated for user', ['user_id' => $event->user->id]);
});
```

### SubscriptionCacheWarmed

Dispatched when cache is warmed:

```php
use App\Events\SubscriptionCacheWarmed;

Event::listen(SubscriptionCacheWarmed::class, function ($event) {
    Log::info('Cache warmed for user', ['user_id' => $event->user->id]);
});
```

## Performance Optimization

### Cache Tags

The service uses cache tags (`subscriptions`) for efficient bulk invalidation:

```php
// Clear all subscription caches at once
Cache::tags(['subscriptions'])->flush();
```

### Selective Field Loading

Only essential fields are loaded from the database:

```php
Subscription::select([
    'id',
    'user_id',
    'plan_type',
    'status',
    'starts_at',
    'expires_at',
    'max_properties',
    'max_tenants',
])
```

### Error Handling

Graceful fallback to database queries if cache operations fail:

```php
try {
    return $this->cache->tags([self::CACHE_TAG])->remember(...);
} catch (\Exception $e) {
    Log::error('Cache failure, falling back to database');
    return Subscription::where('user_id', $user->id)->first();
}
```

## Security

### Cache Poisoning Prevention

User IDs are validated before cache key generation:

```php
private function validateUserId(User $user): void
{
    if ($user->id <= 0) {
        throw new \InvalidArgumentException(
            sprintf('Invalid user ID for cache key: %d', $user->id)
        );
    }
}
```

### Cache Key Format

Predictable, namespaced format prevents collisions:

```
subscription.{user_id}.{type}
```

Examples:
- `subscription.123.subscription`
- `subscription.123.status`

## Testing

### Unit Tests

```bash
php artisan test --filter=SubscriptionCheckerTest
```

### Integration Tests

```bash
php artisan test tests/Feature/Services/SubscriptionCheckerIntegrationTest.php
```

### Property Tests

```bash
php artisan test tests/Feature/PropertyTests/SubscriptionCachePropertyTest.php
```

## Monitoring

### Logging

The service logs:
- Cache misses (debug level)
- Cache invalidations (info level)
- Cache failures (error level)
- Bulk operations (info level)

### Metrics

Track these metrics for observability:
- Cache hit rate
- Cache invalidation frequency
- Fallback query count
- Average response time

## Migration Guide

### From Direct Subscription Queries

**Before:**
```php
$subscription = Subscription::where('user_id', $user->id)->first();
if ($subscription && $subscription->isActive()) {
    // ...
}
```

**After:**
```php
if ($this->subscriptionChecker->isActive($user)) {
    // ...
}
```

### From Facade Usage

**Before:**
```php
use App\Services\SubscriptionChecker;

$checker = new SubscriptionChecker();
$subscription = $checker->getSubscription($user);
```

**After:**
```php
use App\Contracts\SubscriptionCheckerInterface;

public function __construct(
    private readonly SubscriptionCheckerInterface $subscriptionChecker
) {
}

$subscription = $this->subscriptionChecker->getSubscription($user);
```

## Extensibility

The `SubscriptionChecker` class is non-final, allowing for custom implementations through inheritance:

```php
use App\Services\SubscriptionChecker;
use App\Models\User;

class CustomSubscriptionChecker extends SubscriptionChecker
{
    /**
     * Override to add custom business logic
     */
    public function isActive(User $user): bool
    {
        // Call parent implementation
        $isActive = parent::isActive($user);
        
        // Add custom validation
        if ($isActive && $this->hasSpecialCondition($user)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Add custom methods
     */
    public function hasSpecialCondition(User $user): bool
    {
        // Custom business logic
        return $user->hasRole('premium');
    }
}
```

### Binding Custom Implementation

Update `AppServiceProvider` to use your custom implementation:

```php
$this->app->singleton(
    \App\Contracts\SubscriptionCheckerInterface::class,
    \App\Services\CustomSubscriptionChecker::class
);
```

### Extension Guidelines

When extending `SubscriptionChecker`:

1. **Preserve Cache Behavior**: Call parent methods to maintain caching
2. **Validate User IDs**: Use `validateUserId()` for custom methods
3. **Invalidate Appropriately**: Call `invalidateCache()` when custom logic changes state
4. **Document Overrides**: Clearly document any behavioral changes
5. **Test Thoroughly**: Ensure custom logic doesn't break existing functionality

## Best Practices

1. **Use Dependency Injection**: Always inject the interface, not the concrete class
2. **Trust Automatic Invalidation**: The observer handles most cache invalidation
3. **Warm Cache for Batch Operations**: Pre-load data when processing multiple users
4. **Monitor Cache Performance**: Track hit rates and adjust TTL if needed
5. **Handle Null Returns**: Always check for null when calling `getSubscription()`
6. **Extend Carefully**: When extending, preserve core caching and validation behavior

## Troubleshooting

### Cache Not Invalidating

Check that the observer is registered:

```php
// In AppServiceProvider::boot()
\App\Models\Subscription::observe(\App\Observers\SubscriptionObserver::class);
```

### Cache Tags Not Working

Ensure your cache driver supports tags:
- ✅ Redis
- ✅ Memcached
- ✅ Database (Laravel 12+)
- ❌ File
- ❌ Array

### High Cache Miss Rate

Consider:
- Increasing cache TTL
- Implementing cache warming for frequently accessed users
- Checking cache driver performance

## Related Documentation

- [Subscription Model](../models/SUBSCRIPTION_MODEL.md)
- [Subscription Policy](../policies/SUBSCRIPTION_POLICY.md)
- [Subscription Service](./SUBSCRIPTION_SERVICE.md)
- [Caching Strategy](../architecture/CACHING_STRATEGY.md)
