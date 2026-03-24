<?php

namespace App\Livewire\Tenant;

use App\Filament\Actions\Tenant\Readings\SubmitTenantReadingAction;
use App\Livewire\Concerns\AppliesShellLocale;
use App\Livewire\Concerns\ResolvesTenantWorkspace;
use App\Models\Meter;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
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

    public ?string $successMessage = null;

    /**
     * @var array{meter_identifier: string, meter_name: string, unit: string, value: string, date: string}|null
     */
    public ?array $submittedReading = null;

    public function mount(): void
    {
        $this->tenantWorkspace();
        $this->readingDate = now()->toDateString();

        if ($this->meterSelectionLocked) {
            $this->meterId = (string) $this->availableMeters->firstOrFail()->id;
        }
    }

    public function submit(SubmitTenantReadingAction $submitTenantReadingAction): void
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

        $reading->loadMissing('meter:id,name,identifier,unit');

        unset(
            $this->availableMeters,
            $this->selectedMeter,
            $this->consumption,
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

        $this->dispatch('reading.submitted');
    }

    #[On('shell-locale-updated')]
    public function refreshTranslations(): void
    {
        $this->applyShellLocale();

        unset(
            $this->availableMeters,
            $this->selectedMeter,
            $this->consumption,
            $this->meterSelectionLocked,
            $this->tenant,
        );

        if ($this->successMessage !== null) {
            $this->successMessage = __('tenant.pages.readings.success');
        }
    }

    public function render(): View
    {
        return view('livewire.tenant.submit-reading-page', [
            'meters' => $this->availableMeters,
            'selectedMeter' => $this->selectedMeter,
            'consumption' => $this->consumption,
            'meterSelectionLocked' => $this->meterSelectionLocked,
        ]);
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
                'value' => number_format((float) $previousReading->reading_value, 3),
                'unit' => $selectedMeter->unit,
                'date' => $previousReading->reading_date->format('Y-m-d'),
            ]),
            'delta' => number_format($delta, 3),
            'warning' => $delta < 0
                ? __('tenant.pages.readings.lower_than_previous_warning')
                : null,
        ];
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
