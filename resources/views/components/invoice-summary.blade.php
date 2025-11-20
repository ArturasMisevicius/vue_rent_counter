@props(['invoice', 'showPropertyFilter' => false, 'properties' => []])

<div class="invoice-summary bg-white shadow-md rounded-lg p-6">
    {{-- Invoice Header --}}
    <div class="invoice-header mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h3 class="text-2xl font-bold text-gray-800">Invoice #{{ $invoice->id }}</h3>
                <p class="text-gray-600 mt-1">
                    Period: {{ $invoice->billing_period_start->format('Y-m-d') }} - {{ $invoice->billing_period_end->format('Y-m-d') }}
                </p>
                @if($invoice->tenant)
                    <p class="text-gray-600">
                        Tenant: {{ $invoice->tenant->name }}
                    </p>
                    @if($invoice->tenant->property)
                        <p class="text-gray-600">
                            Property: {{ $invoice->tenant->property->address }}
                        </p>
                    @endif
                @endif
            </div>
            <div class="text-right">
                <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold
                    @if($invoice->status->value === 'draft') bg-yellow-100 text-yellow-800
                    @elseif($invoice->status->value === 'finalized') bg-blue-100 text-blue-800
                    @elseif($invoice->status->value === 'paid') bg-green-100 text-green-800
                    @endif">
                    {{ ucfirst($invoice->status->value) }}
                </span>
                @if($invoice->finalized_at)
                    <p class="text-sm text-gray-500 mt-2">
                        Finalized: {{ $invoice->finalized_at->format('Y-m-d H:i') }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Property Filter (for multi-property tenants) --}}
    @if($showPropertyFilter && count($properties) > 1)
        <div class="property-filter mb-6" x-data="{ selectedProperty: '' }">
            <label for="property-filter" class="block text-sm font-medium text-gray-700 mb-2">
                Filter by Property
            </label>
            <select 
                id="property-filter" 
                x-model="selectedProperty"
                @change="window.location.href = '?property_id=' + selectedProperty"
                class="block w-full md:w-64 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                <option value="">All Properties</option>
                @foreach($properties as $property)
                    <option value="{{ $property->id }}" {{ request('property_id') == $property->id ? 'selected' : '' }}>
                        {{ $property->address }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    {{-- Itemized Breakdown --}}
    <div class="invoice-items mb-6">
        <h4 class="text-lg font-semibold text-gray-800 mb-4">Itemized Breakdown</h4>
        
        @if($invoice->items->isEmpty())
            <p class="text-gray-500 italic">No items in this invoice.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Service
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Consumption
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Rate
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($invoice->items as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $item->description }}
                                    </div>
                                    @if($item->meter_reading_snapshot)
                                        <div class="text-xs text-gray-500 mt-1">
                                            @if(isset($item->meter_reading_snapshot['previous_reading']))
                                                Previous: {{ number_format($item->meter_reading_snapshot['previous_reading'], 2) }}
                                                → Current: {{ number_format($item->meter_reading_snapshot['current_reading'], 2) }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900">
                                        {{ number_format($item->quantity, 2) }} {{ $item->unit ?? '' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-gray-900">
                                        €{{ number_format($item->unit_price, 4) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-medium text-gray-900">
                                        €{{ number_format($item->total, 2) }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right text-sm font-bold text-gray-900">
                                Total Amount
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-lg font-bold text-gray-900">
                                    €{{ number_format($invoice->total_amount, 2) }}
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </div>

    {{-- Consumption History (if provided) --}}
    @if(isset($consumptionHistory) && $consumptionHistory->isNotEmpty())
        <div class="consumption-history mt-8">
            <h4 class="text-lg font-semibold text-gray-800 mb-4">Consumption History</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Meter
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Reading
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Consumption
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($consumptionHistory as $reading)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $reading->reading_date->format('Y-m-d') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ $reading->meter->serial_number ?? 'N/A' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ ucfirst(str_replace('_', ' ', $reading->meter->type->value ?? '')) }}
                                        @if($reading->zone)
                                            ({{ $reading->zone }})
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                    {{ number_format($reading->value, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                                    @if(isset($reading->consumption))
                                        {{ number_format($reading->consumption, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Additional Notes --}}
    @if(isset($notes) && $notes)
        <div class="invoice-notes mt-6 p-4 bg-gray-50 rounded-md">
            <h5 class="text-sm font-semibold text-gray-700 mb-2">Notes</h5>
            <p class="text-sm text-gray-600">{{ $notes }}</p>
        </div>
    @endif
</div>
