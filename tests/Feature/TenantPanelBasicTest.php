<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Property;
use App\Models\Building;
use App\Models\Invoice;
use App\Enums\InvoiceStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('can access tenant dashboard when authenticated as tenant', function () {
    // Create a building and property
    $building = Building::factory()->create();
    $property = Property::factory()->create(['building_id' => $building->id]);
    
    // Create a tenant user
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'property_id' => $property->id,
    ]);

    $response = $this->actingAs($tenant)
        ->get('/tenant');

    $response->assertStatus(200);
});

it('redirects non-tenant users away from tenant panel', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    $response = $this->actingAs($admin)
        ->get('/tenant');

    $response->assertStatus(403);
});

it('tenant can view their property information', function () {
    $building = Building::factory()->create();
    $property = Property::factory()->create(['building_id' => $building->id]);
    
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'property_id' => $property->id,
    ]);

    $response = $this->actingAs($tenant)
        ->get('/tenant/properties');

    $response->assertStatus(200);
});

it('tenant can view their invoices', function () {
    $building = Building::factory()->create();
    $property = Property::factory()->create(['building_id' => $building->id]);
    
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'property_id' => $property->id,
    ]);

    // Create an invoice for the property
    Invoice::factory()->create([
        'property_id' => $property->id,
        'status' => InvoiceStatus::FINALIZED,
    ]);

    $response = $this->actingAs($tenant)
        ->get('/tenant/invoices');

    $response->assertStatus(200);
});

it('tenant cannot access other tenants data', function () {
    $building = Building::factory()->create();
    $property1 = Property::factory()->create(['building_id' => $building->id]);
    $property2 = Property::factory()->create(['building_id' => $building->id]);
    
    $tenant1 = User::factory()->create([
        'role' => UserRole::TENANT,
        'property_id' => $property1->id,
    ]);
    
    $tenant2 = User::factory()->create([
        'role' => UserRole::TENANT,
        'property_id' => $property2->id,
    ]);

    // Create invoices for both properties
    $invoice1 = Invoice::factory()->create(['property_id' => $property1->id]);
    $invoice2 = Invoice::factory()->create(['property_id' => $property2->id]);

    // Tenant 1 should only see their own invoice
    $response = $this->actingAs($tenant1)
        ->get('/tenant/invoices');

    $response->assertStatus(200);
    
    // Try to access tenant2's invoice directly - should fail
    $response = $this->actingAs($tenant1)
        ->get("/tenant/invoices/{$invoice2->id}");

    $response->assertStatus(404);
});