<?php

use App\Models\User;
use App\Enums\UserRole;

test('admin sees admin navigation items', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    
    $response = $this->actingAs($admin)->get(route('admin.dashboard'));
    
    $response->assertOk();
    $response->assertSee('Users');
    $response->assertSee('Providers');
    $response->assertSee('Tariffs');
    $response->assertSee('Settings');
    $response->assertSee('Audit');
});

test('manager sees manager navigation items', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    
    $response = $this->actingAs($manager)->get(route('manager.dashboard'));
    
    $response->assertOk();
    $response->assertSee('Properties');
    $response->assertSee('Buildings');
    $response->assertSee('Meters');
    $response->assertSee('Readings');
    $response->assertSee('Invoices');
    $response->assertSee('Reports');
});

test('tenant sees tenant navigation items', function () {
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    
    $response = $this->actingAs($tenant)->get(route('tenant.dashboard'));
    
    $response->assertOk();
    $response->assertSee('My Property');
    $response->assertSee('Meters');
    $response->assertSee('Readings');
    $response->assertSee('Invoices');
    $response->assertSee('Profile');
});

test('navigation shows user name and role for admin', function () {
    $admin = User::factory()->create([
        'name' => 'Test Admin',
        'role' => UserRole::ADMIN
    ]);
    
    $response = $this->actingAs($admin)->get(route('admin.dashboard'));
    
    $response->assertOk();
    $response->assertSee('Test Admin');
    $response->assertSee('Admin');
});

test('navigation shows user name and role for manager', function () {
    $manager = User::factory()->create([
        'name' => 'Test Manager',
        'role' => UserRole::MANAGER
    ]);
    
    $response = $this->actingAs($manager)->get(route('manager.dashboard'));
    
    $response->assertOk();
    $response->assertSee('Test Manager');
    $response->assertSee('Manager');
});

test('navigation shows user name and role for tenant', function () {
    $tenant = User::factory()->create([
        'name' => 'Test Tenant',
        'role' => UserRole::TENANT
    ]);
    
    $response = $this->actingAs($tenant)->get(route('tenant.dashboard'));
    
    $response->assertOk();
    $response->assertSee('Test Tenant');
    $response->assertSee('Tenant');
});

test('navigation includes logout button for admin', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    
    $response = $this->actingAs($admin)->get(route('admin.dashboard'));
    
    $response->assertOk();
    $response->assertSee('Logout');
});

test('navigation includes logout button for manager', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    
    $response = $this->actingAs($manager)->get(route('manager.dashboard'));
    
    $response->assertOk();
    $response->assertSee('Logout');
});

test('navigation includes logout button for tenant', function () {
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    
    $response = $this->actingAs($tenant)->get(route('tenant.dashboard'));
    
    $response->assertOk();
    $response->assertSee('Logout');
});
