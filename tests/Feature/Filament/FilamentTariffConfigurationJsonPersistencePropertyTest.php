<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\TariffResource;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $this->provider = Provider::factory()->create();
});

describe('Property 12: Tariff configuration JSON persistence', function () {
    test('flat tariff configuration persists correctly as JSON', function () {
        actingAs($this->admin);
        
        $configuration = [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.15,
            'fixed_fee' => 5.00,
        ];
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Flat Tariff Test',
                'active_from' => now()->toDateString(),
                'configuration' => $configuration,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        
        $tariff = Tariff::where('name', 'Flat Tariff Test')->first();
        
        expect($tariff)->not->toBeNull()
            ->and($tariff->configuration)->toBeArray()
            ->and($tariff->configuration['type'])->toBe('flat')
            ->and($tariff->configuration['currency'])->toBe('EUR')
            ->and($tariff->configuration['rate'])->toBe(0.15)
            ->and($tariff->configuration['fixed_fee'])->toBe(5.00);
    });

    test('time of use tariff configuration persists correctly as JSON', function () {
        actingAs($this->admin);
        
        $configuration = [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                [
                    'id' => 'day',
                    'start' => '07:00',
                    'end' => '23:00',
                    'rate' => 0.20,
                ],
                [
                    'id' => 'night',
                    'start' => '23:00',
                    'end' => '07:00',
                    'rate' => 0.10,
                ],
            ],
            'weekend_logic' => 'apply_night_rate',
            'fixed_fee' => 3.50,
        ];
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'TOU Tariff Test',
                'active_from' => now()->toDateString(),
                'configuration' => $configuration,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        
        $tariff = Tariff::where('name', 'TOU Tariff Test')->first();
        
        expect($tariff)->not->toBeNull()
            ->and($tariff->configuration)->toBeArray()
            ->and($tariff->configuration['type'])->toBe('time_of_use')
            ->and($tariff->configuration['currency'])->toBe('EUR')
            ->and($tariff->configuration['zones'])->toBeArray()
            ->and($tariff->configuration['zones'])->toHaveCount(2)
            ->and($tariff->configuration['zones'][0]['id'])->toBe('day')
            ->and($tariff->configuration['zones'][0]['start'])->toBe('07:00')
            ->and($tariff->configuration['zones'][0]['end'])->toBe('23:00')
            ->and($tariff->configuration['zones'][0]['rate'])->toBe(0.20)
            ->and($tariff->configuration['zones'][1]['id'])->toBe('night')
            ->and($tariff->configuration['weekend_logic'])->toBe('apply_night_rate')
            ->and($tariff->configuration['fixed_fee'])->toBe(3.50);
    });

    test('configuration JSON structure is preserved after update', function () {
        actingAs($this->admin);
        
        $tariff = Tariff::factory()
            ->for($this->provider)
            ->flat()
            ->create([
                'name' => 'Original Tariff',
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ]);
        
        $newConfiguration = [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.18,
            'fixed_fee' => 2.50,
        ];
        
        Livewire::test(TariffResource\Pages\EditTariff::class, ['record' => $tariff->id])
            ->fillForm([
                'configuration' => $newConfiguration,
            ])
            ->call('save')
            ->assertHasNoFormErrors();
        
        $tariff->refresh();
        
        expect($tariff->configuration)->toBeArray()
            ->and($tariff->configuration['type'])->toBe('flat')
            ->and($tariff->configuration['rate'])->toBe(0.18)
            ->and($tariff->configuration['fixed_fee'])->toBe(2.50);
    });

    test('complex zone configurations persist with all fields', function () {
        actingAs($this->admin);
        
        $configuration = [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                [
                    'id' => 'peak',
                    'start' => '08:00',
                    'end' => '12:00',
                    'rate' => 0.25,
                ],
                [
                    'id' => 'standard',
                    'start' => '12:00',
                    'end' => '18:00',
                    'rate' => 0.18,
                ],
                [
                    'id' => 'off_peak',
                    'start' => '18:00',
                    'end' => '22:00',
                    'rate' => 0.15,
                ],
                [
                    'id' => 'night',
                    'start' => '22:00',
                    'end' => '08:00',
                    'rate' => 0.10,
                ],
            ],
            'weekend_logic' => 'apply_weekend_rate',
        ];
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Complex TOU Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => $configuration,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        
        $tariff = Tariff::where('name', 'Complex TOU Tariff')->first();
        
        expect($tariff->configuration['zones'])->toHaveCount(4)
            ->and($tariff->configuration['zones'][0]['id'])->toBe('peak')
            ->and($tariff->configuration['zones'][1]['id'])->toBe('standard')
            ->and($tariff->configuration['zones'][2]['id'])->toBe('off_peak')
            ->and($tariff->configuration['zones'][3]['id'])->toBe('night');
    });

    test('configuration JSON can be retrieved and edited', function () {
        actingAs($this->admin);
        
        $originalConfig = [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                [
                    'id' => 'day',
                    'start' => '07:00',
                    'end' => '23:00',
                    'rate' => 0.20,
                ],
            ],
        ];
        
        $tariff = Tariff::factory()
            ->for($this->provider)
            ->create([
                'name' => 'Editable Tariff',
                'configuration' => $originalConfig,
            ]);
        
        // Retrieve and verify
        $component = Livewire::test(TariffResource\Pages\EditTariff::class, ['record' => $tariff->id]);
        
        expect($component->get('data.configuration.type'))->toBe('time_of_use')
            ->and($component->get('data.configuration.zones'))->toBeArray()
            ->and($component->get('data.configuration.zones.0.id'))->toBe('day');
        
        // Update configuration
        $component->fillForm([
            'configuration' => [
                'type' => 'time_of_use',
                'currency' => 'EUR',
                'zones' => [
                    [
                        'id' => 'day',
                        'start' => '06:00', // Changed
                        'end' => '22:00',   // Changed
                        'rate' => 0.22,     // Changed
                    ],
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();
        
        $tariff->refresh();
        
        expect($tariff->configuration['zones'][0]['start'])->toBe('06:00')
            ->and($tariff->configuration['zones'][0]['end'])->toBe('22:00')
            ->and($tariff->configuration['zones'][0]['rate'])->toBe(0.22);
    });

    test('numeric precision is maintained in JSON', function () {
        actingAs($this->admin);
        
        $configuration = [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.1234, // 4 decimal places
            'fixed_fee' => 12.99,
        ];
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Precision Test Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => $configuration,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        
        $tariff = Tariff::where('name', 'Precision Test Tariff')->first();
        
        expect($tariff->configuration['rate'])->toBe(0.1234)
            ->and($tariff->configuration['fixed_fee'])->toBe(12.99);
    });

    test('optional fields are preserved when null', function () {
        actingAs($this->admin);
        
        $configuration = [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 0.15,
            // fixed_fee is optional and not provided
        ];
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Optional Fields Test',
                'active_from' => now()->toDateString(),
                'configuration' => $configuration,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        
        $tariff = Tariff::where('name', 'Optional Fields Test')->first();
        
        expect($tariff->configuration)->toBeArray()
            ->and($tariff->configuration['type'])->toBe('flat')
            ->and($tariff->configuration['rate'])->toBe(0.15)
            ->and(isset($tariff->configuration['fixed_fee']))->toBeFalse();
    });

    test('configuration structure matches between create and retrieve', function () {
        actingAs($this->admin);
        
        $originalConfiguration = [
            'type' => 'time_of_use',
            'currency' => 'EUR',
            'zones' => [
                [
                    'id' => 'morning',
                    'start' => '06:00',
                    'end' => '12:00',
                    'rate' => 0.18,
                ],
                [
                    'id' => 'afternoon',
                    'start' => '12:00',
                    'end' => '18:00',
                    'rate' => 0.22,
                ],
            ],
            'weekend_logic' => 'apply_day_rate',
            'fixed_fee' => 4.50,
        ];
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Structure Test Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => $originalConfiguration,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        
        $tariff = Tariff::where('name', 'Structure Test Tariff')->first();
        
        // Verify structure matches exactly
        expect($tariff->configuration)->toBe($originalConfiguration);
    });
});
