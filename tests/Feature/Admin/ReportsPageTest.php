<?php

use App\Enums\InvoiceStatus;
use App\Filament\Pages\Reports;
use App\Models\Building;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the unified reports page for admin-like users and blocks tenants', function () {
    [
        'admin' => $admin,
        'manager' => $manager,
        'superadmin' => $superadmin,
        'tenant' => $tenant,
    ] = seedAdminReportsPageWorkspace();

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.reports'))
        ->assertSuccessful()
        ->assertSeeText('Reports')
        ->assertSeeText('Consumption')
        ->assertSeeText('Export CSV')
        ->assertSeeText('Export PDF');

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.reports'))
        ->assertSuccessful()
        ->assertSeeText('Reports');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.reports'))
        ->assertSuccessful()
        ->assertSeeText(__('admin.reports.messages.organization_context_required'));

    $tenantResponse = $this->actingAs($tenant)
        ->get(route('filament.admin.pages.reports'));

    expect($tenantResponse->getStatusCode())->toBeIn([302, 403]);
});

it('restores the active report tab and filters from the query string', function () {
    [
        'admin' => $admin,
        'building' => $building,
        'property' => $property,
        'tenant' => $tenant,
    ] = seedAdminReportsPageWorkspace();

    $from = now()->subMonth()->startOfMonth()->toDateString();
    $to = now()->endOfMonth()->toDateString();

    $this->actingAs($admin);

    Livewire::withQueryParams([
        'tab' => 'revenue',
        'from' => $from,
        'to' => $to,
        'building' => (string) $building->id,
        'property' => (string) $property->id,
        'tenant' => (string) $tenant->id,
        'status' => InvoiceStatus::PAID->value,
    ])->test(Reports::class)
        ->assertSet('activeTab', 'revenue')
        ->assertSet('dateFrom', $from)
        ->assertSet('dateTo', $to)
        ->assertSet('buildingId', (string) $building->id)
        ->assertSet('propertyId', (string) $property->id)
        ->assertSet('tenantId', (string) $tenant->id)
        ->assertSet('statusFilter', InvoiceStatus::PAID->value)
        ->assertSeeText(__('admin.reports.descriptions.revenue_grouped'));
});

it('refreshes translated reports copy when the shell locale changes', function () {
    [
        'admin' => $admin,
    ] = seedAdminReportsPageWorkspace();

    $component = Livewire::actingAs($admin)
        ->test(Reports::class)
        ->assertSeeText(__('admin.reports.title', [], 'en'));

    $admin->forceFill([
        'locale' => 'lt',
    ])->save();

    Auth::setUser($admin->fresh());
    app()->setLocale('lt');

    $component
        ->dispatch('shell-locale-updated')
        ->assertSeeText(__('admin.reports.title', [], 'lt'))
        ->assertSeeText(__('admin.reports.filters.all', [], 'lt'));
});

function seedAdminReportsPageWorkspace(): array
{
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Tower',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
        'unit_number' => '12',
    ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Nora Tenant',
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonth(),
            'unassigned_at' => null,
        ]);

    $superadmin = User::factory()->superadmin()->create();

    return compact('admin', 'building', 'manager', 'organization', 'property', 'superadmin', 'tenant');
}
