<?php

use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Filament\Pages\Reports;
use App\Filament\Support\Admin\Reports\ReportExportService;
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

it('returns only the authenticated admins organization data in the consumption report', function () {
    [
        'admin' => $admin,
    ] = seedBillingReportsFeatureWorkspace();

    $component = Livewire::actingAs($admin)
        ->test(Reports::class)
        ->set('activeTab', 'consumption')
        ->set('dateFrom', now()->startOfMonth()->toDateString())
        ->set('dateTo', now()->endOfMonth()->toDateString());

    $report = $component->instance()->report();
    $rows = collect($report['rows']);

    expect($rows)->toHaveCount(1)
        ->and($rows->pluck('tenant')->all())->toContain('Nora Tenant')
        ->and($rows->pluck('tenant')->all())->not->toContain('Foreign Tenant')
        ->and($rows->pluck('building')->all())->toContain('North Tower')
        ->and($rows->pluck('building')->all())->not->toContain('Outside Plaza');

    $component
        ->assertSeeText('Nora Tenant')
        ->assertDontSeeText('Foreign Tenant');
});

it('matches monthly paid totals in the revenue report', function () {
    [
        'admin' => $admin,
        'paidInvoiceCurrentMonth' => $paidInvoiceCurrentMonth,
        'paidInvoicePreviousMonth' => $paidInvoicePreviousMonth,
    ] = seedBillingReportsFeatureWorkspace();

    $component = Livewire::actingAs($admin)
        ->test(Reports::class)
        ->set('activeTab', 'revenue')
        ->set('dateFrom', now()->subMonth()->startOfMonth()->toDateString())
        ->set('dateTo', now()->endOfMonth()->toDateString())
        ->set('statusFilter', InvoiceStatus::PAID->value);

    $rows = collect($component->instance()->report()['rows'])->keyBy('month');

    expect($rows)->toHaveCount(2)
        ->and($rows[(string) $paidInvoiceCurrentMonth->billing_period_end?->format('Y-m')]['total_paid'] ?? null)
        ->toBe('EUR 120.00')
        ->and($rows[(string) $paidInvoiceCurrentMonth->billing_period_end?->format('Y-m')]['total_invoiced'] ?? null)
        ->toBe('EUR 120.00')
        ->and($rows[(string) $paidInvoicePreviousMonth->billing_period_end?->format('Y-m')]['total_paid'] ?? null)
        ->toBe('EUR 200.00')
        ->and($rows[(string) $paidInvoicePreviousMonth->billing_period_end?->format('Y-m')]['total_invoiced'] ?? null)
        ->toBe('EUR 200.00');
});

it('identifies overdue invoices in the outstanding balances report', function () {
    [
        'admin' => $admin,
        'overdueInvoice' => $overdueInvoice,
    ] = seedBillingReportsFeatureWorkspace();

    $component = Livewire::actingAs($admin)
        ->test(Reports::class)
        ->set('activeTab', 'outstanding_balances')
        ->set('dateFrom', now()->subMonth()->startOfMonth()->toDateString())
        ->set('dateTo', now()->endOfMonth()->toDateString())
        ->set('statusFilter', 'overdue');

    $rows = collect($component->instance()->report()['rows']);
    $row = $rows->firstWhere('invoice_number', $overdueInvoice->invoice_number);

    expect($row)->not->toBeNull()
        ->and($row['status'] ?? null)->toBe(__('admin.invoices.statuses.overdue'))
        ->and((int) ($row['days_overdue'] ?? 0))->toBeGreaterThan(0);

    $component
        ->assertSeeText($overdueInvoice->invoice_number)
        ->assertSeeText(__('admin.invoices.statuses.overdue'));
});

it('streams a csv export with the expected report headers', function () {
    [
        'admin' => $admin,
    ] = seedBillingReportsFeatureWorkspace();

    $component = Livewire::actingAs($admin)
        ->test(Reports::class)
        ->set('activeTab', 'consumption')
        ->set('dateFrom', now()->startOfMonth()->toDateString())
        ->set('dateTo', now()->endOfMonth()->toDateString());

    $response = $component->instance()->exportCsv(app(ReportExportService::class));

    ob_start();
    $response->sendContent();
    $csv = (string) ob_get_clean();

    expect($response->headers->get('content-type'))->toContain('text/csv')
        ->and($csv)->toContain('Consumption')
        ->and($csv)->toContain('Tenant,Building,Property,Type');
});

