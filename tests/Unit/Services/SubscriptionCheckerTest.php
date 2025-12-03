<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionChecker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->checker = new SubscriptionChecker();
    Cache::flush();
});

test('isActive returns true for active subscriptions', function () {
    $user = User::factory()->create();
    
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addMonths(6),
    ]);

    expect($this->checker->isActive($user))->toBeTrue();
});

test('isActive returns false for expired subscriptions', function () {
    $user = User::factory()->create();
    
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::EXPIRED->value,
        'expires_at' => now()->subDays(5),
    ]);

    expect($this->checker->isActive($user))->toBeFalse();
});

test('isActive returns false when no subscription exists', function () {
    $user = User::factory()->create();

    expect($this->checker->isActive($user))->toBeFalse();
});

test('isActive caches the result', function () {
    $user = User::factory()->create();
    
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addMonths(6),
    ]);

    // First call - should hit database
    $this->checker->isActive($user);
    
    // Second call - should use cache
    $cached = Cache::get("subscription.{$user->id}.status");
    
    expect($cached)->toBeTrue();
});

test('getSubscription returns subscription for user', function () {
    $user = User::factory()->create();
    
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::ACTIVE->value,
    ]);

    $result = $this->checker->getSubscription($user);
    
    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($subscription->id);
});

test('getSubscription returns null when no subscription exists', function () {
    $user = User::factory()->create();

    expect($this->checker->getSubscription($user))->toBeNull();
});

test('getSubscription caches the result', function () {
    $user = User::factory()->create();
    
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::ACTIVE->value,
    ]);

    // First call
    $this->checker->getSubscription($user);
    
    // Check cache
    $cached = Cache::get("subscription.{$user->id}.subscription");
    
    expect($cached)->not->toBeNull();
});

test('isExpired returns true for expired subscriptions', function () {
    $user = User::factory()->create();
    
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->subDays(1),
    ]);

    expect($this->checker->isExpired($user))->toBeTrue();
});

test('isExpired returns false for active subscriptions', function () {
    $user = User::factory()->create();
    
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addMonths(6),
    ]);

    expect($this->checker->isExpired($user))->toBeFalse();
});

test('isExpired returns true when no subscription exists', function () {
    $user = User::factory()->create();

    expect($this->checker->isExpired($user))->toBeTrue();
});

test('daysUntilExpiry returns correct number of days', function () {
    $user = User::factory()->create();
    
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(30),
    ]);

    $days = $this->checker->daysUntilExpiry($user);
    
    expect($days)->toBe(30);
});

test('daysUntilExpiry returns negative for expired subscriptions', function () {
    $user = User::factory()->create();
    
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::EXPIRED->value,
        'expires_at' => now()->subDays(5),
    ]);

    $days = $this->checker->daysUntilExpiry($user);
    
    expect($days)->toBe(-5);
});

test('daysUntilExpiry returns null when no subscription exists', function () {
    $user = User::factory()->create();

    expect($this->checker->daysUntilExpiry($user))->toBeNull();
});

test('invalidate clears cached subscription data', function () {
    $user = User::factory()->create();
    
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::ACTIVE->value,
    ]);

    // Cache the data
    $this->checker->isActive($user);
    $this->checker->getSubscription($user);
    
    // Verify cache exists
    expect(Cache::has("subscription.{$user->id}.status"))->toBeTrue()
        ->and(Cache::has("subscription.{$user->id}.subscription"))->toBeTrue();
    
    // Invalidate
    $this->checker->invalidate($user);
    
    // Verify cache cleared
    expect(Cache::has("subscription.{$user->id}.status"))->toBeFalse()
        ->and(Cache::has("subscription.{$user->id}.subscription"))->toBeFalse();
});

test('invalidateMany clears cache for multiple users', function () {
    $users = User::factory()->count(3)->create();
    
    foreach ($users as $user) {
        Subscription::factory()->create([
            'user_id' => $user->id,
            'status' => SubscriptionStatus::ACTIVE->value,
        ]);
        
        // Cache the data
        $this->checker->isActive($user);
    }
    
    // Invalidate all
    $this->checker->invalidateMany($users->all());
    
    // Verify all caches cleared
    foreach ($users as $user) {
        expect(Cache::has("subscription.{$user->id}.status"))->toBeFalse();
    }
});

test('warmCache pre-loads subscription data', function () {
    $user = User::factory()->create();
    
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::ACTIVE->value,
    ]);

    // Warm cache
    $this->checker->warmCache($user);
    
    // Verify cache exists
    expect(Cache::has("subscription.{$user->id}.status"))->toBeTrue()
        ->and(Cache::has("subscription.{$user->id}.subscription"))->toBeTrue();
});

test('cache expires after TTL', function () {
    $user = User::factory()->create();
    
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::ACTIVE->value,
    ]);

    // Cache the data
    $this->checker->isActive($user);
    
    // Verify cache exists
    expect(Cache::has("subscription.{$user->id}.status"))->toBeTrue();
    
    // Travel forward 6 minutes (past TTL)
    $this->travel(6)->minutes();
    
    // Verify cache expired
    expect(Cache::has("subscription.{$user->id}.status"))->toBeFalse();
});

test('multiple calls use cached data', function () {
    $user = User::factory()->create();
    
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::ACTIVE->value,
    ]);

    // Enable query log
    DB::enableQueryLog();

    // First call - hits database
    $this->checker->isActive($user);
    $firstQueryCount = count(DB::getQueryLog());

    // Clear query log
    DB::flushQueryLog();

    // Second call - uses cache
    $this->checker->isActive($user);
    $secondQueryCount = count(DB::getQueryLog());
    
    // Second call should have fewer queries (cached)
    expect($secondQueryCount)->toBeLessThan($firstQueryCount);
});
