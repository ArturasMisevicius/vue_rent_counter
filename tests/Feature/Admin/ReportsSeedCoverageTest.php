<?php

use App\Filament\Pages\Reports;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('seeds the login demo organization with populated current-period report data for admins', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::query()
        ->where('email', 'admin@example.com')
        ->firstOrFail();

    $component = Livewire::actingAs($admin)
        ->test(Reports::class)
        ->set('dateFrom', now()->startOfMonth()->toDateString())
        ->set('dateTo', now()->endOfMonth()->toDateString());

    $consumptionRows = collect($component->instance()->report()['rows']);

    expect($consumptionRows)->not->toBeEmpty();

    $revenueRows = collect($component
        ->set('activeTab', 'revenue')
        ->instance()
        ->report()['rows']);

    expect($revenueRows)->not->toBeEmpty();

    $outstandingRows = collect($component
        ->set('activeTab', 'outstanding_balances')
        ->instance()
        ->report()['rows']);

    expect($outstandingRows)->not->toBeEmpty();

    $meterComplianceRows = collect($component
        ->set('activeTab', 'meter_compliance')
        ->instance()
        ->report()['rows']);

    expect($meterComplianceRows)->not->toBeEmpty()
        ->and($meterComplianceRows->where('compliance_state', 'compliant'))->not->toBeEmpty()
        ->and($meterComplianceRows->where('compliance_state', 'needs_attention'))->not->toBeEmpty()
        ->and($meterComplianceRows->where('compliance_state', 'missing'))->not->toBeEmpty();
});

it('lets superadmins choose a seeded organization and view populated reports', function () {
    $this->seed(DatabaseSeeder::class);

    $superadmin = User::query()
        ->where('email', 'superadmin@example.com')
        ->firstOrFail();

    $expectedDefaultOrganizationId = (string) Organization::query()
        ->where('slug', 'tenanto-demo-organization')
        ->value('id');

    $component = Livewire::actingAs($superadmin)
        ->test(Reports::class)
        ->assertSet('reportOrganizationId', $expectedDefaultOrganizationId);

    expect(collect($component->instance()->report()['rows']))->not->toBeEmpty();

    $response = $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.reports'));

    $response
        ->assertSuccessful()
        ->assertDontSeeText(__('admin.reports.messages.organization_context_required'))
        ->assertSeeText(__('admin.reports.filters.organization'));
});
