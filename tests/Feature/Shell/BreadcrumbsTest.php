<?php

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders breadcrumbs on tenant non-dashboard pages and keeps dashboards breadcrumb-free', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Apartment 12',
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    Meter::factory()->for($organization)->for($property)->create();
    Invoice::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create();

    $this->actingAs($tenant)
        ->get(route('tenant.property.show'))
        ->assertSuccessful()
        ->assertSee('data-breadcrumbs="true"', false)
        ->assertSee('data-breadcrumb-link="'.route('tenant.home').'"', false)
        ->assertDontSee('data-breadcrumb-link="'.route('tenant.property.show').'"', false);

    $this->actingAs($tenant)
        ->get(route('tenant.invoices.index'))
        ->assertSuccessful()
        ->assertSee('data-breadcrumbs="true"', false)
        ->assertSee('data-breadcrumb-link="'.route('tenant.home').'"', false)
        ->assertDontSee('data-breadcrumb-link="'.route('tenant.invoices.index').'"', false);

    $this->actingAs($tenant)
        ->get(route('tenant.home'))
        ->assertSuccessful()
        ->assertDontSee('data-breadcrumbs="true"', false);
});

it('renders explicit breadcrumbs on admin resource view pages with the current item as plain text', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $meter = Meter::factory()->for($organization)->for($property)->create([
        'name' => 'Main Water Meter',
    ]);
    $invoice = Invoice::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create([
        'invoice_number' => 'INV-BREADCRUMB-001',
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.buildings.view', $building))
        ->assertSuccessful()
        ->assertSee('href="'.route('filament.admin.resources.buildings.index').'"', false)
        ->assertSeeText($building->name)
        ->assertDontSee('href="'.route('filament.admin.resources.buildings.view', $building).'"', false);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.invoices.view', $invoice))
        ->assertSuccessful()
        ->assertSee('href="'.route('filament.admin.resources.invoices.index').'"', false)
        ->assertSeeText($invoice->invoice_number)
        ->assertDontSee('href="'.route('filament.admin.resources.invoices.view', $invoice).'"', false);
});
