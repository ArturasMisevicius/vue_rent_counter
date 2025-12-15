<?php

use App\Enums\UserRole;
use App\Filament\Resources\TariffResource;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 12: Tariff configuration JSON persistence
// Validates: Requirements 5.6
test('Filament TariffResource persists flat rate configuration as valid JSON', function () {
    // Create an admin user (only admins can manage tariffs)
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    // Create a provider
    $provider = Provider::factory()->create();
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate random flat rate tariff data
    $testData = [
        'provider_id' => $provider->id,
        'name' => fake()->words(3, true),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => fake()->randomFloat(4, 0.01, 1.0),
        ],
        'active_from' => now()->subDays(fake()->numberBetween(1, 30))->format('Y-m-d'),
        'active_until' => fake()->boolean() ? now()->addDays(fake()->numberBetween(1, 365))->format('Y-m-d') : null,
    ];
    
    // Create tariff through Filament
    $component = Livewire::test(TariffResource\Pages\CreateTariff::class);
    
    $component->fillForm([
        'provider_id' => $testData['provider_id'],
        'name' => $testData['name'],
        'configuration' => $testData['configuration'],
        'active_from' => $testData['active_from'],
        'active_until' => $testData['active_until'],
    ]);
    
    $component->call('create');
    
    // Property: The saved tariff should have valid JSON in configuration column
    $tariff = Tariff::where('name', $testData['name'])->first();
    
    expect($tariff)->not->toBeNull('Tariff should be created');
    
    // Get the raw JSON from database
    $rawJson = DB::table('tariffs')
        ->where('id', $tariff->id)
        ->value('configuration');
    
    expect($rawJson)->not->toBeNull('Configuration should not be null');
    
    // Property: JSON should be decodable without errors
    $decoded = json_decode($rawJson, true);
    $jsonError = json_last_error();
    
    expect($jsonError)->toBe(JSON_ERROR_NONE, 
        'JSON should decode without errors. Error: ' . json_last_error_msg() . '. Raw JSON: ' . $rawJson
    );
    
    expect($decoded)->toBeArray('Decoded configuration should be an array');
    
    // Property: Decoded JSON should match the original configuration
    expect($decoded)->toHaveKey('type');
    expect($decoded['type'])->toBe($testData['configuration']['type']);
    expect($decoded)->toHaveKey('currency');
    expect($decoded['currency'])->toBe($testData['configuration']['currency']);
    expect($decoded)->toHaveKey('rate');
    expect($decoded['rate'])->toBe($testData['configuration']['rate']);
    
    // Property: Eloquent model should be able to access configuration as array
    $tariff->refresh();
    expect($tariff->configuration)->toBeArray('Model configuration should be cast to array');
    expect($tariff->configuration['type'])->toBe($testData['configuration']['type']);
    expect($tariff->configuration['rate'])->toBe($testData['configuration']['rate']);
})->repeat(100);

// Feature: filament-admin-panel, Property 12: Tariff configuration JSON persistence
// Validates: Requirements 5.6
test('Filament TariffResource persists time-of-use configuration as valid JSON', function () {
    // Create an admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    // Create a provider
    $provider = Provider::factory()->create();
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate random time-of-use tariff data with valid zones
    $zoneCount = fake()->numberBetween(1, 4);
    $zones = [];
    
    for ($i = 0; $i < $zoneCount; $i++) {
        $zones[] = [
            'id' => fake()->unique()->randomElement(['day', 'night', 'peak', 'off_peak', 'shoulder']),
            'start' => sprintf('%02d:00', fake()->numberBetween(0, 23)),
            'end' => sprintf('%02d:00', fake()->numberBetween(0, 23)),
            'rate' => fake()->randomFloat(4, 0.01, 1.0),
        ];
    }
    
    $weekendLogic = fake()->randomElement(['apply_night_rate', 'apply_day_rate', 'apply_weekend_rate', null]);
    $fixedFee = fake()->boolean() ? fake()->randomFloat(2, 0, 50) : null;
    
    $testData = [
        'provider_id' => $provider->id,
        'name' => fake()->words(3, true),
        'configuration' => [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => $zones,
        ],
        'active_from' => now()->subDays(fake()->numberBetween(1, 30))->format('Y-m-d'),
        'active_until' => fake()->boolean() ? now()->addDays(fake()->numberBetween(1, 365))->format('Y-m-d') : null,
    ];
    
    if ($weekendLogic !== null) {
        $testData['configuration']['weekend_logic'] = $weekendLogic;
    }
    
    if ($fixedFee !== null) {
        $testData['configuration']['fixed_fee'] = $fixedFee;
    }
    
    // Create tariff through Filament
    $component = Livewire::test(TariffResource\Pages\CreateTariff::class);
    
    $component->fillForm([
        'provider_id' => $testData['provider_id'],
        'name' => $testData['name'],
        'configuration' => $testData['configuration'],
        'active_from' => $testData['active_from'],
        'active_until' => $testData['active_until'],
    ]);
    
    $component->call('create');
    
    // Property: The saved tariff should have valid JSON in configuration column
    $tariff = Tariff::where('name', $testData['name'])->first();
    
    expect($tariff)->not->toBeNull('Tariff should be created');
    
    // Get the raw JSON from database
    $rawJson = DB::table('tariffs')
        ->where('id', $tariff->id)
        ->value('configuration');
    
    expect($rawJson)->not->toBeNull('Configuration should not be null');
    
    // Property: JSON should be decodable without errors
    $decoded = json_decode($rawJson, true);
    $jsonError = json_last_error();
    
    expect($jsonError)->toBe(JSON_ERROR_NONE, 
        'JSON should decode without errors. Error: ' . json_last_error_msg() . '. Raw JSON: ' . $rawJson
    );
    
    expect($decoded)->toBeArray('Decoded configuration should be an array');
    
    // Property: Decoded JSON should match the original configuration structure
    expect($decoded)->toHaveKey('type');
    expect($decoded['type'])->toBe('time_of_use');
    expect($decoded)->toHaveKey('currency');
    expect($decoded['currency'])->toBe('EUR');
    expect($decoded)->toHaveKey('zones');
    expect($decoded['zones'])->toBeArray();
    expect(count($decoded['zones']))->toBe(count($zones));
    
    // Verify each zone is properly persisted
    foreach ($decoded['zones'] as $index => $zone) {
        expect($zone)->toHaveKey('id');
        expect($zone)->toHaveKey('start');
        expect($zone)->toHaveKey('end');
        expect($zone)->toHaveKey('rate');
    }
    
    // Property: Eloquent model should be able to access configuration as array
    $tariff->refresh();
    expect($tariff->configuration)->toBeArray('Model configuration should be cast to array');
    expect($tariff->configuration['type'])->toBe('time_of_use');
    expect($tariff->configuration['zones'])->toBeArray();
    expect(count($tariff->configuration['zones']))->toBe(count($zones));
})->repeat(100);

