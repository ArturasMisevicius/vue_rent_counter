<?php

use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Filament\Pages\Reports;
use App\Models\BillingRecord;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows report tabs and only enables exports after a dataset is loaded', function () {
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $utilityService = UtilityService::factory()->for($organization)->create([
        'name' => 'Water',
    ]);

    $invoice = Invoice::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create([
        'status' => InvoiceStatus::PARTIALLY_PAID,
        'total_amount' => 100,
        'amount_paid' => 25,
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
    ]);

    BillingRecord::factory()->for($organization)->for($property)->for($utilityService)->for($invoice)->for($tenant, 'tenant')->create([
        'amount' => 75,
        'consumption' => 10,
        'rate' => 7.5,
        'billing_period_start' => now()->startOfMonth()->toDateString(),
        'billing_period_end' => now()->endOfMonth()->toDateString(),
    ]);

    $meter = Meter::factory()->for($organization)->for($property)->create();
    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 10,
        'reading_date' => now()->subDays(30)->toDateString(),
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);
    MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 20,
        'reading_date' => now()->toDateString(),
        'validation_status' => MeterReadingValidationStatus::FLAGGED,
    ]);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.reports'))
        ->assertSuccessful()
        ->assertSeeText('Reports')
        ->assertSeeText('Consumption')
        ->assertSeeText('Revenue')
        ->assertSeeText('Outstanding Balances')
        ->assertSeeText('Meter Compliance')
        ->assertDontSeeText('Export CSV');

    Livewire::actingAs($admin)
        ->test(Reports::class)
        ->assertSet('activeTab', 'consumption')
        ->assertSet('hasLoadedReport', false)
        ->set('filters.start_date', now()->startOfMonth()->toDateString())
        ->set('filters.end_date', now()->endOfMonth()->toDateString())
        ->call('loadReport', 'consumption')
        ->assertSet('activeTab', 'consumption')
        ->assertSet('hasLoadedReport', true)
        ->assertSee('Export CSV')
        ->call('loadReport', 'revenue')
        ->assertSet('activeTab', 'revenue')
        ->call('loadReport', 'outstanding')
        ->assertSet('activeTab', 'outstanding')
        ->call('loadReport', 'compliance')
        ->assertSet('activeTab', 'compliance');
});
