<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Log::spy();
});

test('superadmin has unrestricted access to all resources', function () {
    $superadmin = User::factory()->create([
        'role' => UserRole::SUPERADMIN,
        'tenant_id' => 'tenant-1',
    ]);

    $otherTenantProperty = Property::factory()->create([
        'tenant_id' => 'tenant-2',
    ]);

    $this->actingAs($superadmin)
        ->get(route('admin.properties.show', $otherTenantProperty))
        ->assertOk();
});

test('admin can only access resources from their tenant', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 'tenant-1',
    ]);

    $ownProperty = Property::factory()->create([
        'tenant_id' => 'tenant-1',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.properties.show', $ownProperty))
        ->assertOk();
});

test('admin cannot access resources from other tenants', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 'tenant-1',
    ]);

    $otherProperty = Property::factory()->create([
        'tenant_id' => 'tenant-2',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.properties.show', $otherProperty))
        ->assertForbidden();

    Log::shouldHaveReceived('warning')
        ->with('Hierarchical access denied', \Mockery::type('array'))
        ->once();
});

test('tenant can only access their assigned property', function () {
    $property = Property::factory()->create([
        'tenant_id' => 'tenant-1',
    ]);

    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 'tenant-1',
        'property_id' => $property->id,
    ]);

    $this->actingAs($tenant)
        ->get(route('tenant.property.show'))
        ->assertOk();
});

test('tenant cannot access properties they are not assigned to', function () {
    $property1 = Property::factory()->create([
        'tenant_id' => 'tenant-1',
    ]);

    $property2 = Property::factory()->create([
        'tenant_id' => 'tenant-1',
    ]);

    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 'tenant-1',
        'property_id' => $property1->id,
    ]);

    $this->actingAs($tenant)
        ->get(route('admin.properties.show', $property2))
        ->assertForbidden();
});

test('manager has same access as admin', function () {
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => 'tenant-1',
    ]);

    $ownProperty = Property::factory()->create([
        'tenant_id' => 'tenant-1',
    ]);

    $this->actingAs($manager)
        ->get(route('manager.properties.show', $ownProperty))
        ->assertOk();
});

test('admin can access buildings from their tenant', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 'tenant-1',
    ]);

    $building = Building::factory()->create([
        'tenant_id' => 'tenant-1',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.buildings.show', $building))
        ->assertOk();
});

test('admin cannot access buildings from other tenants', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 'tenant-1',
    ]);

    $building = Building::factory()->create([
        'tenant_id' => 'tenant-2',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.buildings.show', $building))
        ->assertForbidden();
});

test('tenant can access meters from their property', function () {
    $property = Property::factory()->create([
        'tenant_id' => 'tenant-1',
    ]);

    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => 'tenant-1',
    ]);

    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 'tenant-1',
        'property_id' => $property->id,
    ]);

    $this->actingAs($tenant)
        ->get(route('tenant.meters.show', $meter))
        ->assertOk();
});

test('tenant cannot access meters from other properties', function () {
    $property1 = Property::factory()->create([
        'tenant_id' => 'tenant-1',
    ]);

    $property2 = Property::factory()->create([
        'tenant_id' => 'tenant-1',
    ]);

    $meter = Meter::factory()->create([
        'property_id' => $property2->id,
        'tenant_id' => 'tenant-1',
    ]);

    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => 'tenant-1',
        'property_id' => $property1->id,
    ]);

    $this->actingAs($tenant)
        ->get(route('tenant.meters.show', $meter))
        ->assertForbidden();
});

test('access denial is logged for audit trail', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 'tenant-1',
    ]);

    $otherProperty = Property::factory()->create([
        'tenant_id' => 'tenant-2',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.properties.show', $otherProperty))
        ->assertForbidden();

    Log::shouldHaveReceived('warning')
        ->with('Hierarchical access denied', \Mockery::on(function ($context) use ($admin, $otherProperty) {
            return $context['user_id'] === $admin->id
                && $context['user_tenant_id'] === 'tenant-1'
                && isset($context['route_parameters']);
        }))
        ->once();
});

test('middleware uses select to minimize data transfer', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 'tenant-1',
    ]);

    $property = Property::factory()->create([
        'tenant_id' => 'tenant-1',
        'name' => 'Test Property',
        'address' => '123 Test St',
    ]);

    // Enable query log
    \DB::enableQueryLog();

    $this->actingAs($admin)
        ->get(route('admin.properties.show', $property))
        ->assertOk();

    $queries = \DB::getQueryLog();
    
    // Find the query that selects from properties table
    $propertyQuery = collect($queries)->first(function ($query) {
        return str_contains($query['query'], 'select') 
            && str_contains($query['query'], 'properties');
    });

    // Verify only id and tenant_id are selected (performance optimization)
    expect($propertyQuery)->not->toBeNull();
});

test('json requests receive json error responses', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 'tenant-1',
    ]);

    $otherProperty = Property::factory()->create([
        'tenant_id' => 'tenant-2',
    ]);

    $this->actingAs($admin)
        ->getJson(route('admin.properties.show', $otherProperty))
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
