<?php

declare(strict_types=1);

use App\Enums\AuditLogAction;
use App\Enums\InvoiceItemSourceType;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Filament\Actions\Admin\BillingIntegrity\CancelDuplicateInvoice;
use App\Filament\Actions\Admin\BillingIntegrity\RemoveDuplicateInvoiceItem;
use App\Filament\Actions\Admin\BillingIntegrity\VoidMeterReadingDuplicate;
use App\Filament\Actions\Admin\Invoices\ValidateInvoiceCalculationBeforeApproval;
use App\Filament\Support\Admin\BillingIntegrity\DetectBillingDuplicates;
use App\Models\AuditLog;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceReminderLog;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\User;
use App\Models\UtilityService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('detects duplicate billing records in one organization', function (): void {
    $workspace = billingIntegrityWorkspace();

    [$firstReading, $secondReading] = billingIntegrityDuplicateReadings($workspace);
    $duplicateInvoice = billingIntegrityDuplicateInvoice($workspace, 'INV-DUPLICATE-2');
    $utilityService = UtilityService::factory()->for($workspace['organization'])->create();

    InvoiceItem::factory()->for($workspace['invoice'])->create([
        'source_type' => InvoiceItemSourceType::FIXED_SERVICE,
        'source_id' => null,
        'utility_service_id' => $utilityService->id,
        'total' => '10.00',
    ]);
    InvoiceItem::factory()->for($workspace['invoice'])->create([
        'source_type' => InvoiceItemSourceType::FIXED_SERVICE,
        'source_id' => null,
        'utility_service_id' => $utilityService->id,
        'total' => '20.00',
    ]);
    InvoiceItem::factory()->for($workspace['invoice'])->create([
        'source_type' => InvoiceItemSourceType::EXTRA_CHARGE,
        'source_id' => 987,
        'total' => '7.00',
    ]);
    InvoiceItem::factory()->for($duplicateInvoice)->create([
        'source_type' => InvoiceItemSourceType::EXTRA_CHARGE,
        'source_id' => 987,
        'total' => '7.00',
    ]);
    InvoiceReminderLog::factory()->count(2)->for($workspace['organization'])->for($workspace['invoice'])->create([
        'recipient_email' => $workspace['tenant']->email,
        'channel' => 'email',
        'sent_at' => '2026-05-20 10:00:00',
    ]);

    $issues = app(DetectBillingDuplicates::class)
        ->forOrganization($workspace['organization']->id)
        ->pluck('problemType')
        ->all();

    expect($issues)->toContain(
        'duplicate_active_readings',
        'duplicate_invoices',
        'duplicate_invoice_items',
        'charges_included_twice',
        'duplicate_reminders',
    )
        ->and($firstReading->exists)->toBeTrue()
        ->and($secondReading->exists)->toBeTrue();
});

it('shows the cleanup center to admins and blocks tenants', function (): void {
    $workspace = billingIntegrityWorkspace();
    billingIntegrityDuplicateReadings($workspace);

    $this->actingAs($workspace['admin'])
        ->get(route('filament.admin.pages.billing-cleanup-center'))
        ->assertSuccessful()
        ->assertSee('Cleanup &amp; Duplicates', false)
        ->assertSee('Duplicate active readings detected');

    $this->actingAs($workspace['tenant'])
        ->get(route('filament.admin.pages.billing-cleanup-center'))
        ->assertForbidden();
});

it('filters cleanup center issues by dashboard attention key', function (): void {
    $workspace = billingIntegrityWorkspace();
    billingIntegrityDuplicateReadings($workspace);
    billingIntegrityDuplicateInvoice($workspace, 'INV-DUPLICATE-FILTER');

    $this->actingAs($workspace['admin'])
        ->get(route('filament.admin.pages.billing-cleanup-center', [
            'attention' => 'duplicate_active_readings',
        ]))
        ->assertSuccessful()
        ->assertSee('Duplicate active readings detected')
        ->assertDontSee('Duplicate invoices for the same tenant, property, and period');
});

