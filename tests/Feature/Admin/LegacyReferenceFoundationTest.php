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

    $this->seed(DatabaseSeeder::class);

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
        ->and($invoice->fresh()->currencyDefinition?->is($currency))->toBeTrue();
});
