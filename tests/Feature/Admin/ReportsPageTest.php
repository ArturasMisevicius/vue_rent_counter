<?php

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Filament\Pages\Reports;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

$buildReportingWorkspace = function (): array {
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create([
        'name' => 'North Tower',
        'address_line_1' => '123 Admin Street',
        'city' => 'Vilnius',
    ]);
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-12',
        'unit_number' => '12',
    ]);

    $electricMeter = Meter::factory()->for($organization)->for($property)->create([
        'name' => 'Electric Main',
        'type' => MeterType::ELECTRICITY,
        'unit' => 'kWh',
    ]);

    $waterMeter = Meter::factory()->for($organization)->for($property)->create([
        'name' => 'Water Backup',
        'type' => MeterType::WATER,
        'unit' => 'm3',
    ]);

    MeterReading::factory()->for($organization)->for($property)->for($electricMeter)->create([
        'reading_value' => 100,
        'reading_date' => now()->startOfMonth()->addDay()->toDateString(),
    ]);

    MeterReading::factory()->for($organization)->for($property)->for($electricMeter)->create([
        'reading_value' => 135,
        'reading_date' => now()->startOfMonth()->addDays(10)->toDateString(),
    ]);

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Nora Tenant',
    ]);

    $paidInvoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-1001',
            'status' => InvoiceStatus::PAID,
            'total_amount' => 120,
            'amount_paid' => 120,
            'due_date' => now()->startOfMonth()->addDays(7)->toDateString(),
            'billing_period_end' => now()->startOfMonth()->addDays(6)->toDateString(),
            'paid_at' => now()->startOfMonth()->addDays(8)->toDateTimeString(),
        ]);

    $overdueInvoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-2002',
            'status' => InvoiceStatus::OVERDUE,
            'total_amount' => 90,
            'amount_paid' => 20,
            'due_date' => now()->startOfMonth()->addDays(9)->toDateString(),
            'billing_period_end' => now()->startOfMonth()->addDays(8)->toDateString(),
            'paid_at' => null,
        ]);

    $otherOrganization = Organization::factory()->create();
    $otherBuilding = Building::factory()->for($otherOrganization)->create();
    $otherProperty = Property::factory()->for($otherOrganization)->for($otherBuilding)->create();
    $foreignMeter = Meter::factory()->for($otherOrganization)->for($otherProperty)->create([
        'name' => 'Foreign Meter',
        'type' => MeterType::ELECTRICITY,
    ]);

    MeterReading::factory()->for($otherOrganization)->for($otherProperty)->for($foreignMeter)->create([
        'reading_value' => 999,
        'reading_date' => now()->startOfMonth()->addDays(5)->toDateString(),
    ]);

    $foreignTenant = User::factory()->tenant()->create([
        'organization_id' => $otherOrganization->id,
    ]);

    $foreignInvoice = Invoice::factory()
        ->for($otherOrganization)
        ->for($otherProperty)
        ->for($foreignTenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-9999',
            'status' => InvoiceStatus::PAID,
            'total_amount' => 999,
            'amount_paid' => 999,
            'billing_period_end' => now()->startOfMonth()->addDays(5)->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(6)->toDateString(),
            'paid_at' => now()->startOfMonth()->addDays(6)->toDateTimeString(),
        ]);

    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);

    $manager = User::factory()->manager()->create([
        'organization_id' => $organization->id,
    ]);

    $superadmin = User::factory()->superadmin()->create();

    return compact(
        'admin',
        'electricMeter',
        'foreignInvoice',
        'foreignMeter',
        'manager',
        'organization',
        'overdueInvoice',
        'paidInvoice',
        'superadmin',
        'waterMeter',
    );
};

