<?php

use App\Enums\UserRole;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;

/**
 * Tariff Authorization Tests
 * 
 * Tests role-based access control for tariff management functionality.
 * Verifies that only admins can create, update, and delete tariffs,
 * while managers and tenants are properly denied access.
 * 
 * Requirements: 3.1, 3.2, 3.3
 */

test('admin can access tariff index page', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);

    // Access tariff index
    $response = $this->actingAs($admin)->get('/admin/tariffs');

    // Assert successful access
    $response->assertOk();
});

test('manager accessing tariff index gets 403 error', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);

    // Attempt to access tariff index
    $response = $this->actingAs($manager)->get('/admin/tariffs');

    // Assert forbidden
    $response->assertForbidden();
});

test('tenant accessing tariff index gets 403 error', function () {
    // Create tenant user
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);

    // Attempt to access tariff index
    $response = $this->actingAs($tenant)->get('/admin/tariffs');

    // Assert forbidden
    $response->assertForbidden();
});

test('admin can access tariff create page', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create a provider (required for tariff creation)
    Provider::factory()->create();

    // Access tariff create page
    $response = $this->actingAs($admin)->get('/admin/tariffs/create');

    // Assert successful access
    $response->assertOk();
});

test('manager cannot access tariff create page', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);

    // Attempt to access tariff create page
    $response = $this->actingAs($manager)->get('/admin/tariffs/create');

    // Assert forbidden
    $response->assertForbidden();
});

test('tenant cannot access tariff create page', function () {
    // Create tenant user
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);

    // Attempt to access tariff create page
    $response = $this->actingAs($tenant)->get('/admin/tariffs/create');

    // Assert forbidden
    $response->assertForbidden();
});

test('admin can create tariff', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();

    // Create tariff
    $response = $this->actingAs($admin)->post('/admin/tariffs', [
        'provider_id' => $provider->id,
        'name' => 'Test Tariff',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.15,
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    // Assert redirect (successful creation)
    $response->assertRedirect();
    
    // Assert tariff was created in database
    $this->assertDatabaseHas('tariffs', [
        'name' => 'Test Tariff',
        'provider_id' => $provider->id,
    ]);
});

test('manager cannot create tariff', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();

    // Attempt to create tariff
    $response = $this->actingAs($manager)->post('/admin/tariffs', [
        'provider_id' => $provider->id,
        'name' => 'Test Tariff',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.15,
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    // Assert forbidden
    $response->assertForbidden();
    
    // Assert tariff was not created
    $this->assertDatabaseMissing('tariffs', [
        'name' => 'Test Tariff',
    ]);
});

test('tenant cannot create tariff', function () {
    // Create tenant user
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();

    // Attempt to create tariff
    $response = $this->actingAs($tenant)->post('/admin/tariffs', [
        'provider_id' => $provider->id,
        'name' => 'Test Tariff',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.15,
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    // Assert forbidden
    $response->assertForbidden();
    
    // Assert tariff was not created
    $this->assertDatabaseMissing('tariffs', [
        'name' => 'Test Tariff',
    ]);
});

test('admin can access tariff edit page', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
    ]);

    // Access tariff edit page
    $response = $this->actingAs($admin)->get("/admin/tariffs/{$tariff->id}/edit");

    // Assert successful access
    $response->assertOk();
});

test('manager cannot access tariff edit page', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
    ]);

    // Attempt to access tariff edit page
    $response = $this->actingAs($manager)->get("/admin/tariffs/{$tariff->id}/edit");

    // Assert forbidden
    $response->assertForbidden();
});

test('tenant cannot access tariff edit page', function () {
    // Create tenant user
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
    ]);

    // Attempt to access tariff edit page
    $response = $this->actingAs($tenant)->get("/admin/tariffs/{$tariff->id}/edit");

    // Assert forbidden
    $response->assertForbidden();
});

test('admin can update tariff', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Original Name',
    ]);

    // Update tariff
    $response = $this->actingAs($admin)->put("/admin/tariffs/{$tariff->id}", [
        'provider_id' => $provider->id,
        'name' => 'Updated Name',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.20,
        ],
        'active_from' => $tariff->active_from->format('Y-m-d'),
    ]);

    // Assert redirect (successful update)
    $response->assertRedirect();
    
    // Assert tariff was updated in database
    $this->assertDatabaseHas('tariffs', [
        'id' => $tariff->id,
        'name' => 'Updated Name',
    ]);
});

