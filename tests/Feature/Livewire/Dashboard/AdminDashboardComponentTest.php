<?php

use App\Enums\InvoiceStatus;
use App\Enums\UserStatus;
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
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the admin dashboard component for admin users with the new contract', function () {
    $admin = seedAdminDashboardComponentData();

    Livewire::actingAs($admin)
        ->test(AdminDashboard::class)
        ->assertSeeText('Total Properties')
        ->assertSeeText('Active Tenants')
        ->assertSeeText('Pending Invoices')
        ->assertSeeText('Revenue This Month')
        ->assertSeeText('Subscription Usage')
        ->assertSeeText('Properties')
        ->assertSeeText('Tenants')
        ->assertSeeText('Upgrade Plan')
        ->assertSeeText('Recent Invoices')
        ->assertSeeText('Upcoming Reading Deadlines')
        ->assertSeeText('INV-FINALIZED-001')
        ->assertSeeText('EUR 321.45')
        ->assertSeeText('WM-A1')
        ->assertDontSeeText('INV-OUTSIDE-001')
        ->assertSeeHtml('wire:poll.visible.30s="refreshDashboardOnInterval"');
});

it('does not render subscription usage for managers', function () {
    $manager = seedAdminDashboardComponentData(role: 'manager');

    Livewire::actingAs($manager)
        ->test(AdminDashboard::class)
        ->assertDontSeeText('Subscription Usage')
        ->assertDontSeeText('Upgrade Plan');
});

it('renders the forbidden experience when a tenant tries to render the admin dashboard component', function () {
    $tenant = User::factory()->tenant()->create();

    Livewire::actingAs($tenant)
        ->test(AdminDashboard::class)
        ->assertStatus(403)
        ->assertSeeText('You do not have permission to view this page')
        ->assertSeeText('403');
});

it('returns the same computed dashboard payload as the admin dashboard stats service', function () {
    $admin = seedAdminDashboardComponentData();

    $component = Livewire::actingAs($admin)->test(AdminDashboard::class);

    expect($component->instance()->dashboard())
        ->toEqual(app(AdminDashboardStats::class)->dashboardFor($admin, 10, 10));
});

it('refreshes translated admin dashboard copy when the shell locale changes', function () {
    $admin = seedAdminDashboardComponentData();

    $component = Livewire::actingAs($admin)
        ->test(AdminDashboard::class)
        ->assertSeeText(__('dashboard.organization_usage.heading', [], 'en'));

    $admin->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($admin->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('dashboard.organization_usage.heading', [], 'lt'))
        ->assertSeeText(__('dashboard.organization_metrics.total_properties', [], 'lt'));
});

function seedAdminDashboardComponentData(string $role = 'admin'): User
{
    $organization = Organization::factory()->create();
    $user = User::factory()->{$role}()->create([
        'organization_id' => $organization->id,
    ]);

    Subscription::factory()->for($organization)->active()->create([
        'property_limit_snapshot' => 2,
        'tenant_limit_snapshot' => 2,
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
            'status' => UserStatus::ACTIVE,
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
            'billing_period_start' => now()->startOfMonth(),
            'billing_period_end' => now()->endOfMonth(),
        ]);

    Invoice::factory()
        ->for($organization)
        ->for($properties[1])
        ->for($tenants[1], 'tenant')
        ->create([
            'invoice_number' => 'INV-FINALIZED-001',
            'status' => InvoiceStatus::FINALIZED,
            'total_amount' => 321.45,
            'amount_paid' => 0,
            'paid_at' => null,
            'finalized_at' => now()->subDay(),
            'billing_period_start' => now()->subMonth()->startOfMonth(),
            'billing_period_end' => now()->subMonth()->endOfMonth(),
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
        ->for($user, 'submittedBy')
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
            'invoice_number' => 'INV-OUTSIDE-001',
            'status' => InvoiceStatus::PAID,
            'amount_paid' => 999.99,
            'total_amount' => 999.99,
            'paid_at' => now(),
        ]);

    return $user;
}
