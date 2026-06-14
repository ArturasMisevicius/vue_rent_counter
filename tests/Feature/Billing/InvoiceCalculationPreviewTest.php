<?php

declare(strict_types=1);

use App\Enums\AuditLogAction;
use App\Enums\BillingMethod;
use App\Enums\DistributionMethod;
use App\Enums\ExtraChargeStatus;
use App\Enums\InvoiceItemSourceType;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Actions\Admin\Invoices\AddManualInvoiceAdjustment;
use App\Filament\Actions\Admin\Invoices\CalculateInvoice;
use App\Filament\Actions\Admin\Invoices\FinalizeInvoiceAction;
use App\Filament\Actions\Admin\Invoices\GenerateBulkInvoicesAction;
use App\Filament\Actions\Admin\Invoices\RecalculateInvoice;
use App\Filament\Actions\Admin\Invoices\ValidateInvoiceCalculationBeforeApproval;
use App\Models\AuditLog;
use App\Models\BillingPeriod;
use App\Models\Building;
use App\Models\ExtraCharge;
use App\Models\ExtraChargeType;
use App\Models\Invoice;
use App\Models\InvoiceGenerationAudit;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\Provider;
use App\Models\ServiceConfiguration;
use App\Models\Tariff;
use App\Models\User;
use App\Models\UtilityService;
use App\Services\Billing\InvoicePresentationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\TenantPortalFactory;

uses(RefreshDatabase::class);

it('builds meter based and fixed service calculation preview rows with snapshots', function (): void {
    $scenario = invoiceCalculationPreviewScenario();
    invoiceCalculationPreviewFixedService($scenario);

    $preview = resolve(CalculateInvoice::class)->handle($scenario['organization'], [
        'tenant_user_id' => $scenario['tenant']->id,
        'billing_period_start' => '2026-05-01',
        'billing_period_end' => '2026-05-31',
    ]);

    $rows = collect($preview['items']);
    $meterRow = $rows->firstWhere('source_type', InvoiceItemSourceType::METER_READING->value);
    $fixedRow = $rows->firstWhere('source_type', InvoiceItemSourceType::FIXED_SERVICE->value);

    expect($preview['total_amount'])->toBe('42.50')
        ->and($meterRow['source_id'])->toBe($scenario['endReading']->id)
        ->and($meterRow['quantity'])->toBe('10.000')
        ->and($meterRow['unit_price'])->toBe('1.7500')
        ->and($meterRow['total'])->toBe('17.50')
        ->and(data_get($meterRow, 'calculation_snapshot.meter_reading_snapshot.start.value'))->toBe('50.000')
        ->and(data_get($meterRow, 'calculation_snapshot.meter_reading_snapshot.end.value'))->toBe('60.000')
        ->and(data_get($meterRow, 'calculation_snapshot.tariff_snapshot.configuration.rate'))->toBe(1.75)
        ->and($fixedRow['quantity'])->toBe('1.000')
        ->and($fixedRow['unit_price'])->toBe('25.0000')
        ->and($fixedRow['total'])->toBe('25.00')
        ->and($fixedRow['source_id'])->toBe($fixedRow['service_configuration_id'])
        ->and($fixedRow['description_for_tenant'])->toBe('Internet monthly service');
});

