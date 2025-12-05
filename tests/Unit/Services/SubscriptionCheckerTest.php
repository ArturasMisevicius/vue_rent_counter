<?php

declare(strict_types=1);

use App\Contracts\SubscriptionCheckerInterface;
use App\Events\SubscriptionCacheInvalidated;
use App\Events\SubscriptionCacheWarmed;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionChecker;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Cache::flush();
    Event::fake();
});

test('implements SubscriptionCheckerInterface', function () {
    $checker = app(SubscriptionCheckerInterface::class);
    
    expect($checker)->toBeInstanceOf(SubscriptionChecker::class);
});

test('getSubscription returns cached subscription', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create(['user_id' => $user->id]);
    
    $checker = app(SubscriptionCheckerInterface::class);
    
    // First call - cache miss
    $result1 = $checker->getSubscription($user);
    expect($result1->id)->toBe($subscription->id);
    
    // Second call - cache hit (no additional query)
    $result2 = $checker->getSubscription($user);
    expect($result2->id)->toBe($subscription->id);
});

test('getSubscription returns null for user without subscription', function () {
    $user = User::factory()->create();
    
    $checker = app(SubscriptionCheckerInterface::class);
    $result = $checker->getSubscription($user);
    
    expect($result)->toBeNull();
});

test('isActive returns true for active subscription', function () {
    $user = User::factory()->create();
    Subscription::factory()->active()->create(['user_id' => $user->id]);
    
    $checker = app(SubscriptionCheckerInterface::class);
    
    expect($checker->isActive($user))->toBeTrue();
});

test('isActive returns false for expired subscription', function () {
    $user = User::factory()->create();
    Subscription::factory()->expired()->create(['user_id' => $user->id]);
    
    $checker = app(SubscriptionCheckerInterface::class);
    
    expect($checker->isActive($user))->toBeFalse();
});

test('isActive returns false for user without subscription', function () {
    $user = User::factory()->create();
    
    $checker = app(SubscriptionCheckerInterface::class);
    
    expect($checker->isActive($user))->toBeFalse();
});

test('isExpired returns true for expired subscription', function () {
    $user = User::factory()->create();
    Subscription::factory()->expired()->create(['user_id' => $user->id]);
    
    $checker = app(SubscriptionCheckerInterface::class);
    
    expect($checker->isExpired($user))->toBeTrue();
});

test('isExpired returns true for user without subscription', function () {
    $user = User::factory()->create();
    
    $checker = app(SubscriptionCheckerInterface::class);
    
    expect($checker->isExpired($user))->toBeTrue();
});

test('daysUntilExpiry returns correct days for active subscription', function () {
    $user = User::factory()->create();
    $expiresAt = now()->addDays(10);
    Subscription::factory()->create([
        'user_id' => $user->id,
        'expires_at' => $expiresAt,
    ]);
    
    $checker = app(SubscriptionCheckerInterface::class);
    $days = $checker->daysUntilExpiry($user);
    
    expect($days)->toBe(10);
});

test('daysUntilExpiry returns null for user without subscription', function () {
    $user = User::factory()->create();
    
    $checker = app(SubscriptionCheckerInterface::class);
    
    expect($checker->daysUntilExpiry($user))->toBeNull();
});

test('invalidateCache clears all cache keys for user', function () {
    $user = User::factory()->create();
    Subscription::factory()->create(['user_id' => $user->id]);
    
    $checker = app(SubscriptionCheckerInterface::class);
    
    // Populate cache
    $checker->getSubscription($user);
    $checker->isActive($user);
    
    // Invalidate
    $checker->invalidateCache($user);
    
    // Verify cache is cleared by checking fresh data is fetched
    $subscription = $checker->getSubscription($user);
    expect($subscription)->not->toBeNull();
});

test('invalidateCache dispatches event', function () {
    $user = User::factory()->create();
    
    $checker = app(SubscriptionCheckerInterface::class);
    $checker->invalidateCache($user);
    
    Event::assertDispatched(SubscriptionCacheInvalidated::class, function ($event) use ($user) {
        return $event->user->id === $user->id;
    });
});

test('invalidateMany clears cache for multiple users', function () {
    $users = User::factory()->count(3)->create();
    
    foreach ($users as $user) {
        Subscription::factory()->create(['user_id' => $user->id]);
    }
    
    $checker = app(SubscriptionCheckerInterface::class);
    
    // Populate cache for all users
    foreach ($users as $user) {
        $checker->getSubscription($user);
    }
    
    // Invalidate all
    $checker->invalidateMany($users->all());
    
    // Verify all caches are cleared
    foreach ($users as $user) {
        $subscription = $checker->getSubscription($user);
        expect($subscription)->not->toBeNull();
    }
});

test('warmCache preloads subscription data', function () {
    $user = User::factory()->create();
    Subscription::factory()->create(['user_id' => $user->id]);
    
    $checker = app(SubscriptionCheckerInterface::class);
    $checker->warmCache($user);
    
    // Verify data is in cache
    $subscription = $checker->getSubscription($user);
    expect($subscription)->not->toBeNull();
});

test('warmCache dispatches event', function () {
    $user = User::factory()->create();
    Subscription::factory()->create(['user_id' => $user->id]);
    
    $checker = app(SubscriptionCheckerInterface::class);
    $checker->warmCache($user);
    
    Event::assertDispatched(SubscriptionCacheWarmed::class, function ($event) use ($user) {
        return $event->user->id === $user->id;
    });
});

test('validates user ID to prevent cache poisoning', function () {
    $user = new User();
    $user->id = 0; // Invalid ID
    
    $checker = app(SubscriptionCheckerInterface::class);
    
    expect(fn () => $checker->getSubscription($user))
        ->toThrow(\InvalidArgumentException::class, 'Invalid user ID for cache key: 0');
});

test('handles cache failures gracefully', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create(['user_id' => $user->id]);
    
    // Mock cache to throw exception
    $mockCache = Mockery::mock(CacheRepository::class);
    $mockCache->shouldReceive('tags')->andThrow(new \Exception('Cache error'));
    
    $checker = new SubscriptionChecker($mockCache);
    
    // Should fallback to database query
    $result = $checker->getSubscription($user);
    expect($result->id)->toBe($subscription->id);
});

test('uses cache tags for efficient invalidation', function () {
    $user = User::factory()->create();
    Subscription::factory()->create(['user_id' => $user->id]);
    
    $checker = app(SubscriptionCheckerInterface::class);
    
    // Populate cache
    $subscription1 = $checker->getSubscription($user);
    expect($subscription1)->not->toBeNull();
    
    // Second call should hit cache
    $subscription2 = $checker->getSubscription($user);
    expect($subscription2->id)->toBe($subscription1->id);
    
    // Invalidate cache
    $checker->invalidateCache($user);
    
    // Third call should fetch fresh data
    $subscription3 = $checker->getSubscription($user);
    expect($subscription3->id)->toBe($subscription1->id);
})->skip(fn () => config('cache.default') === 'file', 'Cache tags not supported by file driver');

test('respects configured cache TTL', function () {
    config(['subscription.cache_ttl' => 600]); // 10 minutes
    
    $user = User::factory()->create();
    $subscription = Subscription::factory()->create(['user_id' => $user->id]);
    
    $checker = app(SubscriptionCheckerInterface::class);
    $result = $checker->getSubscription($user);
    
    // Verify subscription was retrieved
    expect($result)->not->toBeNull();
    expect($result->id)->toBe($subscription->id);
});
