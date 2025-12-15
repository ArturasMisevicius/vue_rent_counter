@props(['meters' => [], 'providers' => []])

<div x-data="meterReadingForm()" class="bg-white shadow-md rounded-lg p-6">
    <h2 class="text-2xl font-bold mb-6">{{ __('meter_readings.form_component.title') }}</h2>
    
    <form @submit.prevent="submitReading" class="space-y-6">
        <!-- Meter Selection -->
        <div>
            <label for="meter_id" class="block text-sm font-medium text-slate-700 mb-2">
                {{ __('meter_readings.form_component.select_meter') }}
            </label>
            <select 
                id="meter_id"
                x-model="formData.meter_id" 
                @change="onMeterChange()"
                class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
            >
                <option value="">{{ __('meter_readings.form_component.meter_placeholder') }}</option>
                @foreach($meters as $meter)
                        <option value="{{ $meter->id }}" 
                            data-service="{{ $meter->getServiceDisplayName() }}"
                            data-unit="{{ $meter->getUnitOfMeasurement() }}"
                            data-supports-zones="{{ $meter->supports_zones ? 'true' : 'false' }}"
                            data-serial="{{ $meter->serial_number }}"
                            data-property="{{ $meter->property->address ?? '' }}">
                        {{ $meter->serial_number }} - {{ $meter->getServiceDisplayName() }} ({{ $meter->property->address ?? __('app.common.na') }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Provider Selection (Dynamic) -->
        <div x-show="formData.meter_id">
            <label for="provider_id" class="block text-sm font-medium text-slate-700 mb-2">
                {{ __('meter_readings.form_component.select_provider') }}
            </label>
            <select 
                id="provider_id"
                x-model="formData.provider_id" 
                @change="onProviderChange()"
                class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
            >
                <option value="">{{ __('meter_readings.form_component.provider_placeholder') }}</option>
                <template x-for="provider in availableProviders" :key="provider.id">
                    <option :value="provider.id" x-text="provider.name"></option>
                </template>
            </select>
        </div>

        <!-- Tariff Selection (Dynamic) -->
        <div x-show="formData.provider_id">
            <label for="tariff_id" class="block text-sm font-medium text-slate-700 mb-2">
                {{ __('meter_readings.form_component.select_tariff') }}
            </label>
            <select 
                id="tariff_id"
                x-model="formData.tariff_id"
                @change="onTariffChange()"
                class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
            >
                <option value="">{{ __('meter_readings.form_component.tariff_placeholder') }}</option>
                <template x-for="tariff in availableTariffs" :key="tariff.id">
                    <option :value="tariff.id" x-text="tariff.name"></option>
                </template>
            </select>
        </div>

        <!-- Previous Reading Display -->
        <div x-show="previousReading !== null" class="bg-blue-50 border border-blue-200 rounded-md p-4">
            <h3 class="text-sm font-semibold text-blue-900 mb-2">{{ __('meter_readings.form_component.previous') }}</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-slate-600">{{ __('meter_readings.form_component.date_label') }}</span>
                    <span class="font-medium ml-2" x-text="previousReading?.date || naText"></span>
                </div>
                <div>
                    <span class="text-slate-600">{{ __('meter_readings.form_component.value_label') }}</span>
                    <span class="font-medium ml-2" x-text="previousReading?.value || naText"></span>
                </div>
            </div>
        </div>

        <!-- Reading Date -->
        <div>
            <label for="reading_date" class="block text-sm font-medium text-slate-700 mb-2">
                {{ __('meter_readings.form_component.reading_date') }}
            </label>
            <input 
                type="date" 
                id="reading_date"
                x-model="formData.reading_date"
                :max="maxDate"
                class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
            >
            <p x-show="errors.reading_date" class="mt-1 text-sm text-red-600" x-text="errors.reading_date"></p>
        </div>

        <!-- Reading Value (Single or Multi-zone) -->
        <div x-show="!supportsZones">
            <label for="value" class="block text-sm font-medium text-slate-700 mb-2">
                {{ __('meter_readings.form_component.reading_value') }}
            </label>
            <input 
                type="number" 
                id="value"
                x-model="formData.value"
                @input="validateReading()"
                step="0.01"
                min="0"
                class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2"
                :class="errors.value ? 'border-red-500 focus:ring-red-500' : 'border-slate-300 focus:ring-blue-500'"
                required
            >
            <p x-show="errors.value" class="mt-1 text-sm text-red-600" x-text="errors.value"></p>
        </div>

        <!-- Multi-zone Readings (for electricity with zones) -->
        <div x-show="supportsZones" class="space-y-4">
            <div>
                <label for="day_value" class="block text-sm font-medium text-slate-700 mb-2">
                    {{ __('meter_readings.form_component.day_zone') }}
                </label>
                <input 
                    type="number" 
                    id="day_value"
                    x-model="formData.day_value"
                    @input="validateReading()"
                    step="0.01"
                    min="0"
                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2"
                    :class="errors.day_value ? 'border-red-500 focus:ring-red-500' : 'border-slate-300 focus:ring-blue-500'"
                >
                <p x-show="errors.day_value" class="mt-1 text-sm text-red-600" x-text="errors.day_value"></p>
            </div>
            
            <div>
                <label for="night_value" class="block text-sm font-medium text-slate-700 mb-2">
                    {{ __('meter_readings.form_component.night_zone') }}
                </label>
                <input 
                    type="number" 
                    id="night_value"
                    x-model="formData.night_value"
                    @input="validateReading()"
                    step="0.01"
                    min="0"
                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2"
                    :class="errors.night_value ? 'border-red-500 focus:ring-red-500' : 'border-slate-300 focus:ring-blue-500'"
                >
                <p x-show="errors.night_value" class="mt-1 text-sm text-red-600" x-text="errors.night_value"></p>
            </div>
        </div>

        <!-- Consumption Display -->
        <div x-show="consumption !== null && consumption >= 0" class="bg-green-50 border border-green-200 rounded-md p-4">
            <h3 class="text-sm font-semibold text-green-900 mb-2">{{ __('meter_readings.form_component.consumption') }}</h3>
            <div class="text-2xl font-bold text-green-700" x-text="consumption.toFixed(2)"></div>
            <p class="text-sm text-slate-600 mt-1">
                {{ __('meter_readings.form_component.units') }}:
                <span class="font-semibold text-slate-900" x-text="unitOfMeasurement || '—'"></span>
            </p>
        </div>

        <!-- Charge Preview -->
        <div x-show="chargePreview !== null" class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
            <h3 class="text-sm font-semibold text-yellow-900 mb-2">{{ __('meter_readings.form_component.estimated_charge') }}</h3>
            <div class="text-2xl font-bold text-yellow-700">
                €<span x-text="chargePreview.toFixed(2)"></span>
            </div>
            <p class="text-sm text-slate-600 mt-1" x-show="selectedTariff">
                {{ __('meter_readings.form_component.rate') }} €<span x-text="currentRate?.toFixed(4) || '0.0000'"></span> {{ __('meter_readings.form_component.per_unit') }}
            </p>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-4">
            <button 
                type="button"
                @click="resetForm()"
                class="px-4 py-2 border border-slate-300 rounded-md text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                {{ __('meter_readings.form_component.reset') }}
            </button>
            <button 
                type="submit"
                :disabled="!isValid || isSubmitting"
                class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span x-show="!isSubmitting">{{ __('meter_readings.form_component.submit') }}</span>
                <span x-show="isSubmitting">{{ __('meter_readings.form_component.submitting') }}</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function meterReadingForm() {
    return {
        formData: {
            meter_id: '',
            provider_id: '',
            tariff_id: '',
            reading_date: new Date().toISOString().split('T')[0],
            value: '',
            day_value: '',
            night_value: '',
            zone: null
        },
        previousReading: null,
        availableProviders: @json($providers),
        availableTariffs: [],
        selectedTariff: null,
        supportsZones: false,
        unitOfMeasurement: '',
        naText: @js(__('app.common.na')),
        errors: {},
        isSubmitting: false,
        maxDate: new Date().toISOString().split('T')[0],
        
        get consumption() {
            if (this.previousReading === null) return null;
            
            if (this.supportsZones) {
                const dayConsumption = parseFloat(this.formData.day_value || 0) - parseFloat(this.previousReading.day_value || 0);
                const nightConsumption = parseFloat(this.formData.night_value || 0) - parseFloat(this.previousReading.night_value || 0);
                return dayConsumption + nightConsumption;
            }
            
            const current = parseFloat(this.formData.value || 0);
            const previous = parseFloat(this.previousReading.value || 0);
            return current - previous;
        },
        
        get currentRate() {
            if (!this.selectedTariff) return null;
            
            const config = this.selectedTariff.configuration;
            if (config.type === 'flat') {
                return config.rate;
            }
            
            // For time-of-use, use average rate for preview
            if (config.type === 'time_of_use' && config.zones) {
                const rates = config.zones.map(z => z.rate);
                return rates.reduce((a, b) => a + b, 0) / rates.length;
            }
            
            return null;
        },
        
        get chargePreview() {
            if (this.consumption === null || this.consumption < 0 || this.currentRate === null) {
                return null;
            }
            
            return this.consumption * this.currentRate;
        },
        
        get isValid() {
            return Object.keys(this.errors).length === 0 
                && this.formData.meter_id 
                && this.formData.reading_date
                && (this.supportsZones ? (this.formData.day_value || this.formData.night_value) : this.formData.value);
        },
        
        async onMeterChange() {
            const select = document.getElementById('meter_id');
            const option = select.options[select.selectedIndex];
            
            if (!option || !option.value) {
                this.resetMeterData();
                return;
            }
            
            this.supportsZones = option.dataset.supportsZones === 'true';
            this.unitOfMeasurement = option.dataset.unit || '';
            
            // Reset provider and tariff
            this.formData.provider_id = '';
            this.formData.tariff_id = '';
            this.availableTariffs = [];
            this.selectedTariff = null;
            
            // Load previous reading
            await this.loadPreviousReading();
        },
        
        async loadPreviousReading() {
            if (!this.formData.meter_id) return;
            
            try {
                const response = await fetch(`/api/meters/${this.formData.meter_id}/last-reading`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    this.previousReading = await response.json();
                } else {
                    this.previousReading = null;
                }
            } catch (error) {
                console.error('Error loading previous reading:', error);
                this.previousReading = null;
            }
        },
        
        async onProviderChange() {
            if (!this.formData.provider_id) {
                this.availableTariffs = [];
                this.formData.tariff_id = '';
                this.selectedTariff = null;
                return;
            }
            
            try {
                const response = await fetch(`/api/providers/${this.formData.provider_id}/tariffs`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                if (response.ok) {
                    this.availableTariffs = await response.json();
                } else {
                    this.availableTariffs = [];
                }
            } catch (error) {
                console.error('Error loading tariffs:', error);
                this.availableTariffs = [];
            }
            
            this.formData.tariff_id = '';
            this.selectedTariff = null;
        },
        
        onTariffChange() {
            if (!this.formData.tariff_id) {
                this.selectedTariff = null;
                return;
            }
            
            this.selectedTariff = this.availableTariffs.find(t => t.id == this.formData.tariff_id);
        },
        
        validateReading() {
            this.errors = {};
            
            // Validate reading date
            if (this.formData.reading_date > this.maxDate) {
                this.errors.reading_date = 'Reading date cannot be in the future';
            }
            
            if (!this.previousReading) return;
            
            // Validate monotonicity
            if (this.supportsZones) {
                if (this.formData.day_value && parseFloat(this.formData.day_value) < parseFloat(this.previousReading.day_value || 0)) {
                    this.errors.day_value = `Reading cannot be lower than previous reading (${this.previousReading.day_value})`;
                }
                if (this.formData.night_value && parseFloat(this.formData.night_value) < parseFloat(this.previousReading.night_value || 0)) {
                    this.errors.night_value = `Reading cannot be lower than previous reading (${this.previousReading.night_value})`;
                }
            } else {
                if (this.formData.value && parseFloat(this.formData.value) < parseFloat(this.previousReading.value || 0)) {
                    this.errors.value = `Reading cannot be lower than previous reading (${this.previousReading.value})`;
                }
            }
        },
        
        async submitReading() {
            this.validateReading();
            
            if (!this.isValid) {
                return;
            }
            
            this.isSubmitting = true;
            
            try {
                const response = await fetch('/api/meter-readings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.formData)
                });
                
                if (response.ok) {
                    window.location.href = '/manager/meter-readings?success=Reading submitted successfully';
                } else {
                    const data = await response.json();
                    if (data.errors) {
                        this.errors = data.errors;
                    } else {
                        alert('Error submitting reading. Please try again.');
                    }
                }
            } catch (error) {
                console.error('Error submitting reading:', error);
                alert('Error submitting reading. Please try again.');
            } finally {
                this.isSubmitting = false;
            }
        },
        
        resetForm() {
            this.formData = {
                meter_id: '',
                provider_id: '',
                tariff_id: '',
                reading_date: new Date().toISOString().split('T')[0],
                value: '',
                day_value: '',
                night_value: '',
                zone: null
            };
            this.resetMeterData();
        },
        
        resetMeterData() {
            this.previousReading = null;
            this.availableTariffs = [];
            this.selectedTariff = null;
            this.supportsZones = false;
            this.unitOfMeasurement = '';
            this.errors = {};
        }
    }
}
</script>
@endpush
