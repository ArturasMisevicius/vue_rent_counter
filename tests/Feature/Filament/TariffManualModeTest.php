<?php

declare(strict_types=1);

use App\Filament\Resources\TariffResource;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => 'admin']);
    actingAs($this->admin);
});

it('can create a manual tariff without provider', function () {
    $data = [
        'manual_mode' => true,  // Enable manual mode
        'name' => 'Manual Test Tariff',
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
            'currency' => 'EUR',
        ],
        'active_from' => now()->toDateString(),
        'active_until' => null,
    ];

    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasNoFormErrors();

    $tariff = Tariff::where('name', 'Manual Test Tariff')->first();
    
    expect($tariff)->not->toBeNull()
        ->and($tariff->provider_id)->toBeNull()
        ->and($tariff->remote_id)->toBeNull()
        ->and($tariff->isManual())->toBeTrue();
});

it('can create a tariff with provider and remote_id', function () {
    $provider = Provider::factory()->create();
    
    $data = [
        'provider_id' => $provider->id,
        'remote_id' => 'EXT-12345',
        'name' => 'Provider Linked Tariff',
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.20,
            'currency' => 'EUR',
        ],
        'active_from' => now()->toDateString(),
        'active_until' => null,
    ];

    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasNoFormErrors();

    $tariff = Tariff::where('name', 'Provider Linked Tariff')->first();
    
    expect($tariff)->not->toBeNull()
        ->and($tariff->provider_id)->toBe($provider->id)
        ->and($tariff->remote_id)->toBe('EXT-12345')
        ->and($tariff->isManual())->toBeFalse();
});

it('requires provider when remote_id is provided in non-manual mode', function () {
    $data = [
        'manual_mode' => false, // Explicitly set to non-manual mode
        'remote_id' => 'EXT-12345',
        'name' => 'Invalid Tariff',
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
            'currency' => 'EUR',
        ],
        'active_from' => now()->toDateString(),
    ];

    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasFormErrors(['provider_id' => 'required']);
});

it('validates remote_id max length', function () {
    $provider = Provider::factory()->create();
    
    $data = [
        'provider_id' => $provider->id,
        'remote_id' => str_repeat('A', 256), // Exceeds 255 char limit
        'name' => 'Test Tariff',
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
            'currency' => 'EUR',
        ],
        'active_from' => now()->toDateString(),
    ];

    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasFormErrors(['remote_id' => 'max']);
});

it('can edit manual tariff and add provider later', function () {
    $tariff = Tariff::factory()->create([
        'provider_id' => null,
        'remote_id' => null,
        'name' => 'Manual Tariff',
    ]);
    
    $provider = Provider::factory()->create();

    Livewire::test(TariffResource\Pages\EditTariff::class, [
        'record' => $tariff->getRouteKey(),
    ])
        ->fillForm([
            'provider_id' => $provider->id,
            'remote_id' => 'EXT-NEW-123',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $tariff->refresh();
    
    expect($tariff->provider_id)->toBe($provider->id)
        ->and($tariff->remote_id)->toBe('EXT-NEW-123')
        ->and($tariff->isManual())->toBeFalse();
});

// NEW TESTS: Field Visibility and Reactivity

it('hides provider and remote_id fields when manual mode is enabled', function () {
    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->set('data.manual_mode', true)
        ->assertFormFieldExists('name')
        ->assertFormFieldIsVisible('name')
        ->assertFormFieldExists('provider_id')
        ->assertFormFieldIsHidden('provider_id')
        ->assertFormFieldExists('remote_id')
        ->assertFormFieldIsHidden('remote_id');
});

it('shows provider and remote_id fields when manual mode is disabled', function () {
    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->set('data.manual_mode', false)
        ->assertFormFieldExists('provider_id')
        ->assertFormFieldIsVisible('provider_id')
        ->assertFormFieldExists('remote_id')
        ->assertFormFieldIsVisible('remote_id');
});

it('preserves form data when toggling manual mode', function () {
    $provider = Provider::factory()->create();
    
    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->fillForm([
            'name' => 'Test Tariff',
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
                'currency' => 'EUR',
            ],
            'active_from' => now()->toDateString(),
        ])
        ->set('data.manual_mode', true)
        ->assertFormSet([
            'name' => 'Test Tariff',
            'configuration.type' => 'flat',
            'configuration.rate' => 0.15,
        ])
        ->set('data.manual_mode', false)
        ->assertFormSet([
            'name' => 'Test Tariff',
            'configuration.type' => 'flat',
            'configuration.rate' => 0.15,
        ]);
});

it('does not save manual_mode field to database', function () {
    $data = [
        'manual_mode' => true,
        'name' => 'Manual Tariff',
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
            'currency' => 'EUR',
        ],
        'active_from' => now()->toDateString(),
    ];

    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasNoFormErrors();

    $tariff = Tariff::where('name', 'Manual Tariff')->first();
    
    // Verify manual_mode is not a database column
    expect($tariff)->not->toBeNull()
        ->and($tariff->getAttributes())->not->toHaveKey('manual_mode');
});