it('shows the shared reports page to admin-like users and hides export actions until a dataset is loaded', function () use ($buildReportingWorkspace) {
    [
        'admin' => $admin,
        'manager' => $manager,
        'superadmin' => $superadmin,
    ] = $buildReportingWorkspace();

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.organization-dashboard'))
        ->assertSuccessful()
        ->assertSee(route('filament.admin.pages.reports'), false);

    $this->actingAs($admin)
        ->get(route('filament.admin.pages.reports'))
        ->assertSuccessful()
        ->assertSeeText('Reports')
        ->assertSeeText('Consumption')
        ->assertSeeText('Revenue')
        ->assertSeeText('Outstanding Balances')
        ->assertSeeText('Meter Compliance')
        ->assertSeeText('Start Date')
        ->assertSeeText('End Date')
        ->assertDontSeeText('Export CSV')
        ->assertDontSeeText('Export PDF');

    $this->actingAs($manager)
        ->get(route('filament.admin.pages.reports'))
        ->assertSuccessful()
        ->assertSeeText('Reports');

    $this->actingAs($superadmin)
        ->get(route('filament.admin.pages.reports'))
        ->assertForbidden();
});

it('loads organization-scoped datasets for each report tab and persists filters between visits', function () use ($buildReportingWorkspace) {
    [
        'admin' => $admin,
        'electricMeter' => $electricMeter,
        'foreignInvoice' => $foreignInvoice,
        'foreignMeter' => $foreignMeter,
        'overdueInvoice' => $overdueInvoice,
        'paidInvoice' => $paidInvoice,
        'waterMeter' => $waterMeter,
    ] = $buildReportingWorkspace();

    $this->actingAs($admin);

    Livewire::test(Reports::class)
        ->set('filters.start_date', now()->startOfMonth()->toDateString())
        ->set('filters.end_date', now()->endOfMonth()->toDateString())
        ->set('filters.meter_type', MeterType::ELECTRICITY->value)
        ->call('loadReport')
        ->assertSet('hasLoadedReport', true)
        ->assertSeeText('Export CSV')
        ->assertSeeText('Export PDF')
        ->assertSeeText($electricMeter->name)
        ->assertSeeText('35.000')
        ->assertDontSeeText($foreignMeter->name)
        ->call('switchTab', 'revenue')
        ->set('filters.invoice_status', InvoiceStatus::PAID->value)
        ->call('loadReport')
        ->assertSeeText($paidInvoice->invoice_number)
        ->assertDontSeeText($overdueInvoice->invoice_number)
        ->assertDontSeeText($foreignInvoice->invoice_number)
        ->call('switchTab', 'outstanding_balances')
        ->set('filters.only_overdue', true)
        ->call('loadReport')
        ->assertSeeText($overdueInvoice->invoice_number)
        ->assertDontSeeText($paidInvoice->invoice_number)
        ->call('switchTab', 'meter_compliance')
        ->set('filters.compliance_state', 'missing')
        ->call('loadReport')
        ->assertSeeText($waterMeter->name)
        ->assertDontSeeText($electricMeter->name)
        ->assertDontSeeText($foreignMeter->name);

    expect(session()->get('filament.admin.reports.'.$admin->id))
        ->toMatchArray([
            'active_tab' => 'meter_compliance',
            'has_loaded_report' => true,
            'filters' => [
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'meter_type' => MeterType::ELECTRICITY->value,
                'invoice_status' => InvoiceStatus::PAID->value,
                'only_overdue' => true,
                'compliance_state' => 'missing',
            ],
        ]);

    Livewire::test(Reports::class)
        ->assertSet('activeTab', 'meter_compliance')
        ->assertSet('hasLoadedReport', true)
        ->assertSet('filters.compliance_state', 'missing')
        ->assertSeeText($waterMeter->name)
        ->assertSeeText('Export CSV')
        ->assertSeeText('Export PDF')
        ->call('exportCsv')
        ->assertFileDownloaded(
            'reports-meter-compliance-'.now()->startOfMonth()->toDateString().'-to-'.now()->endOfMonth()->toDateString().'.csv',
            null,
            'text/csv; charset=UTF-8',
        )
        ->call('exportPdf')
        ->assertFileDownloaded(
            'reports-meter-compliance-'.now()->startOfMonth()->toDateString().'-to-'.now()->endOfMonth()->toDateString().'.pdf',
            null,
            'application/pdf',
        );
});
