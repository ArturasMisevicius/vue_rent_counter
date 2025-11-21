<?php

use App\Enums\UserRole;
use App\Filament\Resources\ProviderResource;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Feature: filament-admin-panel, Property 19: Provider-tariff relationship visibility
// Validates: Requirements 8.4
test('ProviderResource displays all associated tariffs in relationship manager', function () {
    // Create a provider
    $provider = Provider::factory()->create();
    
    // Generate random number of tariffs (between 1 and 15)
    $tariffsCount = fake()->numberBetween(1, 15);
    $createdTariffs = [];
    
    for ($i = 0; $i < $tariffsCount; $i++) {
        $tariffType = fake()->randomElement(['flat', 'time_of_use']);
        
        $configuration = $tariffType === 'flat'
            ? [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => fake()->randomFloat(4, 0.05, 0.30),
            ]
            : [
                'type' => 'time_of_use',
                'currency' => 'EUR',
                'zones' => [
                    ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => fake()->randomFloat(4, 0.10, 0.25)],
                    ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => fake()->randomFloat(4, 0.05, 0.15)],
                ],
            ];
        
        $tariff = Tariff::create([
            'provider_id' => $provider->id,
            'name' => fake()->words(3, true),
            'configuration' => $configuration,
            'active_from' => now()->subMonths(fake()->numberBetween(1, 12)),
            'active_until' => fake()->boolean(70) ? null : now()->addMonths(fake()->numberBetween(1, 12)),
        ]);
        
        $createdTariffs[] = $tariff;
    }
    
    // Create an admin user (only admins can access providers)
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Property: When viewing a provider edit page, all associated tariffs should be accessible
    $component = Livewire::test(ProviderResource\Pages\EditProvider::class, [
        'record' => $provider->id,
    ]);
    
    $component->assertSuccessful();
    
    // Verify the provider is loaded
    expect($component->instance()->record->id)->toBe($provider->id);
    
    // Get the tariffs through the relationship
    $providerTariffs = $component->instance()->record->tariffs;
    
    // Property: All created tariffs should be present
    expect($providerTariffs)->toHaveCount($tariffsCount);
    
    // Property: Each tariff should have all required details
    foreach ($createdTariffs as $createdTariff) {
        $foundTariff = $providerTariffs->firstWhere('id', $createdTariff->id);
        
        expect($foundTariff)->not->toBeNull();
        expect($foundTariff->name)->toBe($createdTariff->name);
        expect($foundTariff->configuration)->toBe($createdTariff->configuration);
        expect($foundTariff->provider_id)->toBe($provider->id);
        expect($foundTariff->active_from->format('Y-m-d H:i:s'))
            ->toBe($createdTariff->active_from->format('Y-m-d H:i:s'));
        
        if ($createdTariff->active_until) {
            expect($foundTariff->active_until->format('Y-m-d H:i:s'))
                ->toBe($createdTariff->active_until->format('Y-m-d H:i:s'));
        } else {
            expect($foundTariff->active_until)->toBeNull();
        }
    }
    
    // Verify the relation manager can be accessed
    $relationManager = Livewire::test(
        ProviderResource\RelationManagers\TariffsRelationManager::class,
        [
            'ownerRecord' => $provider,
            'pageClass' => ProviderResource\Pages\EditProvider::class,
        ]
    );
    
    $relationManager->assertSuccessful();
    
    // Get table records from the relation manager
    $tableRecords = $relationManager->instance()->getTableRecords();
    
    // Property: All tariffs should be visible in the relation manager table
    expect($tableRecords)->toHaveCount($tariffsCount);
    
    // Property: Each tariff in the table should match the created tariffs
    $tableRecords->each(function ($tableTariff) use ($createdTariffs, $provider) {
        $matchingTariff = collect($createdTariffs)->firstWhere('id', $tableTariff->id);
        
        expect($matchingTariff)->not->toBeNull();
        expect($tableTariff->name)->toBe($matchingTariff->name);
        expect($tableTariff->provider_id)->toBe($provider->id);
        expect($tableTariff->configuration['type'])->toBe($matchingTariff->configuration['type']);
    });
})->repeat(100);

// Feature: filament-admin-panel, Property 19: Provider-tariff relationship visibility
// Validates: Requirements 8.4
test('ProviderResource displays tariffs even when provider has no tariffs', function () {
    // Create a provider without any tariffs
    $provider = Provider::factory()->create();
    
    // Create an admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Property: When viewing a provider with no tariffs, the relation manager should still be accessible
    $component = Livewire::test(ProviderResource\Pages\EditProvider::class, [
        'record' => $provider->id,
    ]);
    
    $component->assertSuccessful();
    
    // Get the tariffs through the relationship
    $providerTariffs = $component->instance()->record->tariffs;
    
    // Property: Provider should have zero tariffs
    expect($providerTariffs)->toHaveCount(0);
    
    // Verify the relation manager can be accessed even with no tariffs
    $relationManager = Livewire::test(
        ProviderResource\RelationManagers\TariffsRelationManager::class,
        [
            'ownerRecord' => $provider,
            'pageClass' => ProviderResource\Pages\EditProvider::class,
        ]
    );
    
    $relationManager->assertSuccessful();
    
    // Get table records from the relation manager
    $tableRecords = $relationManager->instance()->getTableRecords();
    
    // Property: Table should show zero tariffs
    expect($tableRecords)->toHaveCount(0);
})->repeat(100);

