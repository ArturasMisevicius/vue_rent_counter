<?php

use App\Enums\InvoiceItemSourceType;
use App\Enums\MoveOutProcessStatus;
use App\Enums\PropertyAssignmentStatus;
use App\Enums\PropertyOccupancyStatus;
use App\Enums\RentalContractStatus;
use App\Enums\TenantStatus;
use App\Filament\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Filament\Actions\Admin\Properties\AssignTenantToPropertyAction;
use App\Filament\Actions\Admin\TenantMoveOut\CompleteTenantMoveOut;
use App\Filament\Actions\Admin\TenantMoveOut\GenerateFinalInvoice;
use App\Filament\Actions\Admin\TenantMoveOut\RecordFinalMoveOutReadings;
use App\Filament\Actions\Admin\TenantMoveOut\ScheduleTenantMoveOut;
use App\Filament\Actions\Tenant\Readings\SubmitTenantReadingAction;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Carbon\Carbon::setTestNow('2026-06-14 10:00:00');
});

it('lets an admin schedule move-out only inside their organization', function (): void {
    $workspace = tenantMoveOutWorkspace();
    $otherAdmin = User::factory()->admin()->create([
        'organization_id' => Organization::factory()->create()->id,
    ]);

    expect(fn () => app(ScheduleTenantMoveOut::class)->handle($otherAdmin, $workspace['assignment'], [
        'move_out_date' => '2026-06-30',
    ]))->toThrow(HttpException::class);

    $process = app(ScheduleTenantMoveOut::class)->handle($workspace['admin'], $workspace['assignment'], [
        'move_out_date' => '2026-06-30',
        'reason' => 'Lease ended.',
    ]);

    expect($process->status)->toBe(MoveOutProcessStatus::SCHEDULED)
        ->and($process->organization_id)->toBe($workspace['organization']->id)
        ->and($workspace['assignment']->fresh()->status)->toBe(PropertyAssignmentStatus::MOVE_OUT_SCHEDULED)
        ->and($workspace['tenant']->fresh()->tenant_status)->toBe(TenantStatus::MOVE_OUT_SCHEDULED)
        ->and($workspace['property']->fresh()->occupancy_status)->toBe(PropertyOccupancyStatus::MOVE_OUT_SCHEDULED)
        ->and(AuditLog::query()->where('description', 'Tenant move-out scheduled')->exists())->toBeTrue();
});

it('requires an active assignment before scheduling move-out', function (): void {
    $workspace = tenantMoveOutWorkspace([
        'status' => PropertyAssignmentStatus::ENDED,
        'unassigned_at' => now()->subDay(),
    ]);

    expect(fn () => app(ScheduleTenantMoveOut::class)->handle($workspace['admin'], $workspace['assignment'], [
        'move_out_date' => '2026-06-30',
    ]))->toThrow(ValidationException::class);
});

it('records final readings, generates final invoice, completes move-out, and preserves history', function (): void {
    $workspace = tenantMoveOutWorkspace();
    $meter = Meter::factory()
        ->for($workspace['organization'])
        ->for($workspace['property'])
        ->create([
            'identifier' => 'EL-100',
            'unit' => 'kWh',
        ]);
    $contract = RentalContract::factory()
        ->for($workspace['organization'])
        ->for($workspace['tenant'], 'tenant')
        ->for($workspace['property'])
        ->for($workspace['assignment'], 'propertyAssignment')
        ->create([
            'status' => RentalContractStatus::ACTIVE,
        ]);

    $process = app(ScheduleTenantMoveOut::class)->handle($workspace['admin'], $workspace['assignment'], [
        'move_out_date' => '2026-06-10',
        'reason' => 'Tenant moved to another unit.',
    ]);

    expect($process->contract_id)->toBe($contract->id);

    $readings = app(RecordFinalMoveOutReadings::class)->handle($workspace['admin'], $process, [[
        'meter_id' => $meter->id,
        'reading_value' => '1280.500',
    ]]);
    $reading = $readings->first();

    expect($reading->move_out_process_id)->toBe($process->id)
        ->and($reading->property_assignment_id)->toBe($workspace['assignment']->id);

    $invoice = app(GenerateFinalInvoice::class)->handle($workspace['admin'], $process->fresh());

    expect($invoice->is_final)->toBeTrue()
        ->and($invoice->move_out_process_id)->toBe($process->id)
        ->and($invoice->property_assignment_id)->toBe($workspace['assignment']->id)
        ->and($invoice->invoiceItems->pluck('source_type')->first())->toBe(InvoiceItemSourceType::METER_READING)
        ->and($invoice->invoiceItems->pluck('source_id'))->toContain($reading->id)
        ->and($reading->fresh()->invoice_id)->toBe($invoice->id);

    $completed = app(CompleteTenantMoveOut::class)->handle($workspace['admin'], $process->fresh());

    expect($completed->status)->toBe(MoveOutProcessStatus::COMPLETED)
        ->and($workspace['assignment']->fresh()->status)->toBe(PropertyAssignmentStatus::ENDED)
        ->and($workspace['assignment']->fresh()->unassigned_at)->not->toBeNull()
        ->and($workspace['tenant']->fresh()->tenant_status)->toBe(TenantStatus::MOVED_OUT)
        ->and($workspace['tenant']->fresh()->portal_access_enabled)->toBeTrue()
        ->and($workspace['property']->fresh()->occupancy_status)->toBe(PropertyOccupancyStatus::VACANT)
        ->and($contract->fresh()->status)->toBe(RentalContractStatus::TERMINATED)
        ->and($workspace['tenant']->invoices()->whereKey($invoice->id)->exists())->toBeTrue()
        ->and(AuditLog::query()->where('description', 'Tenant move-out completed')->exists())->toBeTrue();
});

