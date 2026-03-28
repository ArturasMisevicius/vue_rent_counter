<?php

use App\Enums\PropertyType;
use App\Filament\Actions\Admin\Buildings\CreateBuildingAction;
use App\Filament\Actions\Admin\Buildings\DeleteBuildingAction;
use App\Filament\Actions\Admin\Buildings\UpdateBuildingAction;
use App\Filament\Resources\Buildings\BuildingResource;
use App\Filament\Resources\Buildings\Pages\ListBuildings;
use App\Filament\Resources\Buildings\Pages\ViewBuilding;
use App\Filament\Resources\Buildings\RelationManagers\MetersRelationManager;
use App\Filament\Resources\Buildings\RelationManagers\PropertiesRelationManager;
use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the buildings list create edit and view pages for the admin contract', function () {
    $organization = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
        'address_line_1' => 'Main Street 1, Vilnius',
        'city' => null,
        'postal_code' => null,
        'country_code' => null,
    ]);
    $property = Property::factory()->for($organization)->for($building)->create();
    $meter = Meter::factory()->for($organization)->for($property)->create([
        'identifier' => 'MTR-1001',
    ]);
    MeterReading::factory()->for($organization)->for($property)->for($meter)->create();

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create([
        'name' => 'South Hall',
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
        ->assertSeeText('New Building')
        ->assertSeeText('North Hall')
        ->assertSeeText('Main Street 1, Vilnius')
        ->assertDontSeeText('South Hall');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.buildings.create'))
        ->assertSuccessful()
        ->assertSeeText('New Building')
        ->assertSeeText('Building Information')
        ->assertSeeText('Building Name')
        ->assertSeeText('Full Address')
        ->assertSeeText('Save Building');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.buildings.view', $building))
        ->assertSuccessful()
        ->assertSeeText('North Hall')
        ->assertSeeText('Main Street 1, Vilnius')
        ->assertSeeText('Edit')
        ->assertSeeText('Delete')
        ->assertSeeText('Properties')
        ->assertSeeText('Meters');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.buildings.edit', $building))
        ->assertSuccessful()
        ->assertSeeText('Edit Building: North Hall')
        ->assertSeeText('Save Changes');

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.buildings.index'))
        ->assertSuccessful()
        ->assertSeeText('North Hall');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.buildings.view', $otherBuilding))
        ->assertForbidden();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.buildings.edit', $otherBuilding))
        ->assertForbidden();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.buildings.index'))
        ->assertSuccessful()
        ->assertSeeText('North Hall')
        ->assertSeeText('South Hall')
        ->assertSeeText($organization->name)
        ->assertSeeText($otherOrganization->name);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.buildings.create'))
        ->assertSuccessful()
        ->assertSeeText('Organization');
});

