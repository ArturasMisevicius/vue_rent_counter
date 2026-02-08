<?php

use App\Models\User;
use App\Enums\UserRole;

test('admin sees admin navigation items', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    
    $response = $this->actingAs($admin)->get(route('admin.dashboard'));
    
    $response->assertOk();
    $response->assertSee(route('admin.dashboard'));
    $response->assertSee(route('admin.users.index'));
    $response->assertSee(route('admin.providers.index'));
    $response->assertSee(route('admin.tariffs.index'));
    $response->assertSee(route('admin.settings.index'));
});

test('manager sees manager navigation items', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);
    
    $response = $this->actingAs($manager)->get(route('manager.dashboard'));
    
    $response->assertOk();
    $response->assertSee(route('manager.dashboard'));
    $response->assertSee(route('manager.properties.index'));
    $response->assertSee(route('manager.buildings.index'));
    $response->assertSee(route('manager.meters.index'));
    $response->assertSee(route('manager.meter-readings.index'));
    $response->assertSee(route('manager.invoices.index'));
    $response->assertSee(route('manager.reports.index'));
});

test('tenant sees tenant navigation items', function () {
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    
    $response = $this->actingAs($tenant)->get(route('tenant.dashboard'));
    
    $response->assertOk();
    $response->assertSee(route('tenant.dashboard'));
    $response->assertSee(route('tenant.property.show'));
    $response->assertSee(route('tenant.meters.index'));
    $response->assertSee(route('tenant.meter-readings.index'));
    $response->assertSee(route('tenant.invoices.index'));
    $response->assertSee(route('tenant.profile.show'));
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
