<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Property;
use App\Models\Meter;
use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $this->manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $this->tenant = User::factory()->create(['role' => UserRole::TENANT]);
});

test('admin can view settings page', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));
    
    $response->assertStatus(200);
    $response->assertViewIs('admin.settings.index');
    $response->assertSee('System Settings');
    $response->assertSee('System Information');
    $response->assertSee('Maintenance Tasks');
});

test('settings page displays system statistics', function () {
    // Create some test data
    Property::factory()->count(5)->create();
    Meter::factory()->count(10)->create();
    Invoice::factory()->count(3)->create();
    
    $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));
    
    $response->assertStatus(200);
    $response->assertSee('Total Users');
    $response->assertSee('Total Properties');
    $response->assertSee('Total Meters');
    $response->assertSee('Total Invoices');
    $response->assertSee('Database Size');
    $response->assertSee('Cache Size');
});

test('settings page displays system information', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));
    
    $response->assertStatus(200);
    $response->assertSee('Laravel Version');
    $response->assertSee('PHP Version');
    $response->assertSee('Database');
    $response->assertSee('Environment');
    $response->assertSee('Timezone');
});

test('settings page shows maintenance tasks', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.settings.index'));
    
    $response->assertStatus(200);
    $response->assertSee('Clear Cache');
    $response->assertSee('Run Backup');
});

test('admin can update settings', function () {
    $response = $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), [
            'app_name' => 'Test Application',
            'timezone' => 'Europe/Vilnius',
        ]);
    
    $response->assertRedirect();
    $response->assertSessionHas('success');
});

test('admin can clear cache', function () {
    // Put something in cache
    Cache::put('test_key', 'test_value', 60);
    expect(Cache::has('test_key'))->toBeTrue();
    
    $response = $this->actingAs($this->admin)
        ->post(route('admin.settings.clear-cache'));
    
    $response->assertRedirect();
    $response->assertSessionHas('success');
    
    // Cache should be cleared
    expect(Cache::has('test_key'))->toBeFalse();
});

test('manager cannot access settings page', function () {
    $response = $this->actingAs($this->manager)->get(route('admin.settings.index'));
    
    $response->assertStatus(403);
});

test('tenant cannot access settings page', function () {
    $response = $this->actingAs($this->tenant)->get(route('admin.settings.index'));
    
    $response->assertStatus(403);
});

test('manager cannot update settings', function () {
    $response = $this->actingAs($this->manager)
        ->put(route('admin.settings.update'), [
            'app_name' => 'Test Application',
            'timezone' => 'Europe/Vilnius',
        ]);
    
    $response->assertStatus(403);
});

test('tenant cannot update settings', function () {
    $response = $this->actingAs($this->tenant)
        ->put(route('admin.settings.update'), [
            'app_name' => 'Test Application',
            'timezone' => 'Europe/Vilnius',
        ]);
    
    $response->assertStatus(403);
});

test('manager cannot clear cache', function () {
    $response = $this->actingAs($this->manager)
        ->post(route('admin.settings.clear-cache'));
    
    $response->assertStatus(403);
});

test('tenant cannot clear cache', function () {
    $response = $this->actingAs($this->tenant)
        ->post(route('admin.settings.clear-cache'));
    
    $response->assertStatus(403);
});

test('settings update validates input', function () {
    $response = $this->actingAs($this->admin)
        ->put(route('admin.settings.update'), [
            'app_name' => str_repeat('a', 300), // Too long
            'timezone' => 'Invalid/Timezone',
        ]);
    
    $response->assertSessionHasErrors(['app_name', 'timezone']);
});
