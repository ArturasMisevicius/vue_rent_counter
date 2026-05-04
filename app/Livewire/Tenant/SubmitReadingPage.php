<?php

namespace App\Livewire\Tenant;

use App\Filament\Actions\Tenant\Readings\SubmitTenantReadingAction;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Livewire\Concerns\AppliesShellLocale;
use App\Livewire\Concerns\ResolvesTenantWorkspace;
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
use Livewire\Component;

class SubmitReadingPage extends Component
{
    use AppliesShellLocale;
    use ResolvesTenantWorkspace;

    public string $meterId = '';

    public string $readingValue = '';

    public string $readingDate = '';

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
        $this->readingDate = now()->toDateString();
        $this->syncReadingRowsWithAvailableMeters();

        if ($this->meterSelectionLocked) {
            $this->meterId = (string) $this->availableMeters->firstOrFail()->id;
        }
    }

    public function submit(SubmitTenantReadingAction $submitTenantReadingAction): void
    {
        $this->resetErrorBag();
        $this->successMessage = null;
        $this->submittedReading = null;
        $this->submittedReadings = [];

        if (filled($this->readingValue)) {
            $this->submitLegacyReading($submitTenantReadingAction);

            return;
        }

        $this->submitBatchReadings($submitTenantReadingAction);
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
            'consumption' => $this->consumption,
            'meterSelectionLocked' => $this->meterSelectionLocked,
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
                    'name' => (string) $meter->name,
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

    private function submitLegacyReading(SubmitTenantReadingAction $submitTenantReadingAction): void
    {
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
        $this->completeSubmission([$reading]);
    }

    private function submitBatchReadings(SubmitTenantReadingAction $submitTenantReadingAction): void
    {
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
            $reading->loadMissing('meter:id,name,identifier,unit');

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
     * @return array{meter_identifier: string, meter_name: string, unit: string, value: string, date: string}
     */
    private function submittedReadingPayload(MeterReading $reading): array
    {
        return [
            'meter_identifier' => (string) ($reading->meter?->identifier ?? ''),
            'meter_name' => (string) ($reading->meter?->name ?? ''),
            'unit' => (string) ($reading->meter?->unit ?? ''),
            'value' => $this->formatDecimal((float) $reading->reading_value, 3),
            'date' => LocalizedDateFormatter::date($reading->reading_date),
        ];
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
            $this->addError($fieldMap[$field] ?? $field, $messages[0]);
        }
    }
}
