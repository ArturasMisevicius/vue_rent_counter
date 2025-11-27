<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Log::spy();
});

test('superadmin users bypass subscription check', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
    ]);

    $this->actingAs($superadmin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('tenant users bypass subscription check', function () {
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);

    $this->actingAs($tenant)
        ->get(route('tenant.dashboard'))
        ->assertOk();
});

test('admin with active subscription has full access', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE,
        'expires_at' => now()->addMonths(6),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionMissing('warning')
        ->assertSessionMissing('error');
});

test('admin with expired subscription gets read-only access for GET requests', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(5),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionHas('warning');

    Log::shouldHaveReceived('channel')
        ->with('audit')
        ->once();
});

test('admin with expired subscription cannot perform write operations', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(5),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.dashboard'))
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('error');
});

test('admin with suspended subscription gets read-only access', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::SUSPENDED,
        'expires_at' => now()->addMonths(1),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionHas('warning');
});

test('admin with cancelled subscription gets read-only access', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::CANCELLED,
        'expires_at' => now()->addMonths(1),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionHas('warning');
});

test('admin without subscription can access dashboard but sees error', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionHas('error');
});

test('admin without subscription cannot access other routes', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.properties.index'))
        ->assertRedirect(route('admin.dashboard'))
        ->assertSessionHas('error');
});

test('subscription checks are logged for audit trail', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::EXPIRED,
        'expires_at' => now()->subDays(5),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'));

    Log::shouldHaveReceived('channel')
        ->with('audit')
        ->once();
});

test('admin with active status but expired date is treated as expired', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => SubscriptionStatus::ACTIVE,
        'expires_at' => now()->subDays(1), // Expired date
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSessionHas('warning');
});

test('manager role is treated same as admin for subscription checks', function () {
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);

    Subscription::factory()->create([
        'user_id' => $manager->id,
        'status' => SubscriptionStatus::ACTIVE,
        'expires_at' => now()->addMonths(6),
    ]);

    $this->actingAs($manager)
        ->get(route('manager.dashboard'))
        ->assertOk();
});
