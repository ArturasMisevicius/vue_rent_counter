<?php

namespace App\Livewire\Tenant;

use App\Filament\Actions\Tenant\Readings\SubmitTenantReadingAction;
use App\Models\Meter;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SubmitReadingPage extends Component
{
    public string $meterId = '';

    public string $readingValue = '';

    public string $readingDate = '';

    public string $notes = '';

    public ?string $successMessage = null;

    /**
     * @var array{meter_identifier: string, meter_name: string, unit: string, value: string, date: string}|null
     */
    public ?array $submittedReading = null;

    public function mount(): void
    {
        $this->readingDate = now()->toDateString();

        if ($this->meterSelectionLocked) {
            $this->meterId = (string) $this->availableMeters->firstOrFail()->id;
        }
    }

    public function submit(SubmitTenantReadingAction $submitTenantReadingAction): void
    {
        $validated = $this->validate();

        try {
            $reading = $submitTenantReadingAction->handle(
                tenant: $this->tenant,
                meterId: (int) $validated['meterId'],
                readingValue: $validated['readingValue'],
                readingDate: $validated['readingDate'],
                notes: filled($validated['notes']) ? $validated['notes'] : null,
            );
        } catch (AuthorizationException) {
            $this->addError('meterId', __('tenant.pages.readings.unauthorized_meter'));

            return;
        } catch (ValidationException $exception) {
            $this->mapDomainErrors($exception);

            return;
        }

        $reading->loadMissing('meter:id,name,identifier,unit');

        unset(
            $this->availableMeters,
            $this->availableMeterIds,
            $this->selectedMeter,
            $this->preview,
            $this->meterSelectionLocked,
        );

        $this->reset('readingValue', 'notes');
        $this->successMessage = __('tenant.pages.readings.success');
        $this->submittedReading = [
            'meter_identifier' => (string) ($reading->meter?->identifier ?? ''),
            'meter_name' => (string) ($reading->meter?->name ?? ''),
            'unit' => (string) ($reading->meter?->unit ?? ''),
            'value' => number_format((float) $reading->reading_value, 3, '.', ''),
            'date' => $reading->reading_date->format('Y-m-d'),
        ];
    }

    public function render(): View
    {
        return view('livewire.tenant.submit-reading-page', [
            'meters' => $this->availableMeters,
            'selectedMeter' => $this->selectedMeter,
            'preview' => $this->preview,
            'meterSelectionLocked' => $this->meterSelectionLocked,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'meterId' => [
                'required',
                'string',
                Rule::in($this->availableMeterIds),
            ],
            'readingValue' => ['required', 'numeric', 'gt:0'],
            'readingDate' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array{message: string, delta: string|null}|null
     */
    #[Computed]
    public function preview(): ?array
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
            ];
        }

        $delta = (float) $this->readingValue - (float) $previousReading->reading_value;

        return [
            'message' => __('tenant.pages.readings.previous_reading', [
                'value' => number_format((float) $previousReading->reading_value, 3),
                'unit' => $selectedMeter->unit,
                'date' => $previousReading->reading_date->format('Y-m-d'),
            ]),
            'delta' => number_format($delta, 3),
        ];
    }

    /**
     * @return Collection<int, Meter>
     */
    #[Computed]
    public function availableMeters(): Collection
    {
        $tenant = $this->tenant;
        $propertyId = $tenant->currentPropertyAssignment?->property_id;

        if ($propertyId === null) {
            return collect();
        }

        return Meter::query()
            ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
            ->forOrganization($tenant->organization_id)
            ->forProperty($propertyId)
            ->withLatestReadingSummary()
            ->ordered()
            ->get();
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function availableMeterIds(): array
    {
        return $this->availableMeters
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->all();
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
        $tenantId = auth()->id();
        $tenant = User::query()
            ->select(['id', 'organization_id', 'role'])
            ->findOrFail($tenantId);

        return $tenant->load([
            'currentPropertyAssignment' => fn ($query) => $query
                ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'assigned_at', 'unassigned_at'])
                ->forOrganization($tenant->organization_id)
                ->current(),
        ]);
    }

    protected function mapDomainErrors(ValidationException $exception): void
    {
        $fieldMap = [
            'reading_date' => 'readingDate',
            'reading_value' => 'readingValue',
            'meter_id' => 'meterId',
        ];

        foreach ($exception->errors() as $field => $messages) {
            $this->addError($fieldMap[$field] ?? $field, $messages[0]);
        }
    }
}
