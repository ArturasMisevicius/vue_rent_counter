<?php

use App\Filament\Actions\Admin\Properties\UnassignTenantFromPropertyAction;
use App\Livewire\Tenant\SubmitReadingPage;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows only meters assigned to the tenants current property', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $assignedProperty = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
    ]);
    $otherProperty = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-13',
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($assignedProperty)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonths(2),
        ]);

    $assignedMeter = Meter::factory()->for($organization)->for($assignedProperty)->create([
        'name' => 'Assigned Water Meter',
        'identifier' => 'TEN-ASSIGNED-001',
    ]);
    $otherMeter = Meter::factory()->for($organization)->for($otherProperty)->create([
        'name' => 'Other Unit Meter',
        'identifier' => 'TEN-OTHER-001',
    ]);

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.tenant-property-details'))
        ->assertSuccessful()
        ->assertSeeText($assignedMeter->identifier)
        ->assertDontSeeText($otherMeter->identifier);

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.tenant-submit-meter-reading'))
        ->assertSuccessful()
        ->assertSeeText($assignedMeter->identifier)
        ->assertDontSeeText($otherMeter->identifier);
});

it('rejects manual meter submissions for another property in the same organization', function () {
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
        ->create([
            'assigned_at' => now()->subMonths(2),
        ]);

    Meter::factory()->for($organization)->for($assignedProperty)->create([
        'identifier' => 'TEN-CURRENT-001',
    ]);
    $otherMeter = Meter::factory()->for($organization)->for($otherProperty)->create([
        'identifier' => 'TEN-FOREIGN-001',
    ]);

    Livewire::actingAs($tenant)
        ->test(SubmitReadingPage::class)
        ->set('meterId', (string) $otherMeter->id)
        ->set('readingValue', '88.000')
        ->set('readingDate', now()->toDateString())
        ->call('submit')
        ->assertHasErrors(['meterId']);

    expect(
        MeterReading::query()
            ->where('meter_id', $otherMeter->id)
            ->where('submitted_by_user_id', $tenant->id)
            ->exists()
    )->toBeFalse();
});

it('keeps historical invoices visible after unassignment while exposing no current meters', function () {
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
        ->create([
            'assigned_at' => now()->subMonths(3),
        ]);

    Meter::factory()->for($organization)->for($property)->create([
        'identifier' => 'TEN-HISTORY-001',
    ]);

    Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-TENANT-HISTORY-001',
        ]);

    app(UnassignTenantFromPropertyAction::class)->handle($property);

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful()
        ->assertSeeText('INV-TENANT-HISTORY-001');

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.tenant-submit-meter-reading'))
        ->assertSuccessful()
        ->assertSeeText(__('tenant.messages.no_meters_assigned'));
});

it('does not expose malformed cross-organization meters that point at the tenants current property', function () {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $assignedProperty = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($assignedProperty)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonths(2),
        ]);

    $assignedMeter = Meter::factory()->for($organization)->for($assignedProperty)->create([
        'identifier' => 'TEN-CURRENT-ORG-001',
    ]);

    $foreignOrganization = Organization::factory()->create();
    $foreignMeter = Meter::factory()->for($foreignOrganization)->create([
        'property_id' => $assignedProperty->id,
        'identifier' => 'TEN-MALFORMED-CROSS-ORG-001',
    ]);

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.tenant-property-details'))
        ->assertSuccessful()
        ->assertSeeText($assignedMeter->identifier)
        ->assertDontSeeText($foreignMeter->identifier);

    $this->actingAs($tenant)
        ->get(route('filament.admin.pages.tenant-submit-meter-reading'))
        ->assertSuccessful()
        ->assertSeeText($assignedMeter->identifier)
        ->assertDontSeeText($foreignMeter->identifier);
});