it('allows null provider_id in manual mode', function () {
    $data = [
        'manual_mode' => true,
        'provider_id' => null,
        'name' => 'Manual Tariff',
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
            'currency' => 'EUR',
        ],
        'active_from' => now()->toDateString(),
    ];

    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Tariff::where('name', 'Manual Tariff')->first())
        ->not->toBeNull()
        ->provider_id->toBeNull();
});

it('requires provider_id when manual mode is disabled', function () {
    $data = [
        'manual_mode' => false,
        'provider_id' => null,
        'name' => 'Provider Tariff',
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
            'currency' => 'EUR',
        ],
        'active_from' => now()->toDateString(),
    ];

    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasFormErrors(['provider_id']);
});

it('accepts valid remote_id formats', function () {
    $provider = Provider::factory()->create();
    
    $validIds = [
        'EXT-12345',
        'provider_123',
        'system.id.456',
        'ABC-DEF_123.456',
    ];

    foreach ($validIds as $remoteId) {
        $data = [
            'provider_id' => $provider->id,
            'remote_id' => $remoteId,
            'name' => "Test Tariff {$remoteId}",
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
                'currency' => 'EUR',
            ],
            'active_from' => now()->toDateString(),
        ];

        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $tariff = Tariff::where('name', "Test Tariff {$remoteId}")->first();
        expect($tariff)->not->toBeNull()
            ->and($tariff->remote_id)->toBe($remoteId);
    }
});

it('allows empty remote_id even with provider selected', function () {
    $provider = Provider::factory()->create();
    
    $data = [
        'provider_id' => $provider->id,
        'remote_id' => null,
        'name' => 'Provider Tariff Without Remote ID',
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
            'currency' => 'EUR',
        ],
        'active_from' => now()->toDateString(),
    ];

    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasNoFormErrors();

    $tariff = Tariff::where('name', 'Provider Tariff Without Remote ID')->first();
    
    expect($tariff)->not->toBeNull()
        ->and($tariff->provider_id)->toBe($provider->id)
        ->and($tariff->remote_id)->toBeNull();
});

it('accepts hierarchical remote_id with dots', function () {
    $provider = Provider::factory()->create();
    
    $hierarchicalIds = [
        'system.provider.id.123',
        'aws.s3.bucket.456',
        'api.v2.endpoint.789',
    ];

    foreach ($hierarchicalIds as $remoteId) {
        $data = [
            'provider_id' => $provider->id,
            'remote_id' => $remoteId,
            'name' => "Test Tariff {$remoteId}",
            'configuration' => [
                'type' => 'flat',
                'rate' => 0.15,
                'currency' => 'EUR',
            ],
            'active_from' => now()->toDateString(),
        ];

        Livewire::test(TariffResource\Pages\CreateTariff::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $tariff = Tariff::where('name', "Test Tariff {$remoteId}")->first();
        
        expect($tariff)->not->toBeNull()
            ->and($tariff->remote_id)->toBe($remoteId);
    }
});

it('sanitizes remote_id with dots correctly', function () {
    $provider = Provider::factory()->create();
    
    $data = [
        'provider_id' => $provider->id,
        'remote_id' => 'test@provider#123.id!456',
        'name' => 'Test Tariff Sanitization',
        'configuration' => [
            'type' => 'flat',
            'rate' => 0.15,
            'currency' => 'EUR',
        ],
        'active_from' => now()->toDateString(),
    ];

    Livewire::test(TariffResource\Pages\CreateTariff::class)
        ->fillForm($data)
        ->call('create')
        ->assertHasNoFormErrors();

    $tariff = Tariff::where('name', 'Test Tariff Sanitization')->first();
    
    expect($tariff)->not->toBeNull()
        ->and($tariff->remote_id)->toBe('testprovider123.id456')
        ->and($tariff->remote_id)->not->toContain('@')
        ->and($tariff->remote_id)->not->toContain('#')
        ->and($tariff->remote_id)->not->toContain('!')
        ->and($tariff->remote_id)->toContain('.');
});
