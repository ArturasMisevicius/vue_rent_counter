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

it('shows organization dashboard metrics, usage, invoices, and deadlines for admins', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 25,
        'meter_limit_snapshot' => 50,
        'invoice_limit_snapshot' => 100,
    ]);

    $building = Building::factory()->for($organization)->create();

    $properties = Property::factory()
        ->count(3)
        ->for($organization)
        ->for($building)
        ->create();

    $tenants = User::factory()
        ->count(2)
        ->tenant()
        ->create([
            'organization_id' => $organization->id,
        ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($properties[0])
        ->for($tenants[0], 'tenant')
        ->create();

    PropertyAssignment::factory()
        ->for($organization)
        ->for($properties[1])
        ->for($tenants[1], 'tenant')
        ->create();

    Invoice::factory()
        ->for($organization)
        ->for($properties[0])
        ->for($tenants[0], 'tenant')
        ->create([
            'invoice_number' => 'INV-100001',
            'status' => InvoiceStatus::FINALIZED,
            'total_amount' => 128.40,
            'amount_paid' => 0,
            'paid_at' => null,
        ]);

    Invoice::factory()
        ->for($organization)
        ->for($properties[1])
        ->for($tenants[1], 'tenant')
        ->create([
            'invoice_number' => 'INV-100002',
            'status' => InvoiceStatus::PAID,
            'total_amount' => 321.45,
            'amount_paid' => 321.45,
            'paid_at' => now(),
        ]);

    $dueMeter = Meter::factory()
        ->for($organization)
        ->for($properties[0])
        ->create([
            'name' => 'Water Meter A1',
        ]);

    MeterReading::factory()
        ->for($organization)
        ->for($properties[0])
        ->for($dueMeter)
        ->for($admin, 'submittedBy')
        ->create([
            'reading_date' => now()->subDays(28)->toDateString(),
        ]);

    $recentMeter = Meter::factory()
        ->for($organization)
        ->for($properties[1])
        ->create([
            'name' => 'Heat Meter B2',
        ]);

    MeterReading::factory()
        ->for($organization)
        ->for($properties[1])
        ->for($recentMeter)
        ->for($admin, 'submittedBy')
        ->create([
            'reading_date' => now()->subDays(3)->toDateString(),
        ]);

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create();

    Property::factory()
        ->for($otherOrganization)
        ->for($otherBuilding)
        ->create();

    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    Invoice::factory()
        ->for($otherOrganization)
        ->for(
            Property::factory()
                ->for($otherOrganization)
                ->for($otherBuilding),
        )
        ->for($otherTenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-999999',
            'status' => InvoiceStatus::PAID,
            'total_amount' => 999.99,
            'amount_paid' => 999.99,
            'paid_at' => now(),
        ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Total Properties')
        ->assertSeeText('Active Tenants')
        ->assertSeeText('Pending Invoices')
        ->assertSeeText('Revenue This Month')
        ->assertSeeText('Subscription Usage')
        ->assertSeeText('Recent Invoices')
        ->assertSeeText('Upcoming Reading Deadlines')
        ->assertSeeText('3 / 10')
        ->assertSeeText('2 / 25')
        ->assertSeeText('2 / 100')
        ->assertSeeText('EUR 321.45')
        ->assertSeeText('INV-100001')
        ->assertSeeText('INV-100002')
        ->assertSeeText('Water Meter A1')
        ->assertDontSeeText('INV-999999')
        ->assertSee('wire:poll.30s', false);
});
