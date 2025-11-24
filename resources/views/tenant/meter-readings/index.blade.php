@extends('layouts.tenant')

@section('title', __('meter_readings.tenant.title'))

@section('tenant-content')
@php($meterTypeLabels = \App\Enums\MeterType::labels())
<x-tenant.page :title="__('meter_readings.tenant.title')" :description="__('meter_readings.tenant.description')" x-data="consumptionHistory()">
    <x-tenant.quick-actions />

    <x-tenant.section-card :title="__('meter_readings.tenant.filters.title')" :description="__('meter_readings.tenant.filters.description')">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-semibold text-slate-800">{{ __('meter_readings.tenant.filters.meter_type') }}</label>
                <select x-model="filters.meterType" @change="applyFilters()" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('meter_readings.tenant.filters.all_types') }}</option>
                    @foreach($meterTypeLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-800">{{ __('meter_readings.tenant.filters.date_from') }}</label>
                <input x-model="filters.dateFrom" @change="applyFilters()" type="date" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-800">{{ __('meter_readings.tenant.filters.date_to') }}</label>
                <input x-model="filters.dateTo" @change="applyFilters()" type="date" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>
    </x-tenant.section-card>

    <x-tenant.section-card :title="__('meter_readings.tenant.submit.title')" :description="__('meter_readings.tenant.submit.description')">
        @if(($properties ?? collect())->isEmpty())
            <p class="text-sm text-slate-600">{{ __('meter_readings.tenant.submit.no_property') }}</p>
        @else
        <form method="POST" action="{{ route('tenant.meter-readings.store') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            @csrf
            <div>
                <label class="block text-sm font-semibold text-slate-800">{{ __('meter_readings.tenant.submit.meter') }}</label>
                <select name="meter_id" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    @foreach(($properties ?? collect()) as $property)
                        @foreach($property->meters as $meter)
                            <option value="{{ $meter->id }}" {{ old('meter_id') == $meter->id ? 'selected' : '' }}>
                                {{ $meter->serial_number }} ({{ enum_label($meter->type) }})
                            </option>
                        @endforeach
                    @endforeach
                </select>
                @error('meter_id') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-800">{{ __('meter_readings.tenant.submit.reading_date') }}</label>
                <input type="date" name="reading_date" value="{{ old('reading_date') }}" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @error('reading_date') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-800">{{ __('meter_readings.tenant.submit.value') }}</label>
                <input type="number" step="0.01" name="value" value="{{ old('value') }}" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                @error('value') <p class="text-sm text-rose-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto">
                    {{ __('meter_readings.tenant.submit.button') }}
                </button>
            </div>
        </form>
        @endif
    </x-tenant.section-card>

    <x-tenant.stack gap="6">
        <template x-for="(meterReadings, meterName) in groupedReadings" :key="meterName">
            <x-tenant.section-card>
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h3 class="text-lg font-semibold text-slate-900" x-text="meterName"></h3>
                </div>

                <div class="hidden sm:block overflow-hidden rounded-xl border border-slate-200/80 shadow-sm">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-600 sm:pl-6">{{ __('meter_readings.tenant.table.date') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">{{ __('meter_readings.tenant.table.reading') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">{{ __('meter_readings.tenant.table.consumption') }}</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-600">{{ __('meter_readings.tenant.table.zone') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white">
                            <template x-for="(reading, index) in meterReadings" :key="reading.id">
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6" x-text="formatDate(reading.reading_date)"></td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600" x-text="reading.value"></td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">
                                        <span x-show="index < meterReadings.length - 1" x-text="calculateConsumption(reading.value, meterReadings[index + 1].value)"></span>
                                        <span x-show="index === meterReadings.length - 1" class="text-slate-400">-</span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">
                                        <span x-show="reading.zone" x-text="reading.zone" class="inline-flex items-center rounded-md bg-sky-50 px-2 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-700/10"></span>
                                        <span x-show="!reading.zone" class="text-slate-400">-</span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <x-tenant.stack gap="3" class="sm:hidden">
                    <template x-for="(reading, index) in meterReadings" :key="`mobile-${reading.id}`">
                        <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-slate-900" x-text="formatDate(reading.reading_date)"></p>
                                <p class="text-xs font-semibold text-slate-500" x-text="reading.zone || '—'"></p>
                            </div>
                            <p class="mt-1 text-sm text-slate-700">
                                {{ __('meter_readings.tenant.table.reading') }}: <span class="font-semibold" x-text="reading.value"></span>
                            </p>
                            <p class="mt-1 text-sm text-slate-700">
                                {{ __('meter_readings.tenant.table.consumption') }}:
                                <span x-show="index < meterReadings.length - 1" class="font-semibold" x-text="calculateConsumption(reading.value, meterReadings[index + 1].value)"></span>
                                <span x-show="index === meterReadings.length - 1" class="text-slate-400">—</span>
                            </p>
                        </div>
                    </template>
                </x-tenant.stack>
            </x-tenant.section-card>
        </template>

        <div x-show="Object.keys(groupedReadings).length === 0" class="rounded-2xl border border-dashed border-slate-200 bg-white/80 py-12 text-center shadow-sm">
            <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-slate-900">{{ __('meter_readings.tenant.empty.title') }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ __('meter_readings.tenant.empty.description') }}</p>
        </div>
    </x-tenant.stack>
</x-tenant.page>

@push('scripts')
<script>
function consumptionHistory() {
    return {
        readings: @json($readings instanceof \Illuminate\Pagination\AbstractPaginator ? $readings->items() : $readings),
        meterTypeLabels: @json($meterTypeLabels),
        filters: {
            meterType: '{{ request('meter_type') }}',
            dateFrom: '{{ request('date_from') }}',
            dateTo: '{{ request('date_to') }}'
        },
        init() {
            // Default to last 12 months if no date filter was provided
            if (!this.filters.dateFrom) {
                const d = new Date();
                d.setFullYear(d.getFullYear() - 1);
                this.filters.dateFrom = d.toISOString().slice(0, 10);
            }
        },
        
        get groupedReadings() {
            let filtered = this.readings;
            
            // Apply filters
            if (this.filters.meterType) {
                filtered = filtered.filter(r => r.meter.type === this.filters.meterType);
            }
            
            if (this.filters.dateFrom) {
                filtered = filtered.filter(r => r.reading_date >= this.filters.dateFrom);
            }
            
            if (this.filters.dateTo) {
                filtered = filtered.filter(r => r.reading_date <= this.filters.dateTo);
            }
            
            // Group by meter
            const grouped = {};
            filtered.forEach(reading => {
                const key = `${reading.meter.serial_number} (${this.formatMeterType(reading.meter.type)})`;
                if (!grouped[key]) {
                    grouped[key] = [];
                }
                grouped[key].push(reading);
            });
            
            // Sort readings within each group by date descending
            Object.keys(grouped).forEach(key => {
                grouped[key].sort((a, b) => new Date(b.reading_date) - new Date(a.reading_date));
            });
            
            return grouped;
        },
        
        formatDate(dateString) {
            return new Date(dateString).toLocaleDateString('lt-LT', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        },
        
        formatMeterType(type) {
            return this.meterTypeLabels[type] || type;
        },
        
        calculateConsumption(current, previous) {
            const consumption = current - previous;
            return consumption >= 0 ? consumption.toFixed(2) : '0.00';
        },
        
        applyFilters() {
            // Filters are reactive, so this just triggers recalculation
        }
    }
}
</script>
@endpush
@endsection