it('includes only approved extra charges and discount rows in the calculation preview', function (): void {
    $scenario = invoiceCalculationPreviewScenario(withMeterService: false);
    $billingPeriod = BillingPeriod::factory()->for($scenario['organization'])->create([
        'starts_at' => '2026-05-01',
        'ends_at' => '2026-05-31',
        'name' => 'May 2026',
    ]);
    $chargeType = ExtraChargeType::factory()->for($scenario['organization'])->create([
        'tenant_visible_by_default' => true,
    ]);
    $discountType = ExtraChargeType::factory()->discount()->for($scenario['organization'])->create([
        'tenant_visible_by_default' => true,
    ]);

    ExtraCharge::factory()->for($scenario['organization'])->for($scenario['property'])->create([
        'tenant_id' => $scenario['tenant']->id,
        'billing_period_id' => $billingPeriod->id,
        'extra_charge_type_id' => $chargeType->id,
        'title' => 'Parking remote replacement',
        'description_for_tenant' => 'Replacement remote',
        'amount' => '30.00',
        'unit_price' => '30.0000',
        'total_amount' => '30.00',
        'status' => ExtraChargeStatus::APPROVED,
        'starts_at' => '2026-05-10',
        'ends_at' => '2026-05-10',
    ]);
    ExtraCharge::factory()->for($scenario['organization'])->for($scenario['property'])->create([
        'tenant_id' => $scenario['tenant']->id,
        'billing_period_id' => $billingPeriod->id,
        'extra_charge_type_id' => $discountType->id,
        'title' => 'Service discount',
        'description_for_tenant' => 'Goodwill discount',
        'amount' => '-5.00',
        'unit_price' => '-5.0000',
        'total_amount' => '-5.00',
        'status' => ExtraChargeStatus::APPROVED,
        'starts_at' => '2026-05-10',
        'ends_at' => '2026-05-10',
    ]);
    ExtraCharge::factory()->for($scenario['organization'])->for($scenario['property'])->create([
        'tenant_id' => $scenario['tenant']->id,
        'billing_period_id' => $billingPeriod->id,
        'extra_charge_type_id' => $chargeType->id,
        'title' => 'Pending storage fee',
        'total_amount' => '12.00',
        'status' => ExtraChargeStatus::PENDING_REVIEW,
        'starts_at' => '2026-05-10',
        'ends_at' => '2026-05-10',
    ]);

    $preview = resolve(CalculateInvoice::class)->handle($scenario['organization'], [
        'tenant_user_id' => $scenario['tenant']->id,
        'billing_period_start' => '2026-05-01',
        'billing_period_end' => '2026-05-31',
    ]);

    expect($preview['total_amount'])->toBe('25.00')
        ->and($preview['items'])->toHaveCount(2)
        ->and(collect($preview['items'])->pluck('title')->all())->toContain('Parking remote replacement', 'Service discount')
        ->and(collect($preview['items'])->pluck('title')->all())->not->toContain('Pending storage fee')
        ->and(collect($preview['items'])->pluck('source_type')->unique()->values()->all())->toBe([InvoiceItemSourceType::EXTRA_CHARGE->value])
        ->and(collect($preview['items'])->firstWhere('title', 'Service discount')['discount_amount'])->toBe('-5.00');
});

it('stores invoice totals and tariff snapshots so old invoices do not change after tariff updates', function (): void {
    $scenario = invoiceCalculationPreviewScenario();

    $result = resolve(GenerateBulkInvoicesAction::class)->handle($scenario['organization'], [
        'billing_period_start' => '2026-05-01',
        'billing_period_end' => '2026-05-31',
        'due_date' => '2026-06-14',
    ], $scenario['admin']);

    /** @var Invoice $invoice */
    $invoice = $result['created']->sole();
    $item = $invoice->fresh(['invoiceItems'])->invoiceItems->sole();

    expect((string) $invoice->total_amount)->toBe('17.50')
        ->and($item->source_type)->toBe(InvoiceItemSourceType::METER_READING)
        ->and($item->total)->toBe('17.50')
        ->and(data_get($item->calculation_snapshot, 'tariff_snapshot.configuration.rate'))->toBe(1.75);

    $scenario['tariff']->update([
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 9.99,
        ],
    ]);

    $unchangedItem = $item->fresh();

    expect((string) $invoice->fresh()->total_amount)->toBe('17.50')
        ->and($unchangedItem->total)->toBe('17.50')
        ->and(data_get($unchangedItem->calculation_snapshot, 'tariff_snapshot.configuration.rate'))->toBe(1.75);
});