it('blocks tenant users from the reports page', function () {
    [
        'tenant' => $tenant,
    ] = seedBillingReportsFeatureWorkspace();

    $response = $this->actingAs($tenant)
        ->get(route('filament.admin.pages.reports'));

    expect($response->getStatusCode())->toBeIn([302, 403]);
});

function seedBillingReportsFeatureWorkspace(): array
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

    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
        'name' => 'Nora Tenant',
        'email' => 'nora@example.test',
    ]);

    PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonths(2),
            'unassigned_at' => null,
        ]);

    $electricMeter = Meter::factory()->for($organization)->for($property)->create([
        'name' => 'Electric Main',
        'type' => MeterType::ELECTRICITY,
        'unit' => 'kWh',
    ]);

    MeterReading::factory()->for($organization)->for($property)->for($electricMeter)->create([
        'reading_value' => 100,
        'reading_date' => now()->startOfMonth()->addDay()->toDateString(),
    ]);

    MeterReading::factory()->for($organization)->for($property)->for($electricMeter)->create([
        'reading_value' => 135,
        'reading_date' => now()->startOfMonth()->addDays(10)->toDateString(),
    ]);

    $paidInvoiceCurrentMonth = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-PAID-001',
            'status' => InvoiceStatus::PAID,
            'total_amount' => 120,
            'amount_paid' => 120,
            'paid_amount' => 120,
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->startOfMonth()->addDays(5)->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(12)->toDateString(),
            'paid_at' => now()->startOfMonth()->addDays(6)->toDateTimeString(),
        ]);

    $paidInvoicePreviousMonth = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-PAID-002',
            'status' => InvoiceStatus::PAID,
            'total_amount' => 200,
            'amount_paid' => 200,
            'paid_amount' => 200,
            'billing_period_start' => now()->subMonth()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->subMonth()->endOfMonth()->toDateString(),
            'due_date' => now()->subMonth()->endOfMonth()->toDateString(),
            'paid_at' => now()->subMonth()->endOfMonth()->addDay()->toDateTimeString(),
        ]);

    $overdueInvoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-OVERDUE-001',
            'status' => InvoiceStatus::OVERDUE,
            'total_amount' => 90,
            'amount_paid' => 20,
            'paid_amount' => 20,
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
            'paid_at' => null,
        ]);

    $foreignOrganization = Organization::factory()->create();
    $foreignBuilding = Building::factory()->for($foreignOrganization)->create([
        'name' => 'Outside Plaza',
    ]);
    $foreignProperty = Property::factory()->for($foreignOrganization)->for($foreignBuilding)->create([
        'name' => 'B-99',
    ]);
    $foreignTenant = User::factory()->tenant()->create([
        'organization_id' => $foreignOrganization->id,
        'name' => 'Foreign Tenant',
    ]);

    PropertyAssignment::factory()
        ->for($foreignOrganization)
        ->for($foreignProperty)
        ->for($foreignTenant, 'tenant')
        ->create([
            'assigned_at' => now()->subMonths(2),
            'unassigned_at' => null,
        ]);

    $foreignMeter = Meter::factory()->for($foreignOrganization)->for($foreignProperty)->create([
        'name' => 'Foreign Meter',
        'type' => MeterType::ELECTRICITY,
        'unit' => 'kWh',
    ]);

    MeterReading::factory()->for($foreignOrganization)->for($foreignProperty)->for($foreignMeter)->create([
        'reading_value' => 999,
        'reading_date' => now()->startOfMonth()->addDays(5)->toDateString(),
    ]);

    Invoice::factory()
        ->for($foreignOrganization)
        ->for($foreignProperty)
        ->for($foreignTenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-FOREIGN-001',
            'status' => InvoiceStatus::PAID,
            'total_amount' => 999,
            'amount_paid' => 999,
            'paid_amount' => 999,
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->startOfMonth()->addDays(5)->toDateString(),
            'due_date' => now()->startOfMonth()->addDays(6)->toDateString(),
            'paid_at' => now()->startOfMonth()->addDays(6)->toDateTimeString(),
        ]);

    return compact(
        'admin',
        'building',
        'electricMeter',
        'organization',
        'overdueInvoice',
        'paidInvoiceCurrentMonth',
        'paidInvoicePreviousMonth',
        'property',
        'tenant',
    );
}
