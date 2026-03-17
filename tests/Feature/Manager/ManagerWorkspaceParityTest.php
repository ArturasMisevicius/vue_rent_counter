<?php

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\Provider;
use App\Models\Subscription;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets managers access the same organization workspace routes as admins', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $meter = Meter::factory()->for($organization)->for($property)->create();
    $reading = MeterReading::factory()->for($organization)->for($property)->for($meter)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();
    $provider = Provider::factory()->forOrganization($organization)->create();
    $tariff = Tariff::factory()->for($provider)->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 10,
    ]);

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertSuccessful()
        ->assertSee('data-shell-group="organization"', false)
        ->assertSee('data-shell-group="account"', false)
        ->assertSeeText('Profile')
        ->assertSeeText('Settings')
        ->assertDontSee('data-shell-group="platform"', false);

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.profile'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.settings'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.reports'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.buildings.index'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.buildings.create'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.buildings.view', $building))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.buildings.edit', $building))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.properties.index'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.properties.create'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.properties.view', $property))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.properties.edit', $property))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.tenants.index'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.tenants.create'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.tenants.view', $tenant))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.tenants.edit', $tenant))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.meters.index'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.meters.create'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.meters.view', $meter))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.meters.edit', $meter))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.meter-readings.index'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.meter-readings.create'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.meter-readings.view', $reading))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.meter-readings.edit', $reading))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.invoices.index'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.invoices.view', $invoice))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.providers.index'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.providers.create'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.providers.view', $provider))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.providers.edit', $provider))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.tariffs.index'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.tariffs.create'))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.tariffs.view', $tariff))
        ->assertSuccessful();

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.tariffs.edit', $tariff))
        ->assertSuccessful();
});
