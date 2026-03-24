<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Filament\Support\Admin\Dashboard\AdminDashboardStats;
use App\Filament\Support\Admin\Reports\OutstandingBalancesReportBuilder;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('uses due_date before billing_period_end when filtering overdue invoices', function (): void {
    [
        'organization' => $organization,
    ] = seedOverduePolicyWorkspace();

    $report = app(OutstandingBalancesReportBuilder::class)->build(
        $organization->id,
        Carbon::now()->subMonth()->startOfMonth(),
        Carbon::now()->addWeek()->endOfDay(),
        [
            'building_id' => null,
            'property_id' => null,
            'tenant_id' => null,
            'only_overdue' => true,
            'status_filter' => 'overdue',
        ],
    );

    $rows = collect($report['rows']);
    $overdueRow = $rows->sole();

    expect($rows)->toHaveCount(1)
        ->and($overdueRow['invoice_number'])->toBe('INV-DUE-FIRST')
        ->and($overdueRow['status'])->toBe(__('admin.invoices.statuses.overdue'))
        ->and((int) $overdueRow['days_overdue'])->toBe(3);
});

it('shows dashboard invoice statuses using the same due-date-first overdue policy', function (): void {
    [
        'admin' => $admin,
    ] = seedOverduePolicyWorkspace();

    $recentInvoices = collect(app(AdminDashboardStats::class)->recentInvoicesFor($admin));

    expect(data_get($recentInvoices->firstWhere('number', 'INV-DUE-FIRST'), 'status'))
        ->toBe(__('admin.invoices.statuses.overdue'))
        ->and(data_get($recentInvoices->firstWhere('number', 'INV-NOT-DUE-YET'), 'status'))
        ->toBe(__('admin.invoices.statuses.finalized'));
});

/**
 * @return array{organization: Organization, admin: User}
 */
function seedOverduePolicyWorkspace(): array
{
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
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

    Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-NOT-DUE-YET',
            'status' => InvoiceStatus::FINALIZED,
            'total_amount' => 120,
            'amount_paid' => 0,
            'paid_amount' => 0,
            'billing_period_start' => now()->subDays(20)->toDateString(),
            'billing_period_end' => now()->subDays(10)->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
        ]);

    Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-DUE-FIRST',
            'status' => InvoiceStatus::FINALIZED,
            'total_amount' => 90,
            'amount_paid' => 20,
            'paid_amount' => 20,
            'billing_period_start' => now()->subDays(18)->toDateString(),
            'billing_period_end' => now()->subDays(12)->toDateString(),
            'due_date' => now()->subDays(3)->toDateString(),
        ]);

    return [
        'organization' => $organization,
        'admin' => $admin,
    ];
}
