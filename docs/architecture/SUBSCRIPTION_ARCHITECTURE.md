# Subscription System Architecture

## Overview

The subscription system provides multi-tenant subscription management with cached lookups, automatic invalidation, and extensible business logic for the Vilnius Utilities Billing Platform.

## Component Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                     Application Layer                        │
├─────────────────────────────────────────────────────────────┤
│  Controllers  │  Filament Resources  │  Livewire Components │
└────────┬──────────────────┬──────────────────┬──────────────┘
         │                  │                  │
         └──────────────────┼──────────────────┘
                            │
                            ▼
         ┌──────────────────────────────────┐
         │ SubscriptionCheckerInterface     │
         │  (Contract/Interface)            │
         └──────────────────┬───────────────┘
                            │
                            ▼
         ┌──────────────────────────────────┐
         │   SubscriptionChecker            │
         │   (Core Implementation)          │
         │   - Caching (5min TTL)           │
         │   - Validation                   │
         │   - Cache invalidation           │
         │   - Extensible (non-final)       │
         └──────────┬───────────────────────┘
                    │
         ┌──────────┴──────────┐
         │                     │
         ▼                     ▼
┌────────────────┐    ┌────────────────┐
│ Cache Layer    │    │ Database       │
│ (Redis/File)   │    │ (Subscription) │
└────────────────┘    └────────────────┘
         ▲                     ▲
         │                     │
         └─────────┬───────────┘
                   │
         ┌─────────┴──────────┐
         │ SubscriptionObserver│
         │ (Auto-invalidation) │
         └─────────────────────┘
```

## Core Components

### 1. SubscriptionCheckerInterface

**Location**: `app/Contracts/SubscriptionCheckerInterface.php`

**Purpose**: Defines the contract for subscription checking operations

**Methods**:
- `getSubscription(User $user): ?Subscription`
- `isActive(User $user): bool`
- `isExpired(User $user): bool`
- `daysUntilExpiry(User $user): ?int`
- `invalidateCache(User $user): void`
- `invalidateMany(array $users): void`
- `warmCache(User $user): void`

### 2. SubscriptionChecker

**Location**: `app/Services/SubscriptionChecker.php`

**Purpose**: Core implementation with caching and validation

**Key Features**:
- 5-minute cache TTL (configurable)
- Cache tags for efficient bulk invalidation
- User ID validation to prevent cache poisoning
- Automatic fallback to database on cache failure
- Event dispatching for observability
- Non-final class for extensibility

**Cache Keys**:
- `subscription.{user_id}.subscription` - Full subscription data
- `subscription.{user_id}.status` - Active status boolean

**Cache Tags**:
- `subscriptions` - All subscription-related cache entries

### 3. SubscriptionObserver

**Location**: `app/Observers/SubscriptionObserver.php`

**Purpose**: Automatic cache invalidation on subscription changes

**Triggers**:
- `created` - New subscription created
- `updated` - Subscription modified
- `deleted` - Subscription removed

**Behavior**: Invalidates all cache entries for the affected user

### 4. Subscription Model

**Location**: `app/Models/Subscription.php`

**Key Methods**:
- `isActive(): bool` - Check if subscription is currently active
- `isExpired(): bool` - Check if subscription has expired
- `daysUntilExpiry(): int` - Calculate days until expiration

## Data Flow

### Read Path (Cache Hit)

```
Controller/Resource
    ↓
SubscriptionChecker::isActive($user)
    ↓
Cache::tags(['subscriptions'])->get('subscription.123.status')
    ↓ (Cache Hit)
Return cached boolean
```

### Read Path (Cache Miss)

```
Controller/Resource
    ↓
SubscriptionChecker::isActive($user)
    ↓
Cache::tags(['subscriptions'])->get('subscription.123.status')
    ↓ (Cache Miss)
Database Query: Subscription::where('user_id', 123)->first()
    ↓
Cache::tags(['subscriptions'])->put('subscription.123.status', $result, 300)
    ↓
Return result
```

### Write Path (Automatic Invalidation)

```
Subscription Update
    ↓
SubscriptionObserver::updated($subscription)
    ↓
SubscriptionChecker::invalidateCache($user)
    ↓
Cache::tags(['subscriptions'])->forget('subscription.123.subscription')
Cache::tags(['subscriptions'])->forget('subscription.123.status')
    ↓
