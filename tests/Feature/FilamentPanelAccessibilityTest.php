<?php

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('Filament admin panel login page is accessible', function () {
    $response = $this->get('/admin/login');
    
    $response->assertStatus(200);
    $response->assertSee('Sign in');
});

test('Admin user can login to Filament panel', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    $response = $this->post('/admin/login', [
        'email' => 'admin@test.com',
        'password' => 'password',
    ]);
    
    $response->assertRedirect('/admin');
    $this->assertAuthenticatedAs($admin, 'web');
});

test('Admin user can access Filament dashboard after login', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    $this->actingAs($admin, 'web');
    
    $response = $this->get('/admin');
    
    $response->assertStatus(200);
    $response->assertSee('Dashboard');
});

test('Admin user can access Properties resource', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($admin, 'web');
    
    $response = $this->get('/admin/properties');
    
    $response->assertStatus(200);
});

test('Admin user can access Buildings resource', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($admin, 'web');
    
    $response = $this->get('/admin/buildings');
    
    $response->assertStatus(200);
});

test('Admin user can access Invoices resource', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($admin, 'web');
    
    $response = $this->get('/admin/invoices');
    
    $response->assertStatus(200);
});

test('Admin user can access Meter Readings resource', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($admin, 'web');
    
    $response = $this->get('/admin/meter-readings');
    
    $response->assertStatus(200);
});

test('Admin user can access Users resource', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    $this->actingAs($admin, 'web');
    
    $response = $this->get('/admin/users');
    
    $response->assertStatus(200);
});

test('Admin user can access Tariffs resource', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    $this->actingAs($admin, 'web');
    
    $response = $this->get('/admin/tariffs');
    
    $response->assertStatus(200);
});

test('Manager user can access Filament panel', function () {
    $manager = User::factory()->create([
        'email' => 'manager@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::MANAGER,
        'tenant_id' => 1,
    ]);
    
    $this->actingAs($manager, 'web');
    
    $response = $this->get('/admin');
    
    $response->assertStatus(200);
    $response->assertSee('Dashboard');
});

test('Tenant user can access Filament panel', function () {
    $tenant = User::factory()->create([
        'email' => 'tenant@test.com',
        'password' => bcrypt('password'),
        'role' => UserRole::TENANT,
        'tenant_id' => 1,
        'property_id' => 1,
    ]);
    
    $this->actingAs($tenant, 'web');
    
    $response = $this->get('/admin');
    
    $response->assertStatus(200);
    $response->assertSee('Dashboard');
});
