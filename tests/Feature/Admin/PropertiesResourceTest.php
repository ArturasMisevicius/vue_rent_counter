<?php

use App\Enums\PropertyType;
use App\Filament\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Filament\Actions\Admin\Properties\CreatePropertyAction;
use App\Filament\Actions\Admin\Properties\DeletePropertyAction;
use App\Filament\Actions\Admin\Properties\UnassignTenantFromPropertyAction;
use App\Filament\Actions\Admin\Properties\UpdatePropertyAction;
use App\Filament\Resources\Properties\Pages\ListProperties;
use App\Filament\Resources\Properties\Pages\ViewProperty;
use App\Filament\Resources\Properties\PropertyResource;
use App\Filament\Resources\Properties\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\Properties\RelationManagers\MetersRelationManager;
use App\Filament\Resources\Properties\RelationManagers\ReadingsRelationManager;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Subscription;
use App\Models\User;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the properties list create edit and view pages for the admin contract', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
        'address_line_1' => 'Main Street 1',
        'city' => 'Vilnius',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Apartment 4B',
        'floor' => 0,
        'unit_number' => '4B',
        'type' => PropertyType::APARTMENT,
        'floor_area_sqm' => 56.5,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Taylor Tenant',
        'email' => 'taylor@example.test',
    ]);
    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'unit_area_sqm' => 56.5,
        ]);

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create();
    $otherProperty = Property::factory()->for($otherOrganization)->for($otherBuilding)->create([
        'name' => 'Suite 9Z',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);
    $superadmin = User::factory()->superadmin()->create();

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 10,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.index'))
        ->assertSuccessful()
        ->assertSeeText('Properties')
        ->assertSeeText('New Property')
        ->assertSeeText('Apartment 4B')
        ->assertSeeText('North Hall')
        ->assertSeeText('Taylor Tenant')
        ->assertDontSeeText('Suite 9Z');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.create'))
        ->assertSuccessful()
        ->assertSeeText('New Property')
        ->assertSeeText('Property Information')
        ->assertSeeText('Property Name')
        ->assertSeeText('Building')
        ->assertSeeText('Floor')
        ->assertSeeText('Unit Number')
        ->assertSeeText('Area in Square Meters')
        ->assertSeeText('Property Type')
        ->assertSeeText('Save Property');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.view', $property))
        ->assertSuccessful()
        ->assertSeeText('Apartment 4B')
        ->assertSeeText('North Hall')
        ->assertSeeText('Main Street 1')
        ->assertSeeText('Edit')
        ->assertSeeText('Reassign Tenant')
        ->assertSeeText('Delete')
        ->assertSeeText('Tenant')
        ->assertSeeText('Meters')
        ->assertSeeText('Readings')
        ->assertSeeText('Invoices')
        ->assertSeeText('Taylor Tenant');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.edit', $property))
        ->assertSuccessful()
        ->assertSeeText('Edit Property: Apartment 4B')
        ->assertSeeText('Save Changes');

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.properties.index'))
        ->assertSuccessful()
        ->assertSeeText('Apartment 4B');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.view', $otherProperty))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.edit', $otherProperty))
        ->assertNotFound();

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.properties.index'))
        ->assertSuccessful()
        ->assertSeeText('Apartment 4B')
        ->assertSeeText('Suite 9Z');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.resources.properties.create'))
        ->assertSuccessful()
        ->assertSeeText('Organization');
});