Event: SubscriptionCacheInvalidated
```

## Configuration

### Cache TTL

**File**: `config/subscription.php`

```php
return [
    'cache_ttl' => env('SUBSCRIPTION_CACHE_TTL', 300), // 5 minutes
];
```

### Service Binding

**File**: `app/Providers/AppServiceProvider.php`

```php
$this->app->singleton(
    \App\Contracts\SubscriptionCheckerInterface::class,
    \App\Services\SubscriptionChecker::class
);
```

## Extension Points

### Custom Implementation

Create a custom implementation by extending `SubscriptionChecker`:

```php
namespace App\Services;

use App\Models\User;

class CustomSubscriptionChecker extends SubscriptionChecker
{
    public function isActive(User $user): bool
    {
        // Custom pre-check logic
        if ($this->hasOverride($user)) {
            return true;
        }
        
        // Call parent implementation
        return parent::isActive($user);
    }
    
    protected function hasOverride(User $user): bool
    {
        // Custom business logic
        return $user->hasRole('lifetime_member');
    }
}
```

Update service binding:

```php
$this->app->singleton(
    \App\Contracts\SubscriptionCheckerInterface::class,
    \App\Services\CustomSubscriptionChecker::class
);
```

## Performance Characteristics

### Cache Hit Rate

**Expected**: 95%+ for active users

**Measurement**:
```php
// Monitor via logs
Log::debug('Cache miss for subscription', [
    'user_id' => $user->id,
    'cache_key' => $cacheKey,
]);
```

### Query Reduction

**Without Cache**: 1 query per subscription check  
**With Cache**: 1 query per 5 minutes per user  
**Reduction**: ~95% for frequently accessed subscriptions

### Cache Invalidation

**Automatic**: Via `SubscriptionObserver`  
**Manual**: Via `invalidateCache()` or `invalidateMany()`  
**Bulk**: Via cache tags `Cache::tags(['subscriptions'])->flush()`

## Security Considerations

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

### Multi-Tenancy

Subscription checks respect tenant isolation through:
- User model tenant scoping
- Subscription model tenant relationships
- Policy-based authorization

## Error Handling

### Cache Failure

Graceful fallback to database queries:

```php
try {
    return $this->cache->tags([self::CACHE_TAG])->remember(...);
} catch (\Exception $e) {
    Log::error('Cache failure, falling back to database');
    return Subscription::where('user_id', $user->id)->first();
}
```

### Invalid User ID

Throws `InvalidArgumentException` with descriptive message:

```php
throw new \InvalidArgumentException(
    sprintf('Invalid user ID for cache key: %d', $user->id)
);
```

## Testing Strategy

### Unit Tests

**File**: `tests/Unit/Services/SubscriptionCheckerTest.php`

**Coverage**:
- Interface implementation
- Cache hit/miss scenarios
- Active/expired status checks
- Days until expiry calculations
- Cache invalidation (single and bulk)
- Cache warming
- Security validation
- Error handling
- Cache tags usage
- TTL configuration

### Integration Tests

**Recommended**:
- Observer triggering cache invalidation
- Event dispatching
- Multi-user scenarios
- Cache driver compatibility

## Monitoring and Observability

### Events

**SubscriptionCacheInvalidated**:
```php
Event::listen(SubscriptionCacheInvalidated::class, function ($event) {
    Log::info('Cache invalidated', ['user_id' => $event->user->id]);
});
```

**SubscriptionCacheWarmed**:
```php
Event::listen(SubscriptionCacheWarmed::class, function ($event) {
    Log::info('Cache warmed', ['user_id' => $event->user->id]);
});
```

### Metrics

Track these metrics for observability:
- Cache hit rate
- Cache invalidation frequency
- Fallback query count
- Average response time
- Error rate

### Logging

The service logs:
- Cache misses (debug level)
- Cache invalidations (info level)
- Cache failures (error level)
- Bulk operations (info level)

## Related Documentation

- [SubscriptionChecker Service Documentation](../services/SUBSCRIPTION_CHECKER_SERVICE.md)
- [SubscriptionChecker Refactoring Summary](../refactoring/SUBSCRIPTION_CHECKER_REFACTORING.md)
- [Subscription Model Documentation](../models/SUBSCRIPTION_MODEL.md)
- [Subscription Policy Documentation](../policies/SUBSCRIPTION_POLICY.md)
- [Caching Strategy](./CACHING_STRATEGY.md)

## Changelog

### 2025-12-05
- Removed `final` keyword from `SubscriptionChecker` class
- Added extensibility documentation
- Updated architecture diagrams
- Added custom implementation examples
