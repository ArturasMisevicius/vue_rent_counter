<?php

use App\Enums\MeterStatus;
use App\Enums\MeterType;
use App\Enums\UnitOfMeasurement;
use App\Filament\Actions\Admin\Meters\CreateMeterAction;
use App\Filament\Actions\Admin\Meters\DeleteMeterAction;
use App\Filament\Actions\Admin\Meters\ToggleMeterStatusAction;
use App\Filament\Actions\Admin\Meters\UpdateMeterAction;
use App\Filament\Resources\Meters\MeterResource;
use App\Filament\Resources\Meters\Pages\CreateMeter;
use App\Filament\Resources\Meters\Pages\ListMeters;
use App\Filament\Resources\Meters\Pages\ViewMeter;
use App\Filament\Resources\Meters\RelationManagers\ReadingHistoryRelationManager;
use App\Models\Building;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows organization-scoped meter resource pages with history details', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
    ]);

    $meter = Meter::factory()->for($organization)->for($property)->create([
        'name' => 'Main Water Meter',
        'identifier' => 'MTR-1001',
        'type' => MeterType::WATER,
        'unit' => MeterType::WATER->defaultUnit()->value,
    ]);

    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 125.500,
        'reading_date' => now()->subDay()->toDateString(),
    ]);

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create();
    $otherProperty = Property::factory()->for($otherOrganization)->for($otherBuilding)->create();
    $otherMeter = Meter::factory()->for($otherOrganization)->for($otherProperty)->create([
        'name' => 'Hidden Meter',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.meters.index'))
        ->assertSuccessful()
        ->assertSeeText('Meters')
        ->assertSeeText($meter->name)
        ->assertSeeText($meter->identifier)
        ->assertSeeText($property->name)
        ->assertDontSeeText($otherMeter->name);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.meters.create'))
        ->assertSuccessful()
        ->assertSeeText('Property')
        ->assertSeeText('Meter Type')
        ->assertSeeText('Measurement Unit');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.meters.view', $meter))
        ->assertSuccessful()
        ->assertSeeText('Meter Details')
        ->assertSeeText('Reading History');

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.meters.index'))
        ->assertSuccessful()
        ->assertSeeText($meter->name);
});

it('creates meters with default units, updates and toggles status, and blocks deletion when readings exist', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();

    $created = app(CreateMeterAction::class)->handle($organization, [
        'property_id' => $property->id,
        'name' => 'Basement Meter',
        'identifier' => 'MTR-BASEMENT',
        'type' => MeterType::ELECTRICITY,
        'unit' => null,
        'status' => MeterStatus::ACTIVE,
        'installed_at' => now()->subMonth()->toDateString(),
    ]);

    expect($created)
        ->organization_id->toBe($organization->id)
        ->unit->toBe(UnitOfMeasurement::KILOWATT_HOUR->value)
        ->status->toBe(MeterStatus::ACTIVE);

    $updated = app(UpdateMeterAction::class)->handle($created, [
        'property_id' => $property->id,
        'name' => 'Basement Meter Updated',
        'identifier' => 'MTR-UPDATED',
        'type' => MeterType::WATER,
        'unit' => null,
        'status' => MeterStatus::ACTIVE,
        'installed_at' => now()->subWeeks(2)->toDateString(),
    ]);

    expect($updated)
        ->name->toBe('Basement Meter Updated')
        ->identifier->toBe('MTR-UPDATED')
        ->unit->toBe(UnitOfMeasurement::CUBIC_METER->value);

    expect(fn () => app(CreateMeterAction::class)->handle($organization, [
        'property_id' => $property->id,
        'name' => 'Invalid Meter',
        'identifier' => 'MTR-INVALID',
        'type' => MeterType::ELECTRICITY,
        'unit' => 'watts_per_whatever',
        'status' => MeterStatus::ACTIVE,
        'installed_at' => null,
    ]))->toThrow(ValidationException::class);

    $inactive = app(ToggleMeterStatusAction::class)->handle($updated);
    $reactivated = app(ToggleMeterStatusAction::class)->handle($inactive->fresh());

    expect($inactive->status)->toBe(MeterStatus::INACTIVE)
        ->and($reactivated->status)->toBe(MeterStatus::ACTIVE);

    MeterReading::factory()->for($organization)->for($property)->for($reactivated)->create();

    expect(fn () => app(DeleteMeterAction::class)->handle($reactivated))
        ->toThrow(ValidationException::class);

    expect(Meter::query()->whereKey($reactivated->id)->exists())->toBeTrue();

    $deletableMeter = Meter::factory()->for($organization)->for($property)->create();

    app(DeleteMeterAction::class)->handle($deletableMeter);

    expect(Meter::query()->whereKey($deletableMeter->id)->exists())->toBeFalse();
});

it('reactivates actionable meter statuses but leaves retired meters unchanged', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();

    $faultyMeter = Meter::factory()->for($organization)->for($property)->create([
        'status' => MeterStatus::FAULTY,
    ]);

    $retiredMeter = Meter::factory()->for($organization)->for($property)->create([
        'status' => MeterStatus::RETIRED,
    ]);

    $reactivated = app(ToggleMeterStatusAction::class)->handle($faultyMeter);
    $unchanged = app(ToggleMeterStatusAction::class)->handle($retiredMeter);

    expect($reactivated->status)->toBe(MeterStatus::ACTIVE)
        ->and($unchanged->status)->toBe(MeterStatus::RETIRED);
});