it('exposes the properties list and relation manager contracts', function () {
    $organizationA = Organization::factory()->create([
        'name' => 'Northwind Estates',
    ]);
    $organizationB = Organization::factory()->create([
        'name' => 'Aurora Towers',
    ]);

    $buildingA = Building::factory()->for($organizationA)->create([
        'name' => 'North Hall',
        'address_line_1' => 'Main Street 1',
        'city' => 'Vilnius',
    ]);
    $buildingB = Building::factory()->for($organizationB)->create([
        'name' => 'Aurora Block',
    ]);

    $propertyA = Property::factory()->for($organizationA)->for($buildingA)->create([
        'name' => 'A-12',
        'floor' => 3,
        'type' => PropertyType::APARTMENT,
        'floor_area_sqm' => 48.25,
    ]);
    $propertyB = Property::factory()->for($organizationB)->for($buildingB)->create([
        'name' => 'B-24',
        'type' => PropertyType::OFFICE,
    ]);

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organizationA->id,
        'name' => 'Jordan Tenant',
    ]);
    PropertyAssignment::factory()
        ->for($organizationA)
        ->for($propertyA)
        ->for($tenant, 'tenant')
        ->create([
            'unit_area_sqm' => 48.25,
        ]);

    $meter = Meter::factory()->for($organizationA)->for($propertyA)->create([
        'identifier' => 'MTR-2002',
    ]);
    MeterReading::factory()->for($organizationA)->for($propertyA)->for($meter)->create([
        'reading_value' => 220.1,
    ]);
    MeterReading::factory()->for($organizationA)->for($propertyA)->for($meter)->create([
        'reading_value' => 248.9,
        'reading_date' => now()->toDateString(),
    ]);

    $invoice = Invoice::factory()->for($organizationA)->for($propertyA)->for($tenant, 'tenant')->create([
        'invoice_number' => 'INV-2026-0042',
    ]);

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    Livewire::test(ListProperties::class)
        ->assertTableColumnExists('organization.name', fn (TextColumn $column): bool => $column->getLabel() === 'Organization')
        ->assertTableColumnExists('name', fn (TextColumn $column): bool => $column->getLabel() === 'Property Name')
        ->assertTableColumnExists('building.name', fn (TextColumn $column): bool => $column->getLabel() === 'Building')
        ->assertTableColumnExists('type', fn (TextColumn $column): bool => $column->getLabel() === 'Type')
        ->assertTableColumnExists('floor', fn (TextColumn $column): bool => $column->getLabel() === 'Floor')
        ->assertTableColumnExists('floor_area_sqm', fn (TextColumn $column): bool => $column->getLabel() === 'Area')
        ->assertTableColumnExists('currentAssignment.tenant.name', fn (TextColumn $column): bool => $column->getLabel() === 'Current Tenant')
        ->assertTableColumnExists('occupancy_status', fn (TextColumn $column): bool => $column->getLabel() === 'Status')
        ->assertTableColumnExists('created_at', fn (TextColumn $column): bool => $column->getLabel() === 'Date Created')
        ->assertTableFilterExists('organization', fn (SelectFilter $filter): bool => $filter->getLabel() === 'Organization')
        ->assertTableFilterExists('building_id')
        ->assertTableFilterExists('type')
        ->assertTableFilterExists('occupancy_status')
        ->assertCanSeeTableRecords([$propertyA, $propertyB])
        ->filterTable('organization', (string) $organizationA->getKey())
        ->assertCanSeeTableRecords([$propertyA])
        ->assertCanNotSeeTableRecords([$propertyB]);

    Livewire::test(ViewProperty::class, ['record' => $propertyA->getRouteKey()])
        ->assertActionExists('edit')
        ->assertActionExists('assignTenant')
        ->assertActionExists('unassignTenant')
        ->assertActionExists('delete');

    Livewire::test(MetersRelationManager::class, [
        'ownerRecord' => $propertyA,
        'pageClass' => ViewProperty::class,
    ])
        ->assertTableColumnExists('identifier', fn (TextColumn $column): bool => $column->getLabel() === 'Serial Number')
        ->assertTableColumnExists('type', fn (TextColumn $column): bool => $column->getLabel() === 'Type')
        ->assertTableColumnExists('status', fn (TextColumn $column): bool => $column->getLabel() === 'Status')
        ->assertTableColumnExists('latestReading.reading_date', fn (TextColumn $column): bool => $column->getLabel() === 'Last Reading Date')
        ->assertTableColumnExists('latestReading.reading_value', fn (TextColumn $column): bool => $column->getLabel() === 'Last Value')
        ->assertTableActionExists('view', record: $meter)
        ->assertTableActionExists('edit', record: $meter);

    Livewire::test(ReadingsRelationManager::class, [
        'ownerRecord' => $propertyA,
        'pageClass' => ViewProperty::class,
    ])
        ->assertTableColumnExists('meter.identifier', fn (TextColumn $column): bool => $column->getLabel() === 'Meter Serial')
        ->assertTableColumnExists('reading_date', fn (TextColumn $column): bool => $column->getLabel() === 'Reading Date')
        ->assertTableColumnExists('reading_value', fn (TextColumn $column): bool => $column->getLabel() === 'Value')
        ->assertTableColumnExists('consumption_since_previous', fn (TextColumn $column): bool => $column->getLabel() === 'Consumption Since Previous')
        ->assertTableColumnExists('validation_status', fn (TextColumn $column): bool => $column->getLabel() === 'Validation Status')
        ->assertTableActionExists('view', record: $meter->readings()->latest('id')->first());

    Livewire::test(InvoicesRelationManager::class, [
        'ownerRecord' => $propertyA,
        'pageClass' => ViewProperty::class,
    ])
        ->assertTableColumnExists('invoice_number', fn (TextColumn $column): bool => $column->getLabel() === 'Invoice Number')
        ->assertTableColumnExists('billing_period', fn (TextColumn $column): bool => $column->getLabel() === 'Billing Period')
        ->assertTableColumnExists('total_amount', fn (TextColumn $column): bool => $column->getLabel() === 'Amount')
        ->assertTableColumnExists('status', fn (TextColumn $column): bool => $column->getLabel() === 'Status')
        ->assertTableColumnExists('created_at', fn (TextColumn $column): bool => $column->getLabel() === 'Issued Date')
        ->assertTableColumnExists('paid_at', fn (TextColumn $column): bool => $column->getLabel() === 'Paid Date')
        ->assertTableActionExists('view', record: $invoice)
        ->assertTableActionExists('downloadPdf', record: $invoice);
});

