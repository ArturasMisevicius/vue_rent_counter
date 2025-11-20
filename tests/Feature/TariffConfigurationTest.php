<?php

use App\Enums\UserRole;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Carbon\Carbon;

/**
 * Tariff Configuration Validation Tests
 * 
 * Tests tariff configuration validation including:
 * - Time-of-use zones cannot overlap
 * - Time-of-use zones must cover all 24 hours
 * - Tariff selection based on billing date
 * - Most recent tariff is selected when multiple are active
 * - Weekend rates are applied correctly
 * 
 * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
 */

test('time-of-use zones cannot overlap', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();

    // Attempt to create tariff with overlapping zones
    $response = $this->actingAs($admin)->post('/admin/tariffs', [
        'provider_id' => $provider->id,
        'name' => 'Overlapping Zones Tariff',
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'morning', 'start' => '06:00', 'end' => '14:00', 'rate' => 0.15],
                ['id' => 'afternoon', 'start' => '12:00', 'end' => '20:00', 'rate' => 0.18], // Overlaps with morning
            ],
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    // Assert validation error
    $response->assertSessionHasErrors('configuration.zones');
    
    // Assert error message mentions overlap
    $errors = session('errors');
    expect($errors->get('configuration.zones')[0])->toContain('cannot overlap');
    
    // Assert tariff was not created
    $this->assertDatabaseMissing('tariffs', [
        'name' => 'Overlapping Zones Tariff',
    ]);
});

test('time-of-use zones must cover all 24 hours', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();

    // Attempt to create tariff with incomplete coverage (missing 20:00-06:00)
    $response = $this->actingAs($admin)->post('/admin/tariffs', [
        'provider_id' => $provider->id,
        'name' => 'Incomplete Coverage Tariff',
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '06:00', 'end' => '20:00', 'rate' => 0.18],
                // Missing 20:00-06:00 coverage
            ],
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    // Assert validation error
    $response->assertSessionHasErrors('configuration.zones');
    
    // Assert error message mentions 24-hour coverage
    $errors = session('errors');
    expect($errors->get('configuration.zones')[0])->toContain('must cover all 24 hours');
    
    // Assert tariff was not created
    $this->assertDatabaseMissing('tariffs', [
        'name' => 'Incomplete Coverage Tariff',
    ]);
});

test('tariff is selected based on billing date', function () {
    // Create provider
    $provider = Provider::factory()->create();
    
    // Create tariff active from January 1, 2024
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'January 2024 Tariff',
        'active_from' => Carbon::parse('2024-01-01'),
        'active_until' => null,
    ]);

    // Resolve tariff for a date within the active period
    $resolver = app(\App\Services\TariffResolver::class);
    $resolved = $resolver->resolve($provider, Carbon::parse('2024-06-15'));

    // Assert correct tariff was selected
    expect($resolved->id)->toBe($tariff->id);
    expect($resolved->name)->toBe('January 2024 Tariff');
});

test('most recent tariff is selected when multiple are active', function () {
    // Create provider
    $provider = Provider::factory()->create();
    
    // Create older tariff
    $olderTariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Older Tariff',
        'active_from' => Carbon::parse('2024-01-01'),
        'active_until' => null,
    ]);
    
    // Create newer tariff
    $newerTariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Newer Tariff',
        'active_from' => Carbon::parse('2024-06-01'),
        'active_until' => null,
    ]);

    // Resolve tariff for a date when both are active
    $resolver = app(\App\Services\TariffResolver::class);
    $resolved = $resolver->resolve($provider, Carbon::parse('2024-07-01'));

    // Assert most recent tariff was selected
    expect($resolved->id)->toBe($newerTariff->id);
    expect($resolved->name)->toBe('Newer Tariff');
});

test('weekend rates are applied correctly', function () {
    // Create tariff with weekend logic
    $tariff = Tariff::factory()->create([
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
            'weekend_logic' => 'apply_night_rate',
        ],
    ]);

    // Calculate cost for Saturday afternoon (should use night rate due to weekend logic)
    $resolver = app(\App\Services\TariffResolver::class);
    $saturdayAfternoon = Carbon::parse('2024-11-23 14:00:00'); // Saturday
    
    $cost = $resolver->calculateCost($tariff, 100.0, $saturdayAfternoon);

    // Assert weekend rate (night rate) was applied
    expect($cost)->toBe(10.0); // 100 * 0.10 (night rate)
});

