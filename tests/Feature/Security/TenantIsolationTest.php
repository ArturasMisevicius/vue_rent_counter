<?php

use App\Filament\Resources\Buildings\Pages\CreateBuilding;
use App\Models\Building;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

expect()->extend('toBeForbiddenOrRedirectToPanelHomepage', function () {
    $value = $this->value;
    $isForbidden = $value->status() === 403;
    $isRedirectToPanelHomepage = $value->isRedirect()
        && str_contains((string) $value->headers->get('Location'), route('filament.admin.pages.dashboard'));

    expect($isForbidden || $isRedirectToPanelHomepage)->toBeTrue();
});

it('keeps the buildings index scoped to the authenticated admin organization', function () {
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $buildingA = Building::factory()->for($organizationA)->create([
        'name' => 'North Hall',
    ]);

    $buildingB = Building::factory()->for($organizationB)->create([
        'name' => 'South Hall',
    ]);

    $adminA = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);

    $this->actingAs($adminA)
        ->get(route('filament.admin.resources.buildings.index'))
        ->assertSuccessful()
        ->assertSeeText($buildingA->name)
        ->assertDontSeeText($buildingB->name);
});

it('prevents an admin from viewing another organization building record', function () {
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $foreignBuilding = Building::factory()->for($organizationB)->create();

    $adminA = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);

    $response = $this->actingAs($adminA)
        ->get(route('filament.admin.resources.buildings.view', $foreignBuilding));

    expect($response)->toBeForbiddenOrRedirectToPanelHomepage();
});

it('prevents an admin from viewing another organization tenant record', function () {
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $foreignTenant = User::factory()->tenant()->create([
        'organization_id' => $organizationB->id,
    ]);

    $adminA = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);

    $response = $this->actingAs($adminA)
        ->get(route('filament.admin.resources.tenants.view', $foreignTenant));

    expect($response)->toBeForbiddenOrRedirectToPanelHomepage();
});

it('ignores injected organization ids when an admin creates a building through the panel', function () {
    $organizationA = Organization::factory()->create();
    $organizationB = Organization::factory()->create();

    $adminA = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);

    $this->actingAs($adminA);

    Livewire::test(CreateBuilding::class)
        ->set('data.organization_id', $organizationB->id)
        ->set('data.name', 'Injected Building')
        ->set('data.address_line_1', 'Main Street 1')
        ->set('data.address_line_2', 'Suite 4')
        ->set('data.city', 'Vilnius')
        ->set('data.postal_code', '01100')
        ->set('data.country_code', 'lt')
        ->call('create')
        ->assertHasNoErrors()
        ->assertRedirect(route('filament.admin.resources.buildings.index'));

    expect(Building::query()
        ->where('name', 'Injected Building')
        ->where('organization_id', $organizationB->id)
        ->exists())->toBeFalse();

    expect(Building::query()
        ->where('name', 'Injected Building')
        ->where('organization_id', $organizationA->id)
        ->exists())->toBeTrue();
});
