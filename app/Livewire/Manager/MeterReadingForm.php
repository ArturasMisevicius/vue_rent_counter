<?php

declare(strict_types=1);

namespace App\Livewire\Manager;

use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Provider;
use App\Models\Tariff;
use App\Services\MeterReadingService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class MeterReadingForm extends Component
{
    use AuthorizesRequests;

    public array $meters = [];

    public array $providers = [];

    public array $availableTariffs = [];

    public ?array $selectedTariff = null;

    public ?array $previousReading = null;

    public ?float $averageConsumption = null;

    public bool $supportsZones = false;

    public string $unitOfMeasurement = '';

    public array $formData = [
        'meter_id' => '',
        'provider_id' => '',
        'tariff_id' => '',
        'reading_date' => '',
        'value' => '',
        'day_value' => '',
        'night_value' => '',
    ];

    public function mount(): void
    {
        $this->formData['reading_date'] = now()->toDateString();

        $this->meters = Meter::query()
            ->with('property')
            ->orderBy('serial_number')
            ->get()
            ->map(fn (Meter $meter): array => [
                'id' => $meter->id,
                'label' => sprintf(
                    '%s - %s (%s)',
                    $meter->serial_number,
                    $meter->getServiceDisplayName(),
                    $meter->property?->address ?? __('app.common.na')
                ),
            ])
            ->values()
            ->all();

        $this->providers = Provider::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Provider $provider): array => [
                'id' => $provider->id,
                'name' => $provider->name,
            ])
            ->values()
            ->all();
    }

    public function updatedFormDataMeterId(string $meterId): void
    {
        $this->resetErrorBag();

        $this->supportsZones = false;
        $this->unitOfMeasurement = '';
        $this->previousReading = null;
        $this->averageConsumption = null;

        $this->formData['value'] = '';
        $this->formData['day_value'] = '';
        $this->formData['night_value'] = '';
        $this->formData['provider_id'] = '';
        $this->formData['tariff_id'] = '';

        $this->availableTariffs = [];
        $this->selectedTariff = null;

        if ($meterId === '') {
            return;
        }

        $meter = Meter::query()->find((int) $meterId);

        if ($meter === null) {
            return;
        }

        $this->supportsZones = $meter->supports_zones;
        $this->unitOfMeasurement = $meter->getUnitOfMeasurement();

        $this->loadPreviousReadingData($meter);
    }

    public function updatedFormDataProviderId(string $providerId): void
    {
        $this->formData['tariff_id'] = '';
        $this->selectedTariff = null;

        if ($providerId === '') {
            $this->availableTariffs = [];

            return;
        }

        $this->availableTariffs = Tariff::query()
            ->where('provider_id', (int) $providerId)
            ->active()
            ->orderBy('name')
            ->get()
            ->map(fn (Tariff $tariff): array => [
                'id' => $tariff->id,
                'name' => $tariff->name,
                'configuration' => $tariff->configuration,
            ])
            ->values()
            ->all();
    }

    public function updatedFormDataTariffId(string $tariffId): void
    {
        if ($tariffId === '') {
            $this->selectedTariff = null;

            return;
        }

        $this->selectedTariff = collect($this->availableTariffs)
            ->first(fn (array $tariff): bool => (int) $tariff['id'] === (int) $tariffId);
    }

    public function resetForm(): void
    {
        $this->resetErrorBag();

        $this->formData = [
            'meter_id' => '',
            'provider_id' => '',
            'tariff_id' => '',
            'reading_date' => now()->toDateString(),
            'value' => '',
            'day_value' => '',
            'night_value' => '',
        ];

        $this->supportsZones = false;
        $this->unitOfMeasurement = '';
        $this->previousReading = null;
        $this->averageConsumption = null;
        $this->availableTariffs = [];
        $this->selectedTariff = null;
    }

    public function submit(): void
    {
        $this->authorize('create', MeterReading::class);

        $validated = $this->validate($this->rules(), $this->messages());

        $meter = Meter::query()->find((int) $validated['formData']['meter_id']);

        if ($meter === null) {
            throw ValidationException::withMessages([
                'formData.meter_id' => __('meter_readings.validation.meter_id.exists'),
            ]);
        }

        $this->validateMonotonicity($meter, $validated['formData']);

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        DB::transaction(function () use ($validated, $meter): void {
            if ($this->supportsZones) {
                MeterReading::query()->create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'meter_id' => $meter->id,
                    'reading_date' => $validated['formData']['reading_date'],
                    'value' => (float) $validated['formData']['day_value'],
                    'zone' => 'day',
                    'entered_by' => auth()->id(),
                ]);

                MeterReading::query()->create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'meter_id' => $meter->id,
                    'reading_date' => $validated['formData']['reading_date'],
                    'value' => (float) $validated['formData']['night_value'],
                    'zone' => 'night',
                    'entered_by' => auth()->id(),
                ]);

                return;
            }

            MeterReading::query()->create([
                'tenant_id' => auth()->user()->tenant_id,
                'meter_id' => $meter->id,
                'reading_date' => $validated['formData']['reading_date'],
                'value' => (float) $validated['formData']['value'],
                'zone' => null,
                'entered_by' => auth()->id(),
            ]);
        });

        session()->flash('success', __('meter_readings.messages.submitted_successfully'));

        $this->redirectRoute('manager.meter-readings.index', navigate: true);
    }

    public function render(): View
    {
        $consumption = $this->calculateConsumption();
        $currentRate = $this->calculateCurrentRate();
        $chargePreview = ($consumption !== null && $consumption >= 0 && $currentRate !== null)
            ? $consumption * $currentRate
            : null;

        $showHighConsumptionWarning = false;
        $showLowConsumptionWarning = false;
        $expectedConsumptionRange = null;

        if ($this->averageConsumption !== null) {
            $expectedConsumptionRange = sprintf(
                '%.1f - %.1f',
                $this->averageConsumption * 0.5,
                $this->averageConsumption * 1.5
            );

            if ($consumption !== null && $consumption > 0) {
                $showHighConsumptionWarning = $consumption > ($this->averageConsumption * 2.5);
                $showLowConsumptionWarning = $this->averageConsumption > 0
                    && $consumption < ($this->averageConsumption * 0.1);
            }
        }

        return view('livewire.manager.meter-reading-form', [
            'consumption' => $consumption,
            'currentRate' => $currentRate,
            'chargePreview' => $chargePreview,
            'showHighConsumptionWarning' => $showHighConsumptionWarning,
            'showLowConsumptionWarning' => $showLowConsumptionWarning,
            'expectedConsumptionRange' => $expectedConsumptionRange,
        ]);
    }

    private function rules(): array
    {
        return [
            'formData.meter_id' => ['required', 'integer', 'exists:meters,id'],
            'formData.provider_id' => ['nullable', 'integer', 'exists:providers,id'],
            'formData.tariff_id' => ['nullable', 'integer', 'exists:tariffs,id'],
            'formData.reading_date' => ['required', 'date', 'before_or_equal:today'],
            'formData.value' => [$this->supportsZones ? 'nullable' : 'required', 'numeric', 'min:0'],
            'formData.day_value' => [$this->supportsZones ? 'required' : 'nullable', 'numeric', 'min:0'],
            'formData.night_value' => [$this->supportsZones ? 'required' : 'nullable', 'numeric', 'min:0'],
        ];
    }

    private function messages(): array
    {
        return [
            'formData.meter_id.required' => __('meter_readings.validation.meter_id.required'),
            'formData.meter_id.exists' => __('meter_readings.validation.meter_id.exists'),
            'formData.reading_date.required' => __('meter_readings.validation.reading_date.required'),
            'formData.reading_date.date' => __('meter_readings.validation.reading_date.date'),
            'formData.reading_date.before_or_equal' => __('meter_readings.validation.reading_date.before_or_equal'),
            'formData.value.required' => __('meter_readings.validation.value.required'),
            'formData.value.numeric' => __('meter_readings.validation.value.numeric'),
            'formData.value.min' => __('meter_readings.validation.value.min'),
            'formData.day_value.required' => __('meter_readings.validation.value.required'),
            'formData.day_value.numeric' => __('meter_readings.validation.value.numeric'),
            'formData.day_value.min' => __('meter_readings.validation.value.min'),
            'formData.night_value.required' => __('meter_readings.validation.value.required'),
            'formData.night_value.numeric' => __('meter_readings.validation.value.numeric'),
            'formData.night_value.min' => __('meter_readings.validation.value.min'),
        ];
    }

    private function loadPreviousReadingData(Meter $meter): void
    {
        $service = app(MeterReadingService::class);

        if ($meter->supports_zones) {
            $dayReading = $service->getPreviousReading($meter, 'day');
            $nightReading = $service->getPreviousReading($meter, 'night');

            if ($dayReading === null && $nightReading === null) {
                return;
            }

            $dayAverage = $service->getAverageConsumption($meter, 'day');
            $nightAverage = $service->getAverageConsumption($meter, 'night');

            $this->averageConsumption = ($dayAverage === null && $nightAverage === null)
                ? null
                : ($dayAverage ?? 0.0) + ($nightAverage ?? 0.0);

            $date = $dayReading?->reading_date?->toDateString() ?? $nightReading?->reading_date?->toDateString();

            $this->previousReading = [
                'date' => $date,
                'day_value' => $dayReading?->value,
                'night_value' => $nightReading?->value,
                'value' => (float) ($dayReading?->value ?? 0) + (float) ($nightReading?->value ?? 0),
            ];

            return;
        }

        $previous = $service->getPreviousReading($meter, null);

        if ($previous === null) {
            return;
        }

        $this->averageConsumption = $service->getAverageConsumption($meter, null);

        $this->previousReading = [
            'date' => $previous->reading_date->toDateString(),
            'value' => $previous->value,
            'zone' => $previous->zone,
        ];
    }

    private function validateMonotonicity(Meter $meter, array $validatedData): void
    {
        $service = app(MeterReadingService::class);
        $readingDate = (string) $validatedData['reading_date'];

        if ($this->supportsZones) {
            $dayValue = (float) $validatedData['day_value'];
            $nightValue = (float) $validatedData['night_value'];

            $previousDay = $service->getPreviousReading($meter, 'day', $readingDate);
            $previousNight = $service->getPreviousReading($meter, 'night', $readingDate);

            if ($previousDay !== null && $dayValue < (float) $previousDay->value) {
                $this->addError('formData.day_value', __('meter_readings.validation.custom.monotonicity_lower', [
                    'previous' => $previousDay->value,
                ]));
            }

            if ($previousNight !== null && $nightValue < (float) $previousNight->value) {
                $this->addError('formData.night_value', __('meter_readings.validation.custom.monotonicity_lower', [
                    'previous' => $previousNight->value,
                ]));
            }

            return;
        }

        $value = (float) $validatedData['value'];
        $previous = $service->getPreviousReading($meter, null, $readingDate);

        if ($previous !== null && $value < (float) $previous->value) {
            $this->addError('formData.value', __('meter_readings.validation.custom.monotonicity_lower', [
                'previous' => $previous->value,
            ]));
        }
    }

    private function calculateConsumption(): ?float
    {
        if ($this->previousReading === null) {
            return null;
        }

        if ($this->supportsZones) {
            $dayPrevious = (float) ($this->previousReading['day_value'] ?? 0);
            $nightPrevious = (float) ($this->previousReading['night_value'] ?? 0);

            $dayCurrent = (float) ($this->formData['day_value'] !== '' ? $this->formData['day_value'] : 0);
            $nightCurrent = (float) ($this->formData['night_value'] !== '' ? $this->formData['night_value'] : 0);

            return ($dayCurrent - $dayPrevious) + ($nightCurrent - $nightPrevious);
        }

        $previous = (float) ($this->previousReading['value'] ?? 0);
        $current = (float) ($this->formData['value'] !== '' ? $this->formData['value'] : 0);

        return $current - $previous;
    }

    private function calculateCurrentRate(): ?float
    {
        if ($this->selectedTariff === null) {
            return null;
        }

        $configuration = $this->selectedTariff['configuration'] ?? [];

        if (($configuration['type'] ?? null) === 'flat' && isset($configuration['rate']) && is_numeric($configuration['rate'])) {
            return (float) $configuration['rate'];
        }

        if (($configuration['type'] ?? null) === 'time_of_use' && isset($configuration['zones']) && is_array($configuration['zones'])) {
            $rates = collect($configuration['zones'])
                ->pluck('rate')
                ->filter(fn ($rate): bool => is_numeric($rate))
                ->map(fn ($rate): float => (float) $rate)
                ->values();

            if ($rates->isNotEmpty()) {
                return $rates->avg();
            }
        }

        return null;
    }
}