// Feature: filament-admin-panel, Property 19: Provider-tariff relationship visibility
// Validates: Requirements 8.4
test('ProviderResource only displays tariffs belonging to the provider', function () {
    // Create two providers
    $provider1 = Provider::factory()->create();
    $provider2 = Provider::factory()->create();
    
    // Create tariffs for provider 1
    $provider1TariffsCount = fake()->numberBetween(2, 8);
    $provider1Tariffs = [];
    
    for ($i = 0; $i < $provider1TariffsCount; $i++) {
        $provider1Tariffs[] = Tariff::factory()->create([
            'provider_id' => $provider1->id,
        ]);
    }
    
    // Create tariffs for provider 2
    $provider2TariffsCount = fake()->numberBetween(2, 8);
    $provider2Tariffs = [];
    
    for ($i = 0; $i < $provider2TariffsCount; $i++) {
        $provider2Tariffs[] = Tariff::factory()->create([
            'provider_id' => $provider2->id,
        ]);
    }
    
    // Create an admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Property: When viewing provider 1, only provider 1's tariffs should be visible
    $relationManager1 = Livewire::test(
        ProviderResource\RelationManagers\TariffsRelationManager::class,
        [
            'ownerRecord' => $provider1,
            'pageClass' => ProviderResource\Pages\EditProvider::class,
        ]
    );
    
    $relationManager1->assertSuccessful();
    
    $tableRecords1 = $relationManager1->instance()->getTableRecords();
    
    // Property: Only provider 1's tariffs should be present
    expect($tableRecords1)->toHaveCount($provider1TariffsCount);
    
    $tableRecords1->each(function ($tariff) use ($provider1, $provider2Tariffs) {
        expect($tariff->provider_id)->toBe($provider1->id);
        
        // Verify this tariff is not from provider 2
        $isFromProvider2 = collect($provider2Tariffs)->contains('id', $tariff->id);
        expect($isFromProvider2)->toBeFalse();
    });
    
    // Property: When viewing provider 2, only provider 2's tariffs should be visible
    $relationManager2 = Livewire::test(
        ProviderResource\RelationManagers\TariffsRelationManager::class,
        [
            'ownerRecord' => $provider2,
            'pageClass' => ProviderResource\Pages\EditProvider::class,
        ]
    );
    
    $relationManager2->assertSuccessful();
    
    $tableRecords2 = $relationManager2->instance()->getTableRecords();
    
    // Property: Only provider 2's tariffs should be present
    expect($tableRecords2)->toHaveCount($provider2TariffsCount);
    
    $tableRecords2->each(function ($tariff) use ($provider2, $provider1Tariffs) {
        expect($tariff->provider_id)->toBe($provider2->id);
        
        // Verify this tariff is not from provider 1
        $isFromProvider1 = collect($provider1Tariffs)->contains('id', $tariff->id);
        expect($isFromProvider1)->toBeFalse();
    });
})->repeat(100);

// Feature: filament-admin-panel, Property 19: Provider-tariff relationship visibility
// Validates: Requirements 8.4
test('ProviderResource displays tariff details correctly in relationship manager', function () {
    // Create a provider
    $provider = Provider::factory()->create();
    
    // Create a mix of flat and time-of-use tariffs
    $flatTariff = Tariff::factory()->flat()->create([
        'provider_id' => $provider->id,
        'name' => 'Flat Rate Tariff',
        'active_from' => now()->subMonths(6),
        'active_until' => null,
    ]);
    
    $touTariff = Tariff::factory()->timeOfUse()->create([
        'provider_id' => $provider->id,
        'name' => 'Time of Use Tariff',
        'active_from' => now()->subMonths(3),
        'active_until' => now()->addMonths(6),
    ]);
    
    $expiredTariff = Tariff::factory()->flat()->create([
        'provider_id' => $provider->id,
        'name' => 'Expired Tariff',
        'active_from' => now()->subMonths(12),
        'active_until' => now()->subMonths(1),
    ]);
    
    // Create an admin user
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN,
        'tenant_id' => null,
    ]);
    
    // Act as the admin
    $this->actingAs($admin);
    
    // Property: All tariffs should be visible regardless of active status
    $relationManager = Livewire::test(
        ProviderResource\RelationManagers\TariffsRelationManager::class,
        [
            'ownerRecord' => $provider,
            'pageClass' => ProviderResource\Pages\EditProvider::class,
        ]
    );
    
    $relationManager->assertSuccessful();
    
    $tableRecords = $relationManager->instance()->getTableRecords();
    
    // Property: All three tariffs should be present
    expect($tableRecords)->toHaveCount(3);
    
    // Property: Flat tariff should have correct type
    $foundFlatTariff = $tableRecords->firstWhere('id', $flatTariff->id);
    expect($foundFlatTariff)->not->toBeNull();
    expect($foundFlatTariff->configuration['type'])->toBe('flat');
    expect($foundFlatTariff->name)->toBe('Flat Rate Tariff');
    expect($foundFlatTariff->active_until)->toBeNull();
    
    // Property: Time-of-use tariff should have correct type
    $foundTouTariff = $tableRecords->firstWhere('id', $touTariff->id);
    expect($foundTouTariff)->not->toBeNull();
    expect($foundTouTariff->configuration['type'])->toBe('time_of_use');
    expect($foundTouTariff->name)->toBe('Time of Use Tariff');
    expect($foundTouTariff->active_until)->not->toBeNull();
    
    // Property: Expired tariff should be visible
    $foundExpiredTariff = $tableRecords->firstWhere('id', $expiredTariff->id);
    expect($foundExpiredTariff)->not->toBeNull();
    expect($foundExpiredTariff->name)->toBe('Expired Tariff');
    expect($foundExpiredTariff->active_until)->not->toBeNull();
    expect($foundExpiredTariff->active_until->isPast())->toBeTrue();
})->repeat(100);