test('tariff with valid non-overlapping zones is accepted', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();

    // Create tariff with valid non-overlapping zones covering 24 hours
    $response = $this->actingAs($admin)->post('/admin/tariffs', [
        'provider_id' => $provider->id,
        'name' => 'Valid Time-of-Use Tariff',
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    // Assert successful creation
    $response->assertRedirect();
    
    // Assert tariff was created
    $this->assertDatabaseHas('tariffs', [
        'name' => 'Valid Time-of-Use Tariff',
        'provider_id' => $provider->id,
    ]);
});

test('tariff respects active_until date', function () {
    // Create provider
    $provider = Provider::factory()->create();
    
    // Create expired tariff
    $expiredTariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Expired Tariff',
        'active_from' => Carbon::parse('2024-01-01'),
        'active_until' => Carbon::parse('2024-05-31'),
    ]);
    
    // Create current tariff
    $currentTariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Current Tariff',
        'active_from' => Carbon::parse('2024-06-01'),
        'active_until' => null,
    ]);

    // Resolve tariff for a date after the first tariff expired
    $resolver = app(\App\Services\TariffResolver::class);
    $resolved = $resolver->resolve($provider, Carbon::parse('2024-07-01'));

    // Assert current tariff was selected, not expired one
    expect($resolved->id)->toBe($currentTariff->id);
    expect($resolved->name)->toBe('Current Tariff');
});

test('tariff selection fails when no active tariff exists', function () {
    // Create provider
    $provider = Provider::factory()->create();
    
    // Create tariff that starts in the future
    Tariff::factory()->create([
        'provider_id' => $provider->id,
        'name' => 'Future Tariff',
        'active_from' => Carbon::parse('2024-06-01'),
        'active_until' => null,
    ]);

    // Attempt to resolve tariff for a date before it becomes active
    $resolver = app(\App\Services\TariffResolver::class);
    
    // This should throw ModelNotFoundException
    $resolver->resolve($provider, Carbon::parse('2024-01-01'));
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('zones with midnight crossing are handled correctly', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();

    // Create tariff with zone crossing midnight
    $response = $this->actingAs($admin)->post('/admin/tariffs', [
        'provider_id' => $provider->id,
        'name' => 'Midnight Crossing Tariff',
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10], // Crosses midnight
            ],
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    // Assert successful creation
    $response->assertRedirect();
    
    // Assert tariff was created
    $this->assertDatabaseHas('tariffs', [
        'name' => 'Midnight Crossing Tariff',
    ]);
});

test('multiple zones with gaps are rejected', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();

    // Attempt to create tariff with gaps in coverage
    $response = $this->actingAs($admin)->post('/admin/tariffs', [
        'provider_id' => $provider->id,
        'name' => 'Gapped Coverage Tariff',
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'morning', 'start' => '06:00', 'end' => '12:00', 'rate' => 0.15],
                ['id' => 'evening', 'start' => '18:00', 'end' => '23:00', 'rate' => 0.18],
                // Gap: 12:00-18:00 and 23:00-06:00
            ],
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    // Assert validation error
    $response->assertSessionHasErrors('configuration.zones');
    
    // Assert error message mentions coverage
    $errors = session('errors');
    expect($errors->get('configuration.zones')[0])->toContain('must cover all 24 hours');
});

test('flat rate tariff does not require zone validation', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();

    // Create flat rate tariff (no zones)
    $response = $this->actingAs($admin)->post('/admin/tariffs', [
        'provider_id' => $provider->id,
        'name' => 'Flat Rate Tariff',
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.15,
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    // Assert successful creation
    $response->assertRedirect();
    
    // Assert tariff was created
    $this->assertDatabaseHas('tariffs', [
        'name' => 'Flat Rate Tariff',
        'provider_id' => $provider->id,
    ]);
});

test('tariff with three non-overlapping zones covering 24 hours is accepted', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();

    // Create tariff with three zones
    $response = $this->actingAs($admin)->post('/admin/tariffs', [
        'provider_id' => $provider->id,
        'name' => 'Three Zone Tariff',
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'peak', 'start' => '08:00', 'end' => '20:00', 'rate' => 0.20],
                ['id' => 'off_peak', 'start' => '20:00', 'end' => '23:00', 'rate' => 0.15],
                ['id' => 'night', 'start' => '23:00', 'end' => '08:00', 'rate' => 0.10],
            ],
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    // Assert successful creation
    $response->assertRedirect();
    
    // Assert tariff was created
    $this->assertDatabaseHas('tariffs', [
        'name' => 'Three Zone Tariff',
    ]);
});

test('weekend logic applies night rate on Saturday', function () {
    // Create tariff with weekend logic
    $tariff = Tariff::factory()->create([
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
            'weekend_logic' => 'apply_night_rate',
        ],
    ]);

    // Calculate cost for Saturday
    $resolver = app(\App\Services\TariffResolver::class);
    $saturday = Carbon::parse('2024-11-23 10:00:00'); // Saturday morning
    
    $cost = $resolver->calculateCost($tariff, 100.0, $saturday);

    // Assert night rate was applied on weekend
    expect($cost)->toBe(10.0); // 100 * 0.10
});

