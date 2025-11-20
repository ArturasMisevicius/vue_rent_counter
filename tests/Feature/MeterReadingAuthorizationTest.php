<?php

use App\Enums\UserRole;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\User;

/**
 * Meter Reading Authorization Tests
 * 
 * Tests role-based access control for meter reading management functionality.
 * Verifies that managers can create and submit meter readings,
 * while tenants are properly denied access to these operations.
 * 
 * Requirements: 3.4, 3.5
 */

test('manager can access meter reading create page', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);

    // Access meter reading create page
    $response = $this->actingAs($manager)->get('/manager/meter-readings/create');

    // Assert successful access (200 response)
    $response->assertOk();
});

test('tenant accessing meter reading create page gets 403 error', function () {
    // Create tenant user
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);

    // Attempt to access meter reading create page
    $response = $this->actingAs($tenant)->get('/manager/meter-readings/create');

    // Assert forbidden (403 error)
    $response->assertForbidden();
});

test('manager can submit meter reading', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property and meter
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);

    // Submit meter reading (tenant_id will be set by controller)
    $response = $this->actingAs($manager)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1000.5,
        'zone' => null,
        'tenant_id' => $manager->tenant_id, // Explicitly include tenant_id
    ]);

    // Assert redirect (successful submission)
    $response->assertRedirect();
    
    // Assert meter reading was created in database
    $this->assertDatabaseHas('meter_readings', [
        'meter_id' => $meter->id,
        'value' => 1000.5,
        'entered_by' => $manager->id,
        'tenant_id' => $manager->tenant_id,
    ]);
});

test('tenant cannot submit meter reading', function () {
    // Create tenant user
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);
    
    // Create property and meter
    $property = Property::factory()->create([
        'tenant_id' => $tenant->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $tenant->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);

    // Attempt to submit meter reading
    $response = $this->actingAs($tenant)->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1000.5,
        'zone' => null,
    ]);

    // Assert forbidden (403 error)
    $response->assertForbidden();
    
    // Assert meter reading was not created
    $this->assertDatabaseMissing('meter_readings', [
        'meter_id' => $meter->id,
        'value' => 1000.5,
    ]);
});

test('admin can access meter reading create page via shared routes', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    // Access meter reading create page via shared route (admin,manager)
    $response = $this->actingAs($admin)->get('/meter-readings/create');

    // Assert successful access (200 response)
    $response->assertOk();
});

test('admin can submit meter reading via shared routes', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create property and meter
    $property = Property::factory()->create([
        'tenant_id' => $admin->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $admin->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);

    // Submit meter reading via shared route
    $response = $this->actingAs($admin)->post('/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 2000.75,
        'zone' => null,
        'tenant_id' => $admin->tenant_id, // Explicitly include tenant_id
    ]);

    // Assert redirect (successful submission)
    $response->assertRedirect();
    
    // Assert meter reading was created in database
    $this->assertDatabaseHas('meter_readings', [
        'meter_id' => $meter->id,
        'value' => 2000.75,
        'entered_by' => $admin->id,
        'tenant_id' => $admin->tenant_id,
    ]);
});

test('unauthenticated user cannot access meter reading create page', function () {
    // Attempt to access meter reading create page without authentication
    $response = $this->get('/manager/meter-readings/create');

    // Assert redirected to login
    $response->assertRedirect('/login');
});

test('unauthenticated user cannot submit meter reading', function () {
    // Create property and meter
    $property = Property::factory()->create();
    $meter = Meter::factory()->create([
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);

    // Attempt to submit meter reading without authentication
    $response = $this->post('/manager/meter-readings', [
        'meter_id' => $meter->id,
        'reading_date' => now()->format('Y-m-d'),
        'value' => 1500.0,
        'zone' => null,
    ]);

    // Assert redirected to login
    $response->assertRedirect('/login');
    
    // Assert meter reading was not created
    $this->assertDatabaseMissing('meter_readings', [
        'meter_id' => $meter->id,
        'value' => 1500.0,
    ]);
});

test('manager can access meter reading index page', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);

    // Access meter reading index page
    $response = $this->actingAs($manager)->get('/manager/meter-readings');

    // Assert successful access
    $response->assertOk();
});

