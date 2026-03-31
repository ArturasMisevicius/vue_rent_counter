<?php

use App\Enums\InvoiceStatus;
use App\Enums\UserStatus;
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

it('shows the admin dashboard contract with live usage, recent invoices, and reading deadlines', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 10,
        'tenant_limit_snapshot' => 25,
    ]);

    $building = Building::factory()->for($organization)->create();

    $properties = Property::factory()
        ->count(10)
        ->for($organization)
        ->for($building)
        ->create();

    $tenants = User::factory()
        ->count(25)
        ->tenant()
        ->create([
            'organization_id' => $organization->id,
            'status' => UserStatus::ACTIVE,
        ]);

    foreach ($tenants as $index => $tenant) {
        PropertyAssignment::factory()
            ->for($organization)
            ->for($properties[$index % $properties->count()])
            ->for($tenant, 'tenant')
            ->create([
                'assigned_at' => now()->subDays(10),
            ]);
    }

    $draftInvoice = Invoice::factory()
        ->for($organization)
        ->for($properties[0])
        ->for($tenants[0], 'tenant')
        ->create([
            'invoice_number' => 'INV-100001',
            'status' => InvoiceStatus::DRAFT,
            'billing_period_start' => now()->startOfMonth(),
            'billing_period_end' => now()->endOfMonth(),
            'total_amount' => 128.40,
            'amount_paid' => 0,
            'paid_at' => null,
            'finalized_at' => null,
        ]);

    $finalizedInvoice = Invoice::factory()
        ->for($organization)
        ->for($properties[1])
        ->for($tenants[1], 'tenant')
        ->create([
            'invoice_number' => 'INV-100002',
            'status' => InvoiceStatus::FINALIZED,
            'billing_period_start' => now()->startOfMonth()->subMonth(),
            'billing_period_end' => now()->startOfMonth()->subDay(),
            'total_amount' => 210.15,
            'amount_paid' => 0,
            'paid_at' => null,
            'finalized_at' => now()->subDays(3),
        ]);

    Invoice::factory()
        ->for($organization)
        ->for($properties[2])
        ->for($tenants[2], 'tenant')
        ->create([
            'invoice_number' => 'INV-100003',
            'status' => InvoiceStatus::PAID,
            'billing_period_start' => now()->startOfMonth(),
            'billing_period_end' => now()->endOfMonth(),
            'total_amount' => 321.45,
            'amount_paid' => 321.45,
            'paid_at' => now(),
        ]);

    $dueMeter = Meter::factory()
        ->for($organization)
        ->for($properties[0])
        ->create([
            'name' => 'Water Meter A1',
            'identifier' => 'WM-A1',
        ]);

    MeterReading::factory()
        ->for($organization)
        ->for($properties[0])
        ->for($dueMeter)
        ->for($admin, 'submittedBy')
        ->create([
            'reading_date' => now()->subDays(34)->toDateString(),
        ]);

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create();
    $otherProperty = Property::factory()->for($otherOrganization)->for($otherBuilding)->create();
    $otherTenant = User::factory()->tenant()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    Invoice::factory()
        ->for($otherOrganization)
        ->for($otherProperty)
        ->for($otherTenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-999999',
            'status' => InvoiceStatus::PAID,
            'total_amount' => 999.99,
            'amount_paid' => 999.99,
            'paid_at' => now(),
        ]);

    $response = $this->actingAs($admin)
        ->get(route('filament.admin.pages.dashboard'));

    $response
        ->assertSuccessful()
        ->assertSeeText('Dashboard')
        ->assertSeeText('Total Properties')
        ->assertSeeText('Active Tenants')
        ->assertSeeText('Pending Invoices')
        ->assertSeeText('Revenue This Month')
        ->assertSeeText('Subscription Usage')
        ->assertSeeText('Properties')
        ->assertSeeText('10 of 10 properties used')
        ->assertSeeText('Tenants')
        ->assertSeeText('25 of 25 tenants used')
        ->assertSeeText('Upgrade Plan')
        ->assertSeeText('Recent Invoices')
        ->assertSeeText('View All')
        ->assertSeeText((string) $tenants[0]->name)
        ->assertSeeText((string) $properties[0]->name)
        ->assertSeeText('INV-100002')
        ->assertSeeText('EUR 321.45')
        ->assertSeeText('Process Payment')
        ->assertSeeText('Upcoming Reading Deadlines')
        ->assertSeeText('WM-A1')
        ->assertSeeText('Overdue by 4 days')
        ->assertDontSeeText('INV-999999')
        ->assertSee('wire:poll.visible.30s="refreshDashboardOnInterval"', false)
        ->assertSee('href="'.route('filament.admin.resources.invoices.index').'"', false)
        ->assertSee('href="'.route('filament.admin.resources.invoices.view', $finalizedInvoice).'"', false)
        ->assertSee('href="'.route('filament.admin.pages.settings').'#subscription"', false)
        ->assertSee('href="'.route('filament.admin.resources.meter-readings.create', ['meter' => $dueMeter->id]).'"', false);

    expect($draftInvoice->fresh()->status)->toBe(InvoiceStatus::DRAFT);
});

it('hides subscription usage from managers on the dashboard', function () {
    $organization = Organization::factory()->create();
    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.dashboard'))
        ->assertSuccessful()
        ->assertSeeText('Dashboard')
        ->assertDontSeeText('Subscription Usage')
        ->assertDontSeeText('Upgrade Plan');
});