it('exposes the buildings list table and relation manager contracts', function () {
    $organization = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
        'address_line_1' => 'Main Street 1',
        'city' => 'Vilnius',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Apartment 4B',
        'floor' => 0,
        'type' => PropertyType::APARTMENT,
        'floor_area_sqm' => 54.75,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Taylor Tenant',
    ]);
    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    $meter = Meter::factory()->for($organization)->for($property)->create([
        'identifier' => 'MTR-1001',
    ]);
    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 148.25,
    ]);

    $otherOrganization = Organization::factory()->create([
        'name' => 'Aurora Towers',
    ]);
    $otherBuilding = Building::factory()->for($otherOrganization)->create([
        'name' => 'Aurora Block',
    ]);

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    Livewire::test(ListBuildings::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableColumnExists('name', fn (TextColumn $column): bool => $column->getLabel() === 'Building Name')
        ->assertTableColumnExists('address', fn (TextColumn $column): bool => $column->getLabel() === 'Address')
        ->assertTableColumnExists('properties_count', fn (TextColumn $column): bool => $column->getLabel() === 'Properties')
        ->assertTableColumnExists('meters_count', fn (TextColumn $column): bool => $column->getLabel() === 'Meters')
        ->assertTableColumnSummarizerExists('properties_count', 'totalProperties')
        ->assertTableColumnSummarizerExists('meters_count', 'totalMeters')
        ->assertTableColumnSummarySet('properties_count', 'totalProperties', 1)
        ->assertTableColumnSummarySet('meters_count', 'totalMeters', 1)
        ->assertTableColumnExists('created_at', fn (TextColumn $column): bool => $column->getLabel() === 'Date Created')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertTableFilterExists('created_between')
        ->assertCanSeeTableRecords([$building, $otherBuilding])
        ->filterTable('organization', (string) $organization->getKey())
        ->assertCanSeeTableRecords([$building])
        ->assertCanNotSeeTableRecords([$otherBuilding]);

    Livewire::test(ViewBuilding::class, ['record' => $building->getRouteKey()])
        ->assertActionExists('edit')
        ->assertActionExists('delete');

    Livewire::test(PropertiesRelationManager::class, [
        'ownerRecord' => $building,
        'pageClass' => ViewBuilding::class,
    ])
        ->assertTableColumnExists('name', fn (TextColumn $column): bool => $column->getLabel() === 'Property Name')
        ->assertTableColumnExists('type', fn (TextColumn $column): bool => $column->getLabel() === 'Type')
        ->assertTableColumnExists('floor', fn (TextColumn $column): bool => $column->getLabel() === 'Floor')
        ->assertTableColumnExists('floor_area_sqm', fn (TextColumn $column): bool => $column->getLabel() === 'Area')
        ->assertTableColumnExists('currentAssignment.tenant.name', fn (TextColumn $column): bool => $column->getLabel() === 'Tenant')
        ->assertTableColumnExists('occupancy_status', fn (TextColumn $column): bool => $column->getLabel() === 'Status')
        ->assertTableActionExists('view', record: $property)
        ->assertTableActionExists('edit', record: $property);

    Livewire::test(MetersRelationManager::class, [
        'ownerRecord' => $building,
        'pageClass' => ViewBuilding::class,
    ])
        ->assertTableColumnExists('identifier', fn (TextColumn $column): bool => $column->getLabel() === 'Serial Number')
        ->assertTableColumnExists('type', fn (TextColumn $column): bool => $column->getLabel() === 'Meter Type')
        ->assertTableColumnExists('property.name', fn (TextColumn $column): bool => $column->getLabel() === 'Property')
        ->assertTableColumnExists('latestReading.reading_date', fn (TextColumn $column): bool => $column->getLabel() === 'Last Reading Date')
        ->assertTableColumnExists('latestReading.reading_value', fn (TextColumn $column): bool => $column->getLabel() === 'Last Value')
        ->assertTableColumnExists('status', fn (TextColumn $column): bool => $column->getLabel() === 'Status')
        ->assertTableActionExists('view', record: $meter);
});

it('creates updates and blocks deletion of buildings when properties exist', function () {
    $organization = Organization::factory()->create();

    $created = app(CreateBuildingAction::class)->handle($organization, [
        'name' => 'South Hall',
        'address_line_1' => 'Main Street 1',
        'address_line_2' => null,
        'city' => null,
        'postal_code' => null,
        'country_code' => null,
    ]);

    expect($created)
        ->organization_id->toBe($organization->id)
        ->name->toBe('South Hall')
        ->address_line_1->toBe('Main Street 1');

    $updated = app(UpdateBuildingAction::class)->handle($created, [
        'name' => 'South Hall Annex',
        'address_line_1' => 'Main Street 2',
        'address_line_2' => null,
        'city' => null,
        'postal_code' => null,
        'country_code' => null,
    ]);

    expect($updated)
        ->name->toBe('South Hall Annex')
        ->address_line_1->toBe('Main Street 2');

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

it('keeps building relation tab badges deferred with relation counts', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $meter = Meter::factory()->for($organization)->for($property)->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin);

    $component = Livewire::test(ViewBuilding::class, ['record' => $building->getRouteKey()]);
    $page = $component->invade();
    $record = $page->getRecord();
    $tabs = collect($page->getDeferredRelationManagerTabs(
        BuildingResource::getRelations(),
        $page->hasCombinedRelationManagerTabsWithContent(),
        ['ownerRecord' => $record, 'pageClass' => ViewBuilding::class],
        $record,
    ));

    $tabs->except([''])->each(function (Tab $tab): void {
        expect($tab->isBadgeDeferred())->toBeTrue();
    });

    expect(PropertiesRelationManager::getBadge($record, ViewBuilding::class))->toBe('1')
        ->and(MetersRelationManager::getBadge($record, ViewBuilding::class))->toBe('1');
});
