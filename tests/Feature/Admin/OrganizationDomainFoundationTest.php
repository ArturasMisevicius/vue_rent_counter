<?php

use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('creates the shared organization domain schema required by admin and tenant features', function () {
    expect(Schema::hasTable('organization_settings'))->toBeTrue()
        ->and(Schema::hasTable('buildings'))->toBeTrue()
        ->and(Schema::hasTable('properties'))->toBeTrue()
        ->and(Schema::hasTable('property_assignments'))->toBeTrue()
        ->and(Schema::hasTable('meters'))->toBeTrue()
        ->and(Schema::hasTable('meter_readings'))->toBeTrue()
        ->and(Schema::hasTable('invoices'))->toBeTrue();

    expect(Schema::hasColumns('organization_settings', [
        'organization_id',
        'billing_contact_name',
        'billing_contact_email',
        'payment_instructions',
        'invoice_footer',
    ]))->toBeTrue();

    expect(Schema::hasColumns('buildings', [
        'organization_id',
        'name',
        'address_line_1',
        'city',
        'postal_code',
        'country_code',
    ]))->toBeTrue();

    expect(Schema::hasColumns('properties', [
        'organization_id',
        'building_id',
        'name',
        'unit_number',
        'type',
        'floor_area_sqm',
    ]))->toBeTrue();

    expect(Schema::hasColumns('property_assignments', [
        'organization_id',
        'property_id',
        'tenant_user_id',
        'unit_area_sqm',
        'assigned_at',
        'unassigned_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('meters', [
        'organization_id',
        'property_id',
        'name',
        'identifier',
        'type',
        'status',
        'unit',
    ]))->toBeTrue();

    expect(Schema::hasColumns('meter_readings', [
        'organization_id',
        'property_id',
        'meter_id',
        'submitted_by_user_id',
        'reading_value',
        'reading_date',
        'validation_status',
        'submission_method',
    ]))->toBeTrue();

    expect(Schema::hasColumns('invoices', [
        'organization_id',
        'property_id',
        'tenant_user_id',
        'invoice_number',
        'billing_period_start',
        'billing_period_end',
        'status',
        'currency',
        'total_amount',
        'amount_paid',
        'document_path',
    ]))->toBeTrue();
});

it('resolves current tenant assignment relations and tenant-safe policies across the shared domain', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $assignment = PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'unassigned_at' => null,
        ]);

    $meter = Meter::factory()
        ->for($organization)
        ->for($property)
        ->create();

    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create();

    expect($tenant->fresh()->currentPropertyAssignment->is($assignment))->toBeTrue()
        ->and($tenant->currentProperty->is($property))->toBeTrue()
        ->and($property->fresh()->currentAssignment->is($assignment))->toBeTrue()
        ->and($property->currentTenant->is($tenant))->toBeTrue()
        ->and($meter->fresh()->property->is($property))->toBeTrue()
        ->and($invoice->fresh()->tenant->is($tenant))->toBeTrue();

    expect(Gate::forUser($tenant)->allows('view', $property))->toBeTrue()
        ->and(Gate::forUser($tenant)->allows('view', $meter))->toBeTrue()
        ->and(Gate::forUser($tenant)->allows('view', $invoice))->toBeTrue()
        ->and(Gate::forUser($otherTenant)->allows('view', $property))->toBeFalse()
        ->and(Gate::forUser($otherTenant)->allows('view', $meter))->toBeFalse()
        ->and(Gate::forUser($otherTenant)->allows('view', $invoice))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('view', $property))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $meter))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('view', $invoice))->toBeTrue();
});
