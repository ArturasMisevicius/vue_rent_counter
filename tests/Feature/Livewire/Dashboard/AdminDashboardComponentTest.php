<?php

use App\Enums\InvoiceStatus;
use App\Filament\Support\Admin\Dashboard\AdminDashboardStats;
use App\Livewire\Pages\Dashboard\AdminDashboard;
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
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the admin dashboard component for admin users', function () {
    $admin = seedAdminDashboardComponentData();

    Livewire::actingAs($admin)
        ->test(AdminDashboard::class)
        ->assertSeeText('Total Properties')
        ->assertSeeText('Active Tenants')
        ->assertSeeText('Draft Invoices')
        ->assertSeeText('Revenue This Month')
        ->assertSeeText('Subscription Usage')
        ->assertSeeText('Recent Invoices')
        ->assertSeeText('Upcoming Reading Deadlines')
        ->assertSeeText('INV-DRAFT-001')
        ->assertSeeText('INV-PAID-001')
        ->assertSeeText('Water Meter A1')
        ->assertDontSeeText('INV-OUTSIDE-001')
        ->assertSeeHtml('wire:poll.30s');
});

it('renders the forbidden experience when a tenant tries to render the admin dashboard component', function () {
    $tenant = User::factory()->tenant()->create();

    Livewire::actingAs($tenant)
        ->test(AdminDashboard::class)
        ->assertSeeText('You do not have permission to view this page')
        ->assertSeeText('403');
});

it('returns the same computed dashboard payload as the admin dashboard stats service', function () {
    $admin = seedAdminDashboardComponentData();

    $component = Livewire::actingAs($admin)->test(AdminDashboard::class);

    expect($component->instance()->dashboard())
        ->toEqual(app(AdminDashboardStats::class)->dashboardFor($admin, 10, 10));
});

function seedAdminDashboardComponentData(): User
{
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
        ->count(2)
        ->for($organization)
        ->for($building)
        ->create();

    $tenants = User::factory()
        ->count(2)
        ->tenant()
        ->create([
            'organization_id' => $organization->id,
        ]);

    foreach ($tenants as $index => $tenant) {
        PropertyAssignment::factory()
            ->for($organization)
            ->for($properties[$index])
            ->for($tenant, 'tenant')
            ->create();
    }

    Invoice::factory()
        ->for($organization)
        ->for($properties[0])
        ->for($tenants[0], 'tenant')
        ->create([
            'invoice_number' => 'INV-DRAFT-001',
            'status' => InvoiceStatus::DRAFT,
            'finalized_at' => null,
        ]);

    Invoice::factory()
        ->for($organization)
        ->for($properties[1])
        ->for($tenants[1], 'tenant')
        ->create([
            'invoice_number' => 'INV-PAID-001',
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
            'invoice_number' => 'INV-OUTSIDE-001',
            'status' => InvoiceStatus::PAID,
            'amount_paid' => 999.99,
            'total_amount' => 999.99,
            'paid_at' => now(),
        ]);

    return $admin;
}
