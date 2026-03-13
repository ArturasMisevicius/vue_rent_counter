<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Property;
use App\Models\Building;
use App\Models\Tenant;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('can access tenant dashboard when authenticated as tenant', function () {
    // Create a tenant with property
    $building = Building::factory()->create();
    $property = Property::factory()->create(['building_id' => $building->id]);
    $tenant = Tenant::factory()->create();
    
    $user = User::factory()->create([
        'role' => UserRole::TENANT,
        'property_id' => $property->id,
        'tenant_id' => $tenant->id,
    ]);

    $this->actingAs($user)
        ->get('/tenant')
        ->assertOk()
        ->assertSee('Welcome to Your Property Portal');
});

it('cannot access tenant dashboard when not authenticated', function () {
    $this->get('/tenant')
        ->assertRedirect('/tenant/login');
});

it('cannot access tenant dashboard as non-tenant user', function () {
    $user = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    $this->actingAs($user)
        ->get('/tenant')
        ->assertForbidden();
});

it('can view property resource in tenant panel', function () {
    $building = Building::factory()->create();
    $property = Property::factory()->create(['building_id' => $building->id]);
    $tenant = Tenant::factory()->create();
    
    $user = User::factory()->create([
        'role' => UserRole::TENANT,
        'property_id' => $property->id,
        'tenant_id' => $tenant->id,
    ]);

    $this->actingAs($user)
        ->get('/tenant/properties')
        ->assertOk();
});

it('can view meter readings resource in tenant panel', function () {
    $building = Building::factory()->create();
    $property = Property::factory()->create(['building_id' => $building->id]);
    $tenant = Tenant::factory()->create();
    
    $user = User::factory()->create([
        'role' => UserRole::TENANT,
        'property_id' => $property->id,
        'tenant_id' => $tenant->id,
    ]);

    $this->actingAs($user)
        ->get('/tenant/meter-readings')
        ->assertOk();
});

it('can view invoices resource in tenant panel', function () {
    $building = Building::factory()->create();
    $property = Property::factory()->create(['building_id' => $building->id]);
    $tenant = Tenant::factory()->create();
    
    $user = User::factory()->create([
        'role' => UserRole::TENANT,
        'property_id' => $property->id,
        'tenant_id' => $tenant->id,
    ]);

    $this->actingAs($user)
        ->get('/tenant/invoices')
        ->assertOk();
});