it('skips ended assignments during monthly invoice generation', function (): void {
    $workspace = tenantMoveOutWorkspace([
        'assigned_at' => '2026-04-01',
    ]);
    $meter = Meter::factory()
        ->for($workspace['organization'])
        ->for($workspace['property'])
        ->create(['identifier' => 'W-1']);

    $process = app(ScheduleTenantMoveOut::class)->handle($workspace['admin'], $workspace['assignment'], [
        'move_out_date' => '2026-05-31',
        'final_readings_required' => false,
    ]);
    $invoice = app(GenerateFinalInvoice::class)->handle($workspace['admin'], $process->fresh(), [
        'allow_without_final_readings' => true,
    ]);

    app(CompleteTenantMoveOut::class)->handle($workspace['admin'], $process->fresh());

    $generated = app(GenerateBulkInvoicesAction::class)->handle(
        $workspace['organization'],
        now()->startOfMonth(),
        now()->endOfMonth(),
    );

    expect($meter->exists)->toBeTrue()
        ->and($invoice->fresh()->is_final)->toBeTrue()
        ->and($generated)->toHaveCount(0)
        ->and(Invoice::query()->where('is_final', false)->count())->toBe(0);
});

it('blocks a new primary tenant while the old primary assignment is active', function (): void {
    $workspace = tenantMoveOutWorkspace();
    $newTenant = User::factory()->tenant()->create([
        'organization_id' => $workspace['organization']->id,
    ]);

    expect(fn () => app(AssignTenantToPropertyAction::class)->handle(
        $workspace['property'],
        $newTenant,
        50,
        now(),
    ))->toThrow(ValidationException::class);
});

it('blocks moved-out tenant reading submissions while preserving historical invoice access', function (): void {
    $workspace = tenantMoveOutWorkspace();
    $meter = Meter::factory()
        ->for($workspace['organization'])
        ->for($workspace['property'])
        ->create();
    $process = app(ScheduleTenantMoveOut::class)->handle($workspace['admin'], $workspace['assignment'], [
        'move_out_date' => '2026-06-10',
        'final_readings_required' => false,
    ]);
    $invoice = app(GenerateFinalInvoice::class)->handle($workspace['admin'], $process->fresh(), [
        'allow_without_final_readings' => true,
    ]);

    app(CompleteTenantMoveOut::class)->handle($workspace['admin'], $process->fresh());

    expect(fn () => app(SubmitTenantReadingAction::class)->handle(
        $workspace['tenant']->fresh(),
        $meter->id,
        '150',
        '2026-06-11',
    ))->toThrow(AuthorizationException::class);

    $this->actingAs($workspace['tenant']->fresh())
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful()
        ->assertSeeText($invoice->invoice_number);
});

/**
 * @param  array<string, mixed>  $assignmentOverrides
 * @return array{organization: Organization, admin: User, building: Building, property: Property, tenant: User, assignment: PropertyAssignment}
 */
function tenantMoveOutWorkspace(array $assignmentOverrides = []): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'occupancy_status' => PropertyOccupancyStatus::OCCUPIED,
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $assignment = PropertyAssignment::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'status' => PropertyAssignmentStatus::ACTIVE,
            'is_primary' => true,
            'assigned_at' => '2026-05-01',
            'unassigned_at' => null,
            ...$assignmentOverrides,
        ]);

    return [
        'organization' => $organization,
        'admin' => $admin,
        'building' => $building,
        'property' => $property,
        'tenant' => $tenant,
        'assignment' => $assignment,
    ];
}
