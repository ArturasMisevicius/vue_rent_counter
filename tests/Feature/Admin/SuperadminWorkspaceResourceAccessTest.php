<?php

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('lets superadmin access organization-scoped workspace resources across organizations', function () {
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $providerA = Provider::factory()->forOrganization($organizationA)->create(['name' => 'Provider A']);
    $providerB = Provider::factory()->forOrganization($organizationB)->create(['name' => 'Provider B']);

    Tariff::factory()->for($providerA)->create(['name' => 'Tariff A']);
    Tariff::factory()->for($providerB)->create(['name' => 'Tariff B']);

    $buildingA = Building::factory()->for($organizationA)->create();
    $buildingB = Building::factory()->for($organizationB)->create();

    $propertyA = Property::factory()->for($organizationA)->for($buildingA)->create();
    $propertyB = Property::factory()->for($organizationB)->for($buildingB)->create();

    $utilityServiceA = UtilityService::factory()->create(['organization_id' => $organizationA->id]);
    $utilityServiceB = UtilityService::factory()->create(['organization_id' => $organizationB->id]);

    $meterA = Meter::factory()->for($organizationA)->for($propertyA)->create();
    $meterB = Meter::factory()->for($organizationB)->for($propertyB)->create();

    MeterReading::factory()->for($meterA)->for($propertyA)->for($organizationA)->create();
    MeterReading::factory()->for($meterB)->for($propertyB)->for($organizationB)->create();

    ServiceConfiguration::factory()->create([
        'organization_id' => $organizationA->id,
        'property_id' => $propertyA->id,
        'utility_service_id' => $utilityServiceA->id,
        'provider_id' => $providerA->id,
        'tariff_id' => Tariff::query()->where('provider_id', $providerA->id)->value('id'),
    ]);

    ServiceConfiguration::factory()->create([
        'organization_id' => $organizationB->id,
        'property_id' => $propertyB->id,
        'utility_service_id' => $utilityServiceB->id,
        'provider_id' => $providerB->id,
        'tariff_id' => Tariff::query()->where('provider_id', $providerB->id)->value('id'),
    ]);

    $tenantA = User::factory()->tenant()->create(['organization_id' => $organizationA->id]);
    $tenantB = User::factory()->tenant()->create(['organization_id' => $organizationB->id]);

    Invoice::factory()->for($organizationA)->for($propertyA)->for($tenantA, 'tenant')->create();
    Invoice::factory()->for($organizationB)->for($propertyB)->for($tenantB, 'tenant')->create();

    $superadmin = User::factory()->superadmin()->create();
    actingAs($superadmin);

    get(route('filament.admin.resources.providers.index'))
        ->assertSuccessful()
        ->assertSeeText('Provider A')
        ->assertSeeText('Provider B');

    get(route('filament.admin.resources.tariffs.index'))
        ->assertSuccessful()
        ->assertSeeText('Tariff A')
        ->assertSeeText('Tariff B');

    get(route('filament.admin.resources.invoices.index'))->assertSuccessful();
    get(route('filament.admin.resources.meters.index'))->assertSuccessful();
    get(route('filament.admin.resources.meter-readings.index'))->assertSuccessful();
    get(route('filament.admin.resources.service-configurations.index'))->assertSuccessful();
    get(route('filament.admin.resources.utility-services.index'))->assertSuccessful();
    get(route('filament.admin.resources.providers.create'))->assertSuccessful();
    get(route('filament.admin.resources.tariffs.create'))->assertSuccessful();
});
