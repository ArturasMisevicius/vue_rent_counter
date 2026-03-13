<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\TariffResource;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $this->provider = Provider::factory()->create();
});

describe('Property 11: Tariff validation consistency', function () {
    test('provider_id validation is consistent between form and request', function () {
        actingAs($this->admin);
        
        // Test missing provider_id
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'name' => 'Test Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['provider_id' => 'required']);
        
        // Test invalid provider_id
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => 99999,
                'name' => 'Test Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['provider_id' => 'exists']);
        
        // Test valid provider_id
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Valid Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors(['provider_id']);
    });

    test('name validation is consistent between form and request', function () {
        actingAs($this->admin);
        
        // Test missing name
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
        
        // Test name too long
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => str_repeat('a', 256),
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'max']);
        
        // Test name is not a string (numeric)
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 12345,
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['name' => 'string']);
        
        // Test valid name
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Valid Tariff Name',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors(['name']);
    });

    test('active_from validation is consistent between form and request', function () {
        actingAs($this->admin);
        
        // Test missing active_from
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test Tariff',
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['active_from' => 'required']);
        
        // Test invalid date format
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test Tariff',
                'active_from' => 'invalid-date',
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['active_from' => 'date']);
    });

    test('active_until validation is consistent between form and request', function () {
        actingAs($this->admin);
        
        // Test active_until before active_from
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test Tariff',
                'active_from' => now()->toDateString(),
                'active_until' => now()->subDay()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['active_until' => 'after']);
    });

    test('flat tariff rate validation is consistent', function () {
        actingAs($this->admin);
        
        // Test missing rate for flat tariff
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test Flat Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.rate' => 'required']);
        
        // Test negative rate
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test Flat Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => -0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.rate' => 'min']);
    });

    test('time of use tariff zones validation is consistent', function () {
        actingAs($this->admin);
        
        // Test missing zones for time_of_use tariff
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test TOU Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.zones' => 'required']);
        
        // Test empty zones array
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test TOU Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.zones' => 'min']);
    });

    test('zone field validation is consistent', function () {
        actingAs($this->admin);
        
        // Test invalid time format
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test TOU Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [
                        [
                            'id' => 'day',
                            'start' => '25:00', // Invalid hour
                            'end' => '17:00',
                            'rate' => 0.20,
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.zones.0.start' => 'regex']);
        
        // Test negative zone rate
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test TOU Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [
                        [
                            'id' => 'day',
                            'start' => '07:00',
                            'end' => '17:00',
                            'rate' => -0.20,
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.zones.0.rate' => 'min']);
    });

    test('weekend logic validation is consistent', function () {
        actingAs($this->admin);
        
        // Test invalid weekend logic value
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test TOU Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'weekend_logic' => 'invalid_option',
                    'zones' => [
                        [
                            'id' => 'day',
                            'start' => '07:00',
                            'end' => '17:00',
                            'rate' => 0.20,
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.weekend_logic' => 'in']);
        
        // Test weekend logic is not a string
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test TOU Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'weekend_logic' => 123,
                    'zones' => [
                        [
                            'id' => 'day',
                            'start' => '07:00',
                            'end' => '17:00',
                            'rate' => 0.20,
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.weekend_logic' => 'string']);
        
        // Test valid weekend logic values
        $validOptions = ['apply_night_rate', 'apply_day_rate', 'apply_weekend_rate'];
        
        foreach ($validOptions as $option) {
            Livewire::test(TariffResource\Pages\CreateTariff::class)
                ->fillForm([
                    'provider_id' => $this->provider->id,
                    'name' => "Test TOU Tariff {$option}",
                    'active_from' => now()->toDateString(),
                    'configuration' => [
                        'type' => 'time_of_use',
                        'currency' => 'EUR',
                        'weekend_logic' => $option,
                        'zones' => [
                            [
                                'id' => 'day',
                                'start' => '07:00',
                                'end' => '17:00',
                                'rate' => 0.20,
                            ],
                        ],
                    ],
                ])
                ->call('create')
                ->assertHasNoFormErrors(['configuration.weekend_logic']);
        }
    });

    test('fixed fee validation is consistent', function () {
        actingAs($this->admin);
        
        // Test negative fixed fee
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                    'fixed_fee' => -5.00,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.fixed_fee' => 'min']);
    });

    test('successful tariff creation with valid data', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Valid Flat Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 'EUR',
                    'rate' => 0.15,
                    'fixed_fee' => 5.00,
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        
        expect(Tariff::where('name', 'Valid Flat Tariff')->exists())->toBeTrue();
    });
    
    test('configuration type validation enforces string type', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 123, // Invalid: not a string
                    'currency' => 'EUR',
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.type' => 'string']);
    });
    
    test('configuration currency validation enforces string type', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'flat',
                    'currency' => 123, // Invalid: not a string
                    'rate' => 0.15,
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.currency' => 'string']);
    });
    
    test('zones array validation enforces array type', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test TOU Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => 'not_an_array', // Invalid: not an array
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.zones' => 'array']);
    });
    
    test('zone id validation enforces string type', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test TOU Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [
                        [
                            'id' => 123, // Invalid: not a string
                            'start' => '07:00',
                            'end' => '23:00',
                            'rate' => 0.20,
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.zones.0.id' => 'string']);
    });
    
    test('zone start time validation enforces string type', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test TOU Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [
                        [
                            'id' => 'day',
                            'start' => 700, // Invalid: not a string
                            'end' => '23:00',
                            'rate' => 0.20,
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.zones.0.start' => 'string']);
    });
    
    test('zone end time validation enforces string type', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Test TOU Tariff',
                'active_from' => now()->toDateString(),
                'configuration' => [
                    'type' => 'time_of_use',
                    'currency' => 'EUR',
                    'zones' => [
                        [
                            'id' => 'day',
                            'start' => '07:00',
                            'end' => 2300, // Invalid: not a string
                            'rate' => 0.20,
                        ],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors(['configuration.zones.0.end' => 'string']);
    });
    
    test('all validation rules work together for complex time of use tariff', function () {
        actingAs($this->admin);
        
        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm([
                'provider_id' => $this->provider->id,
                'name' => 'Complex TOU Tariff',
                'active_from' => now()->toDateString(),
                'active_until' => now()->addYear()->toDateString(),
                'configuration' => [
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
                    ],
                    'weekend_logic' => 'apply_weekend_rate',
                    'fixed_fee' => 3.50,
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        
        $tariff = Tariff::where('name', 'Complex TOU Tariff')->first();
        
        expect($tariff)->not->toBeNull()
            ->and($tariff->configuration['zones'])->toHaveCount(3)
            ->and($tariff->configuration['weekend_logic'])->toBe('apply_weekend_rate')
            ->and($tariff->configuration['fixed_fee'])->toBe(3.50);
    });
});