test('manager cannot update tariff', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Original Name',
    ]);

    // Attempt to update tariff
    $response = $this->actingAs($manager)->put("/admin/tariffs/{$tariff->id}", [
        'provider_id' => $provider->id,
        'name' => 'Updated Name',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.20,
        ],
        'active_from' => $tariff->active_from->format('Y-m-d'),
    ]);

    // Assert forbidden
    $response->assertForbidden();
    
    // Assert tariff was not updated
    $this->assertDatabaseHas('tariffs', [
        'id' => $tariff->id,
        'name' => 'Original Name',
    ]);
});

test('tenant cannot update tariff', function () {
    // Create tenant user
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Original Name',
    ]);

    // Attempt to update tariff
    $response = $this->actingAs($tenant)->put("/admin/tariffs/{$tariff->id}", [
        'provider_id' => $provider->id,
        'name' => 'Updated Name',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.20,
        ],
        'active_from' => $tariff->active_from->format('Y-m-d'),
    ]);

    // Assert forbidden
    $response->assertForbidden();
    
    // Assert tariff was not updated
    $this->assertDatabaseHas('tariffs', [
        'id' => $tariff->id,
        'name' => 'Original Name',
    ]);
});

test('admin can delete tariff', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
    ]);

    // Delete tariff
    $response = $this->actingAs($admin)->delete("/admin/tariffs/{$tariff->id}");

    // Assert redirect (successful deletion)
    $response->assertRedirect();
    
    // Assert tariff was deleted from database
    $this->assertDatabaseMissing('tariffs', [
        'id' => $tariff->id,
    ]);
});

test('manager cannot delete tariff', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
    ]);

    // Attempt to delete tariff
    $response = $this->actingAs($manager)->delete("/admin/tariffs/{$tariff->id}");

    // Assert forbidden
    $response->assertForbidden();
    
    // Assert tariff still exists in database
    $this->assertDatabaseHas('tariffs', [
        'id' => $tariff->id,
    ]);
});

test('tenant cannot delete tariff', function () {
    // Create tenant user
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
    ]);

    // Attempt to delete tariff
    $response = $this->actingAs($tenant)->delete("/admin/tariffs/{$tariff->id}");

    // Assert forbidden
    $response->assertForbidden();
    
    // Assert tariff still exists in database
    $this->assertDatabaseHas('tariffs', [
        'id' => $tariff->id,
    ]);
});

test('admin can view tariff details', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Test Tariff Details',
    ]);

    // View tariff details
    $response = $this->actingAs($admin)->get("/admin/tariffs/{$tariff->id}");

    // Assert successful access
    $response->assertOk();
    $response->assertSee('Test Tariff Details');
});

test('manager cannot view tariff details in admin area', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
    ]);

    // Attempt to view tariff details in admin area
    $response = $this->actingAs($manager)->get("/admin/tariffs/{$tariff->id}");

    // Assert forbidden
    $response->assertForbidden();
});

test('tenant cannot view tariff details in admin area', function () {
    // Create tenant user
    $tenant = User::factory()->create([
        'role' => UserRole::TENANT,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
    ]);

    // Attempt to view tariff details in admin area
    $response = $this->actingAs($tenant)->get("/admin/tariffs/{$tariff->id}");

    // Assert forbidden
    $response->assertForbidden();
});

test('unauthenticated user cannot access tariff management', function () {
    // Attempt to access tariff index without authentication
    $response = $this->get('/admin/tariffs');

    // Assert redirected to login
    $response->assertRedirect('/login');
});

test('manager can view tariffs in read-only mode', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Manager Viewable Tariff',
    ]);

    // Access manager's read-only tariff view
    $response = $this->actingAs($manager)->get('/manager/tariffs');

    // Assert successful access
    $response->assertOk();
    $response->assertSee('Manager Viewable Tariff');
});

test('manager can view individual tariff in read-only mode', function () {
    // Create manager user
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
    ]);
    
    // Create provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Manager Viewable Tariff Details',
    ]);

    // Access manager's read-only tariff details view
    $response = $this->actingAs($manager)->get("/manager/tariffs/{$tariff->id}");

    // Assert successful access
    $response->assertOk();
    $response->assertSee('Manager Viewable Tariff Details');
});
