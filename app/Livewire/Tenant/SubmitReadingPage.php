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

        $meters = $this->availableMeters();

        if ($meters->count() === 1) {
            $this->meterId = (string) $meters->firstOrFail()->id;
        }
    }

    public function submit(SubmitTenantReadingAction $submitTenantReadingAction): void
    {
        $validated = $this->validate($this->rules());

        try {
            $reading = $submitTenantReadingAction->handle(
                tenant: $this->tenant(),
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
        $meters = $this->availableMeters();

        return view('livewire.tenant.submit-reading-page', [
            'meters' => $meters,
            'selectedMeter' => $meters->firstWhere('id', (int) $this->meterId),
            'preview' => $this->preview($meters),
            'meterSelectionLocked' => $meters->count() === 1,
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
                Rule::in(
                    $this->availableMeters()
                        ->pluck('id')
                        ->map(fn (int $id): string => (string) $id)
                        ->all()
                ),
            ],
            'readingValue' => ['required', 'numeric', 'gt:0'],
            'readingDate' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @param  Collection<int, Meter>  $meters
     * @return array{message: string, delta: string|null}|null
     */
    protected function preview(Collection $meters): ?array
    {
        $selectedMeter = $meters->firstWhere('id', (int) $this->meterId);

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
    protected function availableMeters(): Collection
    {
        $tenant = $this->tenant();
        $propertyId = $tenant->currentPropertyAssignment?->property_id;

        if ($propertyId === null) {
            return collect();
        }

        return Meter::query()
            ->select(['id', 'organization_id', 'property_id', 'name', 'identifier', 'type', 'status', 'unit'])
            ->with([
                'latestReading' => fn ($query) => $query
                    ->select(['id', 'organization_id', 'meter_id', 'reading_value', 'reading_date', 'validation_status'])
                    ->forOrganization($tenant->organization_id)
                    ->latestFirst(),
            ])
            ->forOrganization($tenant->organization_id)
            ->forProperty($propertyId)
            ->orderBy('name')
            ->get();
    }

    protected function tenant(): User
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
