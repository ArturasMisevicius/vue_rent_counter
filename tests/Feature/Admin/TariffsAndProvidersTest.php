<?php

use App\Enums\DistributionMethod;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Actions\Admin\Providers\DeleteProviderAction;
use App\Filament\Actions\Admin\ServiceConfigurations\CreateServiceConfigurationAction;
use App\Filament\Actions\Admin\ServiceConfigurations\UpdateServiceConfigurationAction;
use App\Filament\Actions\Admin\Tariffs\DeleteTariffAction;
use App\Filament\Actions\Admin\UtilityServices\CreateUtilityServiceAction;
use App\Filament\Actions\Admin\UtilityServices\UpdateUtilityServiceAction;
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

uses(RefreshDatabase::class);

it('shows organization-scoped billing configuration resources', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
    ]);

    $provider = Provider::factory()->for($organization)->create([
        'name' => 'Ignitis',
        'service_type' => ServiceType::ELECTRICITY,
    ]);

    $tariff = Tariff::factory()->for($provider)->create([
        'name' => 'Day Rate',
    ]);

    $utilityService = UtilityService::factory()->for($organization)->create([
        'name' => 'Electricity',
    ]);

    ServiceConfiguration::factory()->for($organization)->for($property)->for($utilityService)->for($provider)->for($tariff)->create();

    $otherOrganization = Organization::factory()->create();
    $otherProvider = Provider::factory()->for($otherOrganization)->create([
        'name' => 'Other Provider',
    ]);

    $otherTariff = Tariff::factory()->for($otherProvider)->create([
        'name' => 'Foreign Rate',
    ]);

    $otherUtilityService = UtilityService::factory()->for($otherOrganization)->create([
        'name' => 'Foreign Service',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.providers.index'))
        ->assertSuccessful()
        ->assertSeeText('Providers')
        ->assertSeeText($provider->name)
        ->assertDontSeeText($otherProvider->name);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.tariffs.index'))
        ->assertSuccessful()
        ->assertSeeText('Tariffs')
        ->assertSeeText($tariff->name)
        ->assertDontSeeText($otherTariff->name);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.service-configurations.create'))
        ->assertSuccessful()
        ->assertSeeText('Property')
        ->assertSeeText('Utility Service')
        ->assertSeeText('Pricing Model');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.utility-services.create'))
        ->assertSuccessful()
        ->assertSeeText('Name')
        ->assertSeeText('Unit of Measurement')
        ->assertSeeText('Default Pricing Model')
        ->assertDontSeeText($otherUtilityService->name);

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.providers.index'))
        ->assertSuccessful()
        ->assertSeeText($provider->name);
});

it('creates and updates utility services and service configurations while blocking deletion of in-use providers and tariffs', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();

    $provider = Provider::factory()->for($organization)->create([
        'service_type' => ServiceType::WATER,
    ]);

    $tariff = Tariff::factory()->for($provider)->create([
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 1.25,
        ],
    ]);

    $utilityService = app(CreateUtilityServiceAction::class)->handle($organization, [
        'name' => 'Cold Water',
        'unit_of_measurement' => 'm3',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'service_type_bridge' => ServiceType::WATER,
        'description' => 'Cold water usage',
        'is_active' => true,
    ]);

    expect($utilityService)
        ->organization_id->toBe($organization->id)
        ->name->toBe('Cold Water');

    $updatedUtilityService = app(UpdateUtilityServiceAction::class)->handle($utilityService, [
        'name' => 'Cold Water Shared',
        'unit_of_measurement' => 'm3',
        'default_pricing_model' => PricingModel::HYBRID,
        'service_type_bridge' => ServiceType::WATER,
        'description' => 'Shared water billing',
        'is_active' => true,
    ]);

    expect($updatedUtilityService)
        ->name->toBe('Cold Water Shared')
        ->default_pricing_model->toBe(PricingModel::HYBRID);

    $serviceConfiguration = app(CreateServiceConfigurationAction::class)->handle($organization, [
        'property_id' => $property->id,
        'utility_service_id' => $updatedUtilityService->id,
        'pricing_model' => PricingModel::CONSUMPTION_BASED,
        'rate_schedule' => ['unit_rate' => 1.25],
        'distribution_method' => DistributionMethod::BY_CONSUMPTION,
        'is_shared_service' => false,
        'effective_from' => now()->startOfMonth(),
        'effective_until' => null,
        'configuration_overrides' => null,
        'tariff_id' => $tariff->id,
        'provider_id' => $provider->id,
        'area_type' => null,
        'custom_formula' => null,
        'is_active' => true,
    ]);

    expect($serviceConfiguration)
        ->organization_id->toBe($organization->id)
        ->provider_id->toBe($provider->id)
        ->tariff_id->toBe($tariff->id);

    $updatedConfiguration = app(UpdateServiceConfigurationAction::class)->handle($serviceConfiguration, [
        'property_id' => $property->id,
        'utility_service_id' => $updatedUtilityService->id,
        'pricing_model' => PricingModel::HYBRID,
        'rate_schedule' => ['unit_rate' => 1.40, 'base_fee' => 7.50],
        'distribution_method' => DistributionMethod::AREA,
        'is_shared_service' => true,
        'effective_from' => now()->startOfMonth(),
        'effective_until' => now()->addMonths(6),
        'configuration_overrides' => ['base_fee' => 7.50],
        'tariff_id' => $tariff->id,
        'provider_id' => $provider->id,
        'area_type' => 'heated',
        'custom_formula' => null,
        'is_active' => true,
    ]);

    expect($updatedConfiguration)
        ->pricing_model->toBe(PricingModel::HYBRID)
        ->distribution_method->toBe(DistributionMethod::AREA)
        ->is_shared_service->toBeTrue();

    expect(fn () => app(DeleteProviderAction::class)->handle($provider))
        ->toThrow(ValidationException::class);

    expect(fn () => app(DeleteTariffAction::class)->handle($tariff))
        ->toThrow(ValidationException::class);

    $freeProvider = Provider::factory()->for($organization)->create();
    $freeTariff = Tariff::factory()->for($freeProvider)->create();

    app(DeleteTariffAction::class)->handle($freeTariff);
    app(DeleteProviderAction::class)->handle($freeProvider);

    expect(Tariff::query()->whereKey($freeTariff->id)->exists())->toBeFalse()
        ->and(Provider::query()->whereKey($freeProvider->id)->exists())->toBeFalse();
});
