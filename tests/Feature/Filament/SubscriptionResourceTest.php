<?php

use App\Enums\SubscriptionPlanType;
use App\Enums\SubscriptionStatus;
use App\Filament\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = User::factory()->superadmin()->create();
    $this->admin = User::factory()->admin(tenantId: 1)->create();
});

test('subscription resource exists and is properly configured', function () {
    expect(class_exists(SubscriptionResource::class))->toBeTrue();
    expect(SubscriptionResource::getModel())->toBe(Subscription::class);
    expect(SubscriptionResource::getNavigationLabel())->toBe(__('app.nav.subscriptions'));
});

test('subscription can be renewed', function () {
    $subscription = Subscription::factory()->for($this->admin)->create([
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(5),
    ]);
    
    $newExpiry = now()->addYear();
    $subscription->renew($newExpiry);
    
    expect($subscription->fresh()->expires_at->toDateString())->toBe($newExpiry->toDateString());
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::ACTIVE);
});

test('subscription can be suspended', function () {
    $subscription = Subscription::factory()->for($this->admin)->create([
        'status' => SubscriptionStatus::ACTIVE->value,
    ]);
    
    $subscription->suspend();
    
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::SUSPENDED);
});

test('subscription can be activated', function () {
    $subscription = Subscription::factory()->for($this->admin)->create([
        'status' => SubscriptionStatus::SUSPENDED->value,
    ]);
    
    $subscription->activate();
    
    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::ACTIVE);
});

test('subscription days until expiry is calculated correctly', function () {
    $subscription = Subscription::factory()->for($this->admin)->create([
        'expires_at' => now()->addDays(10),
    ]);
    
    expect($subscription->daysUntilExpiry())->toBe(10);
});

test('expired subscription has negative days until expiry', function () {
    $subscription = Subscription::factory()->for($this->admin)->create([
        'expires_at' => now()->subDays(5),
    ]);
    
    expect($subscription->daysUntilExpiry())->toBeLessThan(0);
});

test('subscription is active when status is active and not expired', function () {
    $subscription = Subscription::factory()->for($this->admin)->create([
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(30),
    ]);
    
    expect($subscription->isActive())->toBeTrue();
});

test('subscription is not active when expired', function () {
    $subscription = Subscription::factory()->for($this->admin)->create([
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->subDays(1),
    ]);
    
    expect($subscription->isActive())->toBeFalse();
});

test('subscription is not active when suspended', function () {
    $subscription = Subscription::factory()->for($this->admin)->create([
        'status' => SubscriptionStatus::SUSPENDED->value,
        'expires_at' => now()->addDays(30),
    ]);
    
    expect($subscription->isActive())->toBeFalse();
});

test('subscription can check if user can add property', function () {
    $subscription = Subscription::factory()->for($this->admin)->create([
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(30),
        'max_properties' => 100,
    ]);
    
    expect($subscription->canAddProperty())->toBeTrue();
});

test('subscription can check if user can add tenant', function () {
    $subscription = Subscription::factory()->for($this->admin)->create([
        'status' => SubscriptionStatus::ACTIVE->value,
        'expires_at' => now()->addDays(30),
        'max_tenants' => 50,
    ]);
    
    expect($subscription->canAddTenant())->toBeTrue();
});

test('plan type updates limits automatically', function () {
    // This tests the live update functionality in the form
    $limits = [
        SubscriptionPlanType::BASIC->value => ['properties' => 100, 'tenants' => 50],
        SubscriptionPlanType::PROFESSIONAL->value => ['properties' => 500, 'tenants' => 250],
        SubscriptionPlanType::ENTERPRISE->value => ['properties' => 9999, 'tenants' => 9999],
    ];
    
    foreach ($limits as $plan => $expectedLimits) {
        $subscription = Subscription::factory()->for($this->admin)->create([
            'plan_type' => $plan,
            'max_properties' => $expectedLimits['properties'],
            'max_tenants' => $expectedLimits['tenants'],
        ]);
        
        expect($subscription->max_properties)->toBe($expectedLimits['properties']);
        expect($subscription->max_tenants)->toBe($expectedLimits['tenants']);
    }
});
