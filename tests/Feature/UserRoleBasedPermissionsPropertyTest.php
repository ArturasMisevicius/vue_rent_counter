<?php

use App\Enums\UserRole;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Feature: hierarchical-user-management, Property 18: User role-based permissions
 * Validates: Requirements 13.4
 * 
 * Property: For any tenant user attempting to perform an action other than meter reading submission,
 * the action should be denied
 */

test('tenant users cannot create properties', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot create properties
    expect($tenant->can('create', Property::class))->toBeFalse(
        'Tenant users should not be able to create properties'
    );
})->repeat(100);

test('tenant users cannot update properties', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot update properties
    expect($tenant->can('update', $property))->toBeFalse(
        'Tenant users should not be able to update properties'
    );
})->repeat(100);

test('tenant users cannot delete properties', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot delete properties
    expect($tenant->can('delete', $property))->toBeFalse(
        'Tenant users should not be able to delete properties'
    );
})->repeat(100);

test('tenant users cannot create buildings', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot create buildings
    expect($tenant->can('create', Building::class))->toBeFalse(
        'Tenant users should not be able to create buildings'
    );
})->repeat(100);

test('tenant users cannot update buildings', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a building for the admin
    $building = Building::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a property in the building
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot update buildings
    expect($tenant->can('update', $building))->toBeFalse(
        'Tenant users should not be able to update buildings'
    );
})->repeat(100);

test('tenant users cannot delete buildings', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a building for the admin
    $building = Building::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a property in the building
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'building_id' => $building->id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot delete buildings
    expect($tenant->can('delete', $building))->toBeFalse(
        'Tenant users should not be able to delete buildings'
    );
})->repeat(100);

test('tenant users cannot create meters', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot create meters
    expect($tenant->can('create', Meter::class))->toBeFalse(
        'Tenant users should not be able to create meters'
    );
})->repeat(100);

test('tenant users cannot update meters', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a meter for the property
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot update meters
    expect($tenant->can('update', $meter))->toBeFalse(
        'Tenant users should not be able to update meters'
    );
})->repeat(100);

test('tenant users cannot delete meters', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a meter for the property
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot delete meters
    expect($tenant->can('delete', $meter))->toBeFalse(
        'Tenant users should not be able to delete meters'
    );
})->repeat(100);

test('tenant users cannot create meter readings', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot create meter readings through the policy
    // Note: Meter reading submission is handled through a separate controller action
    expect($tenant->can('create', MeterReading::class))->toBeFalse(
        'Tenant users should not be able to create meter readings through standard create action'
    );
})->repeat(100);

test('tenant users cannot update meter readings', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a meter for the property
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a meter reading
    $meterReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot update meter readings
    expect($tenant->can('update', $meterReading))->toBeFalse(
        'Tenant users should not be able to update meter readings'
    );
})->repeat(100);

test('tenant users cannot delete meter readings', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a meter for the property
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a meter reading
    $meterReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot delete meter readings
    expect($tenant->can('delete', $meterReading))->toBeFalse(
        'Tenant users should not be able to delete meter readings'
    );
})->repeat(100);

test('tenant users cannot create invoices', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot create invoices
    expect($tenant->can('create', Invoice::class))->toBeFalse(
        'Tenant users should not be able to create invoices'
    );
})->repeat(100);

test('tenant users cannot update invoices', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create an invoice
    $invoice = Invoice::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot update invoices
    expect($tenant->can('update', $invoice))->toBeFalse(
        'Tenant users should not be able to update invoices'
    );
})->repeat(100);

test('tenant users cannot delete invoices', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create an invoice
    $invoice = Invoice::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot delete invoices
    expect($tenant->can('delete', $invoice))->toBeFalse(
        'Tenant users should not be able to delete invoices'
    );
})->repeat(100);

test('tenant users cannot create other users', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot create other users
    expect($tenant->can('create', User::class))->toBeFalse(
        'Tenant users should not be able to create other users'
    );
})->repeat(100);

test('tenant users cannot update other users', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Create another user to try to update
    $otherUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot update other users
    expect($tenant->can('update', $otherUser))->toBeFalse(
        'Tenant users should not be able to update other users'
    );
})->repeat(100);

test('tenant users cannot delete other users', function () {
    // Create an admin with tenant_id
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);

    // Create a property for the admin
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);

    // Create a tenant user assigned to the property
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'parent_user_id' => $admin->id,
    ]);

    // Create another user to try to delete
    $otherUser = User::factory()->create([
        'role' => UserRole::TENANT,
        'tenant_id' => $admin->tenant_id,
    ]);

    // Act as the tenant user
    $this->actingAs($tenant);

    // Property: Tenant users cannot delete other users
    expect($tenant->can('delete', $otherUser))->toBeFalse(
        'Tenant users should not be able to delete other users'
    );
})->repeat(100);
