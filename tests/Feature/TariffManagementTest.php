<?php

use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\ServiceType;

test('admin can view tariffs index', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create(['provider_id' => $provider->id]);

    $response = $this->actingAs($admin)->get(route('admin.tariffs.index'));

    $response->assertOk();
    $response->assertSee($tariff->name);
});

test('admin can view tariff create form', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    Provider::factory()->create();

    $response = $this->actingAs($admin)->get(route('admin.tariffs.create'));

    $response->assertOk();
    $response->assertSee('Create Tariff');
});

test('admin can create flat rate tariff', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $provider = Provider::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.tariffs.store'), [
        'provider_id' => $provider->id,
        'name' => 'Test Flat Rate',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.15,
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    $response->assertRedirect(route('admin.tariffs.index'));
    $this->assertDatabaseHas('tariffs', [
        'name' => 'Test Flat Rate',
        'provider_id' => $provider->id,
    ]);
});

test('admin can create time of use tariff', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $provider = Provider::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.tariffs.store'), [
        'provider_id' => $provider->id,
        'name' => 'Test Time of Use',
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.09],
            ],
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    $response->assertRedirect(route('admin.tariffs.index'));
    $this->assertDatabaseHas('tariffs', [
        'name' => 'Test Time of Use',
        'provider_id' => $provider->id,
    ]);
});

test('admin can view tariff details with version history', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $provider = Provider::factory()->create();
    
    // Create original tariff
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Test Tariff',
        'active_from' => now()->subMonths(2),
        'active_until' => now()->subMonth(),
    ]);
    
    // Create newer version
    $newVersion = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Test Tariff',
        'active_from' => now()->subMonth(),
        'active_until' => null,
    ]);

    $response = $this->actingAs($admin)->get(route('admin.tariffs.show', $newVersion));

    $response->assertOk();
    $response->assertSee('Version History');
    $response->assertSee($tariff->active_from->format('Y-m-d'));
});

test('admin can edit tariff', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create(['provider_id' => $provider->id]);

    $response = $this->actingAs($admin)->get(route('admin.tariffs.edit', $tariff));

    $response->assertOk();
    $response->assertSee('Edit Tariff');
    $response->assertSee($tariff->name);
});

test('admin can update tariff', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Original Name',
    ]);

    $response = $this->actingAs($admin)->put(route('admin.tariffs.update', $tariff), [
        'provider_id' => $provider->id,
        'name' => 'Updated Name',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.20,
        ],
        'active_from' => $tariff->active_from->format('Y-m-d'),
    ]);

    $response->assertRedirect(route('admin.tariffs.show', $tariff));
    $this->assertDatabaseHas('tariffs', [
        'id' => $tariff->id,
        'name' => 'Updated Name',
    ]);
});

test('admin can create new tariff version', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Test Tariff',
        'active_from' => now()->subMonth(),
        'active_until' => null,
    ]);

    $response = $this->actingAs($admin)->put(route('admin.tariffs.update', $tariff), [
        'provider_id' => $provider->id,
        'name' => 'Test Tariff',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.25,
        ],
        'active_from' => now()->format('Y-m-d'),
        'create_new_version' => true,
    ]);

    $response->assertRedirect();
    
    // Original tariff should have active_until set
    $tariff->refresh();
    expect($tariff->active_until)->not->toBeNull();
    
    // New version should exist
    $newVersion = Tariff::where('provider_id', $provider->id)
        ->where('name', 'Test Tariff')
        ->where('id', '!=', $tariff->id)
        ->first();
    
    expect($newVersion)->not->toBeNull();
    expect($newVersion->configuration['rate'])->toBe(0.25);
});

test('tariff validation rejects invalid configuration', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $provider = Provider::factory()->create();

    $response = $this->actingAs($admin)->post(route('admin.tariffs.store'), [
        'provider_id' => $provider->id,
        'name' => 'Invalid Tariff',
        'configuration' => [
            'type' => 'invalid_type',
            'currency' => 'EUR',
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    $response->assertSessionHasErrors('configuration.type');
});

test('active tariffs are highlighted in index', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $provider = Provider::factory()->create();
    
    // Create active tariff
    $activeTariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Active Tariff',
        'active_from' => now()->subMonth(),
        'active_until' => null,
    ]);
    
    // Create inactive tariff
    $inactiveTariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Inactive Tariff',
        'active_from' => now()->subYear(),
        'active_until' => now()->subMonth(),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.tariffs.index'));

    $response->assertOk();
    $response->assertSee('Active Tariff');
    $response->assertSee('Inactive Tariff');
    // Active tariff should have green background class
    $response->assertSee('bg-green-50');
});

test('manager cannot access tariff management', function () {
    $manager = User::factory()->create(['role' => UserRole::MANAGER]);

    $response = $this->actingAs($manager)->get(route('admin.tariffs.index'));

    $response->assertForbidden();
});

test('tenant cannot access tariff management', function () {
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);

    $response = $this->actingAs($tenant)->get(route('admin.tariffs.index'));

    $response->assertForbidden();
});
