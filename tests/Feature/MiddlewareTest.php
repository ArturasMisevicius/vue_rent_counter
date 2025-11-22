<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('CheckSubscriptionStatus middleware allows superadmin without subscription check', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);

    $this->actingAs($superadmin)
        ->get('/superadmin/dashboard')
        ->assertStatus(200);
});

test('CheckSubscriptionStatus middleware allows admin with active subscription', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => 'active',
        'expires_at' => now()->addDays(30),
    ]);

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertStatus(200);
});

test('CheckSubscriptionStatus middleware allows GET requests for expired subscription', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => 'expired',
        'expires_at' => now()->subDays(1),
    ]);

    $this->actingAs($admin)
        ->get('/admin/dashboard')
        ->assertStatus(200)
        ->assertSessionHas('warning');
});

test('EnsureHierarchicalAccess middleware allows superadmin unrestricted access', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
    ]);

    $building = Building::factory()->create(['tenant_id' => 999]);

    $this->actingAs($superadmin)
        ->get("/buildings/{$building->id}")
        ->assertStatus(200);
});

test('EnsureHierarchicalAccess middleware blocks admin from accessing other tenant resources', function () {
    $admin1 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    $admin2 = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 2,
    ]);

    $building = Building::factory()->create(['tenant_id' => 2]);

    // HierarchicalScope filters the data, so we expect 404 (not found) rather than 403
    $this->actingAs($admin1)
        ->get("/buildings/{$building->id}")
        ->assertStatus(404);
});

test('EnsureHierarchicalAccess middleware allows admin to access own tenant resources', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    Subscription::factory()->create([
        'user_id' => $admin->id,
        'status' => 'active',
        'expires_at' => now()->addDays(30),
    ]);

    $building = Building::factory()->create(['tenant_id' => 1]);

    $this->actingAs($admin)
        ->get("/buildings/{$building->id}")
        ->assertStatus(200);
});

test('EnsureHierarchicalAccess middleware blocks tenant from accessing other property resources', function () {
    $property1 = Property::factory()->create(['tenant_id' => 1]);
    $property2 = Property::factory()->create(['tenant_id' => 1]);

    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
        'property_id' => $property1->id,
    ]);

    // HierarchicalScope filters the data, so we expect 404 (not found) rather than 403
    $this->actingAs($tenant)
        ->get("/properties/{$property2->id}")
        ->assertStatus(404);
});

test('EnsureHierarchicalAccess middleware allows tenant to access own property', function () {
    $property = Property::factory()->create(['tenant_id' => 1]);

    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);

    $this->actingAs($tenant)
        ->get("/properties/{$property->id}")
        ->assertStatus(200);
});