it('requires an internal reason for manual corrections and stores the adjustment snapshot', function (): void {
    $invoice = invoiceCalculationPreviewDraftInvoice([
        invoiceCalculationPreviewRow([
            'source_type' => InvoiceItemSourceType::EXTRA_CHARGE->value,
            'source_id' => null,
            'tariff_id' => null,
            'total' => '100.00',
            'subtotal' => '100.00',
            'unit_price' => '100.0000',
            'tariff_snapshot' => null,
            'calculation_snapshot' => [
                'source_status' => 'approved',
            ],
        ]),
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $invoice->organization_id,
    ]);

    expect(fn () => resolve(AddManualInvoiceAdjustment::class)->handle($invoice, [
        'amount' => '-2.50',
        'description_for_tenant' => 'Manual correction',
        'internal_note' => '',
    ], $admin))->toThrow(ValidationException::class);

    $updatedInvoice = resolve(AddManualInvoiceAdjustment::class)->handle($invoice, [
        'amount' => '-2.50',
        'description_for_tenant' => 'Manual correction',
        'internal_note' => 'Manager approved correction for duplicate charge.',
    ], $admin);

    $adjustment = $updatedInvoice->invoiceItems
        ->firstWhere('source_type', InvoiceItemSourceType::MANUAL_ADJUSTMENT);

    expect((string) $updatedInvoice->total_amount)->toBe('97.50')
        ->and($adjustment)->not->toBeNull()
        ->and($adjustment->internal_note)->toBe('Manager approved correction for duplicate charge.')
        ->and(data_get($adjustment->calculation_snapshot, 'source_type'))->toBe(InvoiceItemSourceType::MANUAL_ADJUSTMENT->value)
        ->and($adjustment->total)->toBe('-2.50');
});

it('blocks invoice approval when calculation rows violate hard rules', function (array $rowOverrides): void {
    $invoice = invoiceCalculationPreviewDraftInvoice([
        invoiceCalculationPreviewRow($rowOverrides),
    ]);

    expect(fn () => resolve(ValidateInvoiceCalculationBeforeApproval::class)->handle($invoice))
        ->toThrow(ValidationException::class);
})->with([
    'unapproved readings' => [[
        'source_type' => InvoiceItemSourceType::METER_READING->value,
        'meter_reading_snapshot' => invoiceCalculationPreviewMeterSnapshot(MeterReadingValidationStatus::VALID, MeterReadingValidationStatus::PENDING),
    ]],
    'rejected readings' => [[
        'source_type' => InvoiceItemSourceType::METER_READING->value,
        'meter_reading_snapshot' => invoiceCalculationPreviewMeterSnapshot(MeterReadingValidationStatus::VALID, MeterReadingValidationStatus::REJECTED),
    ]],
    'missing tariff' => [[
        'source_type' => InvoiceItemSourceType::METER_READING->value,
        'tariff_id' => null,
        'tariff_snapshot' => null,
        'calculation_snapshot' => [
            'source_status' => 'approved',
            'tariff_snapshot' => null,
        ],
    ]],
    'pending source charge' => [[
        'source_type' => InvoiceItemSourceType::EXTRA_CHARGE->value,
        'calculation_snapshot' => [
            'source_status' => 'pending',
        ],
    ]],
    'empty amount' => [[
        'total' => '',
    ]],
    'currency mismatch' => [[
        'currency' => 'USD',
    ]],
    'tenant description missing' => [[
        'description_for_tenant' => '',
    ]],
    'negative consumption' => [[
        'source_type' => InvoiceItemSourceType::METER_READING->value,
        'meter_reading_snapshot' => invoiceCalculationPreviewMeterSnapshot(
            MeterReadingValidationStatus::VALID,
            MeterReadingValidationStatus::VALID,
            negativeConsumption: true,
        ),
    ]],
]);

it('approves invoices with warnings only after explicit confirmation', function (): void {
    $invoice = invoiceCalculationPreviewDraftInvoice([
        invoiceCalculationPreviewRow([
            'source_type' => InvoiceItemSourceType::METER_READING->value,
            'meter_reading_snapshot' => invoiceCalculationPreviewMeterSnapshot(
                MeterReadingValidationStatus::VALID,
                MeterReadingValidationStatus::FLAGGED,
            ),
        ]),
    ]);
    $admin = User::factory()->admin()->create([
        'organization_id' => $invoice->organization_id,
    ]);

    $this->actingAs($admin);

    expect(fn () => resolve(FinalizeInvoiceAction::class)->handle($invoice))
        ->toThrow(ValidationException::class);

    $result = resolve(ValidateInvoiceCalculationBeforeApproval::class)->handle($invoice, allowWarnings: true);
    $finalized = resolve(FinalizeInvoiceAction::class)->handle($invoice, [
        'approve_with_warnings' => true,
    ]);

    expect($result['warnings'])->not->toBeEmpty()
        ->and($finalized->status)->toBe(InvoiceStatus::FINALIZED);
});

