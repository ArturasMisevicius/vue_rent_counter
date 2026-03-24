<?php

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Enums\TariffType;
use App\Filament\Actions\Admin\Tariffs\CreateTariffAction;
use App\Filament\Actions\Admin\Tariffs\DeleteTariffAction;
use App\Filament\Actions\Admin\Tariffs\UpdateTariffAction;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('shows organization-scoped tariff resource pages to admin and manager users', function () {
    $organization = Organization::factory()->create();
    $provider = Provider::factory()
        ->forOrganization($organization)
        ->create([
            'name' => 'Ignitis',
            'service_type' => ServiceType::ELECTRICITY,
        ]);

    $tariff = Tariff::factory()
        ->for($provider)
        ->create([
            'name' => 'Standard Tariff',
            'configuration' => [
                'type' => TariffType::FLAT->value,
                'currency' => 'EUR',
                'rate' => 0.185,
            ],
        ]);

    $otherOrganization = Organization::factory()->create();
    $otherProvider = Provider::factory()->forOrganization($otherOrganization)->create();
    $otherTariff = Tariff::factory()->for($otherProvider)->create([
        'name' => 'Hidden Tariff',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $superadmin = User::factory()->superadmin()->create();

    actingAs($admin);

    get(route('filament.admin.resources.tariffs.index'))
        ->assertSuccessful()
        ->assertSeeText('Tariffs')
        ->assertSeeText($tariff->name)
        ->assertSeeText($provider->name)
        ->assertDontSeeText($otherTariff->name);

    actingAs($admin);

    get(route('filament.admin.resources.tariffs.create'))
        ->assertSuccessful()
        ->assertSeeText('Provider')
        ->assertSeeText('Tariff Type')
        ->assertSeeText('Rate');

    actingAs($admin);

    get(route('filament.admin.resources.tariffs.view', $tariff))
        ->assertSuccessful()
        ->assertSeeText('Tariff Details')
        ->assertSeeText($tariff->name)
        ->assertSeeText($provider->name)
        ->assertSeeText('Flat');

    actingAs($admin);

    get(route('filament.admin.resources.tariffs.edit', $tariff))
        ->assertSuccessful()
        ->assertSeeText('Save changes')
        ->assertSeeText($tariff->name);

    actingAs($manager);

    get(route('filament.admin.resources.tariffs.index'))
        ->assertSuccessful()
        ->assertSeeText($tariff->name);

    actingAs($admin);

    get(route('filament.admin.resources.tariffs.view', $otherTariff))
        ->assertNotFound();

    actingAs($admin);

    get(route('filament.admin.resources.tariffs.edit', $otherTariff))
        ->assertNotFound();

    actingAs($superadmin);

    get(route('filament.admin.resources.tariffs.index'))
        ->assertSuccessful()
        ->assertSeeText('Tariffs')
        ->assertSeeText($tariff->name)
        ->assertSeeText($otherTariff->name);
});

it('creates, updates, and blocks deletion of tariffs with related service configurations', function () {
    $organization = Organization::factory()->create();
    $provider = Provider::factory()
        ->forOrganization($organization)
        ->create([
            'service_type' => ServiceType::WATER,
        ]);

    $created = app(CreateTariffAction::class)->handle($organization, [
        'provider_id' => $provider->id,
        'remote_id' => 'REMOTE-1001',
        'name' => 'Water Base Tariff',
        'configuration' => [
            'type' => TariffType::FLAT,
            'currency' => 'EUR',
            'rate' => 0.1234,
        ],
        'active_from' => now()->subMonth()->toDateTimeString(),
        'active_until' => null,
    ]);

    expect($created)
        ->provider_id->toBe($provider->id)
        ->remote_id->toBe('REMOTE-1001')
        ->and($created->configuration['rate'])->toBe(0.1234);

    $updated = app(UpdateTariffAction::class)->handle($created, [
        'provider_id' => $provider->id,
        'remote_id' => 'REMOTE-2002',
        'name' => 'Water Peak Tariff',
        'configuration' => [
            'type' => TariffType::FLAT,
            'currency' => 'EUR',
            'rate' => 0.2234,
        ],
        'active_from' => now()->subWeeks(2)->toDateTimeString(),
        'active_until' => now()->addMonth()->toDateTimeString(),
    ]);

    expect($updated)
        ->name->toBe('Water Peak Tariff')
        ->remote_id->toBe('REMOTE-2002')
        ->and($updated->configuration['rate'])->toBe(0.2234);

    $property = Property::factory()
        ->for($organization)
        ->for(Building::factory()->for($organization))
        ->create();

    $utilityService = UtilityService::factory()->create([
        'organization_id' => $organization->id,
        'service_type_bridge' => ServiceType::WATER,
    ]);

    ServiceConfiguration::factory()->create([
        'organization_id' => $organization->id,
        'property_id' => $property->id,
        'utility_service_id' => $utilityService->id,
        'pricing_model' => PricingModel::FLAT,
        'distribution_method' => DistributionMethod::EQUAL,
        'provider_id' => $provider->id,
        'tariff_id' => $updated->id,
    ]);

    expect(fn () => app(DeleteTariffAction::class)->handle($updated))
        ->toThrow(ValidationException::class);

    expect(Tariff::query()->whereKey($updated->id)->exists())->toBeTrue();

    $deletableTariff = Tariff::factory()->for($provider)->create();

    app(DeleteTariffAction::class)->handle($deletableTariff);

    expect(Tariff::query()->whereKey($deletableTariff->id)->exists())->toBeFalse();
});
