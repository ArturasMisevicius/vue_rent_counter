<?php

declare(strict_types=1);

namespace App\Filament\Support\Admin\BillingReview;

use App\Enums\InvoiceItemSourceType;
use App\Enums\InvoiceStatus;
use App\Enums\MeterReadingValidationStatus;
use App\Enums\MeterStatus;
use App\Enums\PricingModel;
use App\Enums\ServiceType;
use App\Filament\Support\Admin\BillingIntegrity\BillingIntegrityIssue;
use App\Filament\Support\Admin\BillingIntegrity\DetectBillingDuplicates;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\OrganizationActivityLog;
use App\Models\ServiceConfiguration;
use App\Services\Billing\TariffResolver;
use App\Services\Billing\UniversalBillingCalculator;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final readonly class BuildBillingReviewForPeriod
{
    public function __construct(
        private TariffResolver $tariffResolver,
        private UniversalBillingCalculator $calculator,
        private DetectBillingDuplicates $duplicateDetector,
    ) {}

    /**
     * @return array{summary: BillingReviewSummary, invoices: array<int, BillingInvoiceReviewData>}
     */
    public function handle(int $organizationId, CarbonInterface|string $periodStart, CarbonInterface|string $periodEnd): array
    {
        $start = $this->normalizeDate($periodStart)->startOfDay();
        $end = $this->normalizeDate($periodEnd)->endOfDay();
        $invoices = Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'billing_period_start',
                'billing_period_end',
                'status',
                'currency',
                'total_amount',
                'due_date',
                'finalized_at',
                'items',
                'approval_status',
                'approval_metadata',
                'approved_by',
                'approved_at',
                'updated_at',
            ])
            ->forOrganization($organizationId)
            ->forBillingPeriod($start, $end)
            ->with($this->invoiceRelations())
            ->latestBillingFirst()
            ->get();

        $reviews = $invoices
            ->map(fn (Invoice $invoice): BillingInvoiceReviewData => $this->forInvoice($invoice))
            ->values()
            ->all();

        return [
            'summary' => $this->summary($reviews),
            'invoices' => $reviews,
        ];
    }

    public function forInvoice(Invoice $invoice): BillingInvoiceReviewData
    {
        $invoice->loadMissing($this->invoiceRelations());

        if ($invoice->billing_period_start === null || $invoice->billing_period_end === null) {
            throw ValidationException::withMessages([
                'invoice' => __('admin.billing_review.errors.billing_period_missing'),
            ]);
        }

        $periodStart = $this->normalizeDate($invoice->billing_period_start)->startOfDay();
        $periodEnd = $this->normalizeDate($invoice->billing_period_end)->endOfDay();
        $propertyId = (int) $invoice->property_id;
        $organizationId = (int) $invoice->organization_id;
        $configurations = $this->serviceConfigurations($organizationId, $propertyId, $periodEnd);
        $meters = $this->meters($organizationId, $propertyId);
        $readings = $this->periodReadings($organizationId, $propertyId, $periodStart, $periodEnd);
        $previousReadings = $this->previousReadings($organizationId, $meters->pluck('id')->all(), $periodStart);
        $requiredMeterIds = [];
        $readingData = [];
        $blockingErrors = [];
        $warnings = [];
        $lineItems = [];
        $services = [];

        foreach ($configurations as $configuration) {
            $service = $this->serviceRow($configuration);
            $pricing = $this->tariffResolver->resolve($configuration);

            if (! $this->hasConfiguredRate($configuration)) {
                $blockingErrors[] = __('admin.billing_review.errors.missing_tariff', [
                    'service' => $service['name'],
                ]);
                $service['tone'] = 'danger';
            }

            if (! $configuration->requiresConsumptionData()) {
                $item = $this->fixedChargeItem($configuration, $pricing, $periodStart, $periodEnd);
                $lineItems[] = $item;
                $service['preview_total'] = $item['total'];
                $services[] = $service;

                continue;
            }

            $compatibleMeters = $this->compatibleMeters($configuration, $meters);

            if ($compatibleMeters->isEmpty()) {
                $blockingErrors[] = __('admin.billing_review.errors.missing_meter', [
                    'service' => $service['name'],
                ]);
                $service['tone'] = 'danger';
                $services[] = $service;

                continue;
            }

            foreach ($compatibleMeters as $meter) {
                $requiredMeterIds[$meter->id] = true;
                $meterReadings = $readings->get($meter->id, collect());
                $currentReading = $this->latestReading($meterReadings);
                $approvedReading = $this->latestApprovedReading($meterReadings);
                $previousReading = $previousReadings->get($meter->id);
                $meterIssues = $this->readingIssues($meter, $currentReading, $previousReading);

                $blockingErrors = [...$blockingErrors, ...$meterIssues['blocking']];
                $warnings = [...$warnings, ...$meterIssues['warnings']];
                $readingData[$meter->id] = $this->readingData($meter, $currentReading, $previousReading, $meterIssues);

                if (! $approvedReading instanceof MeterReading || ! $previousReading instanceof MeterReading) {
                    continue;
                }

                $item = $this->consumptionChargeItem($configuration, $meter, $approvedReading, $previousReading, $pricing, $periodStart, $periodEnd);
                $lineItems[] = $item;
            }

            $service['preview_total'] = $this->calculator->sumMoney(
                collect($lineItems)
                    ->filter(fn (array $item): bool => (int) ($item['utility_service_id'] ?? 0) === (int) $configuration->utility_service_id)
                    ->pluck('total')
                    ->map(fn (mixed $total): string => (string) $total)
                    ->all(),
            );
            $services[] = $service;
        }

        $submittedReadings = $readings
            ->flatten(1)
            ->map(fn (MeterReading $reading): array => $this->readingData(
                $reading->meter,
                $reading,
                $previousReadings->get($reading->meter_id),
                ['blocking' => [], 'warnings' => []],
            )->toArray())
            ->values()
            ->all();
        $missingReadings = collect($readingData)
            ->filter(fn (BillingReadingReviewData $reading): bool => $reading->missing)
            ->map(fn (BillingReadingReviewData $reading): array => $reading->toArray())
            ->values()
            ->all();
        $requiredCount = count($requiredMeterIds);
        $approvedCount = collect($readingData)
            ->filter(fn (BillingReadingReviewData $reading): bool => $reading->isApproved())
            ->count();
        $extraCharges = $this->extraCharges($invoice);
        $invoiceItems = [...$lineItems, ...$extraCharges];
        $previewTotal = $this->calculator->sumMoney(
            collect($invoiceItems)
                ->pluck('total')
                ->map(fn (mixed $total): string => (string) $total)
                ->all(),
        );
        $blockingErrors = [
            ...$blockingErrors,
            ...$this->duplicateBlockingErrors($invoice),
        ];

        return new BillingInvoiceReviewData(
            invoiceId: (int) $invoice->id,
            invoiceNumber: (string) $invoice->invoice_number,
            tenantName: (string) ($invoice->tenant?->name ?? __('admin.invoices.empty.tenant')),
            tenantEmail: $invoice->tenant?->email,
            propertyName: (string) ($invoice->property?->displayName() ?? __('admin.invoices.empty.property')),
            buildingName: $invoice->property?->building?->name,
            billingPeriod: $this->periodLabel($periodStart, $periodEnd),
            invoiceStatus: $this->invoiceStatusValue($invoice),
            invoiceStatusLabel: $this->invoiceStatusLabel($invoice),
            approvalStatus: $invoice->approval_status,
            requiredReadingsCount: $requiredCount,
            approvedReadingsCount: $approvedCount,
            submittedReadingsCount: count($submittedReadings),
            readingsProgress: "{$approvedCount}/{$requiredCount}",
            blockingErrors: array_values(array_unique($blockingErrors)),
            warnings: array_values(array_unique($warnings)),
            previewTotal: $previewTotal,
            currency: (string) ($invoice->currency ?: 'EUR'),
            lastActivityAt: $invoice->updated_at?->diffForHumans(),
            lastActivityLabel: $invoice->updated_at?->toDateTimeString() ?? __('dashboard.not_available'),
            reviewUrl: route('filament.admin.pages.billing-review-center.invoice-review', ['invoice' => $invoice->id], false),
            canApprove: $blockingErrors === [] && $invoice->status === InvoiceStatus::DRAFT,
            canSend: $invoice->status !== InvoiceStatus::DRAFT,
            wasSent: $invoice->emailLogs->isNotEmpty(),
            isOverdue: $invoice->isOverdue(),
            submittedReadings: $submittedReadings,
            missingReadings: $missingReadings,
            services: $services,
            extraCharges: $extraCharges,
            calculationPreview: $invoiceItems,
            history: $this->history($invoice, collect($submittedReadings)->pluck('reading_id')->filter()->map(fn (mixed $id): int => (int) $id)->all()),
            invoiceItemPayload: $invoiceItems,
        );
    }

    public function readiness(Invoice $invoice): InvoiceReadinessResult
    {
        $data = $this->forInvoice($invoice);

        return new InvoiceReadinessResult($data->blockingErrors, $data->warnings);
    }

    /**
     * @param  array<int, BillingInvoiceReviewData>  $reviews
     */
    private function summary(array $reviews): BillingReviewSummary
    {
        $collection = collect($reviews);

        return new BillingReviewSummary(
            totalInvoices: $collection->count(),
            waitingForReadings: $collection->filter(fn (BillingInvoiceReviewData $data): bool => $data->missingReadings !== [])->count(),
            submittedReadings: $collection->filter(fn (BillingInvoiceReviewData $data): bool => $data->submittedReadingsCount > 0)->count(),
            readyForReview: $collection->filter(fn (BillingInvoiceReviewData $data): bool => $data->canApprove)->count(),
            configurationErrors: $collection->filter(fn (BillingInvoiceReviewData $data): bool => $data->hasConfigurationErrors())->count(),
            approved: $collection->filter(fn (BillingInvoiceReviewData $data): bool => $data->approvalStatus === 'approved')->count(),
            sent: $collection->filter(fn (BillingInvoiceReviewData $data): bool => $data->wasSent)->count(),
            overdue: $collection->filter(fn (BillingInvoiceReviewData $data): bool => $data->isOverdue)->count(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceRelations(): array
    {
        return [
            'property:id,organization_id,building_id,name,unit_number',
            'property.building:id,organization_id,name',
            'tenant:id,organization_id,name,email',
            'invoiceItems:id,invoice_id,description,quantity,unit,unit_price,total,meter_reading_snapshot,created_at',
            'emailLogs:id,invoice_id,organization_id,status,sent_at,created_at',
            'approvedBy:id,name',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function duplicateBlockingErrors(Invoice $invoice): array
    {
        return $this->duplicateDetector
            ->forInvoice($invoice)
            ->filter(fn (BillingIntegrityIssue $issue): bool => $issue->severity === 'blocking')
            ->map(fn (BillingIntegrityIssue $issue): string => __('admin.billing_review.errors.duplicate_integrity_problem', [
                'problem' => $issue->label(),
            ]))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, ServiceConfiguration>
     */
    private function serviceConfigurations(int $organizationId, int $propertyId, CarbonImmutable $periodEnd): Collection
    {
        return ServiceConfiguration::query()
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
                'invoice_description',
                'is_active',
            ])
            ->forOrganization($organizationId)
            ->forPropertyValue($propertyId)
            ->activeOn($periodEnd)
            ->with([
                'utilityService:id,organization_id,name,unit_of_measurement,service_type_bridge',
                'tariff:id,provider_id,name,configuration',
                'provider:id,organization_id,name,service_type',
            ])
            ->ordered()
            ->get();
    }

    /**
     * @return Collection<int, Meter>
     */
    private function meters(int $organizationId, int $propertyId): Collection
    {
        return Meter::query()
            ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
            ->forOrganization($organizationId)
            ->forProperty($propertyId)
            ->where('status', MeterStatus::ACTIVE)
            ->ordered()
            ->get();
    }

    /**
     * @return Collection<int, Collection<int, MeterReading>>
     */
    private function periodReadings(
        int $organizationId,
        int $propertyId,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): Collection {
        return MeterReading::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'meter_id',
                'submitted_by_user_id',
                'reading_value',
                'reading_date',
                'validation_status',
                'submission_method',
                'notes',
                'created_at',
                'updated_at',
            ])
            ->forOrganization($organizationId)
            ->forProperty($propertyId)
            ->whereDate('reading_date', '>=', $periodStart->toDateString())
            ->whereDate('reading_date', '<=', $periodEnd->toDateString())
            ->with([
                'meter:id,organization_id,property_id,name,identifier,type,unit',
                'submittedBy:id,name,email',
            ])
            ->latestFirst()
            ->get()
            ->groupBy('meter_id');
    }

    /**
     * @param  array<int, int>  $meterIds
     * @return Collection<int, MeterReading>
     */
    private function previousReadings(int $organizationId, array $meterIds, CarbonImmutable $periodStart): Collection
    {
        if ($meterIds === []) {
            return collect();
        }

        return MeterReading::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'meter_id',
                'submitted_by_user_id',
                'reading_value',
                'reading_date',
                'validation_status',
                'created_at',
                'updated_at',
            ])
            ->forOrganization($organizationId)
            ->whereIn('meter_id', $meterIds)
            ->beforeDate($periodStart)
            ->where('validation_status', MeterReadingValidationStatus::VALID)
            ->latestFirst()
            ->get()
            ->unique('meter_id')
            ->keyBy('meter_id');
    }

    /**
     * @param  Collection<int, MeterReading>  $readings
     */
    private function latestReading(Collection $readings): ?MeterReading
    {
        return $readings->first();
    }

    /**
     * @param  Collection<int, MeterReading>  $readings
     */
    private function latestApprovedReading(Collection $readings): ?MeterReading
    {
        return $readings
            ->first(fn (MeterReading $reading): bool => $reading->validation_status === MeterReadingValidationStatus::VALID);
    }

    /**
     * @param  Collection<int, Meter>  $meters
     * @return Collection<int, Meter>
     */
    private function compatibleMeters(ServiceConfiguration $configuration, Collection $meters): Collection
    {
        $serviceType = $configuration->utilityService?->service_type_bridge;

        if (! $serviceType instanceof ServiceType) {
            return $meters->values();
        }

        $compatibleTypes = collect($serviceType->compatibleMeterTypes())
            ->map(fn ($meterType): string => $meterType->value)
            ->all();

        if ($compatibleTypes === []) {
            return collect();
        }

        return $meters
            ->filter(fn (Meter $meter): bool => in_array((string) $meter->type?->value, $compatibleTypes, true))
            ->values();
    }

    /**
     * @return array{name: string, unit: string, tone: string, preview_total: string}
     */
    private function serviceRow(ServiceConfiguration $configuration): array
    {
        return [
            'name' => (string) ($configuration->invoice_description ?: $configuration->utilityService?->name ?: __('admin.billing_review.unknown_service')),
            'unit' => (string) ($configuration->utilityService?->unit_of_measurement ?? ''),
            'tone' => 'default',
            'preview_total' => $this->calculator->money('0'),
        ];
    }

    /**
     * @return array{blocking: array<int, string>, warnings: array<int, string>}
     */
    private function readingIssues(Meter $meter, ?MeterReading $currentReading, ?MeterReading $previousReading): array
    {
        $blocking = [];
        $warnings = [];

        if (! $currentReading instanceof MeterReading) {
            $blocking[] = __('admin.billing_review.errors.missing_reading', [
                'meter' => $meter->displayName(),
            ]);

            return ['blocking' => $blocking, 'warnings' => $warnings];
        }

        if ($currentReading->validation_status === MeterReadingValidationStatus::REJECTED) {
            $blocking[] = __('admin.billing_review.errors.rejected_reading', [
                'meter' => $meter->displayName(),
            ]);
        }

        if ($currentReading->validation_status !== MeterReadingValidationStatus::VALID) {
            $blocking[] = __('admin.billing_review.errors.unapproved_reading', [
                'meter' => $meter->displayName(),
            ]);
        }

        if (! $previousReading instanceof MeterReading) {
            $blocking[] = __('admin.billing_review.errors.missing_previous_reading', [
                'meter' => $meter->displayName(),
            ]);

            return ['blocking' => $blocking, 'warnings' => $warnings];
        }

        $consumption = $this->calculator->subtract($currentReading->reading_value, $previousReading->reading_value, 3);

        if ($this->calculator->compare($consumption, '0', 3) < 0) {
            $warnings[] = __('admin.billing_review.warnings.negative_consumption', [
                'meter' => $meter->displayName(),
            ]);
        }

        return ['blocking' => $blocking, 'warnings' => $warnings];
    }

    /**
     * @param  array{blocking: array<int, string>, warnings: array<int, string>}  $issues
     */
    private function readingData(Meter $meter, ?MeterReading $currentReading, ?MeterReading $previousReading, array $issues): BillingReadingReviewData
    {
        $consumption = null;

        if ($currentReading instanceof MeterReading && $previousReading instanceof MeterReading) {
            $consumption = $this->calculator->quantity(
                $this->calculator->subtract($currentReading->reading_value, $previousReading->reading_value, 3),
            );
        }

        $status = $currentReading?->validation_status instanceof MeterReadingValidationStatus
            ? $currentReading->validation_status->value
            : 'missing';

        return new BillingReadingReviewData(
            meterId: (int) $meter->id,
            meterName: $meter->displayName(),
            meterIdentifier: $meter->identifier,
            meterUnit: $meter->unit,
            readingId: $currentReading?->id,
            readingValue: $currentReading?->reading_value,
            readingDate: $currentReading?->reading_date?->toDateString(),
            previousReadingValue: $previousReading?->reading_value,
            previousReadingDate: $previousReading?->reading_date?->toDateString(),
            consumption: $consumption,
            status: $status,
            statusLabel: $currentReading?->validation_status?->getLabel() ?? __('admin.billing_review.statuses.missing'),
            submittedBy: $currentReading?->submittedBy?->name,
            submittedAt: $currentReading?->created_at?->diffForHumans(),
            missing: ! $currentReading instanceof MeterReading,
            blockingErrors: $issues['blocking'],
            warnings: $issues['warnings'],
        );
    }

    /**
     * @param  array{unit_rate: string, base_fee: string}  $pricing
     * @return array<string, mixed>
     */
    private function fixedChargeItem(
        ServiceConfiguration $configuration,
        array $pricing,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): array {
        $quantity = $configuration->pricing_model === PricingModel::FIXED_DAILY
            ? (string) ($periodStart->diffInDays($periodEnd) + 1)
            : '1';
        $total = $this->calculator->calculateFlatRateCharge($quantity, $pricing['unit_rate'], $pricing['base_fee']);

        return $this->lineItem($configuration, $quantity, $pricing['unit_rate'], $total, null, $periodStart, $periodEnd);
    }

    /**
     * @param  array{unit_rate: string, base_fee: string}  $pricing
     * @return array<string, mixed>
     */
    private function consumptionChargeItem(
        ServiceConfiguration $configuration,
        Meter $meter,
        MeterReading $currentReading,
        MeterReading $previousReading,
        array $pricing,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): array {
        $quantity = $this->calculator->quantity(
            $this->calculator->subtract($currentReading->reading_value, $previousReading->reading_value, 3),
        );
        $total = $this->calculator->calculateFlatRateCharge($quantity, $pricing['unit_rate'], $pricing['base_fee']);

        $negativeConsumption = $this->calculator->compare($quantity, '0', 3) < 0;

        return $this->lineItem($configuration, $quantity, $pricing['unit_rate'], $total, [
            'meter_id' => $meter->id,
            'meter_name' => $meter->displayName(),
            'start' => [
                'id' => $previousReading->id,
                'reading_value' => $previousReading->reading_value,
                'reading_date' => $previousReading->reading_date?->toDateString(),
                'validation_status' => $previousReading->validation_status?->value,
            ],
            'end' => [
                'id' => $currentReading->id,
                'reading_value' => $currentReading->reading_value,
                'reading_date' => $currentReading->reading_date?->toDateString(),
                'validation_status' => $currentReading->validation_status?->value,
            ],
            'previous_reading_id' => $previousReading->id,
            'previous_reading_value' => $previousReading->reading_value,
            'previous_reading_date' => $previousReading->reading_date?->toDateString(),
            'current_reading_id' => $currentReading->id,
            'current_reading_value' => $currentReading->reading_value,
            'current_reading_date' => $currentReading->reading_date?->toDateString(),
            'negative_consumption' => $negativeConsumption,
            'negative_consumption_confirmed' => $negativeConsumption,
        ], $periodStart, $periodEnd);
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>
     */
    private function lineItem(
        ServiceConfiguration $configuration,
        string $quantity,
        string $rate,
        string $total,
        ?array $snapshot,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): array {
        $sourceType = is_array($snapshot) ? InvoiceItemSourceType::METER_READING : InvoiceItemSourceType::FIXED_SERVICE;
        $sourceId = $sourceType === InvoiceItemSourceType::METER_READING
            ? data_get($snapshot, 'end.id')
            : $configuration->id;
        $description = (string) ($configuration->invoice_description ?: $configuration->utilityService?->name ?: __('admin.billing_review.unknown_service'));
        $currency = strtoupper((string) data_get($configuration->tariff?->configuration, 'currency', 'EUR'));
        $serviceSnapshot = $this->serviceSnapshot($configuration);
        $tariffSnapshot = $this->tariffSnapshot($configuration);
        $providerSnapshot = $this->providerSnapshot($configuration);
        $formulaLabel = __('admin.invoices.formulas.quantity_times_unit_price_with_values', [
            'quantity' => $this->calculator->quantity($quantity),
            'unit' => (string) ($configuration->unit ?: $configuration->utilityService?->unit_of_measurement ?? ''),
            'unit_price' => $this->calculator->rate($rate),
        ]);

        return [
            'source_type' => $sourceType->value,
            'source_id' => is_numeric($sourceId) ? (int) $sourceId : null,
            'service_configuration_id' => $configuration->id,
            'utility_service_id' => $configuration->utility_service_id,
            'tariff_id' => $configuration->tariff_id,
            'provider_id' => $configuration->provider_id,
            'title' => $description,
            'description' => $description,
            'description_for_tenant' => $description,
            'internal_note' => null,
            'period' => $this->periodLabel($periodStart, $periodEnd),
            'quantity' => $this->calculator->quantity($quantity),
            'unit' => (string) ($configuration->utilityService?->unit_of_measurement ?? ''),
            'unit_price' => $this->calculator->rate($rate),
            'rate' => $this->calculator->rate($rate),
            'subtotal' => $this->calculator->money($total),
            'tax_amount' => $this->calculator->money('0'),
            'discount_amount' => $this->calculator->money('0'),
            'total' => $this->calculator->money($total),
            'currency' => $currency,
            'formula_label' => $formulaLabel,
            'calculation_snapshot' => [
                'source_type' => $sourceType->value,
                'source_id' => is_numeric($sourceId) ? (int) $sourceId : null,
                'source_status' => 'approved',
                'formula_label' => $formulaLabel,
                'quantity' => $this->calculator->quantity($quantity),
                'unit' => (string) ($configuration->utilityService?->unit_of_measurement ?? ''),
                'unit_price' => $this->calculator->rate($rate),
                'subtotal' => $this->calculator->money($total),
                'tax_amount' => $this->calculator->money('0'),
                'discount_amount' => $this->calculator->money('0'),
                'total' => $this->calculator->money($total),
                'currency' => $currency,
                'meter_reading_snapshot' => $snapshot,
                'service_snapshot' => $serviceSnapshot,
                'tariff_snapshot' => $tariffSnapshot,
                'provider_snapshot' => $providerSnapshot,
            ],
            'tenant_visible' => true,
            'consumption' => $this->calculator->quantity($quantity),
            'is_adjustment' => false,
            'meter_reading_snapshot' => $snapshot,
            'service_snapshot' => $serviceSnapshot,
            'tariff_snapshot' => $tariffSnapshot,
            'provider_snapshot' => $providerSnapshot,
            'billable' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceSnapshot(ServiceConfiguration $configuration): array
    {
        return [
            'id' => $configuration->id,
            'provider_id' => $configuration->provider_id,
            'tariff_id' => $configuration->tariff_id,
            'utility_service_id' => $configuration->utility_service_id,
            'utility_service_name' => $configuration->utilityService?->name,
            'pricing_model' => $configuration->pricing_model?->value,
            'distribution_method' => $configuration->distribution_method?->value,
            'effective_from' => $configuration->effective_from?->toISOString(),
            'effective_until' => $configuration->effective_until?->toISOString(),
            'rate_schedule' => $configuration->rate_schedule,
            'configuration_overrides' => $configuration->configuration_overrides,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tariffSnapshot(ServiceConfiguration $configuration): ?array
    {
        if ($configuration->tariff === null) {
            return null;
        }

        return [
            'id' => $configuration->tariff->id,
            'provider_id' => $configuration->tariff->provider_id,
            'name' => $configuration->tariff->name,
            'configuration' => $configuration->tariff->configuration,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function providerSnapshot(ServiceConfiguration $configuration): ?array
    {
        if ($configuration->provider === null) {
            return null;
        }

        return [
            'id' => $configuration->provider->id,
            'name' => $configuration->provider->name,
            'service_type' => $configuration->provider->service_type?->value,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extraCharges(Invoice $invoice): array
    {
        return collect($invoice->items)
            ->filter(fn (array $item): bool => (bool) ($item['is_adjustment'] ?? false))
            ->map(function (array $item): array {
                $total = $item['total'] ?? $item['amount'] ?? 0;

                return [
                    'source_type' => InvoiceItemSourceType::MANUAL_ADJUSTMENT->value,
                    'source_id' => null,
                    'service_configuration_id' => null,
                    'utility_service_id' => $item['utility_service_id'] ?? null,
                    'tariff_id' => null,
                    'provider_id' => null,
                    'title' => (string) ($item['description'] ?? __('admin.invoices.fields.adjustment')),
                    'description' => (string) ($item['description'] ?? __('admin.invoices.fields.adjustment')),
                    'description_for_tenant' => (string) ($item['description'] ?? __('admin.invoices.fields.adjustment')),
                    'internal_note' => null,
                    'period' => $item['period'] ?? null,
                    'quantity' => $this->calculator->quantity($item['quantity'] ?? 1),
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $this->calculator->rate($item['unit_price'] ?? $total),
                    'rate' => $this->calculator->rate($item['rate'] ?? $item['unit_price'] ?? $total),
                    'subtotal' => $this->calculator->money($total),
                    'tax_amount' => $this->calculator->money('0'),
                    'discount_amount' => $this->calculator->money('0'),
                    'total' => $this->calculator->money($total),
                    'currency' => (string) ($item['currency'] ?? 'EUR'),
                    'formula_label' => __('admin.invoices.formulas.quantity_times_unit_price'),
                    'calculation_snapshot' => [
                        'source_type' => InvoiceItemSourceType::MANUAL_ADJUSTMENT->value,
                        'source_status' => 'approved',
                    ],
                    'tenant_visible' => true,
                    'consumption' => $this->calculator->quantity($item['consumption'] ?? 1),
                    'is_adjustment' => true,
                    'meter_reading_snapshot' => $item['meter_reading_snapshot'] ?? null,
                    'service_snapshot' => null,
                    'tariff_snapshot' => null,
                    'provider_snapshot' => null,
                    'billable' => true,
                ];
            })
            ->values()
            ->all();
    }

    private function hasConfiguredRate(ServiceConfiguration $configuration): bool
    {
        $rateSchedule = is_array($configuration->rate_schedule) ? $configuration->rate_schedule : [];
        $overrides = is_array($configuration->configuration_overrides) ? $configuration->configuration_overrides : [];

        return $configuration->tariff_id !== null
            || filled($rateSchedule['unit_rate'] ?? null)
            || filled($rateSchedule['base_fee'] ?? null)
            || filled($overrides['unit_rate'] ?? null)
            || filled($overrides['base_fee'] ?? null)
            || (is_array($rateSchedule['zones'] ?? null) && $rateSchedule['zones'] !== []);
    }

    /**
     * @param  array<int, int>  $readingIds
     * @return array<int, array<string, mixed>>
     */
    private function history(Invoice $invoice, array $readingIds): array
    {
        $activity = OrganizationActivityLog::query()
            ->select(['id', 'organization_id', 'user_id', 'action', 'resource_type', 'resource_id', 'metadata', 'created_at'])
            ->forOrganization($invoice->organization_id)
            ->where(function (Builder $query) use ($invoice, $readingIds): void {
                $query->where(function (Builder $invoiceQuery) use ($invoice): void {
                    $invoiceQuery
                        ->where('resource_type', Invoice::class)
                        ->where('resource_id', $invoice->id);
                });

                if ($readingIds !== []) {
                    $query->orWhere(function (Builder $readingQuery) use ($readingIds): void {
                        $readingQuery
                            ->where('resource_type', MeterReading::class)
                            ->whereIn('resource_id', $readingIds);
                    });
                }
            })
            ->withActorSummary()
            ->recent()
            ->limit(12)
            ->get()
            ->map(fn (OrganizationActivityLog $log): array => [
                'id' => "activity-{$log->id}",
                'label' => (string) $log->action,
                'actor' => $log->user?->name,
                'at' => $log->created_at?->diffForHumans(),
                'description' => $log->created_at?->toDateTimeString(),
            ]);

        if ($activity->isNotEmpty()) {
            return $activity->values()->all();
        }

        return AuditLog::query()
            ->select(['id', 'organization_id', 'actor_user_id', 'action', 'description', 'occurred_at'])
            ->forOrganization($invoice->organization_id)
            ->forSubject($invoice)
            ->withActorSummary()
            ->recent()
            ->limit(12)
            ->get()
            ->map(fn (AuditLog $log): array => [
                'id' => "audit-{$log->id}",
                'label' => $log->description ?: $log->action?->getLabel(),
                'actor' => $log->actor?->name,
                'at' => $log->occurred_at?->diffForHumans(),
                'description' => $log->occurred_at?->toDateTimeString(),
            ])
            ->values()
            ->all();
    }

    private function normalizeDate(CarbonInterface|string $value): CarbonImmutable
    {
        return $value instanceof CarbonInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse($value);
    }

    private function periodLabel(CarbonInterface $periodStart, CarbonInterface $periodEnd): string
    {
        return $periodStart->toDateString().' - '.$periodEnd->toDateString();
    }

    private function invoiceStatusValue(Invoice $invoice): string
    {
        return $invoice->effectiveStatus()->value;
    }

    private function invoiceStatusLabel(Invoice $invoice): string
    {
        return $invoice->effectiveStatus()->getLabel();
    }
}
