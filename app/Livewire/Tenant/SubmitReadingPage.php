<?php

namespace App\Livewire\Tenant;

use App\Enums\InvoiceStatus;
use App\Filament\Actions\Tenant\Readings\CompleteReadingRequestInvoiceAction;
use App\Filament\Actions\Tenant\Readings\SubmitTenantReadingAction;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Filament\Support\Tenant\Portal\TenantMeterNameLocalizer;
use App\Livewire\Concerns\AppliesShellLocale;
use App\Livewire\Concerns\ResolvesTenantWorkspace;
use App\Models\Invoice;
use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class SubmitReadingPage extends Component
{
    use AppliesShellLocale;
    use ResolvesTenantWorkspace;

    public string $meterId = '';

    public string $readingValue = '';

    public string $readingDate = '';

    #[Url(as: 'invoice', except: '')]
    public string $invoiceId = '';

    public string $notes = '';

    /**
     * @var array<int|string, array{value?: string|int|float|null, notes?: string|null}>
     */
    public array $readings = [];

    public ?string $successMessage = null;

    /**
     * @var array{meter_identifier: string, meter_name: string, unit: string, value: string, date: string}|null
     */
    public ?array $submittedReading = null;

    /**
     * @var list<array{meter_identifier: string, meter_name: string, unit: string, value: string, date: string}>
     */
    public array $submittedReadings = [];

    private ?string $domainErrorMeterId = null;

    public function mount(): void
    {
        $this->tenantWorkspace();
        $invoiceId = $this->normalizedInvoiceId();
        $this->invoiceId = $invoiceId !== null ? (string) $invoiceId : '';
        $this->readingDate = now()->toDateString();
        $this->syncReadingRowsWithAvailableMeters();

        if ($this->meterSelectionLocked) {
            $this->meterId = (string) $this->availableMeters->firstOrFail()->id;
        }
    }

    public function submit(
        SubmitTenantReadingAction $submitTenantReadingAction,
        CompleteReadingRequestInvoiceAction $completeReadingRequestInvoiceAction,
    ): void {
        $this->resetErrorBag();
        $this->successMessage = null;
        $this->submittedReading = null;
        $this->submittedReadings = [];

        if ($this->readingRequestInvoiceSummary === null) {
            $this->addError('readings', $this->readingRequestUnavailableMessage());

            if (filled($this->readingValue)) {
                $this->addError('readingValue', $this->readingRequestUnavailableMessage());
            }

            return;
        }

        if (filled($this->readingValue)) {
            $this->submitLegacyReading($submitTenantReadingAction, $completeReadingRequestInvoiceAction);

            return;
        }

        $this->submitBatchReadings($submitTenantReadingAction, $completeReadingRequestInvoiceAction);
    }

    #[On('shell-locale-updated')]
    public function refreshTranslations(): void
    {
        $this->applyShellLocale();

        unset(
            $this->availableMeters,
            $this->selectedMeter,
            $this->consumption,
            $this->readingRows,
            $this->meterSelectionLocked,
            $this->readingRequestInvoiceSummary,
            $this->tenant,
        );

        if ($this->successMessage !== null) {
            $this->successMessage = $this->submissionSuccessMessage($this->submittedReadings === [] ? 1 : count($this->submittedReadings));
        }
    }

    public function render(): View
    {
        $this->syncReadingRowsWithAvailableMeters();

        /** @var User $tenant */
        $tenant = auth()->user();

        return view('livewire.tenant.submit-reading-page', [
            'meters' => $this->availableMeters,
            'readingRows' => $this->readingRows,
            'selectedMeter' => $this->selectedMeter,
            'selectedMeterName' => $this->meterDisplayName($this->selectedMeter),
            'readingDateDisplay' => $this->readingDateDisplay(),
            'consumption' => $this->consumption,
            'meterSelectionLocked' => $this->meterSelectionLocked,
            'readingRequestInvoiceSummary' => $this->readingRequestInvoiceSummary,
            'readingRequestUnavailableMessage' => $this->readingRequestUnavailableMessage(),
            'readingRequestUnavailableTitle' => $this->readingRequestUnavailableTitle(),
            'tenant' => $tenant,
        ]);
    }

    /**
     * @return list<array{
     *     id: int,
     *     name: string,
     *     identifier: string,
     *     unit: string,
     *     value: string,
     *     notes: string,
     *     previous_message: string,
     *     delta: string|null,
     *     warning: string|null
     * }>
     */
    #[Computed]
    public function readingRows(): array
    {
        return $this->availableMeters
            ->map(function (Meter $meter): array {
                $meterId = (string) $meter->id;
                $readingValue = $this->stringValue(data_get($this->readings, "{$meterId}.value", ''));
                $notes = $this->stringValue(data_get($this->readings, "{$meterId}.notes", ''));
                $latestReading = $meter->latestReading;
                $previousMessage = __('tenant.pages.readings.first_reading');
                $delta = null;
                $warning = null;

                if ($latestReading !== null) {
                    $previousMessage = __('tenant.pages.readings.previous_reading', [
                        'value' => $this->formatDecimal((float) $latestReading->reading_value, 3),
                        'unit' => $meter->unit,
                        'date' => LocalizedDateFormatter::date($latestReading->reading_date),
                    ]);
                }

                if ($latestReading !== null && is_numeric($readingValue)) {
                    $numericDelta = (float) $readingValue - (float) $latestReading->reading_value;
                    $delta = $this->formatDecimal($numericDelta, 3);
                    $warning = $numericDelta < 0
                        ? __('tenant.pages.readings.lower_than_previous_warning')
                        : null;
                }

                return [
                    'id' => (int) $meter->id,
                    'name' => $this->meterDisplayName($meter),
                    'identifier' => (string) $meter->identifier,
                    'unit' => (string) $meter->unit,
                    'value' => $readingValue,
                    'notes' => $notes,
                    'previous_message' => $previousMessage,
                    'delta' => $delta,
                    'warning' => $warning,
                ];
            })
            ->values()
            ->all();
    }

    private function submitLegacyReading(
        SubmitTenantReadingAction $submitTenantReadingAction,
        CompleteReadingRequestInvoiceAction $completeReadingRequestInvoiceAction,
    ): void {
        try {
            $reading = $submitTenantReadingAction->handle(
                tenant: $this->tenant,
                meterId: $this->meterId,
                readingValue: $this->readingValue,
                readingDate: $this->readingDate,
                notes: $this->notes,
            );
        } catch (AuthorizationException) {
            $this->addError('meterId', __('tenant.pages.readings.unauthorized_meter'));

            return;
        } catch (ValidationException $exception) {
            $this->mapDomainErrors($exception);

            return;
        }

        $this->reset('readingValue', 'notes');
        $this->completeReadingRequestInvoice([$reading], $completeReadingRequestInvoiceAction);
        $this->completeSubmission([$reading]);
    }

    private function submitBatchReadings(
        SubmitTenantReadingAction $submitTenantReadingAction,
        CompleteReadingRequestInvoiceAction $completeReadingRequestInvoiceAction,
    ): void {
        $payloads = $this->batchPayloads();

        if ($payloads === []) {
            $this->addError('readings', __('tenant.pages.readings.no_values_entered'));

            return;
        }

        try {
            $readings = DB::transaction(function () use ($payloads, $submitTenantReadingAction): array {
                $createdReadings = [];

                foreach ($payloads as $payload) {
                    $this->domainErrorMeterId = (string) $payload['meterId'];
                    $createdReadings[] = $submitTenantReadingAction->handle(
                        tenant: $this->tenant,
                        meterId: $payload['meterId'],
                        readingValue: $payload['readingValue'],
                        readingDate: $payload['readingDate'],
                        notes: $payload['notes'],
                    );
                }

                return $createdReadings;
            });
        } catch (AuthorizationException) {
            $meterId = $this->domainErrorMeterId;
            $this->domainErrorMeterId = null;
            $this->addError($meterId ? "readings.{$meterId}.value" : 'readings', __('tenant.pages.readings.unauthorized_meter'));

            return;
        } catch (ValidationException $exception) {
            $meterId = $this->domainErrorMeterId;
            $this->domainErrorMeterId = null;
            $this->mapDomainErrors($exception, $meterId);

            return;
        }

        $this->domainErrorMeterId = null;
        $this->completeReadingRequestInvoice($readings, $completeReadingRequestInvoiceAction);
        $this->completeSubmission($readings);
    }

    /**
     * @return array{message: string, delta: string|null, warning: string|null}|null
     */
    #[Computed]
    public function consumption(): ?array
    {
        $selectedMeter = $this->selectedMeter;

        if (! $selectedMeter || ! is_numeric($this->readingValue)) {
            return null;
        }

        $previousReading = $selectedMeter->latestReading;

        if ($previousReading === null) {
            return [
                'message' => __('tenant.pages.readings.first_reading'),
                'delta' => null,
                'warning' => null,
            ];
        }

        $delta = (float) $this->readingValue - (float) $previousReading->reading_value;

        return [
            'message' => __('tenant.pages.readings.previous_reading', [
                'value' => $this->formatDecimal((float) $previousReading->reading_value, 3),
                'unit' => $selectedMeter->unit,
                'date' => LocalizedDateFormatter::date($previousReading->reading_date),
            ]),
            'delta' => $this->formatDecimal($delta, 3),
            'warning' => $delta < 0
                ? __('tenant.pages.readings.lower_than_previous_warning')
                : null,
        ];
    }

    private function formatDecimal(float $value, int $precision): string
    {
        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $precision);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $precision);

        return (string) $formatter->format($value);
    }

    private function readingDateDisplay(): string
    {
        return LocalizedDateFormatter::date($this->readingDate);
    }

    /**
     * @return list<array{meterId: string, readingValue: string|int|float, readingDate: string, notes: string|null}>
     */
    private function batchPayloads(): array
    {
        return collect($this->readings)
            ->map(function (mixed $reading, int|string $meterId): ?array {
                if (! is_array($reading)) {
                    return null;
                }

                $readingValue = $reading['value'] ?? null;

                if (! filled($readingValue)) {
                    return null;
                }

                $notes = $this->stringValue($reading['notes'] ?? null);

                return [
                    'meterId' => (string) $meterId,
                    'readingValue' => is_scalar($readingValue) ? $readingValue : '',
                    'readingDate' => $this->readingDate,
                    'notes' => filled($notes) ? $notes : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<MeterReading>  $readings
     */
    private function completeSubmission(array $readings): void
    {
        foreach ($readings as $reading) {
            $reading->loadMissing('meter:id,name,identifier,unit,type');

            $meterId = (string) $reading->meter_id;
            $this->readings[$meterId] = [
                'value' => '',
                'notes' => '',
            ];
        }

        unset(
            $this->availableMeters,
            $this->selectedMeter,
            $this->consumption,
            $this->readingRows,
            $this->meterSelectionLocked,
        );

        $this->submittedReadings = collect($readings)
            ->map(fn (MeterReading $reading): array => $this->submittedReadingPayload($reading))
            ->values()
            ->all();
        $this->submittedReading = $this->submittedReadings[0] ?? null;
        $this->successMessage = $this->submissionSuccessMessage(count($readings));

        $this->dispatch('reading.submitted');
    }

    /**
     * @param  list<MeterReading>  $readings
     */
    private function completeReadingRequestInvoice(
        array $readings,
        CompleteReadingRequestInvoiceAction $completeReadingRequestInvoiceAction,
    ): void {
        $invoiceId = $this->normalizedInvoiceId();

        if ($invoiceId === null) {
            return;
        }

        $completeReadingRequestInvoiceAction->handle($this->tenant, $invoiceId, $readings);

        unset($this->readingRequestInvoiceSummary);
    }

    /**
     * @return array{meter_identifier: string, meter_name: string, unit: string, value: string, date: string}
     */
    private function submittedReadingPayload(MeterReading $reading): array
    {
        return [
            'meter_identifier' => (string) ($reading->meter?->identifier ?? ''),
            'meter_name' => $this->meterDisplayName($reading->meter),
            'unit' => (string) ($reading->meter?->unit ?? ''),
            'value' => $this->formatDecimal((float) $reading->reading_value, 3),
            'date' => LocalizedDateFormatter::date($reading->reading_date),
        ];
    }

    private function meterDisplayName(?Meter $meter): string
    {
        return app(TenantMeterNameLocalizer::class)->displayName($meter);
    }

    private function submissionSuccessMessage(int $readingCount): string
    {
        if ($readingCount > 1) {
            return __('tenant.pages.readings.success_batch', ['count' => $readingCount]);
        }

        return __('tenant.pages.readings.success');
    }

    private function syncReadingRowsWithAvailableMeters(): void
    {
        $availableIds = $this->availableMeters
            ->map(fn (Meter $meter): string => (string) $meter->id)
            ->all();
        $availableKeys = array_fill_keys($availableIds, true);

        foreach (array_keys($this->readings) as $meterId) {
            if (! isset($availableKeys[(string) $meterId])) {
                unset($this->readings[$meterId]);
            }
        }

        foreach ($this->availableMeters as $meter) {
            $meterId = (string) $meter->id;
            $this->readings[$meterId] ??= [];
            $this->readings[$meterId]['value'] ??= '';
            $this->readings[$meterId]['notes'] ??= '';
        }
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @return Collection<int, Meter>
     */
    #[Computed]
    public function availableMeters(): Collection
    {
        $workspace = $this->tenantWorkspace();
        $propertyId = $workspace->propertyId;

        if ($propertyId === null) {
            return collect();
        }

        return Meter::query()
            ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
            ->forOrganization($workspace->organizationId)
            ->forProperty($propertyId)
            ->withLatestReadingSummary()
            ->ordered()
            ->get();
    }

    /**
     * @return array{number: string, period: string, due: string}|null
     */
    #[Computed]
    public function readingRequestInvoiceSummary(): ?array
    {
        $invoiceId = $this->normalizedInvoiceId();

        if ($invoiceId === null) {
            return null;
        }

        $workspace = $this->tenantWorkspace();

        if ($workspace->propertyId === null) {
            return null;
        }

        $invoice = Invoice::query()
            ->select([
                'id',
                'organization_id',
                'property_id',
                'tenant_user_id',
                'invoice_number',
                'billing_period_start',
                'billing_period_end',
                'due_date',
                'status',
                'automation_level',
                'approval_status',
            ])
            ->forOrganization($workspace->organizationId)
            ->whereKey($invoiceId)
            ->where('property_id', $workspace->propertyId)
            ->where('tenant_user_id', $workspace->userId)
            ->where('status', InvoiceStatus::DRAFT->value)
            ->where('automation_level', 'reading_request')
            ->where('approval_status', 'pending')
            ->first();

        if (! $invoice instanceof Invoice) {
            return null;
        }

        return [
            'number' => (string) $invoice->invoice_number,
            'period' => __('tenant.pages.readings.invoice_request_period', [
                'from' => LocalizedDateFormatter::date($invoice->billing_period_start),
                'to' => LocalizedDateFormatter::date($invoice->billing_period_end),
            ]),
            'due' => __('tenant.pages.readings.invoice_request_due', [
                'date' => LocalizedDateFormatter::date($invoice->due_date),
            ]),
        ];
    }

    private function readingRequestUnavailableTitle(): string
    {
        if ($this->normalizedInvoiceId() !== null) {
            return __('tenant.pages.readings.invoice_request_unavailable_title');
        }

        return __('tenant.pages.readings.no_open_request_title');
    }

    private function readingRequestUnavailableMessage(): string
    {
        if ($this->normalizedInvoiceId() !== null) {
            return __('tenant.pages.readings.invoice_request_unavailable');
        }

        return __('tenant.pages.readings.no_open_request');
    }

    #[Computed]
    public function selectedMeter(): ?Meter
    {
        return $this->availableMeters->firstWhere('id', (int) $this->meterId);
    }

    #[Computed]
    public function meterSelectionLocked(): bool
    {
        return $this->availableMeters->count() === 1;
    }

    #[Computed]
    public function tenant(): User
    {
        $workspace = $this->tenantWorkspace();
        $tenant = $this->currentTenant();

        return $tenant->load([
            'currentPropertyAssignment' => fn ($query) => $query
                ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'assigned_at', 'unassigned_at'])
                ->forOrganization($workspace->organizationId)
                ->when(
                    $workspace->propertyId !== null,
                    fn ($query) => $query->forProperty($workspace->propertyId),
                )
                ->current(),
        ]);
    }

    protected function mapDomainErrors(ValidationException $exception, ?string $meterId = null): void
    {
        $fieldMap = $meterId === null ? [
            'reading_date' => 'readingDate',
            'reading_value' => 'readingValue',
            'meter_id' => 'meterId',
            'readingDate' => 'readingDate',
            'readingValue' => 'readingValue',
            'meterId' => 'meterId',
            'notes' => 'notes',
        ] : [
            'reading_date' => 'readingDate',
            'reading_value' => "readings.{$meterId}.value",
            'meter_id' => "readings.{$meterId}.value",
            'readingDate' => 'readingDate',
            'readingValue' => "readings.{$meterId}.value",
            'meterId' => "readings.{$meterId}.value",
            'notes' => "readings.{$meterId}.notes",
        ];

        foreach ($exception->errors() as $field => $messages) {
            foreach ($messages as $message) {
                $this->addError($fieldMap[$field] ?? $field, $message);
            }
        }
    }

    private function normalizedInvoiceId(): ?int
    {
        $invoiceId = trim($this->invoiceId);

        if ($invoiceId === '' || ! ctype_digit($invoiceId)) {
            return null;
        }

        $resolvedInvoiceId = (int) $invoiceId;

        return $resolvedInvoiceId > 0 ? $resolvedInvoiceId : null;
    }
}