// Feature: filament-admin-panel, Property 12: Tariff configuration JSON persistence
// Validates: Requirements 5.6
test('Filament TariffResource persists optional configuration fields as valid JSON', function () {
    // Create an admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    // Create a provider
    $provider = Provider::factory()->create();
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate flat rate tariff with optional fields
    $fixedFee = fake()->randomFloat(2, 0, 50);
    
    $testData = [
        'provider_id' => $provider->id,
        'name' => fake()->words(3, true),
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => fake()->randomFloat(4, 0.01, 1.0),
            'fixed_fee' => $fixedFee,
        ],
        'active_from' => now()->subDays(fake()->numberBetween(1, 30))->format('Y-m-d'),
        'active_until' => fake()->boolean() ? now()->addDays(fake()->numberBetween(1, 365))->format('Y-m-d') : null,
    ];
    
    // Create tariff through Filament
    $component = Livewire::test(TariffResource\Pages\CreateTariff::class);
    
    $component->fillForm([
        'provider_id' => $testData['provider_id'],
        'name' => $testData['name'],
        'configuration' => $testData['configuration'],
        'active_from' => $testData['active_from'],
        'active_until' => $testData['active_until'],
    ]);
    
    $component->call('create');
    
    // Property: The saved tariff should have valid JSON with optional fields
    $tariff = Tariff::where('name', $testData['name'])->first();
    
    expect($tariff)->not->toBeNull('Tariff should be created');
    
    // Get the raw JSON from database
    $rawJson = DB::table('tariffs')
        ->where('id', $tariff->id)
        ->value('configuration');
    
    expect($rawJson)->not->toBeNull('Configuration should not be null');
    
    // Property: JSON should be decodable without errors
    $decoded = json_decode($rawJson, true);
    $jsonError = json_last_error();
    
    expect($jsonError)->toBe(JSON_ERROR_NONE, 
        'JSON should decode without errors. Error: ' . json_last_error_msg() . '. Raw JSON: ' . $rawJson
    );
    
    expect($decoded)->toBeArray('Decoded configuration should be an array');
    
    // Property: Optional fields should be persisted correctly
    expect($decoded)->toHaveKey('fixed_fee');
    expect($decoded['fixed_fee'])->toBe($fixedFee);
    
    // Property: Eloquent model should access optional fields correctly
    $tariff->refresh();
    expect($tariff->configuration)->toBeArray('Model configuration should be cast to array');
    expect($tariff->configuration['fixed_fee'])->toBe($fixedFee);
})->repeat(100);

// Feature: filament-admin-panel, Property 12: Tariff configuration JSON persistence
// Validates: Requirements 5.6
test('Filament TariffResource can update tariff configuration and maintain valid JSON', function () {
    // Create an admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => 1,
    ]);
    
    // Create a provider and tariff
    $provider = Provider::factory()->create();
    $tariff = Tariff::factory()->create([
        'provider_id' => $provider->id,
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.15,
        ],
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Generate new configuration
    $newRate = fake()->randomFloat(4, 0.01, 1.0);
    $newConfiguration = [
        'type' => 'flat',
        'currency' => 'EUR',
        'rate' => $newRate,
    ];
    
    // Update tariff through Filament
    $component = Livewire::test(TariffResource\Pages\EditTariff::class, [
        'record' => $tariff->id,
    ]);
    
    $component->fillForm([
        'configuration' => $newConfiguration,
    ]);
    
    $component->call('save');
    
    // Property: The updated tariff should have valid JSON in configuration column
    $tariff->refresh();
    
    // Get the raw JSON from database
    $rawJson = DB::table('tariffs')
        ->where('id', $tariff->id)
        ->value('configuration');
    
    expect($rawJson)->not->toBeNull('Configuration should not be null');
    
    // Property: JSON should be decodable without errors
    $decoded = json_decode($rawJson, true);
    $jsonError = json_last_error();
    
    expect($jsonError)->toBe(JSON_ERROR_NONE, 
        'JSON should decode without errors after update. Error: ' . json_last_error_msg() . '. Raw JSON: ' . $rawJson
    );
    
    expect($decoded)->toBeArray('Decoded configuration should be an array');
    
    // Property: Updated configuration should match the new values
    expect($decoded['rate'])->toBe($newRate);
    
    // Property: Eloquent model should access updated configuration correctly
    expect($tariff->configuration)->toBeArray('Model configuration should be cast to array');
    expect($tariff->configuration['rate'])->toBe($newRate);
})->repeat(100);
