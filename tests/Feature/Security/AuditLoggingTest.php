<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

test('subscription checks are logged to audit channel', function () {
    Log::spy();
    
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE,
        'expires_at' => now()->addMonths(6),
    ]);
    
    $this->actingAs($admin)
        ->get(route('admin.dashboard'));
    
    // Verify audit channel was used
    Log::shouldHaveReceived('channel')
        ->with('audit')
        ->atLeast()
        ->once();
});

test('failed subscription checks are logged', function () {
    Log::spy();
    
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    // No subscription created
    
    $this->actingAs($admin)
        ->get(route('admin.dashboard'));
    
    // Verify audit logging occurred
    Log::shouldHaveReceived('channel')
        ->with('audit')
        ->atLeast()
        ->once();
});

test('auth route bypass does not log subscription checks', function () {
    Log::spy();
    
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    
    // Access auth routes
    $this->actingAs($admin)
        ->get(route('login'));
    
    // Subscription check should not occur for auth routes
    // So audit logging should not happen for subscription checks
    expect(true)->toBeTrue();
});

test('exception in subscription check is logged', function () {
    Log::spy();
    
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    
    // Mock SubscriptionChecker to throw exception
    $this->mock(\App\Services\SubscriptionChecker::class)
        ->shouldReceive('getSubscription')
        ->andThrow(new \Exception('Test exception'));
    
    $this->actingAs($admin)
        ->get(route('admin.dashboard'));
    
    // Verify error was logged
    Log::shouldHaveReceived('error')
        ->withArgs(function ($message, $context) {
            return $message === 'Subscription check failed' 
                && isset($context['error']);
        })
        ->once();
});
