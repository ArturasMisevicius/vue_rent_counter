@extends('layouts.app')

@section('title', 'My Consumption History')

@section('content')
@php($meterTypeLabels = \App\Enums\MeterType::labels())
<div class="px-4 sm:px-6 lg:px-8" x-data="consumptionHistory()">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">My Consumption History</h1>
            <p class="mt-2 text-sm text-gray-700">View your meter readings and consumption patterns</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mt-6 bg-white shadow sm:rounded-lg p-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700">Meter Type</label>
                <select x-model="filters.meterType" @change="applyFilters()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">All Types</option>
                    @foreach($meterTypeLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Date From</label>
                <input x-model="filters.dateFrom" @change="applyFilters()" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Date To</label>
                <input x-model="filters.dateTo" @change="applyFilters()" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
        </div>
    </div>

    <!-- Readings by Meter -->
    <div class="mt-8 space-y-6">
        <template x-for="(meterReadings, meterName) in groupedReadings" :key="meterName">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" x-text="meterName"></h3>
                    
                    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Date</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Reading</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Consumption</th>
                                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Zone</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <template x-for="(reading, index) in meterReadings" :key="reading.id">
                                    <tr>
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6" x-text="formatDate(reading.reading_date)"></td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500" x-text="reading.value"></td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <span x-show="index < meterReadings.length - 1" x-text="calculateConsumption(reading.value, meterReadings[index + 1].value)"></span>
                                            <span x-show="index === meterReadings.length - 1" class="text-gray-400">-</span>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                            <span x-show="reading.zone" x-text="reading.zone" class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10"></span>
                                            <span x-show="!reading.zone" class="text-gray-400">-</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="Object.keys(groupedReadings).length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No readings found</h3>
            <p class="mt-1 text-sm text-gray-500">No meter readings match your current filters.</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function consumptionHistory() {
    return {
        readings: @json($readings),
        meterTypeLabels: @json($meterTypeLabels),
        filters: {
            meterType: '{{ request('meter_type') }}',
            dateFrom: '{{ request('date_from') }}',
            dateTo: '{{ request('date_to') }}'
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
