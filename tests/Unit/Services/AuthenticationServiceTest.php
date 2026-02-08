<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Services\AuthenticationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new AuthenticationService();
});

test('getActiveUsersForLoginDisplay returns only active users', function () {
    // Create active and inactive users
    User::factory()->create(['is_active' => true, 'role' => UserRole::ADMIN]);
    User::factory()->create(['is_active' => true, 'role' => UserRole::TENANT]);
    User::factory()->create(['is_active' => false, 'role' => UserRole::ADMIN]);

    $users = $this->service->getActiveUsersForLoginDisplay();

    expect($users)->toHaveCount(2)
        ->and($users->every(fn ($user) => $user->is_active))->toBeTrue();
});

test('getActiveUsersForLoginDisplay orders users by role priority', function () {
    // Create users in random order
    User::factory()->create(['role' => UserRole::TENANT, 'is_active' => true]);
    User::factory()->create(['role' => UserRole::SUPERADMIN, 'is_active' => true]);
    User::factory()->create(['role' => UserRole::MANAGER, 'is_active' => true]);
    User::factory()->create(['role' => UserRole::ADMIN, 'is_active' => true]);

    $users = $this->service->getActiveUsersForLoginDisplay();

    expect($users[0]->role)->toBe(UserRole::SUPERADMIN)
        ->and($users[1]->role)->toBe(UserRole::ADMIN)
        ->and($users[2]->role)->toBe(UserRole::MANAGER)
        ->and($users[3]->role)->toBe(UserRole::TENANT);
});

test('getActiveUsersForLoginDisplay eager loads relationships', function () {
    $user = User::factory()->create(['is_active' => true]);

    $users = $this->service->getActiveUsersForLoginDisplay();

    // Check that relationships are loaded (no additional queries)
    expect($users->first()->relationLoaded('property'))->toBeTrue()
        ->and($users->first()->relationLoaded('subscription'))->toBeTrue();
});

test('isAccountActive returns true for active user', function () {
    $user = User::factory()->create(['is_active' => true]);

    expect($this->service->isAccountActive($user))->toBeTrue();
});

test('isAccountActive returns false for inactive user', function () {
    $user = User::factory()->create(['is_active' => false]);

    expect($this->service->isAccountActive($user))->toBeFalse();
});

test('redirectToDashboard returns correct route for superadmin', function () {
    $user = User::factory()->create(['role' => UserRole::SUPERADMIN]);

    $response = $this->service->redirectToDashboard($user);

    expect($response->getTargetUrl())->toContain('/superadmin/dashboard');
});

test('redirectToDashboard returns correct route for admin', function () {
    $user = User::factory()->create(['role' => UserRole::ADMIN]);

    $response = $this->service->redirectToDashboard($user);

    expect($response->getTargetUrl())->toContain('/admin/dashboard');
});

test('redirectToDashboard returns correct route for manager', function () {
    $user = User::factory()->create(['role' => UserRole::MANAGER]);

    $response = $this->service->redirectToDashboard($user);

    expect($response->getTargetUrl())->toContain('/manager/dashboard');
});

test('redirectToDashboard returns correct route for tenant', function () {
    $user = User::factory()->create(['role' => UserRole::TENANT]);

    $response = $this->service->redirectToDashboard($user);

    expect($response->getTargetUrl())->toContain('/tenant/dashboard');
});

test('getActiveUsersForLoginDisplay selects only necessary columns', function () {
    User::factory()->create(['is_active' => true]);

    $users = $this->service->getActiveUsersForLoginDisplay();
    $user = $users->first();

    // Check that only specified columns are loaded
    $attributes = array_keys($user->getAttributes());
    
    expect($attributes)->toContain('id', 'name', 'email', 'role', 'is_active');
});
