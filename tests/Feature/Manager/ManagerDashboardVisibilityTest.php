<?php

use App\Enums\InvoiceStatus;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('does not show subscription usage bars to managers', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 25,
        'meter_limit_snapshot' => 50,
        'invoice_limit_snapshot' => 100,
    ]);

    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'North Unit',
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

    Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-300001',
            'status' => InvoiceStatus::FINALIZED,
        ]);

    $meter = Meter::factory()
        ->for($organization)
        ->for($property)
        ->create([
            'identifier' => 'MTR-300001',
        ]);

    MeterReading::factory()
        ->for($organization)
        ->for($property)
        ->for($meter)
        ->for($manager, 'submittedBy')
        ->create([
            'reading_date' => now()->subDays(31)->toDateString(),
        ]);

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Total Properties')
        ->assertSeeText('Recent Invoices')
        ->assertSeeText('Upcoming Reading Deadlines')
        ->assertSeeText('Taylor Tenant')
        ->assertSeeText('North Unit')
        ->assertSeeText('Process Payment')
        ->assertSeeText('MTR-300001')
        ->assertDontSeeText('Subscription Usage')
        ->assertDontSeeText('1 / 10');
});
