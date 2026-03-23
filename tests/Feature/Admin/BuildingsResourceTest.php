<?php

use App\Filament\Actions\Admin\Buildings\CreateBuildingAction;
use App\Filament\Actions\Admin\Buildings\DeleteBuildingAction;
use App\Filament\Actions\Admin\Buildings\UpdateBuildingAction;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('shows buildings resource pages to admin and manager users and grants superadmin control-plane access', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
        'city' => 'Vilnius',
    ]);

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create([
        'name' => 'Other Hall',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.buildings.index'))
        ->assertSuccessful()
        ->assertSeeText('Buildings')
        ->assertSeeText($building->name)
        ->assertSeeText($building->city)
        ->assertDontSeeText($otherBuilding->name);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.buildings.create'))
        ->assertSuccessful()
        ->assertSeeText('Name')
        ->assertSeeText('Address Line 1')
        ->assertSeeText('City');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.buildings.view', $building))
        ->assertSuccessful()
        ->assertSeeText('Building Details')
        ->assertSeeText('Overview')
        ->assertSeeText('Properties')
        ->assertSeeText($building->address_line_1);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.buildings.edit', $building))
        ->assertSuccessful()
        ->assertSeeText('Save changes')
        ->assertSeeText($building->name);

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.buildings.index'))
        ->assertSuccessful()
        ->assertSeeText($building->name);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.buildings.view', $otherBuilding))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.buildings.edit', $otherBuilding))
        ->assertForbidden();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.buildings.index'))
        ->assertSuccessful()
        ->assertSeeText($building->name)
        ->assertSeeText($otherBuilding->name);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.buildings.view', $otherBuilding))
        ->assertSuccessful();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.buildings.create'))
        ->assertSuccessful()
        ->assertSeeText('Organization');
});

it('creates, updates, and blocks deletion of buildings when properties exist', function () {
    $organization = Organization::factory()->create();

    $created = app(CreateBuildingAction::class)->handle($organization, [
        'name' => 'South Hall',
        'address_line_1' => 'Main Street 1',
        'address_line_2' => 'Block B',
        'city' => 'Kaunas',
        'postal_code' => '44200',
        'country_code' => 'LT',
    ]);

    expect($created)
        ->organization_id->toBe($organization->id)
        ->name->toBe('South Hall');

    $updated = app(UpdateBuildingAction::class)->handle($created, [
        'name' => 'South Hall Annex',
        'address_line_1' => 'Main Street 2',
        'address_line_2' => 'Block C',
        'city' => 'Klaipėda',
        'postal_code' => '91234',
        'country_code' => 'LT',
    ]);

    expect($updated)
        ->name->toBe('South Hall Annex')
        ->city->toBe('Klaipėda');

    Property::factory()
        ->for($organization)
        ->for($updated)
        ->create();

    expect(fn () => app(DeleteBuildingAction::class)->handle($updated))
        ->toThrow(ValidationException::class);

    expect(Building::query()->whereKey($updated->id)->exists())->toBeTrue();

    $emptyBuilding = Building::factory()->for($organization)->create();

    app(DeleteBuildingAction::class)->handle($emptyBuilding);

    expect(Building::query()->whereKey($emptyBuilding->id)->exists())->toBeFalse();
});

it('allows superadmin to create and update buildings through admin actions', function () {
    $organization = Organization::factory()->create();
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    $created = app(CreateBuildingAction::class)->handle($organization, [
        'name' => 'Control Plane Tower',
        'address_line_1' => 'Platform Street 1',
        'address_line_2' => null,
        'city' => 'Vilnius',
        'postal_code' => '01100',
        'country_code' => 'lt',
    ]);

    expect($created)
        ->organization_id->toBe($organization->id)
        ->country_code->toBe('LT');

    $updated = app(UpdateBuildingAction::class)->handle($created, [
        'name' => 'Control Plane Tower Annex',
        'address_line_1' => 'Platform Street 2',
        'address_line_2' => 'Block A',
        'city' => 'Kaunas',
        'postal_code' => '44200',
        'country_code' => 'LT',
    ]);

    expect($updated)
        ->name->toBe('Control Plane Tower Annex')
        ->city->toBe('Kaunas');
});