it('shows tenant-visible breakdown details while hiding internal notes', function (): void {
    $fixture = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->create();
    $row = invoiceCalculationPreviewRow([
        'source_type' => InvoiceItemSourceType::METER_READING->value,
        'title' => 'Water usage',
        'description' => 'Admin water usage',
        'description_for_tenant' => 'Tenant water explanation',
        'internal_note' => 'Secret admin-only adjustment reason',
        'formula_label' => '10.000 m3 x 1.7500',
        'meter_reading_snapshot' => invoiceCalculationPreviewMeterSnapshot(),
    ]);

    $invoice = Invoice::factory()
        ->for($fixture->organization)
        ->for($fixture->property)
        ->for($fixture->user, 'tenant')
        ->create([
            'invoice_number' => 'INV-PREVIEW-TENANT',
            'status' => InvoiceStatus::FINALIZED,
            'finalized_at' => now(),
            'total_amount' => '17.50',
            'items' => [$row],
            'snapshot_data' => [$row],
        ]);

    $presentation = resolve(InvoicePresentationService::class)->present($invoice->fresh());
    $item = $presentation['items'][0];

    expect($item)
        ->toMatchArray([
            'description' => 'Tenant water explanation',
            'source_type' => InvoiceItemSourceType::METER_READING->value,
            'source_label' => __('admin.invoices.source_types.meter_reading'),
            'formula_label' => '10.000 m3 x 1.7500',
        ])
        ->and(data_get($item, 'meter_reading_snapshot.start.value'))->toBe('50.000')
        ->and(data_get($item, 'meter_reading_snapshot.end.value'))->toBe('60.000');

    $this->actingAs($fixture->user)
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful()
        ->assertSeeText('Tenant water explanation')
        ->assertDontSeeText('Secret admin-only adjustment reason');
});

it('prevents sent or paid invoices from being silently recalculated', function (): void {
    $invoice = invoiceCalculationPreviewDraftInvoice([
        invoiceCalculationPreviewRow(),
    ], [
        'status' => InvoiceStatus::PAID,
        'finalized_at' => now(),
        'paid_at' => now(),
    ]);

    expect(fn () => resolve(RecalculateInvoice::class)->handle($invoice))
        ->toThrow(ValidationException::class);
});

it('recalculates draft invoices and writes audit records', function (): void {
    $scenario = invoiceCalculationPreviewScenario();
    $invoice = Invoice::factory()
        ->for($scenario['organization'])
        ->for($scenario['property'])
        ->for($scenario['tenant'], 'tenant')
        ->create([
            'status' => InvoiceStatus::DRAFT,
            'finalized_at' => null,
            'billing_period_start' => '2026-05-01',
            'billing_period_end' => '2026-05-31',
            'total_amount' => '0.00',
            'items' => [],
            'snapshot_data' => [],
        ]);

    $updatedInvoice = resolve(RecalculateInvoice::class)->handle($invoice, $scenario['admin']);

    expect((string) $updatedInvoice->total_amount)->toBe('17.50')
        ->and(InvoiceGenerationAudit::query()
            ->where('invoice_id', $invoice->id)
            ->where('metadata->context->mutation', 'invoice.recalculated')
            ->exists())->toBeTrue()
        ->and(AuditLog::query()
            ->forSubject($updatedInvoice)
            ->where('action', AuditLogAction::UPDATED)
            ->where('metadata->context->mutation', 'invoice.recalculated')
            ->exists())->toBeTrue();
});

it('keeps tenant invoice breakdown isolated by organization', function (): void {
    $firstTenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->create();
    $secondTenant = TenantPortalFactory::new()
        ->withAssignedProperty()
        ->create();

    Invoice::factory()
        ->for($firstTenant->organization)
        ->for($firstTenant->property)
        ->for($firstTenant->user, 'tenant')
        ->create([
            'invoice_number' => 'INV-OTHER-ORG',
            'status' => InvoiceStatus::FINALIZED,
            'total_amount' => '50.00',
            'items' => [invoiceCalculationPreviewRow(['description_for_tenant' => 'Other organization charge'])],
            'snapshot_data' => [invoiceCalculationPreviewRow(['description_for_tenant' => 'Other organization charge'])],
        ]);

    $this->actingAs($secondTenant->user)
        ->get(route('filament.admin.pages.tenant-invoice-history'))
        ->assertSuccessful()
        ->assertDontSeeText('INV-OTHER-ORG')
        ->assertDontSeeText('Other organization charge');
});

