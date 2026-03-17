<?php

use App\Actions\Admin\Properties\UnassignTenantFromPropertyAction;
use App\Actions\Tenant\Readings\SubmitTenantReadingAction;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('shows only meters from the tenants current property on the submit reading page', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $assignedProperty = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Apartment 12',
    ]);
    $otherProperty = Property::factory()->for($organization)->for($building)->create([
        'name' => 'Apartment 14',
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($assignedProperty)
        ->for($tenant, 'tenant')
        ->create();

    $assignedMeter = Meter::factory()->for($organization)->for($assignedProperty)->create([
        'identifier' => 'TEN-001',
    ]);
    $otherPropertyMeter = Meter::factory()->for($organization)->for($otherProperty)->create([
        'identifier' => 'TEN-999',
    ]);

    $this->actingAs($tenant)
        ->get(route('tenant.readings.create'))
        ->assertSuccessful()
        ->assertSeeText($assignedMeter->identifier)
        ->assertDontSeeText($otherPropertyMeter->identifier);
});

it('rejects reading submissions for meters outside the tenants current property', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $assignedProperty = Property::factory()->for($organization)->for($building)->create();
    $otherProperty = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($assignedProperty)
        ->for($tenant, 'tenant')
        ->create();

    $otherPropertyMeter = Meter::factory()->for($organization)->for($otherProperty)->create();

    expect(fn () => app(SubmitTenantReadingAction::class)->handle(
        tenant: $tenant,
        meter: $otherPropertyMeter,
        readingValue: '145.500',
        readingDate: now()->toDateString(),
    ))->toThrow(ValidationException::class);
});

it('keeps invoice access working after unassignment even when no current meters remain', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    $meter = Meter::factory()->for($organization)->for($property)->create([
        'identifier' => 'TEN-404',
    ]);

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-HIST-404',
        ]);

    app(UnassignTenantFromPropertyAction::class)->handle(
        property: $property,
        unassignedAt: now()->subMinute(),
    );

    $this->actingAs($tenant)
        ->get(route('tenant.readings.create'))
        ->assertSuccessful()
        ->assertSeeText('No meters are currently available for submission.');

    $this->actingAs($tenant)
        ->get(route('tenant.invoices.index'))
        ->assertSuccessful()
        ->assertSeeText('INV-HIST-404');

    expect(Gate::forUser($tenant)->allows('view', $meter))->toBeFalse()
        ->and(Gate::forUser($tenant)->allows('view', $invoice))->toBeTrue();
});
