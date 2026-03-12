<div class="bg-white shadow-md rounded-lg p-6">
    <h2 class="text-2xl font-bold mb-6">{{ __('meter_readings.form_component.title') }}</h2>

    <form wire:submit="submit" class="space-y-6">
        <div>
            <label for="meter_id" class="block text-sm font-medium text-slate-700 mb-2">
                {{ __('meter_readings.form_component.select_meter') }}
            </label>
            <select
                id="meter_id"
                wire:model.live="formData.meter_id"
                class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
            >
                <option value="">{{ __('meter_readings.form_component.meter_placeholder') }}</option>
                @foreach ($meters as $meter)
                    <option value="{{ $meter['id'] }}">{{ $meter['label'] }}</option>
                @endforeach
            </select>
            @error('formData.meter_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="provider_id" class="block text-sm font-medium text-slate-700 mb-2">
                {{ __('meter_readings.form_component.select_provider') }}
            </label>
            <select
                id="provider_id"
                wire:model.live="formData.provider_id"
                class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <option value="">{{ __('meter_readings.form_component.provider_placeholder') }}</option>
                @foreach ($providers as $provider)
                    <option value="{{ $provider['id'] }}">{{ $provider['name'] }}</option>
                @endforeach
            </select>
            @error('formData.provider_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="tariff_id" class="block text-sm font-medium text-slate-700 mb-2">
                {{ __('meter_readings.form_component.select_tariff') }}
            </label>
            <select
                id="tariff_id"
                wire:model.live="formData.tariff_id"
                class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                @disabled($formData['provider_id'] === '')
            >
                <option value="">{{ __('meter_readings.form_component.tariff_placeholder') }}</option>
                @foreach ($availableTariffs as $tariff)
                    <option value="{{ $tariff['id'] }}">{{ $tariff['name'] }}</option>
                @endforeach
            </select>
            @error('formData.tariff_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        @if ($previousReading !== null)
            <div class="bg-blue-50 border-l-4 border-blue-500 rounded-md p-4">
                <h3 class="text-sm font-semibold text-blue-900">{{ __('meter_readings.form_component.previous') }}</h3>
                <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-slate-600">{{ __('meter_readings.form_component.date_label') }}:</span>
                        <span class="font-medium text-blue-900 ml-2">{{ $previousReading['date'] ?? __('app.common.na') }}</span>
                    </div>
                    <div>
                        <span class="text-slate-600">{{ __('meter_readings.form_component.value_label') }}:</span>
                        <span class="font-bold text-blue-900 text-lg ml-2">{{ $previousReading['value'] ?? __('app.common.na') }}</span>
                        <span class="text-slate-500 text-xs ml-1">{{ $unitOfMeasurement }}</span>
                    </div>
                </div>
                <p class="mt-2 text-xs text-blue-700">{{ __('meter_readings.form_component.previous_reading_info') }}</p>
            </div>
        @endif

        <div>
            <label for="reading_date" class="block text-sm font-medium text-slate-700 mb-2">
                {{ __('meter_readings.form_component.reading_date') }}
            </label>
            <input
                type="date"
                id="reading_date"
                wire:model="formData.reading_date"
                max="{{ now()->toDateString() }}"
                class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
            >
            @error('formData.reading_date')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        @if ($supportsZones)
            <div class="space-y-4">
                <div>
                    <label for="day_value" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('meter_readings.form_component.day_zone') }}
                    </label>
                    <input
                        type="number"
                        id="day_value"
                        wire:model.live="formData.day_value"
                        step="0.01"
                        min="0"
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    @error('formData.day_value')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="night_value" class="block text-sm font-medium text-slate-700 mb-2">
                        {{ __('meter_readings.form_component.night_zone') }}
                    </label>
                    <input
                        type="number"
                        id="night_value"
                        wire:model.live="formData.night_value"
                        step="0.01"
                        min="0"
                        class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                    @error('formData.night_value')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        @else
            <div>
                <label for="value" class="block text-sm font-medium text-slate-700 mb-2">
                    {{ __('meter_readings.form_component.reading_value') }}
                </label>
                <input
                    type="number"
                    id="value"
                    wire:model.live="formData.value"
                    step="0.01"
                    min="0"
                    class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    required
                >
                @error('formData.value')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        @if ($consumption !== null && $consumption >= 0)
            <div class="bg-green-50 border border-green-200 rounded-md p-4">
                <h3 class="text-sm font-semibold text-green-900 mb-2">{{ __('meter_readings.form_component.consumption') }}</h3>
                <div class="text-2xl font-bold text-green-700">{{ number_format($consumption, 2) }}</div>
                <p class="text-sm text-slate-600 mt-1">
                    {{ __('meter_readings.form_component.units') }}:
                    <span class="font-semibold text-slate-900">{{ $unitOfMeasurement !== '' ? $unitOfMeasurement : '—' }}</span>
                </p>
            </div>
        @endif

        @if ($showHighConsumptionWarning)
            <div class="bg-amber-50 border-l-4 border-amber-500 rounded-md p-4">
                <h3 class="text-sm font-medium text-amber-800">{{ __('meter_readings.form_component.consumption_warning') }}</h3>
                @if ($expectedConsumptionRange)
                    <p class="mt-1 text-xs text-amber-700">
                        Expected range: {{ $expectedConsumptionRange }} {{ $unitOfMeasurement }}
                    </p>
                @endif
            </div>
        @endif

        @if ($showLowConsumptionWarning)
            <div class="bg-blue-50 border-l-4 border-blue-400 rounded-md p-4">
                <h3 class="text-sm font-medium text-blue-800">{{ __('meter_readings.form_component.consumption_low_warning') }}</h3>
                @if ($expectedConsumptionRange)
                    <p class="mt-1 text-xs text-blue-700">
                        Expected range: {{ $expectedConsumptionRange }} {{ $unitOfMeasurement }}
                    </p>
                @endif
            </div>
        @endif

        @if ($chargePreview !== null)
            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                <h3 class="text-sm font-semibold text-yellow-900 mb-2">{{ __('meter_readings.form_component.estimated_charge') }}</h3>
                <div class="text-2xl font-bold text-yellow-700">
                    €{{ number_format($chargePreview, 2) }}
                </div>
                @if ($currentRate !== null)
                    <p class="text-sm text-slate-600 mt-1">
                        {{ __('meter_readings.form_component.rate') }}
                        €{{ number_format($currentRate, 4) }} {{ __('meter_readings.form_component.per_unit') }}
                    </p>
                @endif
            </div>
        @endif

        <div class="flex justify-end gap-4">
            <button
                type="button"
                wire:click="resetForm"
                class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                {{ __('meter_readings.form_component.reset') }}
            </button>

            <button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="submit"
                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span wire:loading.remove wire:target="submit">{{ __('meter_readings.form_component.submit') }}</span>
                <span wire:loading wire:target="submit">{{ __('meter_readings.form_component.submitting') }}</span>
            </button>
        </div>
    </form>
</div>
