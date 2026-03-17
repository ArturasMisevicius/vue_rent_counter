<?php

use App\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Actions\Admin\Properties\CreatePropertyAction;
use App\Actions\Admin\Properties\DeletePropertyAction;
use App\Actions\Admin\Properties\UnassignTenantFromPropertyAction;
use App\Actions\Admin\Properties\UpdatePropertyAction;
use App\Enums\PropertyType;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('shows organization-scoped properties resource pages and occupancy details', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Hall',
    ]);

    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
        'unit_number' => '12',
    ]);

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Taylor Tenant',
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subDays(14),
        ]);

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create();
    $otherProperty = Property::factory()->for($otherOrganization)->for($otherBuilding)->create([
        'name' => 'Z-99',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 10,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.index'))
        ->assertSuccessful()
        ->assertSeeText('Properties')
        ->assertSeeText($property->name)
        ->assertSeeText($building->name)
        ->assertSeeText('Taylor Tenant')
        ->assertDontSeeText($otherProperty->name);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.create'))
        ->assertSuccessful()
        ->assertSeeText('Building')
        ->assertSeeText('Unit Number')
        ->assertSeeText('Property Type');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.view', $property))
        ->assertSuccessful()
        ->assertSeeText('Current Occupancy')
        ->assertSeeText('Assignment History')
        ->assertSeeText('Taylor Tenant');

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.edit', $property))
        ->assertSuccessful()
        ->assertSeeText('Save changes')
        ->assertSeeText($property->name);

    $this->actingAs($manager)
        ->get(route('filament.admin.resources.properties.index'))
        ->assertSuccessful()
        ->assertSeeText($property->name);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.view', $otherProperty))
        ->assertNotFound();

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.edit', $otherProperty))
        ->assertNotFound();
});

it('creates properties within limits and keeps assignment history during reassign and unassign flows', function () {
    $organization = Organization::factory()->create();

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

    $property = app(CreatePropertyAction::class)->handle($organization, [
        'building_id' => $building->id,
        'name' => 'B-21',
        'unit_number' => '21',
        'type' => PropertyType::APARTMENT,
        'floor_area_sqm' => 48.5,
    ]);

    expect($property)
        ->organization_id->toBe($organization->id)
        ->building_id->toBe($building->id);

    $updated = app(UpdatePropertyAction::class)->handle($property, [
        'building_id' => $building->id,
        'name' => 'B-21 Prime',
        'unit_number' => '21A',
        'type' => PropertyType::OFFICE,
        'floor_area_sqm' => 50.25,
    ]);

    expect($updated)
        ->name->toBe('B-21 Prime')
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

it('blocks property create entry when the subscription limit is exhausted and prevents deleting properties with history', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 1,
    ]);

    $propertyAtLimit = Property::factory()->for($organization)->for($building)->create();

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.resources.properties.create'))
        ->assertForbidden();

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($propertyAtLimit)
        ->for($tenant, 'tenant')
        ->create();

    expect(fn () => app(DeletePropertyAction::class)->handle($propertyAtLimit))
        ->toThrow(ValidationException::class);

    expect(Property::query()->whereKey($propertyAtLimit->id)->exists())->toBeTrue();
});
