<div>
    <x-card class="mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-slate-900">{{ __('invoices.filters.title') }}</h3>
                <button 
                    type="button"
                    wire:click="resetFilters"
                    class="text-sm text-indigo-600 hover:text-indigo-900 font-medium"
                >
                    {{ __('invoices.filters.reset') }}
                </button>
            </div>
            
            <form wire:submit.prevent>
                {{ $this->form }}
            </form>
        </div>
    </x-card>

    <x-card>
        <div class="hidden sm:block">
            <x-data-table :caption="__('invoices.shared.index.caption')">
                <x-slot name="header">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-0">
                            {{ __('invoices.shared.index.headers.number') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">
                            {{ __('invoices.shared.index.headers.property') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">
                            {{ __('invoices.shared.index.headers.billing_period') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">
                            {{ __('invoices.shared.index.headers.amount') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">
                            {{ __('invoices.shared.index.headers.status') }}
                        </th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">
                            {{ __('invoices.shared.index.headers.due') }}
                        </th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                            <span class="sr-only">{{ __('invoices.shared.index.headers.actions') }}</span>
                        </th>
                    </tr>
                </x-slot>

                @forelse($invoices as $invoice)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-0">
                        <a href="{{ route('manager.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900">
                            #{{ $invoice->id }}
                        </a>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        @if($invoice->tenant && $invoice->tenant->property)
                            <a href="{{ route('manager.properties.show', $invoice->tenant->property) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ $invoice->tenant->property->address }}
                            </a>
                        @else
                            <span class="text-slate-400">{{ __('app.common.na') }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        €{{ number_format($invoice->total_amount, 2) }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                        <x-status-badge :status="$invoice->status->value">
                            {{ enum_label($invoice->status) }}
                        </x-status-badge>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        @if($invoice->due_date)
                            @if($invoice->due_date->isPast() && !$invoice->isPaid())
                                <span class="text-red-600 font-semibold">{{ $invoice->due_date->format('Y-m-d') }}</span>
                            @else
                                {{ $invoice->due_date->format('Y-m-d') }}
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                        <div class="flex justify-end gap-2">
                            @can('view', $invoice)
                            <a href="{{ route('manager.invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-900">
                                {{ __('invoices.actions.view') }}
                            </a>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">
                        {{ __('invoices.shared.index.empty.text') }}
                    </td>
                </tr>
                @endforelse
            </x-data-table>
        </div>

        <div class="sm:hidden space-y-3">
            @forelse($invoices as $invoice)
                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">#{{ $invoice->id }}</p>
                            <p class="text-xs text-slate-600">
                                {{ $invoice->billing_period_start->format('M d') }} - {{ $invoice->billing_period_end->format('M d, Y') }}
                            </p>
                            <p class="text-xs text-slate-600 mt-1">
                                @if($invoice->tenant && $invoice->tenant->property)
                                    {{ $invoice->tenant->property->address }}
                                @else
                                    {{ __('app.common.na') }}
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-semibold text-slate-900">€{{ number_format($invoice->total_amount, 2) }}</p>
                            <x-status-badge :status="$invoice->status->value" class="mt-1">
                                {{ enum_label($invoice->status) }}
                            </x-status-badge>
                            @if($invoice->due_date)
                                <p class="text-xs text-slate-600 mt-1">
                                    {{ __('invoices.shared.index.headers.due') }}: 
                                    @if($invoice->due_date->isPast() && !$invoice->isPaid())
                                        <span class="text-red-600 font-semibold">{{ $invoice->due_date->format('Y-m-d') }}</span>
                                    @else
                                        {{ $invoice->due_date->format('Y-m-d') }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @can('view', $invoice)
                        <a href="{{ route('manager.invoices.show', $invoice) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('invoices.actions.view') }}
                        </a>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                    {{ __('invoices.shared.index.empty.text') }}
                </div>
            @endforelse
        </div>

        @if($invoices->hasPages())
        <div class="mt-4">
            {{ $invoices->links() }}
        </div>
        @endif
    </x-card>
</div>