test('weekend logic applies night rate on Sunday', function () {
    // Create tariff with weekend logic
    $tariff = Tariff::factory()->create([
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
            'weekend_logic' => 'apply_night_rate',
        ],
    ]);

    // Calculate cost for Sunday
    $resolver = app(\App\Services\TariffResolver::class);
    $sunday = Carbon::parse('2024-11-24 15:00:00'); // Sunday afternoon
    
    $cost = $resolver->calculateCost($tariff, 100.0, $sunday);

    // Assert night rate was applied on weekend
    expect($cost)->toBe(10.0); // 100 * 0.10
});

test('weekend logic does not apply on weekdays', function () {
    // Create tariff with weekend logic
    $tariff = Tariff::factory()->create([
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
            ],
            'weekend_logic' => 'apply_night_rate',
        ],
    ]);

    // Calculate cost for Monday during day hours
    $resolver = app(\App\Services\TariffResolver::class);
    $monday = Carbon::parse('2024-11-18 10:00:00'); // Monday morning
    
    $cost = $resolver->calculateCost($tariff, 100.0, $monday);

    // Assert day rate was applied on weekday
    expect($cost)->toBe(18.0); // 100 * 0.18
});

test('tariff with single zone covering full day is accepted', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();

    // Create tariff with single zone covering full 24 hours (midnight to midnight)
    $response = $this->actingAs($admin)->post('/admin/tariffs', [
        'provider_id' => $provider->id,
        'name' => 'Full Day Single Zone',
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'all_day', 'start' => '00:00', 'end' => '00:00', 'rate' => 0.15], // Wraps around to cover full day
            ],
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    // Assert successful creation
    $response->assertRedirect();
    
    // Assert tariff was created
    $this->assertDatabaseHas('tariffs', [
        'name' => 'Full Day Single Zone',
    ]);
});

test('adjacent zones without gaps are accepted', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();

    // Create tariff with adjacent zones (no gaps, no overlaps)
    $response = $this->actingAs($admin)->post('/admin/tariffs', [
        'provider_id' => $provider->id,
        'name' => 'Adjacent Zones Tariff',
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'zone1', 'start' => '00:00', 'end' => '08:00', 'rate' => 0.10],
                ['id' => 'zone2', 'start' => '08:00', 'end' => '16:00', 'rate' => 0.15],
                ['id' => 'zone3', 'start' => '16:00', 'end' => '00:00', 'rate' => 0.12],
            ],
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    // Assert successful creation
    $response->assertRedirect();
    
    // Assert tariff was created
    $this->assertDatabaseHas('tariffs', [
        'name' => 'Adjacent Zones Tariff',
    ]);
});

test('tariff selection considers only provider-specific tariffs', function () {
    // Create two providers
    $provider1 = Provider::factory()->create(['name' => 'Provider 1']);
    $provider2 = Provider::factory()->create(['name' => 'Provider 2']);
    
    // Create tariff for provider 1
    $tariff1 = Tariff::factory()->create([
        'provider_id' => $provider1->id,
        'name' => 'Provider 1 Tariff',
        'active_from' => Carbon::parse('2024-01-01'),
    ]);
    
    // Create tariff for provider 2
    $tariff2 = Tariff::factory()->create([
        'provider_id' => $provider2->id,
        'name' => 'Provider 2 Tariff',
        'active_from' => Carbon::parse('2024-01-01'),
    ]);

    // Resolve tariff for provider 1
    $resolver = app(\App\Services\TariffResolver::class);
    $resolved = $resolver->resolve($provider1, Carbon::parse('2024-06-15'));

    // Assert only provider 1's tariff was selected
    expect($resolved->id)->toBe($tariff1->id);
    expect($resolved->name)->toBe('Provider 1 Tariff');
});

test('overlapping zones are properly detected and rejected', function () {
    // Create admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
    ]);
    
    // Create provider
    $provider = Provider::factory()->create();

    // Attempt to create tariff with clearly overlapping zones
    $response = $this->actingAs($admin)->post('/admin/tariffs', [
        'provider_id' => $provider->id,
        'name' => 'Clear Overlap Tariff',
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                ['id' => 'zone1', 'start' => '08:00', 'end' => '16:00', 'rate' => 0.15],
                ['id' => 'zone2', 'start' => '14:00', 'end' => '22:00', 'rate' => 0.18], // Overlaps from 14:00 to 16:00
                ['id' => 'zone3', 'start' => '22:00', 'end' => '08:00', 'rate' => 0.10],
            ],
        ],
        'active_from' => now()->format('Y-m-d'),
    ]);

    // Assert validation error
    $response->assertSessionHasErrors('configuration.zones');
    
    // Assert error message mentions overlap
    $errors = session('errors');
    expect($errors->get('configuration.zones')[0])->toContain('cannot overlap');
    
    // Assert tariff was not created
    $this->assertDatabaseMissing('tariffs', [
        'name' => 'Clear Overlap Tariff',
    ]);
});

