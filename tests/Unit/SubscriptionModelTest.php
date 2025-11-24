<?php

use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use App\Services\SubscriptionService;

test('subscription model can be created with all fields', function () {
    $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    
    $subscription = Subscription::create([
        'user_id' => $user->id,
        'plan_type' => SubscriptionPlanType::BASIC->value,
        'status' => SubscriptionStatus::ACTIVE->value,
        'starts_at' => now(),
        'expires_at' => now()->addYear(),
        'max_properties' => 10,
        'max_tenants' => 50,
    ]);

    expect($subscription)->toBeInstanceOf(Subscription::class)
        ->and($subscription->user_id)->toBe($user->id)
        ->and($subscription->plan_type)->toBe(SubscriptionPlanType::BASIC->value)
        ->and($subscription->status)->toBe(SubscriptionStatus::ACTIVE->value)
        ->and($subscription->max_properties)->toBe(10)
        ->and($subscription->max_tenants)->toBe(50);
});

test('subscription isActive returns true for active subscription with future expiry', function () {
    $subscription = Subscription::factory()->create([
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addMonth(),
    ]);

    expect($subscription->isActive())->toBeTrue();
});

test('subscription isActive returns false for expired subscription', function () {
    $subscription = Subscription::factory()->create([
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->subDay(),
    ]);

    expect($subscription->isActive())->toBeFalse();
});

test('subscription isActive returns false for suspended subscription', function () {
    $subscription = Subscription::factory()->create([
        'status' => SubscriptionStatus::SUSPENDED->value,
        'expires_at' => now()->addMonth(),
    ]);

    expect($subscription->isActive())->toBeFalse();
});

test('subscription isExpired returns true for past expiry date', function () {
    $subscription = Subscription::factory()->create([
        'expires_at' => now()->subDay(),
    ]);

    expect($subscription->isExpired())->toBeTrue();
});

test('subscription isExpired returns false for future expiry date', function () {
    $subscription = Subscription::factory()->create([
        'expires_at' => now()->addMonth(),
    ]);

    expect($subscription->isExpired())->toBeFalse();
});

test('subscription daysUntilExpiry returns correct number of days', function () {
    $subscription = Subscription::factory()->create([
        'expires_at' => now()->addDays(10),
    ]);

    expect($subscription->daysUntilExpiry())->toBeGreaterThanOrEqual(9)
        ->and($subscription->daysUntilExpiry())->toBeLessThanOrEqual(10);
});

test('subscription daysUntilExpiry returns negative for expired subscription', function () {
    $subscription = Subscription::factory()->create([
        'expires_at' => now()->subDays(5),
    ]);

    expect($subscription->daysUntilExpiry())->toBeLessThan(0);
});

test('subscription canAddProperty returns true when under limit', function () {
    $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addMonth(),
        'max_properties' => 10,
    ]);

    expect($subscription->canAddProperty())->toBeTrue();
});

test('subscription canAddProperty returns false when subscription is not active', function () {
    $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::EXPIRED->value,
        'expires_at' => now()->subDay(),
        'max_properties' => 10,
    ]);

    expect($subscription->canAddProperty())->toBeFalse();
});

test('subscription canAddTenant returns true when under limit', function () {
    $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addMonth(),
        'max_tenants' => 50,
    ]);

    expect($subscription->canAddTenant())->toBeTrue();
});

test('subscription canAddTenant returns false when subscription is not active', function () {
    $user = User::factory()->create(['role' => 'admin', 'tenant_id' => 1]);
    $subscription = Subscription::factory()->create([
        'user_id' => $user->id,
        'status' => SubscriptionStatus::SUSPENDED->value,
        'expires_at' => now()->addMonth(),
        'max_tenants' => 50,
    ]);

    expect($subscription->canAddTenant())->toBeFalse();
});