function invoiceCalculationPreviewScenario(bool $withMeterService = true): array
{
    $organization = Organization::factory()->create();
    $admin = User::factory()->admin()->create([
        'organization_id' => $organization->id,
    ]);
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create([
        'name' => 'A-1',
    ]);
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $assignment = PropertyAssignment::factory()->for($organization)->for($property)->for($tenant, 'tenant')->create([
        'assigned_at' => '2026-01-01 00:00:00',
        'unassigned_at' => null,
    ]);
    $provider = Provider::factory()->for($organization)->create([
        'service_type' => ServiceType::WATER,
    ]);
    $tariff = Tariff::factory()->for($provider)->create([
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 1.75,
        ],
        'active_from' => '2026-01-01 00:00:00',
        'active_until' => null,
    ]);
    $utilityService = UtilityService::factory()->for($organization)->create([
        'name' => 'Water',
        'unit_of_measurement' => 'm3',
        'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
        'service_type_bridge' => ServiceType::WATER,
    ]);
    $meter = Meter::factory()->for($organization)->for($property)->create([
        'type' => MeterType::WATER,
        'unit' => 'm3',
    ]);
    $startReading = MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 50,
        'reading_date' => '2026-04-30',
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);
    $endReading = MeterReading::factory()->for($organization)->for($property)->for($meter)->create([
        'reading_value' => 60,
        'reading_date' => '2026-05-31',
        'validation_status' => MeterReadingValidationStatus::VALID,
    ]);
    $serviceConfiguration = null;

    if ($withMeterService) {
        $serviceConfiguration = ServiceConfiguration::factory()
            ->for($organization)
            ->for($property)
            ->for($utilityService)
            ->for($provider)
            ->for($tariff)
            ->create([
                'service_name' => 'Water usage',
                'billing_method' => BillingMethod::METER_BASED,
                'pricing_model' => PricingModel::CONSUMPTION_BASED,
                'distribution_method' => DistributionMethod::BY_CONSUMPTION,
                'is_shared_service' => false,
                'rate_schedule' => [
                    'unit_rate' => 1.75,
                    'base_fee' => 0,
                ],
                'tenant_visible_name' => 'Water usage',
                'tenant_visible_description' => 'Water consumption charge',
                'unit' => 'm3',
                'effective_from' => '2026-01-01 00:00:00',
                'effective_until' => null,
                'starts_at' => '2026-01-01 00:00:00',
                'ends_at' => null,
            ]);
    }

    return [
        'organization' => $organization,
        'admin' => $admin,
        'building' => $building,
        'property' => $property,
        'tenant' => $tenant,
        'assignment' => $assignment,
        'provider' => $provider,
        'tariff' => $tariff,
        'utilityService' => $utilityService,
        'meter' => $meter,
        'startReading' => $startReading,
        'endReading' => $endReading,
        'serviceConfiguration' => $serviceConfiguration,
    ];
}

function invoiceCalculationPreviewFixedService(array $scenario): ServiceConfiguration
{
    $provider = Provider::factory()->for($scenario['organization'])->create([
        'service_type' => ServiceType::ELECTRICITY,
    ]);
    $tariff = Tariff::factory()->for($provider)->create([
        'configuration' => [
            'type' => 'flat',
            'currency' => 'EUR',
            'rate' => 25.00,
        ],
        'active_from' => '2026-01-01 00:00:00',
        'active_until' => null,
    ]);
    $utilityService = UtilityService::factory()->for($scenario['organization'])->create([
        'name' => 'Internet',
        'unit_of_measurement' => 'month',
        'default_pricing_model' => PricingModel::FIXED_MONTHLY,
        'service_type_bridge' => ServiceType::ELECTRICITY,
    ]);

    return ServiceConfiguration::factory()
        ->fixedMonthly('25.00')
        ->for($scenario['organization'])
        ->for($scenario['property'])
        ->for($utilityService)
        ->for($provider)
        ->for($tariff)
        ->create([
            'service_name' => 'Internet',
            'billing_method' => BillingMethod::FIXED_MONTHLY,
            'pricing_model' => PricingModel::FIXED_MONTHLY,
            'rate_schedule' => [
                'unit_rate' => 25.00,
                'base_fee' => 0,
            ],
            'tenant_visible_name' => 'Internet',
            'tenant_visible_description' => 'Internet monthly service',
            'unit' => 'month',
            'effective_from' => '2026-01-01 00:00:00',
            'effective_until' => null,
            'starts_at' => '2026-01-01 00:00:00',
            'ends_at' => null,
        ]);
}

