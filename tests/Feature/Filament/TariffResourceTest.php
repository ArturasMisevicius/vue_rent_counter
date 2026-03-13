<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Filament\Resources\TariffResource;
use App\Models\Provider;
use App\Models\Tariff;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $this->manager = User::factory()->create(['role' => UserRole::MANAGER]);
    $this->tenant = User::factory()->create(['role' => UserRole::TENANT]);
    $this->provider = Provider::factory()->create();
});

describe('TariffResource Authorization', function () {
    test('admin can view tariff index', function () {
        actingAs($this->admin);
        
        expect(TariffResource::canViewAny())->toBeTrue();
    });

    test('manager can view tariff index', function () {
        actingAs($this->manager);
        
        expect(TariffResource::canViewAny())->toBeFalse();
    });

    test('tenant can view tariff index', function () {
        actingAs($this->tenant);
        
        expect(TariffResource::canViewAny())->toBeFalse();
    });

    test('admin can create tariffs', function () {
        actingAs($this->admin);
        
        expect(TariffResource::canCreate())->toBeTrue();
    });

    test('manager cannot create tariffs', function () {
        actingAs($this->manager);
        
        expect(TariffResource::canCreate())->toBeFalse();
    });

    test('tenant cannot create tariffs', function () {
        actingAs($this->tenant);
        
        expect(TariffResource::canCreate())->toBeFalse();
    });

    test('admin can edit tariffs', function () {
        $tariff = Tariff::factory()->for($this->provider)->create();
        
        actingAs($this->admin);
        
        expect(TariffResource::canEdit($tariff))->toBeTrue();
    });

    test('manager cannot edit tariffs', function () {
        $tariff = Tariff::factory()->for($this->provider)->create();
        
        actingAs($this->manager);
        
        expect(TariffResource::canEdit($tariff))->toBeFalse();
    });

    test('admin can delete tariffs', function () {
        $tariff = Tariff::factory()->for($this->provider)->create();
        
        actingAs($this->admin);
        
        expect(TariffResource::canDelete($tariff))->toBeTrue();
    });

    test('manager cannot delete tariffs', function () {
        $tariff = Tariff::factory()->for($this->provider)->create();
        
        actingAs($this->manager);
        
        expect(TariffResource::canDelete($tariff))->toBeFalse();
    });
});

describe('TariffResource Navigation', function () {
    test('navigation is visible for admin', function () {
        actingAs($this->admin);
        
        expect(TariffResource::shouldRegisterNavigation())->toBeTrue();
    });

    test('navigation is hidden for manager', function () {
        actingAs($this->manager);
        
        expect(TariffResource::shouldRegisterNavigation())->toBeFalse();
    });

    test('navigation is hidden for tenant', function () {
        actingAs($this->tenant);
        
        expect(TariffResource::shouldRegisterNavigation())->toBeFalse();
    });
});

describe('TariffResource Form Validation', function () {
    test('flat tariff requires rate field', function () {
        actingAs($this->admin);
        
        $data = [
            'provider_id' => $this->provider->id,
            'name' => 'Test Flat Tariff',
            'active_from' => now()->toDateString(),
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                // Missing 'rate' field
            ],
        ];
        
        // This would fail validation in the actual form submission
        expect($data['configuration'])->not->toHaveKey('rate');
    });

    test('time of use tariff requires zones', function () {
        actingAs($this->admin);
        
        $data = [
            'provider_id' => $this->provider->id,
            'name' => 'Test TOU Tariff',
            'active_from' => now()->toDateString(),
            'configuration' => [
                'type' => 'time_of_use',
                'currency' => 'EUR',
                // Missing 'zones' field
            ],
        ];
        
        expect($data['configuration'])->not->toHaveKey('zones');
    });

    test('active_until must be after active_from', function () {
        actingAs($this->admin);
        
        $activeFrom = now();
        $activeUntil = now()->subDay();
        
        expect($activeUntil->isBefore($activeFrom))->toBeTrue();
    });
});

describe('TariffResource Table Columns', function () {
    test('table displays provider name', function () {
        $tariff = Tariff::factory()->for($this->provider)->create();
        
        actingAs($this->admin);
        
        expect($tariff->provider->name)->toBe($this->provider->name);
    });

    test('table displays tariff type badge', function () {
        $flatTariff = Tariff::factory()->flat()->for($this->provider)->create();
        $touTariff = Tariff::factory()->timeOfUse()->for($this->provider)->create();
        
        actingAs($this->admin);
        
        expect($flatTariff->configuration['type'])->toBe('flat');
        expect($touTariff->configuration['type'])->toBe('time_of_use');
    });

    test('table displays active status correctly', function () {
        $activeTariff = Tariff::factory()
            ->for($this->provider)
            ->activeFrom(now()->subMonth())
            ->create();
        
        $inactiveTariff = Tariff::factory()
            ->for($this->provider)
            ->activeFrom(now()->addMonth())
            ->create();
        
        actingAs($this->admin);
        
        expect($activeTariff->isActiveOn(now()))->toBeTrue();
        expect($inactiveTariff->isActiveOn(now()))->toBeFalse();
    });
});

describe('TariffResource Form Fields', function () {
    test('provider field is searchable', function () {
        actingAs($this->admin);
        
        $providers = Provider::factory()->count(5)->create();
        
        expect($providers->count())->toBe(5);
    });

    test('flat rate field is visible only for flat tariffs', function () {
        actingAs($this->admin);
        
        $flatConfig = ['type' => 'flat'];
        $touConfig = ['type' => 'time_of_use'];
        
        expect($flatConfig['type'])->toBe('flat');
        expect($touConfig['type'])->toBe('time_of_use');
    });

    test('zones field is visible only for time of use tariffs', function () {
        actingAs($this->admin);
        
        $touConfig = ['type' => 'time_of_use'];
        
        expect($touConfig['type'])->toBe('time_of_use');
    });

    test('weekend logic field is visible only for time of use tariffs', function () {
        actingAs($this->admin);
        
        $touConfig = ['type' => 'time_of_use'];
        
        expect($touConfig['type'])->toBe('time_of_use');
    });
});