it('voids duplicate readings only with a reason and writes audit history', function (): void {
    $workspace = billingIntegrityWorkspace();
    [$keepReading, $duplicateReading] = billingIntegrityDuplicateReadings($workspace);

    expect(fn () => app(VoidMeterReadingDuplicate::class)->handle($keepReading, [$duplicateReading->id], '', $workspace['admin']))
        ->toThrow(ValidationException::class);

    $kept = app(VoidMeterReadingDuplicate::class)->handle(
        $keepReading,
        [$duplicateReading->id],
        'Duplicate tenant submission.',
        $workspace['admin'],
    );

    expect($kept->validation_status)->toBe(MeterReadingValidationStatus::VALID)
        ->and($duplicateReading->fresh()->validation_status)->toBe(MeterReadingValidationStatus::VOID)
        ->and(AuditLog::query()
            ->forSubject($duplicateReading->fresh())
            ->where('action', AuditLogAction::DELETED)
            ->exists())->toBeTrue();
});

it('cancels duplicate draft invoices only inside the actor organization', function (): void {
    $workspace = billingIntegrityWorkspace();
    $duplicateInvoice = billingIntegrityDuplicateInvoice($workspace, 'INV-DUPLICATE-CANCEL');

    expect(fn () => app(CancelDuplicateInvoice::class)->handle($workspace['invoice'], $duplicateInvoice, '', $workspace['admin']))
        ->toThrow(ValidationException::class);

    $cancelled = app(CancelDuplicateInvoice::class)->handle(
        $workspace['invoice'],
        $duplicateInvoice,
        'Scheduler created the same draft twice.',
        $workspace['admin'],
    );

    expect($cancelled->status)->toBe(InvoiceStatus::VOID)
        ->and($cancelled->approval_status)->toBe('voided_duplicate')
        ->and(AuditLog::query()
            ->forSubject($cancelled)
            ->where('action', AuditLogAction::REJECTED)
            ->exists())->toBeTrue();

    $otherWorkspace = billingIntegrityWorkspace();
    $otherDuplicate = billingIntegrityDuplicateInvoice($otherWorkspace, 'INV-OTHER-DUP');

    expect(fn () => app(CancelDuplicateInvoice::class)->handle(
        $otherWorkspace['invoice'],
        $otherDuplicate,
        'Cross organization attempt.',
        $workspace['admin'],
    ))->toThrow(ValidationException::class);
});

it('voids duplicate draft invoice items and excludes them from totals', function (): void {
    $workspace = billingIntegrityWorkspace();
    $utilityService = UtilityService::factory()->for($workspace['organization'])->create();
    $keepItem = InvoiceItem::factory()->for($workspace['invoice'])->create([
        'source_type' => InvoiceItemSourceType::FIXED_SERVICE,
        'source_id' => null,
        'utility_service_id' => $utilityService->id,
        'total' => '10.00',
        'sort_order' => 1,
    ]);
    $duplicateItem = InvoiceItem::factory()->for($workspace['invoice'])->create([
        'source_type' => InvoiceItemSourceType::FIXED_SERVICE,
        'source_id' => null,
        'utility_service_id' => $utilityService->id,
        'total' => '20.00',
        'sort_order' => 2,
    ]);

    $invoice = app(RemoveDuplicateInvoiceItem::class)->handle(
        $keepItem,
        $duplicateItem,
        'Same fixed service added twice.',
        $workspace['admin'],
    );

    expect($duplicateItem->fresh()->voided_at)->not->toBeNull()
        ->and((string) $invoice->total_amount)->toBe('10.00')
        ->and($invoice->items)->toHaveCount(1)
        ->and(AuditLog::query()
            ->forSubject($duplicateItem->fresh())
            ->where('action', AuditLogAction::REJECTED)
            ->exists())->toBeTrue();
});

it('keeps the database-level invoice item source uniqueness in place', function (): void {
    $workspace = billingIntegrityWorkspace();

    InvoiceItem::factory()->for($workspace['invoice'])->create([
        'source_type' => InvoiceItemSourceType::EXTRA_CHARGE,
        'source_id' => 321,
    ]);

    expect(fn () => InvoiceItem::factory()->for($workspace['invoice'])->create([
        'source_type' => InvoiceItemSourceType::EXTRA_CHARGE,
        'source_id' => 321,
    ]))->toThrow(QueryException::class);
});