function invoiceCalculationPreviewDraftInvoice(array $rows, array $overrides = []): Invoice
{
    $organization = Organization::factory()->create();
    $building = Building::factory()->for($organization)->create();
    $property = Property::factory()->for($organization)->for($building)->create();
    $tenant = User::factory()->tenant()->create([
        'organization_id' => $organization->id,
    ]);
    $total = array_reduce(
        $rows,
        fn (float $carry, array $row): float => $carry + (is_numeric($row['total'] ?? null) ? (float) $row['total'] : 0.0),
        0.0,
    );

    return Invoice::factory()
        ->for($organization)
        ->for($property)
        ->for($tenant, 'tenant')
        ->create([
            'status' => InvoiceStatus::DRAFT,
            'finalized_at' => null,
            'billing_period_start' => '2026-05-01',
            'billing_period_end' => '2026-05-31',
            'currency' => 'EUR',
            'total_amount' => number_format($total, 2, '.', ''),
            'items' => $rows,
            'snapshot_data' => $rows,
            ...$overrides,
        ]);
}

function invoiceCalculationPreviewRow(array $overrides = []): array
{
    $row = [
        'source_type' => InvoiceItemSourceType::FIXED_SERVICE->value,
        'source_id' => 101,
        'tariff_id' => 202,
        'title' => 'Water usage',
        'description' => 'Water usage',
        'description_for_tenant' => 'Water usage details',
        'internal_note' => 'Internal-only note',
        'quantity' => '10.000',
        'unit' => 'm3',
        'unit_price' => '1.7500',
        'subtotal' => '17.50',
        'tax_amount' => '0.00',
        'discount_amount' => '0.00',
        'total' => '17.50',
        'currency' => 'EUR',
        'formula_label' => '10.000 m3 x 1.7500',
        'calculation_snapshot' => [
            'source_status' => 'approved',
            'tariff_snapshot' => [
                'id' => 202,
                'configuration' => [
                    'rate' => 1.75,
                    'currency' => 'EUR',
                ],
            ],
        ],
        'tenant_visible' => true,
        'sort_order' => 1,
        'meter_reading_snapshot' => null,
        'tariff_snapshot' => [
            'id' => 202,
            'configuration' => [
                'rate' => 1.75,
                'currency' => 'EUR',
            ],
        ],
    ];

    $row = array_replace_recursive($row, $overrides);

    if (isset($row['meter_reading_snapshot']) && is_array($row['meter_reading_snapshot'])) {
        $row['calculation_snapshot']['meter_reading_snapshot'] = $row['meter_reading_snapshot'];
    }

    return $row;
}

function invoiceCalculationPreviewMeterSnapshot(
    MeterReadingValidationStatus $startStatus = MeterReadingValidationStatus::VALID,
    MeterReadingValidationStatus $endStatus = MeterReadingValidationStatus::VALID,
    bool $negativeConsumption = false,
): array {
    return [
        'meter_id' => 1,
        'meter_name' => 'Main Water Meter',
        'consumption_delta' => $negativeConsumption ? '-5.000' : '10.000',
        'negative_consumption' => $negativeConsumption,
        'negative_consumption_confirmed' => false,
        'start' => [
            'id' => 11,
            'value' => '50.000',
            'date' => '2026-04-30',
            'validation_status' => $startStatus->value,
        ],
        'end' => [
            'id' => 12,
            'value' => $negativeConsumption ? '45.000' : '60.000',
            'date' => '2026-05-31',
            'validation_status' => $endStatus->value,
        ],
    ];
}
