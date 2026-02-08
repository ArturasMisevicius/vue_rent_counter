<div class="bg-white/90 border border-slate-200/80 shadow-lg shadow-slate-200/60 rounded-2xl p-6 backdrop-blur-sm">
    {{-- Invoice Header --}}
    <div class="mb-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-1">
                <h3 class="text-2xl font-bold text-slate-900 font-display">Invoice #{{ $invoice->id }}</h3>
                <p class="text-slate-600 mt-1">
                    Period: {{ $invoice->billing_period_start->format('Y-m-d') }} - {{ $invoice->billing_period_end->format('Y-m-d') }}
                </p>
                @if($invoice->tenant)
                    <p class="text-slate-600">
                        Tenant: {{ $invoice->tenant->name }}
                    </p>
                    @if($invoice->tenant->property)
                        <p class="text-slate-600">
                            Property: {{ $invoice->tenant->property->address }}
                        </p>
                    @endif
                @endif
            </div>
            <div class="text-left sm:text-right space-y-2">
                <x-status-badge :status="$invoice->status->value" class="justify-start sm:justify-end" />
                @if($invoice->due_date)
                    <p class="text-sm font-semibold {{ $isOverdue($invoice) ? 'text-rose-600' : 'text-slate-700' }}">
                        Due: {{ $invoice->due_date->format('Y-m-d') }}
                        @if($isOverdue($invoice))
                            <span class="ml-2 inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">Overdue</span>
                        @endif
                    </p>
                @endif
                @if($invoice->payment_reference)
                    <p class="text-sm text-slate-700">Payment ref: {{ $invoice->payment_reference }}</p>
                @endif
                @if($invoice->paid_amount)
                    <p class="text-sm text-slate-700">Paid amount: €{{ number_format($invoice->paid_amount, 2) }}</p>
                @endif
                @if($invoice->finalized_at)
                    <p class="text-sm text-slate-500 mt-2">
                        Finalized: {{ $invoice->finalized_at->format('Y-m-d H:i') }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Property Filter (for multi-property tenants) --}}
    @if($showPropertyFilter && count($properties) > 1)
        <div class="mb-6" x-data="{ selectedProperty: '' }">
            <label for="property-filter" class="block text-sm font-medium text-slate-700 mb-2">
                Filter by Property
            </label>
            <select 
                id="property-filter" 
                x-model="selectedProperty"
                @change="window.location.href = '?property_id=' + selectedProperty"
                class="block w-full md:w-64 px-3 py-2 border border-slate-200 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300 bg-white/90">
                <option value="">{{ __('invoices.summary.labels.all_properties') }}</option>
                @foreach($properties as $property)
                    <option value="{{ $property->id }}" {{ request('property_id') == $property->id ? 'selected' : '' }}>
                        {{ $property->address }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    {{-- Itemized Breakdown --}}
    <div class="mb-6">
        <h4 class="text-lg font-semibold text-slate-900 mb-4 font-display">{{ __('invoices.summary.labels.itemized') }}</h4>
        
        @if($invoice->items->isEmpty())
            <p class="text-slate-500 italic">{{ __('invoices.summary.labels.empty_items') }}</p>
        @else
            <div class="hidden sm:block overflow-hidden rounded-2xl border border-slate-200/80 shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-gradient-to-r from-slate-50 via-white to-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{ __('invoices.summary.labels.headers.service') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{ __('invoices.summary.labels.headers.consumption') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{ __('invoices.summary.labels.headers.rate') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{ __('invoices.summary.labels.headers.total') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($invoice->items as $item)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-slate-900">
                                        {{ $item->description }}
                                    </div>
                                    @if($readingSummary($item->meter_reading_snapshot))
                                        <div class="text-xs text-slate-500 mt-1">{{ $readingSummary($item->meter_reading_snapshot) }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-slate-900">
                                        {{ number_format($item->quantity, 2) }} {{ $item->unit ?? '' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-slate-900">
                                        €{{ number_format($item->unit_price, 4) }}
                                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                    <div class="text-sm font-semibold text-slate-900">
                        €{{ number_format($item->total, 2) }}
                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right text-sm font-bold text-slate-900">
                                Total Amount
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="text-lg font-bold text-slate-900 font-display">
                                    €{{ number_format($invoice->total_amount, 2) }}
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="sm:hidden space-y-3">
                @foreach($invoice->items as $item)
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-slate-900">{{ $item->description }}</p>
                            @if($readingSummary($item->meter_reading_snapshot, true))
                                <p class="text-xs text-slate-500 mt-1">{{ $readingSummary($item->meter_reading_snapshot, true) }}</p>
                            @endif
                        </div>
                        <div class="text-right">
                        <p class="text-sm font-semibold text-slate-900">€{{ number_format($item->total, 2) }}</p>
                        </div>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-slate-600">
                        <p>{{ __('invoices.summary.labels.consumption') }}: <span class="font-semibold text-slate-900">{{ number_format($item->quantity, 2) }} {{ $item->unit ?? '' }}</span></p>
                        <p class="text-right">{{ __('invoices.summary.labels.rate') }}: <span class="font-semibold text-slate-900">€{{ number_format($item->unit_price, 4) }}</span></p>
                    </div>
                </div>
                @endforeach
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 shadow-inner">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-bold text-slate-900">{{ __('invoices.summary.labels.total_amount') }}</p>
                        <p class="text-lg font-bold text-slate-900 font-display">€{{ number_format($invoice->total_amount, 2) }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Consumption History (if provided) --}}
    @if(isset($consumptionHistory) && $consumptionHistory->isNotEmpty())
        <div class="mt-8">
            <h4 class="text-lg font-semibold text-slate-900 mb-4 font-display">{{ __('invoices.summary.labels.history_title') }}</h4>
            <div class="hidden sm:block overflow-hidden rounded-2xl border border-slate-200/80 shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-gradient-to-r from-slate-50 via-white to-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{ __('invoices.summary.labels.history_headers.date') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{ __('invoices.summary.labels.history_headers.meter') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{ __('invoices.summary.labels.history_headers.reading') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                {{ __('invoices.summary.labels.history_headers.consumption') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($consumptionHistory as $reading)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                    {{ $reading->reading_date->format('Y-m-d') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-slate-900">
                                        {{ $reading->meter->serial_number ?? __('app.common.na') }}
                                    </div>
                                    <div class="text-xs text-slate-500">
                                        {{ $reading->meter?->getServiceDisplayName() ?? __('app.common.na') }}
                                        @if($reading->zone)
                                            ({{ \App\Enums\TariffZone::tryFrom($reading->zone)?->label() ?? $reading->zone }})
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-900">
                                    {{ number_format($reading->value, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-900">
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
            <div class="sm:hidden space-y-3">
                @foreach($consumptionHistory as $reading)
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-slate-900">{{ $reading->reading_date->format('Y-m-d') }}</p>
                            <p class="text-xs font-semibold text-slate-500">
                                {{ \App\Enums\TariffZone::tryFrom($reading->zone)?->label() ?? ($reading->zone ?? '—') }}
                            </p>
                        </div>
                        <p class="mt-1 text-sm text-slate-700">
                            {{ __('invoices.summary.labels.meter') }}: <span class="font-semibold">{{ $reading->meter->serial_number ?? __('app.common.na') }}</span>
                        </p>
                        <div class="mt-1 grid grid-cols-2 gap-2 text-xs text-slate-600">
                            <p>{{ __('invoices.summary.labels.reading') }}: <span class="font-semibold text-slate-900">{{ number_format($reading->value, 2) }}</span></p>
                            <p class="text-right">{{ __('invoices.summary.labels.consumption') }}: <span class="font-semibold text-slate-900">{{ isset($reading->consumption) ? number_format($reading->consumption, 2) : '—' }}</span></p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Additional Notes --}}
    @if(isset($notes) && $notes)
        <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50/80 p-4">
            <h5 class="text-sm font-semibold text-slate-700 mb-2">{{ __('invoices.summary.labels.notes') }}</h5>
            <p class="text-sm text-slate-600">{{ $notes }}</p>
        </div>
    @endif
</div>
