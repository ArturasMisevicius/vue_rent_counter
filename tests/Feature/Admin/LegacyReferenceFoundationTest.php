<?php

use App\Models\Building;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\Faq;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\Translation;
use App\Models\UtilityService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the legacy reference foundation tables and models', function () {
    expect(Schema::hasTable('currencies'))->toBeTrue()
        ->and(Schema::hasTable('exchange_rates'))->toBeTrue()
        ->and(Schema::hasTable('faqs'))->toBeTrue()
        ->and(Schema::hasTable('translations'))->toBeTrue()
        ->and(Schema::hasTable('providers'))->toBeTrue()
        ->and(Schema::hasTable('tariffs'))->toBeTrue()
        ->and(Schema::hasTable('utility_services'))->toBeTrue()
        ->and(Schema::hasTable('service_configurations'))->toBeTrue()
        ->and(class_exists(Currency::class))->toBeTrue()
        ->and(class_exists(ExchangeRate::class))->toBeTrue()
        ->and(class_exists(Faq::class))->toBeTrue()
        ->and(class_exists(Translation::class))->toBeTrue()
        ->and(class_exists(Provider::class))->toBeTrue()
        ->and(class_exists(Tariff::class))->toBeTrue()
        ->and(class_exists(UtilityService::class))->toBeTrue()
        ->and(class_exists(ServiceConfiguration::class))->toBeTrue();
});

it('seeds the legacy reference foundation and links it through current models', function () {
    $organization = Organization::factory()->create();
    $property = Property::factory()
        ->for($organization)
        ->for(Building::factory()->for($organization))
        ->create();

    Artisan::call('db:seed', [
        '--class' => DatabaseSeeder::class,
        '--no-interaction' => true,
    ]);

    $provider = Provider::factory()
        ->for($organization)
        ->create();

    $utilityService = UtilityService::factory()
        ->for($organization)
        ->create();

    $tariff = Tariff::factory()
        ->for($provider)
        ->create();

    $serviceConfiguration = ServiceConfiguration::factory()
        ->for($organization)
        ->for($property)
        ->for($utilityService)
        ->for($provider)
        ->for($tariff)
        ->create();

    $currency = Currency::query()->updateOrCreate(
        ['code' => 'EUR'],
        [
            'name' => 'Euro',
            'symbol' => 'EUR',
            'decimal_places' => 2,
            'is_active' => true,
            'is_default' => true,
        ],
    );

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->create([
            'currency' => $currency->code,
        ]);

    $freshOrganization = $organization->fresh()->load([
        'providers',
        'utilityServices',
        'serviceConfigurations',
    ]);

    $seededProviders = Provider::query()
        ->where('organization_id', $organization->id)
        ->whereIn('name', ['Ignitis', 'Vilniaus Vandenys', 'Vilniaus Energija'])
        ->with('tariffs:id,provider_id,remote_id,active_until')
        ->get();

    $seededServiceConfigurations = ServiceConfiguration::query()
        ->where('organization_id', $organization->id)
        ->with(['provider:id,organization_id', 'tariff:id,provider_id'])
        ->get();

    expect(Currency::query()->count())->toBeGreaterThan(0)
        ->and(ExchangeRate::query()->count())->toBeGreaterThan(0)
        ->and(Faq::query()->count())->toBeGreaterThan(0)
        ->and(Translation::query()->count())->toBeGreaterThan(0)
        ->and(Tariff::query()->count())->toBeGreaterThan(0)
        ->and($freshOrganization->providers->contains($provider))->toBeTrue()
        ->and($freshOrganization->utilityServices->contains($utilityService))->toBeTrue()
        ->and($freshOrganization->serviceConfigurations->contains($serviceConfiguration))->toBeTrue()
        ->and($serviceConfiguration->fresh()->provider->is($provider))->toBeTrue()
        ->and($serviceConfiguration->utilityService->is($utilityService))->toBeTrue()
        ->and($seededProviders)->toHaveCount(3)
        ->and($seededProviders->every(fn (Provider $seededProvider): bool => $seededProvider->organization_id === $organization->id))->toBeTrue()
        ->and($seededProviders->flatMap->tariffs)->toHaveCount(3)
        ->and($seededProviders->flatMap->tariffs->every(fn (Tariff $seededTariff): bool => filled($seededTariff->remote_id)))->toBeTrue()
        ->and($seededServiceConfigurations->every(fn (ServiceConfiguration $seededConfiguration): bool => $seededConfiguration->provider_id !== null && $seededConfiguration->tariff_id !== null))->toBeTrue()
        ->and($seededServiceConfigurations->contains(fn (ServiceConfiguration $seededConfiguration): bool => $seededConfiguration->configuration_overrides !== null))->toBeTrue()
        ->and($seededServiceConfigurations->contains(fn (ServiceConfiguration $seededConfiguration): bool => $seededConfiguration->effective_until !== null))->toBeTrue()
        ->and($seededServiceConfigurations->contains(fn (ServiceConfiguration $seededConfiguration): bool => filled($seededConfiguration->area_type)))->toBeTrue()
        ->and($seededServiceConfigurations->contains(fn (ServiceConfiguration $seededConfiguration): bool => filled($seededConfiguration->custom_formula)))->toBeTrue()
        ->and($invoice->fresh()->currencyDefinition?->is($currency))->toBeTrue();
});

it('creates a fully linked provider graph from default factories', function () {
    $serviceConfiguration = ServiceConfiguration::factory()->create();

    $serviceConfiguration->load([
        'organization:id',
        'property:id,organization_id',
        'utilityService:id,organization_id,service_type_bridge',
        'provider:id,organization_id,service_type',
        'tariff:id,provider_id,remote_id,active_until',
    ]);

    expect($serviceConfiguration->provider_id)->not->toBeNull()
        ->and($serviceConfiguration->tariff_id)->not->toBeNull()
        ->and($serviceConfiguration->provider?->organization_id)->toBe($serviceConfiguration->organization_id)
        ->and($serviceConfiguration->property?->organization_id)->toBe($serviceConfiguration->organization_id)
        ->and($serviceConfiguration->utilityService?->organization_id)->toBe($serviceConfiguration->organization_id)
        ->and($serviceConfiguration->tariff?->provider_id)->toBe($serviceConfiguration->provider_id)
        ->and($serviceConfiguration->provider?->service_type)->toBe($serviceConfiguration->utilityService?->service_type_bridge)
        ->and($serviceConfiguration->configuration_overrides)->not->toBeNull()
        ->and($serviceConfiguration->effective_until)->not->toBeNull()
        ->and($serviceConfiguration->area_type)->not->toBeNull()
        ->and($serviceConfiguration->custom_formula)->not->toBeNull()
        ->and($serviceConfiguration->tariff?->remote_id)->not->toBeNull();
});
