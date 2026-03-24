<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Contracts\BillingServiceInterface;
use App\Enums\DistributionMethod;
use App\Enums\InvoiceStatus;
use App\Enums\MeterType;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Support\Admin\Invoices\FinalizedInvoiceGuard;
use App\Filament\Support\Admin\Invoices\InvoiceEligibilityWindow;
use App\Http\Requests\Admin\Invoices\CreateInvoiceDraftRequest;
use App\Http\Requests\Admin\Invoices\ProcessPaymentRequest;
use App\Http\Requests\Admin\Invoices\SaveInvoiceDraftRequest;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Organization;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\ServiceConfiguration;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class BillingService implements BillingServiceInterface
{
    public function __construct(
        private readonly TariffResolver $tariffResolver,
        private readonly UniversalBillingCalculator $calculator,
        private readonly InvoiceService $invoiceService,
        private readonly SharedServiceCostDistributorService $sharedServiceCostDistributorService,
        private readonly InvoiceEligibilityWindow $invoiceEligibilityWindow,
        private readonly FinalizedInvoiceGuard $finalizedInvoiceGuard,
    ) {}

    public function previewBulkInvoices(Organization $organization, array $attributes): array
    {
        $prepared = $this->preparedBulkInvoicePayloads($organization, $attributes);

        $valid = array_map(function (array $candidate): array {
            /** @var PropertyAssignment $assignment */
            $assignment = $candidate['assignment'];
            /** @var array{items: array<int, array<string, mixed>>, total_amount: string} $lineItems */
            $lineItems = $candidate['line_items'];

            return [
                'assignment_key' => $candidate['assignment_key'],
                'property_id' => $assignment->property_id,
                'tenant_user_id' => $assignment->tenant_user_id,
                'tenant_name' => (string) ($assignment->tenant?->name ?? ''),
                'property_name' => (string) ($assignment->property?->name ?? ''),
                'unit_area_sqm' => $assignment->unit_area_sqm,
                'items' => $lineItems['items'],
                'total' => $lineItems['total_amount'],
            ];
        }, $prepared['valid']);

        return [
            'valid' => $valid,
            'skipped' => $prepared['skipped'],
        ];
    }

    public function previewInvoiceDraft(Organization $organization, array $attributes): array
    {
        $periodStart = $this->normalizeDate($attributes['billing_period_start'])->startOfDay();
        $periodEnd = $this->normalizeDate($attributes['billing_period_end'])->endOfDay();
        $assignment = $this->invoiceAssignment(
            $organization,
            (int) $attributes['tenant_user_id'],
            $periodStart,
            $periodEnd,
        );

        $this->ensureCanCreateInvoiceForPeriod($organization, $assignment, $periodStart, $periodEnd);

        $lineItems = $this->buildLineItemPayload($assignment, $periodStart, $periodEnd);

        return [
            'property_id' => $assignment->property_id,
            'property_name' => (string) ($assignment->property?->name ?? ''),
            'tenant_name' => (string) ($assignment->tenant?->name ?? ''),
            'items' => $lineItems['items'],
            'total_amount' => $lineItems['total_amount'],
        ];
    }

    public function generateBulkInvoices(Organization $organization, array $attributes, ?User $actor = null): array
    {
        $prepared = $this->preparedBulkInvoicePayloads($organization, $attributes);

        $created = collect();

        foreach ($prepared['valid'] as $candidate) {
            /** @var PropertyAssignment $assignment */
            $assignment = $candidate['assignment'];
            /** @var array{items: array<int, array<string, mixed>>, total_amount: string} $lineItems */
            $lineItems = $candidate['line_items'];
            $invoice = $this->invoiceService->createGeneratedInvoice(
                $organization,
                $assignment,
                $lineItems,
                $prepared['period_start'],
                $prepared['period_end'],
                $prepared['due_date'],
                $actor,
            );

            $created = $created->push($invoice);
        }

        return [
            'created' => $created,
            'skipped' => $prepared['skipped'],
        ];
    }

    public function createDraft(Organization $organization, array $attributes, ?User $actor = null): Invoice
    {
        $normalized = $this->normalizeDraftAttributes($attributes);

        /** @var CreateInvoiceDraftRequest $request */
        $request = new CreateInvoiceDraftRequest;
        $validated = $request->validatePayload([
            ...$normalized,
            'organization_id' => $normalized['organization_id'] ?? $organization->id,
        ], $actor ?? auth()->user());
        $periodStart = $this->normalizeDate($validated['billing_period_start'])->startOfDay();
        $periodEnd = $this->normalizeDate($validated['billing_period_end'])->endOfDay();
        $assignment = $this->invoiceAssignment(
            $organization,
            (int) $validated['tenant_user_id'],
            $periodStart,
            $periodEnd,
        );

        $this->ensureCanCreateInvoiceForPeriod($organization, $assignment, $periodStart, $periodEnd);

        return $this->invoiceService->createDraft($organization, $assignment, $validated, $actor);
    }

    public function saveDraft(Invoice $invoice, array $attributes): Invoice
    {
        $normalized = $this->normalizeDraftAttributes($attributes);

        $this->finalizedInvoiceGuard->ensureCanMutate($invoice, $normalized);

        $validated = (new SaveInvoiceDraftRequest)->validatePayload($normalized, auth()->user());

        if (! $this->finalizedInvoiceGuard->isImmutable($invoice) && ! array_key_exists('status', $validated)) {
            $validated['status'] = InvoiceStatus::DRAFT;
        }

        return $this->invoiceService->updateDraft($invoice, $validated);
    }

    public function finalize(Invoice $invoice, array $attributes = []): Invoice
    {
        $beforeSnapshot = $this->invoiceAuditSnapshot($invoice);
        $invoice = $this->saveDraft($invoice, $attributes);

        return $this->invoiceService->markAsFinalized($invoice, auth()->user(), $beforeSnapshot);
    }

    public function applyPayment(Invoice $invoice, array $attributes, ?User $actor = null): Invoice
    {
        $validated = (new ProcessPaymentRequest)->validatePayload($attributes, $actor ?? auth()->user());

        if (! array_key_exists('amount_paid', $validated) && ! array_key_exists('paid_amount', $validated)) {
            throw ValidationException::withMessages([
                'amount_paid' => __('validation.required', [
                    'attribute' => __('requests.attributes.amount_paid'),
                ]),
            ]);
        }

        return $this->invoiceService->recordPayment($invoice, $validated, $actor);
    }

    public function calculateFlatRateCharge(string|int|float $quantity, string|int|float $unitRate, string|int|float $baseFee = '0'): string
    {
        return $this->calculator->calculateFlatRateCharge($quantity, $unitRate, $baseFee);
    }

    public function calculateTimeOfUseCharge(array $zoneConsumptions, array $zones, string|int|float $baseFee = '0'): string
    {
        return $this->calculator->calculateTimeOfUseCharge($zoneConsumptions, $zones, $baseFee);
    }

    public function distributeSharedServiceCost(
        string|int|float $totalCost,
        DistributionMethod $distributionMethod,
        array $context = [],
    ): string {
        return $this->sharedServiceCostDistributorService->distribute($totalCost, $distributionMethod, $context);
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceAuditSnapshot(Invoice $invoice): array
    {
        return [
            'status' => $invoice->status instanceof InvoiceStatus
                ? $invoice->status->value
                : $invoice->status,
            'total_amount' => $this->normalizeNumericSnapshotValue($invoice->total_amount),
            'amount_paid' => $this->normalizeNumericSnapshotValue($invoice->amount_paid),
            'paid_amount' => $this->normalizeNumericSnapshotValue($invoice->paid_amount),
            'payment_reference' => $invoice->payment_reference,
            'finalized_at' => $invoice->finalized_at?->toISOString(),
            'paid_at' => $invoice->paid_at?->toISOString(),
        ];
    }

    private function normalizeNumericSnapshotValue(string|int|float|null $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numericValue = (float) $value;

        if ((float) (int) $numericValue === $numericValue) {
            return (int) $numericValue;
        }

        return $numericValue;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{
     *     period_start: CarbonImmutable,
     *     period_end: CarbonImmutable,
     *     due_date: string,
     *     valid: array<int, array{
     *         assignment_key: string,
     *         assignment: PropertyAssignment,
     *         line_items: array{items: array<int, array<string, mixed>>, total_amount: string, billable: bool}
     *     }>,
     *     skipped: array<int, array{
     *         assignment_key: string,
     *         tenant_id: int,
     *         property_id: int,
     *         tenant_name: string,
     *         property_name: string,
     *         reason: string
     *     }>
     * }
     */
    private function preparedBulkInvoicePayloads(Organization $organization, array $attributes): array
    {
        $periodStart = $this->normalizeDate($attributes['billing_period_start'])->startOfDay();
        $periodEnd = $this->normalizeDate($attributes['billing_period_end'])->endOfDay();
        $dueDate = isset($attributes['due_date'])
            ? $this->normalizeDate($attributes['due_date'])->toDateString()
            : $periodEnd->addDays(14)->toDateString();
        [$assignments, $existingInvoiceKeys] = $this->invoiceCandidates($organization, $periodStart, $periodEnd);
        $selectedAssignmentKeys = $this->selectedAssignmentKeys($attributes);

        $valid = [];
        $skipped = [];

        foreach ($assignments as $assignment) {
            $assignmentKey = $this->invoiceKey($assignment->property_id, $assignment->tenant_user_id);

            if ($selectedAssignmentKeys !== [] && ! isset($selectedAssignmentKeys[$assignmentKey])) {
                continue;
            }

            if (isset($existingInvoiceKeys[$assignmentKey])) {
                $skipped[] = [
                    'assignment_key' => $assignmentKey,
                    'tenant_id' => $assignment->tenant_user_id,
                    'property_id' => $assignment->property_id,
                    'tenant_name' => (string) ($assignment->tenant?->name ?? ''),
                    'property_name' => (string) ($assignment->property?->name ?? ''),
                    'unit_area_sqm' => $assignment->unit_area_sqm,
                    'reason' => 'already_billed',
                ];

                continue;
            }

            $lineItems = $this->buildLineItemPayload($assignment, $periodStart, $periodEnd);

            if (! $lineItems['billable']) {
                $skipped[] = [
                    'assignment_key' => $assignmentKey,
                    'tenant_id' => $assignment->tenant_user_id,
                    'property_id' => $assignment->property_id,
                    'tenant_name' => (string) ($assignment->tenant?->name ?? ''),
                    'property_name' => (string) ($assignment->property?->name ?? ''),
                    'unit_area_sqm' => $assignment->unit_area_sqm,
                    'reason' => 'ineligible_meter_readings',
                ];

                continue;
            }

            $valid[] = [
                'assignment_key' => $assignmentKey,
                'assignment' => $assignment,
                'line_items' => $lineItems,
            ];
        }

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'due_date' => $dueDate,
            'valid' => $valid,
            'skipped' => $skipped,
        ];
    }

    /**
     * @return array{0: Collection<int, PropertyAssignment>, 1: array<string, true>}
     */
    private function invoiceCandidates(
        Organization $organization,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): array {
        $assignments = PropertyAssignment::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'unit_area_sqm',
                'assigned_at',
                'unassigned_at',
            ])
            ->forOrganization($organization->id)
            ->activeDuring($periodStart, $periodEnd)
            ->with([
                'tenant:id,organization_id,name,email',
                'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'property.serviceConfigurations' => fn ($query) => $query
                    ->select([
                        'id',
                        'organization_id',
                        'property_id',
                        'utility_service_id',
                        'pricing_model',
                        'rate_schedule',
                        'distribution_method',
                        'effective_from',
                        'effective_until',
                        'configuration_overrides',
                        'tariff_id',
                        'provider_id',
                        'is_shared_service',
                        'custom_formula',
                        'is_active',
                    ])
                    ->activeOn($periodEnd)
                    ->with([
                        'utilityService:id,organization_id,name,unit_of_measurement,service_type_bridge',
                        'tariff:id,provider_id,name,configuration',
                    ]),
                'property.meters' => fn ($query) => $query
                    ->select([
                        'id',
                        'organization_id',
                        'property_id',
                        'name',
                        'type',
                    ])
                    ->with([
                        'readings' => fn ($readingQuery) => $readingQuery
                            ->select([
                                'id',
                                'organization_id',
                                'property_id',
                                'meter_id',
                                'reading_value',
                                'reading_date',
                                'validation_status',
                            ])
                            ->comparable()
                            ->beforeOrOnDate($periodEnd)
                            ->latestFirst(),
                    ]),
            ])
            ->get()
            ->filter(fn (PropertyAssignment $assignment): bool => $this->invoiceEligibilityWindow->allows($assignment, $periodStart, $periodEnd))
            ->sortBy('id')
            ->values();

        $existingInvoiceKeys = Invoice::query()
            ->select(['property_id', 'tenant_user_id'])
            ->forOrganization($organization->id)
            ->forBillingPeriod($periodStart, $periodEnd)
            ->whereIn('property_id', $assignments->pluck('property_id')->all())
            ->whereIn('tenant_user_id', $assignments->pluck('tenant_user_id')->all())
            ->get()
            ->mapWithKeys(fn (Invoice $invoice): array => [
                $this->invoiceKey($invoice->property_id, $invoice->tenant_user_id) => true,
            ])
            ->all();

        return [$assignments, $existingInvoiceKeys];
    }

    private function invoiceAssignment(
        Organization $organization,
        int $tenantUserId,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): PropertyAssignment {
        $assignment = PropertyAssignment::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'unit_area_sqm',
                'assigned_at',
                'unassigned_at',
            ])
            ->forOrganization($organization->id)
            ->forTenant($tenantUserId)
            ->activeDuring($periodStart, $periodEnd)
            ->with([
                'tenant:id,organization_id,name,email',
                'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'property.serviceConfigurations' => fn ($query) => $query
                    ->select([
                        'id',
                        'organization_id',
                        'property_id',
                        'utility_service_id',
                        'pricing_model',
                        'rate_schedule',
                        'distribution_method',
                        'effective_from',
                        'effective_until',
                        'configuration_overrides',
                        'tariff_id',
                        'provider_id',
                        'is_shared_service',
                        'custom_formula',
                        'is_active',
                    ])
                    ->activeOn($periodEnd)
                    ->with([
                        'utilityService:id,organization_id,name,unit_of_measurement,service_type_bridge',
                        'tariff:id,provider_id,name,configuration',
                    ]),
                'property.meters' => fn ($query) => $query
                    ->select([
                        'id',
                        'organization_id',
                        'property_id',
                        'name',
                        'type',
                    ])
                    ->with([
                        'readings' => fn ($readingQuery) => $readingQuery
                            ->select([
                                'id',
                                'organization_id',
                                'property_id',
                                'meter_id',
                                'reading_value',
                                'reading_date',
                                'validation_status',
                            ])
                            ->comparable()
                            ->beforeOrOnDate($periodEnd)
                            ->latestFirst(),
                    ]),
            ])
            ->latestAssignedFirst()
            ->first();

        if ($assignment instanceof PropertyAssignment) {
            return $assignment;
        }

        throw ValidationException::withMessages([
            'tenant_user_id' => __('admin.invoices.messages.assignment_required'),
        ]);
    }

    private function invoiceKey(int $propertyId, int $tenantId): string
    {
        return $propertyId.':'.$tenantId;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, true>
     */
    private function selectedAssignmentKeys(array $attributes): array
    {
        $selectedAssignments = $attributes['selected_assignments'] ?? [];

        if (! is_array($selectedAssignments)) {
            return [];
        }

        return collect($selectedAssignments)
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->mapWithKeys(fn (string $value): array => [$value => true])
            ->all();
    }

    private function normalizeDate(CarbonInterface|string $value): CarbonImmutable
    {
        return $value instanceof CarbonInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse($value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function normalizeDraftAttributes(array $attributes): array
    {
        if (($attributes['status'] ?? null) instanceof InvoiceStatus) {
            $attributes['status'] = $attributes['status']->value;
        }

        if (array_key_exists('items', $attributes) && is_string($attributes['items'])) {
            $trimmedItems = trim($attributes['items']);

            if ($trimmedItems === '') {
                $attributes['items'] = null;
            } else {
                $decodedItems = json_decode($trimmedItems, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $attributes['items'] = $decodedItems;
                }
            }
        }

        return $attributes;
    }

    private function ensureCanCreateInvoiceForPeriod(
        Organization $organization,
        PropertyAssignment $assignment,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): void {
        $hasExistingInvoice = Invoice::query()
            ->select(['id'])
            ->forOrganization($organization->id)
            ->forProperty($assignment->property_id)
            ->forTenant($assignment->tenant_user_id)
            ->forBillingPeriod($periodStart, $periodEnd)
            ->exists();

        if (! $hasExistingInvoice) {
            return;
        }

        throw ValidationException::withMessages([
            'tenant_user_id' => __('admin.invoices.messages.invoice_exists_for_period'),
        ]);
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total_amount: string, billable: bool}
     */
    private function buildLineItemPayload(
        PropertyAssignment $assignment,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): array {
        $property = $assignment->property;

        if (! $property instanceof Property) {
            return $this->defaultLineItemPayload($assignment, $periodStart, $periodEnd);
        }

        $effectiveConfigurations = collect($property->serviceConfigurations)
            ->filter(fn (ServiceConfiguration $configuration): bool => $this->configurationIsEffective($configuration, $periodEnd))
            ->values();
        $items = $effectiveConfigurations
            ->filter(fn (ServiceConfiguration $configuration): bool => $this->configurationIsEffective($configuration, $periodEnd))
            ->map(fn (ServiceConfiguration $configuration): array => $this->buildLineItem(
                $assignment,
                $property,
                $configuration,
                $periodStart,
                $periodEnd,
            ))
            ->filter(fn (array $item): bool => $item['description'] !== '')
            ->values()
            ->all();
        $billableItems = array_values(array_filter(
            $items,
            fn (array $item): bool => $item['billable'] ?? true,
        ));

        if ($billableItems === [] && $effectiveConfigurations->isNotEmpty()) {
            return [
                'items' => [],
                'total_amount' => $this->calculator->money('0'),
                'billable' => false,
            ];
        }

        if ($billableItems === []) {
            return $this->defaultLineItemPayload($assignment, $periodStart, $periodEnd);
        }

        return [
            'items' => $billableItems,
            'total_amount' => $this->calculator->sumMoney(
                array_map(fn (array $item): string => (string) $item['total'], $billableItems),
            ),
            'billable' => true,
        ];
    }

    private function configurationIsEffective(ServiceConfiguration $configuration, CarbonImmutable $periodEnd): bool
    {
        $effectiveFrom = CarbonImmutable::parse($configuration->effective_from);
        $effectiveUntil = $configuration->effective_until !== null
            ? CarbonImmutable::parse($configuration->effective_until)
            : null;

        return $effectiveFrom->lte($periodEnd)
            && ($effectiveUntil === null || $effectiveUntil->gte($periodEnd));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLineItem(
        PropertyAssignment $assignment,
        Property $property,
        ServiceConfiguration $configuration,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): array {
        $pricing = $this->tariffResolver->resolve($configuration);
        $measurement = $this->measurementContext($property, $configuration, $periodStart, $periodEnd);
        $quantity = $this->billableQuantity($configuration, $measurement['quantity'], $periodStart, $periodEnd);
        $zoneConsumption = $measurement['zone_consumption'];
        $total = $this->calculateServiceTotal(
            $configuration,
            $pricing,
            $quantity,
            $zoneConsumption,
        );

        if ($configuration->is_shared_service && $configuration->distribution_method instanceof DistributionMethod) {
            $total = $this->sharedServiceCostDistributorService->distribute(
                $total,
                $configuration->distribution_method,
                $this->sharedDistributionContext(
                    $assignment,
                    $configuration,
                    $measurement['consumption'],
                    $periodStart,
                    $periodEnd,
                ),
            );
        }

        return [
            'utility_service_id' => $configuration->utility_service_id,
            'description' => (string) ($configuration->utilityService?->name ?? ''),
            'period' => $this->billingPeriodLabel($periodStart, $periodEnd),
            'quantity' => $this->calculator->quantity($quantity),
            'unit' => (string) ($configuration->utilityService?->unit_of_measurement ?? ''),
            'unit_price' => $this->calculator->rate($pricing['unit_rate']),
            'total' => $this->calculator->money($total),
            'consumption' => $this->calculator->quantity(
                $configuration->pricing_model?->requiresBillingPeriodQuantity()
                    ? $quantity
                    : $measurement['consumption'],
            ),
            'billable' => $measurement['billable'],
            'rate' => $this->calculator->rate($pricing['unit_rate']),
            'meter_reading_snapshot' => $measurement['snapshot'],
        ];
    }

    /**
     * @param  array{type: string, unit_rate: string, base_fee: string, zones: array<int, array{id: string, rate: string, start: string|null, end: string|null}>}  $pricing
     * @param  array<string, string>  $zoneConsumption
     */
    private function calculateServiceTotal(
        ServiceConfiguration $configuration,
        array $pricing,
        string $quantity,
        array $zoneConsumption,
    ): string {
        if (
            $configuration->pricing_model === PricingModel::TIME_OF_USE
            && $pricing['zones'] !== []
            && $zoneConsumption !== []
        ) {
            return $this->calculator->calculateTimeOfUseCharge(
                $zoneConsumption,
                $pricing['zones'],
                $pricing['base_fee'],
            );
        }

        return $this->calculator->calculateFlatRateCharge(
            $quantity,
            $pricing['unit_rate'],
            $pricing['base_fee'],
        );
    }

    private function billableQuantity(
        ServiceConfiguration $configuration,
        string $measuredQuantity,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): string {
        if (! $configuration->pricing_model?->requiresBillingPeriodQuantity()) {
            return $measuredQuantity;
        }

        $days = $periodStart->startOfDay()->diffInDays($periodEnd->startOfDay()) + 1;

        return $this->calculator->quantity(
            (string) $days,
        );
    }

    /**
     * @return array{
     *     quantity: string,
     *     consumption: string,
     *     billable: bool,
     *     snapshot: array<string, mixed>|null,
     *     zone_consumption: array<string, string>
     * }
     */
    private function measurementContext(
        Property $property,
        ServiceConfiguration $configuration,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): array {
        $defaultZoneConsumption = collect($configuration->rate_schedule['zone_consumption'] ?? [])
            ->mapWithKeys(fn (mixed $value, string $zone): array => [
                (string) $zone => $this->calculator->quantity(is_numeric($value) ? (string) $value : 0),
            ])
            ->all();

        if (! $configuration->requiresConsumptionData()) {
            return [
                'quantity' => $this->calculator->quantity('1'),
                'consumption' => $this->calculator->quantity('1'),
                'billable' => true,
                'snapshot' => null,
                'zone_consumption' => $defaultZoneConsumption,
            ];
        }

        $serviceType = $configuration->utilityService?->service_type_bridge;

        if (! $serviceType instanceof ServiceType) {
            return [
                'quantity' => $this->calculator->quantity('0'),
                'consumption' => $this->calculator->quantity('0'),
                'billable' => false,
                'snapshot' => null,
                'zone_consumption' => $defaultZoneConsumption,
            ];
        }

        $compatibleMeterTypes = array_map(
            static fn (MeterType $meterType): string => $meterType->value,
            $serviceType->compatibleMeterTypes(),
        );

        /** @var Meter|null $meter */
        $meter = $property->meters
            ->first(fn (Meter $candidate): bool => in_array($candidate->type?->value, $compatibleMeterTypes, true));

        if (! $meter instanceof Meter) {
            return [
                'quantity' => $this->calculator->quantity('0'),
                'consumption' => $this->calculator->quantity('0'),
                'billable' => false,
                'snapshot' => null,
                'zone_consumption' => $defaultZoneConsumption,
            ];
        }

        /** @var MeterReading|null $startReading */
        $startReading = $meter->readings
            ->first(fn (MeterReading $reading): bool => $reading->reading_date !== null && $reading->reading_date->lt($periodStart));
        /** @var MeterReading|null $endReading */
        $endReading = $meter->readings
            ->first(fn (MeterReading $reading): bool => $reading->reading_date !== null && $reading->reading_date->lte($periodEnd));

        if (
            ! $startReading instanceof MeterReading
            || ! $endReading instanceof MeterReading
            || ! $endReading->reading_date?->gte($periodStart)
        ) {
            return [
                'quantity' => $this->calculator->quantity('0'),
                'consumption' => $this->calculator->quantity('0'),
                'billable' => false,
                'snapshot' => null,
                'zone_consumption' => $defaultZoneConsumption,
            ];
        }

        $consumptionDelta = $this->calculator->subtract(
            $endReading->reading_value,
            $startReading->reading_value,
            3,
        );
        $consumption = $this->calculator->compare($consumptionDelta, '0', 3) < 0
            ? $this->calculator->quantity('0')
            : $this->calculator->quantity($consumptionDelta);

        return [
            'quantity' => $consumption,
            'consumption' => $consumption,
            'billable' => true,
            'snapshot' => [
                'meter_id' => $meter->id,
                'meter_name' => $meter->name,
                'start' => [
                    'id' => $startReading->id,
                    'value' => $this->calculator->quantity($startReading->reading_value),
                    'date' => $startReading->reading_date?->toDateString(),
                ],
                'end' => [
                    'id' => $endReading->id,
                    'value' => $this->calculator->quantity($endReading->reading_value),
                    'date' => $endReading->reading_date?->toDateString(),
                ],
            ],
            'zone_consumption' => $defaultZoneConsumption,
        ];
    }

    /**
     * @param  array<string, mixed>  $consumption
     * @return array<string, mixed>
     */
    private function sharedDistributionContext(
        PropertyAssignment $assignment,
        ServiceConfiguration $configuration,
        string $participantConsumption,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): array {
        $peerAssignments = PropertyAssignment::query()
            ->select(['id', 'property_id', 'tenant_user_id', 'unit_area_sqm'])
            ->forOrganization($assignment->organization_id)
            ->activeDuring($periodStart, $periodEnd)
            ->with([
                'property:id,organization_id,building_id,name,unit_number,type,floor_area_sqm',
                'property.meters' => fn ($query) => $query
                    ->select([
                        'id',
                        'organization_id',
                        'property_id',
                        'name',
                        'type',
                    ])
                    ->with([
                        'readings' => fn ($readingQuery) => $readingQuery
                            ->select([
                                'id',
                                'organization_id',
                                'property_id',
                                'meter_id',
                                'reading_value',
                                'reading_date',
                                'validation_status',
                            ])
                            ->comparable()
                            ->beforeOrOnDate($periodEnd)
                            ->latestFirst(),
                    ]),
            ])
            ->get();

        $peerAssignments = $peerAssignments
            ->sortBy('id')
            ->values();
        $participantCount = max($peerAssignments->count(), 1);
        $participantIndex = $peerAssignments->search(
            fn (PropertyAssignment $candidate): bool => $candidate->id === $assignment->id,
        );
        $areaWeights = $peerAssignments
            ->map(fn (PropertyAssignment $candidate): string => (string) ($candidate->unit_area_sqm ?? '0'))
            ->values();
        $consumptionWeights = $peerAssignments
            ->map(function (PropertyAssignment $peerAssignment) use (
                $configuration,
                $periodStart,
                $periodEnd,
            ): string {
                $property = $peerAssignment->property;

                if (! $property instanceof Property) {
                    return $this->calculator->quantity('0');
                }

                return $this->measurementContext(
                    $property,
                    $configuration,
                    $periodStart,
                    $periodEnd,
                )['consumption'];
            })
            ->values();
        $totalArea = $this->calculator->sum($areaWeights->all(), 6);
        $totalConsumption = $this->calculator->sum($consumptionWeights->all(), 6);

        return [
            'participant_count' => $participantCount,
            'participant_index' => is_int($participantIndex) ? $participantIndex : null,
            'participant_occupants' => 1,
            'total_occupants' => $participantCount,
            'participant_area' => (string) ($assignment->unit_area_sqm ?? '0'),
            'total_area' => $totalArea,
            'participant_consumption' => $participantConsumption,
            'total_consumption' => $totalConsumption,
            'area_weights' => $areaWeights->all(),
            'consumption_weights' => $consumptionWeights->all(),
            'occupancy_weights' => array_fill(0, $participantCount, '1'),
            'custom_share' => null,
        ];
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, total_amount: string, billable: bool}
     */
    private function defaultLineItemPayload(
        PropertyAssignment $assignment,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): array {
        $propertyName = $assignment->property?->name ?? __('admin.invoices.empty.property');
        $item = [
            'description' => __('admin.invoices.generated.default_line_item', [
                'property' => $propertyName,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ]),
            'period' => $this->billingPeriodLabel($periodStart, $periodEnd),
            'quantity' => $this->calculator->quantity('1'),
            'unit' => null,
            'unit_price' => $this->calculator->rate('0'),
            'total' => $this->calculator->money('0'),
            'consumption' => $this->calculator->quantity('0'),
            'rate' => $this->calculator->rate('0'),
            'meter_reading_snapshot' => null,
        ];

        return [
            'items' => [$item],
            'total_amount' => $this->calculator->money('0'),
            'billable' => true,
        ];
    }

    private function billingPeriodLabel(CarbonImmutable $periodStart, CarbonImmutable $periodEnd): string
    {
        return $periodStart->format('F Y').' - '.$periodEnd->format('F Y');
    }
}
