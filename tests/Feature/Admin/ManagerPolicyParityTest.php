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

it('allows managers to view same-organization invoices and meters while blocking cross-organization records', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create();
    $otherProperty = Property::factory()->for($otherOrganization)->for($otherBuilding)->create();

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    $otherInvoice = Invoice::factory()
        ->for($otherOrganization)
        ->for($otherProperty)
        ->for($otherTenant, 'tenant')
        ->create();

    $meter = Meter::factory()
        ->for($organization)
        ->for($property)
        ->create();

    $otherMeter = Meter::factory()
        ->for($otherOrganization)
        ->for($otherProperty)
        ->create();

    expect($manager->can('viewAny', Invoice::class))->toBeTrue()
        ->and($manager->can('view', $invoice))->toBeTrue()
        ->and($manager->can('view', $otherInvoice))->toBeFalse()
        ->and($manager->can('viewAny', Meter::class))->toBeTrue()
        ->and($manager->can('view', $meter))->toBeTrue()
        ->and($manager->can('view', $otherMeter))->toBeFalse();
});

it('keeps tenant invoice and meter access scoped to their own records and assigned property', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $otherProperty = Property::factory()->for($organization)->for($building)->create();

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    $otherInvoice = Invoice::factory()
        ->for($organization)
        ->for($otherProperty)
        ->for($otherTenant, 'tenant')
        ->create();

    $meter = Meter::factory()
        ->for($organization)
        ->for($property)
        ->create();

    $otherMeter = Meter::factory()
        ->for($organization)
        ->for($otherProperty)
        ->create();

    expect($tenant->can('viewAny', Invoice::class))->toBeFalse()
        ->and($tenant->can('view', $invoice))->toBeTrue()
        ->and($tenant->can('view', $otherInvoice))->toBeFalse()
        ->and($tenant->can('viewAny', Meter::class))->toBeFalse()
        ->and($tenant->can('view', $meter))->toBeTrue()
        ->and($tenant->can('view', $otherMeter))->toBeFalse();
});