it('shows organization context on the meters list for superadmins while keeping admins scoped', function () {
    $organizationA = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);
    $organizationB = Organization::factory()->create([
        'name' => 'Aurora Towers',
    ]);

    $buildingA = Building::factory()->for($organizationA)->create([
        'name' => 'North Hall',
    ]);
    $buildingB = Building::factory()->for($organizationB)->create([
        'name' => 'Aurora Block',
    ]);

    $propertyA = Property::factory()->for($organizationA)->for($buildingA)->create([
        'name' => 'A-12',
    ]);
    $propertyB = Property::factory()->for($organizationB)->for($buildingB)->create([
        'name' => 'B-24',
    ]);

    $meterA = Meter::factory()->for($organizationA)->for($propertyA)->create([
        'name' => 'North Meter',
        'type' => MeterType::WATER,
        'status' => MeterStatus::ACTIVE,
    ]);
    $meterB = Meter::factory()->for($organizationB)->for($propertyB)->create([
        'name' => 'Aurora Meter',
        'type' => MeterType::ELECTRICITY,
        'status' => MeterStatus::INACTIVE,
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organizationA->id,
    ]);
    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.meters.index'))
        ->assertSuccessful()
        ->assertSeeText('Meters')
        ->assertSeeText($meterA->name)
        ->assertDontSeeText($meterB->name);

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.meters.index'))
        ->assertSuccessful()
        ->assertSeeText('Meters')
        ->assertSeeText($meterA->name)
        ->assertSeeText($meterB->name)
        ->assertSeeText($organizationA->name)
        ->assertSeeText($organizationB->name);

    $this->actingAs($superadmin);

    Livewire::test(ListMeters::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertTableColumnStateSet('organization.name', $organizationA->name, $meterA)
        ->assertTableColumnStateSet('organization.name', $organizationB->name, $meterB)
        ->assertTableColumnStateSet('type', MeterType::WATER, $meterA)
        ->assertCanSeeTableRecords([$meterA, $meterB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$meterA])
        ->assertCanNotSeeTableRecords([$meterB]);
});

it('shows create-time organization and building filters for superadmins and narrows property options', function () {
    $organizationA = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);
    $organizationB = Organization::factory()->create([
        'name' => 'Aurora Towers',
    ]);

    $buildingA = Building::factory()->for($organizationA)->create([
        'name' => 'North Hall',
    ]);
    $buildingASecondary = Building::factory()->for($organizationA)->create([
        'name' => 'North Annex',
    ]);
    $buildingB = Building::factory()->for($organizationB)->create([
        'name' => 'Aurora Block',
    ]);

    $propertyA = Property::factory()->for($organizationA)->for($buildingA)->create([
        'name' => 'A-12',
    ]);
    $propertyASecondary = Property::factory()->for($organizationA)->for($buildingASecondary)->create([
        'name' => 'A-34',
    ]);
    $propertyB = Property::factory()->for($organizationB)->for($buildingB)->create([
        'name' => 'B-24',
    ]);

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    Livewire::test(CreateMeter::class)
        ->assertFormFieldExists('organization_scope_id', fn (FormSelect $field): bool => $field->getLabel() === 'Organization')
        ->assertFormFieldExists('building_scope_id', fn (FormSelect $field): bool => $field->getLabel() === 'Building')
        ->assertFormFieldExists('property_id', fn (FormSelect $field): bool => $field->getOptions() === [])
        ->fillForm([
            'organization_scope_id' => $organizationA->id,
        ])
        ->assertFormFieldExists('building_scope_id', function (FormSelect $field) use ($buildingA, $buildingASecondary, $buildingB): bool {
            $options = $field->getOptions();

            return ($options[$buildingA->id] ?? null) === $buildingA->name
                && ($options[$buildingASecondary->id] ?? null) === $buildingASecondary->name
                && ! array_key_exists($buildingB->id, $options);
        })
        ->assertFormFieldExists('property_id', function (FormSelect $field) use ($propertyA, $propertyASecondary, $propertyB): bool {
            $options = $field->getOptions();

            return ($options[$propertyA->id] ?? null) === $propertyA->name
                && ($options[$propertyASecondary->id] ?? null) === $propertyASecondary->name
                && ! array_key_exists($propertyB->id, $options);
        })
        ->fillForm([
            'organization_scope_id' => $organizationA->id,
            'building_scope_id' => $buildingA->id,
        ])
        ->assertFormFieldExists('property_id', function (FormSelect $field) use ($propertyA, $propertyASecondary, $propertyB): bool {
            $options = $field->getOptions();

            return ($options[$propertyA->id] ?? null) === $propertyA->name
                && ! array_key_exists($propertyASecondary->id, $options)
                && ! array_key_exists($propertyB->id, $options);
        });
});

it('keeps meter relation tab badges deferred with reading history counts', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $meter = Meter::factory()->for($organization)->for($property)->create();

    MeterReading::factory()->count(2)->for($organization)->for($property)->for($meter)->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin);

    $component = Livewire::test(ViewMeter::class, ['record' => $meter->getRouteKey()]);
    $page = $component->invade();
    $record = $page->getRecord();
    $tabs = collect($page->getDeferredRelationManagerTabs(
        MeterResource::getRelations(),
        $page->hasCombinedRelationManagerTabsWithContent(),
        ['ownerRecord' => $record, 'pageClass' => ViewMeter::class],
        $record,
    ));

    $tabs->except([''])->each(function (Tab $tab): void {
        expect($tab->isBadgeDeferred())->toBeTrue();
    });

    expect(ReadingHistoryRelationManager::getBadge($record, ViewMeter::class))->toBe('2');
});