test('tenant can access their own meter readings index', function () {
    // Create tenant user
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);

    // Access tenant's meter reading index page
    $response = $this->actingAs($tenant)->get('/tenant/meter-readings');

    // Assert successful access (tenants can view their own readings)
    $response->assertOk();
});

test('manager can view meter reading details', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property, meter, and reading
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
    ]);
    
    $reading = MeterReading::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'meter_id' => $meter->id,
        'entered_by' => $manager->id,
    ]);

    // View meter reading details
    $response = $this->actingAs($manager)->get("/manager/meter-readings/{$reading->id}");

    // Assert successful access
    $response->assertOk();
});

test('manager can access meter reading edit page', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property, meter, and reading
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
    ]);
    
    $reading = MeterReading::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'meter_id' => $meter->id,
        'entered_by' => $manager->id,
    ]);

    // Access meter reading edit page
    $response = $this->actingAs($manager)->get("/manager/meter-readings/{$reading->id}/edit");

    // Assert successful access
    $response->assertOk();
});

test('tenant cannot access meter reading edit page', function () {
    // Create tenant user
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);
    
    // Create property, meter, and reading
    $property = Property::factory()->create([
        'tenant_id' => $tenant->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $tenant->tenant_id,
        'property_id' => $property->id,
    ]);
    
    $reading = MeterReading::factory()->create([
        'tenant_id' => $tenant->tenant_id,
        'meter_id' => $meter->id,
    ]);

    // Attempt to access meter reading edit page
    $response = $this->actingAs($tenant)->get("/manager/meter-readings/{$reading->id}/edit");

    // Assert forbidden
    $response->assertForbidden();
});

test('manager can update meter reading', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create property, meter, and reading
    $property = Property::factory()->create([
        'tenant_id' => $manager->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);
    
    $reading = MeterReading::factory()->create([
        'tenant_id' => $manager->tenant_id,
        'meter_id' => $meter->id,
        'value' => 1000.0,
        'entered_by' => $manager->id,
    ]);

    // Update meter reading
    $response = $this->actingAs($manager)->put("/manager/meter-readings/{$reading->id}", [
        'meter_id' => $meter->id,
        'reading_date' => $reading->reading_date->format('Y-m-d'),
        'value' => 1050.0,
        'zone' => null,
        'tenant_id' => $manager->tenant_id, // Include tenant_id
        'change_reason' => 'Correction due to misread',
    ]);

    // Assert redirect (successful update)
    $response->assertRedirect();
    
    // Assert meter reading was updated in database
    $this->assertDatabaseHas('meter_readings', [
        'id' => $reading->id,
        'value' => 1050.0,
    ]);
});

test('tenant cannot update meter reading', function () {
    // Create tenant user
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);
    
    // Create property, meter, and reading
    $property = Property::factory()->create([
        'tenant_id' => $tenant->tenant_id,
    ]);
    
    $meter = Meter::factory()->create([
        'tenant_id' => $tenant->tenant_id,
        'property_id' => $property->id,
        'supports_zones' => false,
    ]);
    
    $reading = MeterReading::factory()->create([
        'tenant_id' => $tenant->tenant_id,
        'meter_id' => $meter->id,
        'value' => 1000.0,
    ]);

    // Attempt to update meter reading
    $response = $this->actingAs($tenant)->put("/manager/meter-readings/{$reading->id}", [
        'meter_id' => $meter->id,
        'reading_date' => $reading->reading_date->format('Y-m-d'),
        'value' => 1050.0,
        'zone' => null,
        'tenant_id' => $tenant->tenant_id, // Include tenant_id
        'change_reason' => 'Attempted correction',
    ]);

    // Assert forbidden
    $response->assertForbidden();
    
    // Assert meter reading was not updated
    $this->assertDatabaseHas('meter_readings', [
        'id' => $reading->id,
        'value' => 1000.0,
    ]);
});

