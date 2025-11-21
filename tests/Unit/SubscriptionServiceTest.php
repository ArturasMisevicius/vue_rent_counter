<?php

use App\Exceptions\SubscriptionExpiredException;
use App\Exceptions\SubscriptionLimitExceededException;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;

test('subscription service can create subscription', function () {
    $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    $service = new SubscriptionService();
    
    $subscription = $service->createSubscription($user, 'basic', now()->addYear());

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->user_id)->toBe($user->id)
        ->and($subscription->plan_type)->toBe('basic')
        ->and($subscription->status)->toBe('active')
        ->and($subscription->max_properties)->toBe(10)
        ->and($subscription->max_tenants)->toBe(50);
});

test('subscription service can renew subscription', function () {
    $subscription = Subscription::factory()->expired()->create();
    $service = new SubscriptionService();
    
    $newExpiryDate = now()->addYear();
    $renewed = $service->renewSubscription($subscription, $newExpiryDate);

    expect($renewed->status)->toBe('active')
        ->and($renewed->expires_at->format('Y-m-d'))->toBe($newExpiryDate->format('Y-m-d'));
});

test('subscription service can suspend subscription', function () {
    $subscription = Subscription::factory()->create();
    $service = new SubscriptionService();
    
    $service->suspendSubscription($subscription, 'Payment failed');

    expect($subscription->fresh()->status)->toBe('suspended');
});

test('subscription service can cancel subscription', function () {
    $subscription = Subscription::factory()->create();
    $service = new SubscriptionService();
    
    $service->cancelSubscription($subscription);

    expect($subscription->fresh()->status)->toBe('cancelled');
});

test('subscription service checkSubscriptionStatus returns correct data for user with subscription', function () {
    $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'expires_at' => now()->addDays(30),
    ]);
    $service = new SubscriptionService();
    
    $status = $service->checkSubscriptionStatus($user);

    expect($status['has_subscription'])->toBeTrue()
        ->and($status['is_active'])->toBeTrue()
        ->and($status['status'])->toBe('active')
        ->and($status['days_until_expiry'])->toBeGreaterThanOrEqual(29)
        ->and($status['days_until_expiry'])->toBeLessThanOrEqual(30);
});

test('subscription service checkSubscriptionStatus returns correct data for user without subscription', function () {
    $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    $service = new SubscriptionService();
    
    $status = $service->checkSubscriptionStatus($user);

    expect($status['has_subscription'])->toBeFalse()
        ->and($status['is_active'])->toBeFalse()
        ->and($status['max_properties'])->toBe(0)
        ->and($status['max_tenants'])->toBe(0);
});

test('subscription service enforceSubscriptionLimits throws exception for expired subscription', function () {
    $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    Subscription::factory()->expired()->create(['user_id' => $user->id]);
    $service = new SubscriptionService();
    
    $service->enforceSubscriptionLimits($user);
})->throws(SubscriptionExpiredException::class);

test('subscription service enforceSubscriptionLimits throws exception for no subscription', function () {
    $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    $service = new SubscriptionService();
    
    $service->enforceSubscriptionLimits($user);
})->throws(SubscriptionExpiredException::class);

test('subscription service enforceSubscriptionLimits passes for active subscription', function () {
    $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'expires_at' => now()->addMonth(),
    ]);
    $service = new SubscriptionService();
    
    $service->enforceSubscriptionLimits($user);

    expect(true)->toBeTrue(); // If no exception, test passes
});

test('subscription service enforceSubscriptionLimits throws exception when property limit exceeded', function () {
    $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'expires_at' => now()->addMonth(),
        'max_properties' => 0,
    ]);
    $service = new SubscriptionService();
    
    $service->enforceSubscriptionLimits($user, 'property');
})->throws(SubscriptionLimitExceededException::class);

test('subscription service enforceSubscriptionLimits throws exception when tenant limit exceeded', function () {
    $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => 'active',
        'expires_at' => now()->addMonth(),
        'max_tenants' => 0,
    ]);
    $service = new SubscriptionService();
    
    $service->enforceSubscriptionLimits($user, 'tenant');
})->throws(SubscriptionLimitExceededException::class);
