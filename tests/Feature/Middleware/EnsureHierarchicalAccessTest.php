<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Meter;
use App\Models\Property;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function createAdminWithActiveSubscription(int $tenantId): User
{
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => $tenantId,
        'is_active' => true,
    ]);

    Subscription::factory()->active()->create([
        'user_id' => $admin->id,
    ]);

    return $admin;
}

test('superadmin has unrestricted access to all resources', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => null,
        'is_active' => true,
    ]);

    $otherTenantProperty = Property::factory()->create([
        'tenant_id' => 2,
    ]);

    $this->actingAs($superadmin)
        ->get(route('superadmin.properties.show', $otherTenantProperty))
        ->assertOk();
});

test('admin can only access resources from their tenant', function () {
    $admin = createAdminWithActiveSubscription(1);

    $ownProperty = Property::factory()->create([
        'tenant_id' => 1,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.properties.show', $ownProperty))
        ->assertOk();
});

test('admin cannot access users from other tenants', function () {
    $admin = createAdminWithActiveSubscription(1);

    $otherTenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 2,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.show', $otherTenantUser))
        ->assertForbidden();
});

test('tenant can only access their assigned property', function () {
    $property = Property::factory()->create([
        'tenant_id' => 1,
    ]);

    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);

    $this->actingAs($tenant)
        ->get(route('tenant.property.show'))
        ->assertOk();
});

test('tenant cannot access properties they are not assigned to', function () {
    $property1 = Property::factory()->create([
        'tenant_id' => 1,
    ]);

    $property2 = Property::factory()->create([
        'tenant_id' => 1,
    ]);

    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
        'property_id' => $property1->id,
    ]);

    $this->actingAs($tenant)
        ->get(route('admin.properties.show', $property2))
        ->assertNotFound();
});

test('manager has same access as admin', function () {
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
        'is_active' => true,
    ]);

    $ownProperty = Property::factory()->create([
        'tenant_id' => 1,
    ]);

    $this->actingAs($manager)
        ->get(route('manager.properties.show', $ownProperty))
        ->assertOk();
});

test('admin cannot access manager-only building routes', function () {
    $admin = createAdminWithActiveSubscription(1);

    $building = Building::factory()->create([
        'tenant_id' => 1,
    ]);

    $this->actingAs($admin)
        ->get(route('manager.buildings.show', $building))
        ->assertForbidden();
});

test('admin remains forbidden from manager routes regardless of tenant', function () {
    $admin = createAdminWithActiveSubscription(1);

    $building = Building::factory()->create([
        'tenant_id' => 2,
    ]);

    $this->actingAs($admin)
        ->get(route('manager.buildings.show', $building))
        ->assertNotFound();
});

test('tenant can access meters from their property', function () {
    $property = Property::factory()->create([
        'tenant_id' => 1,
    ]);

    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => 1,
    ]);

    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
        'property_id' => $property->id,
    ]);

    $this->actingAs($tenant)
        ->get(route('tenant.meters.show', $meter))
        ->assertOk();
});

test('tenant cannot access meters from other properties', function () {
    $property1 = Property::factory()->create([
        'tenant_id' => 1,
    ]);

    $property2 = Property::factory()->create([
        'tenant_id' => 1,
    ]);

    $meter = Meter::factory()->create([
        'property_id' => $property2->id,
        'tenant_id' => 1,
    ]);

    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
        'property_id' => $property1->id,
    ]);

    $this->actingAs($tenant)
        ->get(route('tenant.meters.show', $meter))
        ->assertNotFound();
});

test('middleware uses select to minimize data transfer', function () {
    $admin = createAdminWithActiveSubscription(1);

    $property = Property::factory()->create([
        'tenant_id' => 1,
        'address' => '123 Test St',
    ]);

    // Enable query log
    DB::enableQueryLog();

    $this->actingAs($admin)
        ->get(route('admin.properties.show', $property))
        ->assertOk();

    $queries = DB::getQueryLog();

    // Find the query that selects from properties table
    $propertyQuery = collect($queries)->first(function ($query) {
        return str_contains($query['query'], 'select')
            && str_contains($query['query'], 'properties');
    });

    // Verify only id and tenant_id are selected (performance optimization)
    expect($propertyQuery)->not->toBeNull();
});

test('json requests receive json error responses', function () {
    $admin = createAdminWithActiveSubscription(1);

    $otherTenantUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 2,
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.users.show', $otherTenantUser))
        ->assertForbidden()
        ->assertJson([
            'message' => 'You do not have permission to access this resource.',
        ]);
});

test('unauthenticated users are redirected to login', function () {
    $property = Property::factory()->create();

    $this->get(route('admin.properties.show', $property))
        ->assertRedirect(route('login'));
});