it('creates updates assigns reassigns and unassigns properties through the admin actions', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 5,
    ]);

    $building = Building::factory()->for($organization)->create();
    $tenantA = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Tenant A',
    ]);
    $tenantB = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Tenant B',
    ]);

    $this->actingAs($admin);

    $property = app(CreatePropertyAction::class)->handle($organization, [
        'building_id' => $building->id,
        'name' => 'B-21',
        'floor' => 2,
        'unit_number' => null,
        'type' => PropertyType::APARTMENT,
        'floor_area_sqm' => 48.5,
    ]);

    expect($property)
        ->organization_id->toBe($organization->id)
        ->building_id->toBe($building->id)
        ->floor->toBe(2)
        ->unit_number->toBeNull();

    $updated = app(UpdatePropertyAction::class)->handle($property, [
        'building_id' => $building->id,
        'name' => 'B-21 Prime',
        'floor' => 3,
        'unit_number' => '21A',
        'type' => PropertyType::OFFICE,
        'floor_area_sqm' => 50.25,
    ]);

    expect($updated)
        ->name->toBe('B-21 Prime')
        ->floor->toBe(3)
        ->unit_number->toBe('21A')
        ->type->toBe(PropertyType::OFFICE);

    $firstAssignment = app(AssignTenantToPropertyAction::class)->handle($updated, $tenantA, 48.5);

    expect($updated->fresh()->currentTenant?->is($tenantA))->toBeTrue()
        ->and($firstAssignment->unassigned_at)->toBeNull();

    $secondAssignment = app(AssignTenantToPropertyAction::class)->handle($updated->fresh(), $tenantB, 50.25);

    expect($firstAssignment->fresh()->unassigned_at)->not->toBeNull()
        ->and($updated->fresh()->currentTenant?->is($tenantB))->toBeTrue()
        ->and($secondAssignment->tenant->is($tenantB))->toBeTrue();

    $closedAssignment = app(UnassignTenantFromPropertyAction::class)->handle($updated->fresh());

    expect($closedAssignment)->not->toBeNull()
        ->and($updated->fresh()->currentAssignment)->toBeNull()
        ->and($updated->assignments()->count())->toBe(2);
});

it('prevents deleting properties with an active tenant assignment and lets superadmin bypass property limits', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 1,
    ]);

    $propertyAtLimit = Property::factory()->for($organization)->for($building)->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($propertyAtLimit)
        ->for($tenant, 'tenant')
        ->create();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.create'))
        ->assertSuccessful();

    expect(fn () => app(DeletePropertyAction::class)->handle($propertyAtLimit))
        ->toThrow(ValidationException::class);

    expect(Property::query()->whereKey($propertyAtLimit->id)->exists())->toBeTrue();

    $superadmin = User::factory()->superadmin()->create();

    $this->actingAs($superadmin);

    $property = app(CreatePropertyAction::class)->handle($organization, [
        'building_id' => $building->id,
        'name' => 'Control Plane Property',
        'floor' => null,
        'unit_number' => 'CP-01',
        'type' => PropertyType::OFFICE,
        'floor_area_sqm' => 120,
    ]);

    expect($property)
        ->organization_id->toBe($organization->id)
        ->name->toBe('Control Plane Property');
});

it('keeps property meter readings queries unambiguous when joining meters', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $meter = Meter::factory()->for($organization)->for($property)->create();

    MeterReading::factory()
        ->for($organization)
        ->for($property)
        ->for($meter)
        ->create();

    $readingsCount = $property->meterReadings()
        ->forOrganization($property->organization_id)
        ->count();

    expect($readingsCount)->toBe(1);
});

it('keeps property relation tab badges deferred with relation counts', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $meter = Meter::factory()->for($organization)->for($property)->create();
    MeterReading::factory()->for($organization)->for($property)->for($meter)->create();
    Invoice::factory()->for($organization)->for($property)->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin);

    $component = Livewire::test(ViewProperty::class, ['record' => $property->getRouteKey()]);
    $page = $component->invade();
    $record = $page->getRecord();
    $tabs = collect($page->getDeferredRelationManagerTabs(
        PropertyResource::getRelations(),
        $page->hasCombinedRelationManagerTabsWithContent(),
        ['ownerRecord' => $record, 'pageClass' => ViewProperty::class],
        $record,
    ));

    $tabs->except([''])->each(function (Tab $tab): void {
        expect($tab->isBadgeDeferred())->toBeTrue();
    });

    expect(MetersRelationManager::getBadge($record, ViewProperty::class))->toBe('1')
        ->and(ReadingsRelationManager::getBadge($record, ViewProperty::class))->toBe('1')
        ->and(InvoicesRelationManager::getBadge($record, ViewProperty::class))->toBe('1');
});