it('blocks invoice approval when unresolved duplicates exist', function (): void {
    $workspace = billingIntegrityWorkspace();
    $readings = billingIntegrityDuplicateReadings($workspace);

    InvoiceItem::factory()->for($workspace['invoice'])->create([
        'source_type' => InvoiceItemSourceType::EXTRA_CHARGE,
        'source_id' => 654,
        'description_for_tenant' => 'Parking fee',
        'total' => '15.00',
        'currency' => 'EUR',
    ]);
    $invoice = $workspace['invoice']->fresh();

    expect((int) $invoice->property_id)->toBe((int) $workspace['property']->id)
        ->and((int) $readings[0]->organization_id)->toBe((int) $workspace['organization']->id)
        ->and((int) $readings[0]->property_id)->toBe((int) $workspace['property']->id)
        ->and($readings[0]->reading_date?->toDateString())->toBe('2026-05-31')
        ->and($invoice->billing_period_start?->toDateString())->toBe('2026-05-01')
        ->and($invoice->billing_period_end?->toDateString())->toBe('2026-05-31')
        ->and(MeterReading::query()
            ->forOrganization($workspace['organization']->id)
            ->where('property_id', $workspace['property']->id)
            ->betweenDates('2026-05-01', '2026-05-31')
            ->count())->toBe(2);

    expect(app(DetectBillingDuplicates::class)
        ->forInvoice($invoice)
        ->pluck('problemType')
        ->all())->toContain('duplicate_active_readings');

    expect(fn () => app(ValidateInvoiceCalculationBeforeApproval::class)->handle($invoice))
        ->toThrow(ValidationException::class, 'Duplicate active readings detected');
});

/**
 * @return array{
 *     organization: Organization,
 *     admin: User,
 *     tenant: User,
 *     property: Property,
 *     meter: Meter,
 *     invoice: Invoice
 * }
 */
function billingIntegrityWorkspace(): array
{
    $workspace = createOrgWithAdmin();
    $organization = $workspace['organization'];
    $admin = $workspace['admin'];
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $meter = Meter::factory()->for($organization)->for($property)->create();
    $invoice = Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'invoice_number' => 'INV-INTEGRITY-'.str()->ulid(),
            'billing_period_start' => '2026-05-01',
            'billing_period_end' => '2026-05-31',
            'status' => InvoiceStatus::DRAFT,
            'finalized_at' => null,
            'total_amount' => '0.00',
            'items' => [],
            'snapshot_data' => [],
            'approval_status' => 'ready_for_review',
        ]);

    return [
        'organization' => $organization,
        'admin' => $admin,
        'tenant' => $tenant,
        'property' => $property,
        'meter' => $meter,
        'invoice' => $invoice,
    ];
}

/**
 * @param  array{organization: Organization, tenant: User, property: Property, meter: Meter}  $workspace
 * @return array{0: MeterReading, 1: MeterReading}
 */
function billingIntegrityDuplicateReadings(array $workspace): array
{
    return [
        MeterReading::factory()
            ->for($workspace['organization'])
            ->for($workspace['property'])
            ->for($workspace['meter'])
            ->for($workspace['tenant'], 'submittedBy')
            ->create([
                'reading_value' => '120.000',
                'reading_date' => '2026-05-31',
                'validation_status' => MeterReadingValidationStatus::VALID,
            ]),
        MeterReading::factory()
            ->for($workspace['organization'])
            ->for($workspace['property'])
            ->for($workspace['meter'])
            ->for($workspace['tenant'], 'submittedBy')
            ->create([
                'reading_value' => '121.000',
                'reading_date' => '2026-05-31',
                'validation_status' => MeterReadingValidationStatus::PENDING,
            ]),
    ];
}

/**
 * @param  array{organization: Organization, tenant: User, property: Property}  $workspace
 */
function billingIntegrityDuplicateInvoice(array $workspace, string $number): Invoice
{
    return Invoice::factory()
        ->for($workspace['organization'])
        ->for($workspace['property'])
        ->for($workspace['tenant'], 'tenant')
        ->create([
            'invoice_number' => $number,
            'billing_period_start' => '2026-05-01',
            'billing_period_end' => '2026-05-31',
            'status' => InvoiceStatus::DRAFT,
            'finalized_at' => null,
            'total_amount' => '0.00',
            'items' => [],
            'snapshot_data' => [],
        ]);
}
