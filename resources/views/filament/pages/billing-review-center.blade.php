<x-filament-panels::page>
    @php($review = $this->review)
    @php($summary = $review['summary'])

    <div class="space-y-6">
        <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div class="grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end">
                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __('admin.invoices.fields.billing_period_start') }}</span>
                    <input
                        type="date"
                        wire:model.live="period.billing_period_start"
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-950"
                    >
                </label>

                <label class="block">
                    <span class="text-sm font-medium text-slate-700">{{ __('admin.invoices.fields.billing_period_end') }}</span>
                    <input
                        type="date"
                        wire:model.live="period.billing_period_end"
                        class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-950"
                    >
                </label>

                <div wire:loading.flex wire:target="period.billing_period_start,period.billing_period_end" class="items-center gap-2 rounded-md bg-slate-100 px-3 py-2 text-sm text-slate-600">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-slate-900"></span>
                    {{ __('admin.billing_review.refreshing') }}
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                'total_invoices',
                'waiting_for_readings',
                'submitted_readings',
                'ready_for_review',
                'configuration_errors',
                'approved',
                'sent',
                'overdue',
            ] as $metric)
                <article class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase text-slate-500">{{ __("admin.billing_review.summary.{$metric}") }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-950">{{ $summary[$metric] ?? 0 }}</p>
                </article>
            @endforeach
        </section>

        <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-base font-semibold text-slate-950">{{ __('admin.billing_review.invoice_list.title') }}</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                        <tr>
                            <th class="px-4 py-3">{{ __('admin.billing_review.invoice_list.tenant') }}</th>
                            <th class="px-4 py-3">{{ __('admin.billing_review.invoice_list.property') }}</th>
                            <th class="px-4 py-3">{{ __('admin.billing_review.invoice_list.period') }}</th>
                            <th class="px-4 py-3">{{ __('admin.billing_review.invoice_list.status') }}</th>
                            <th class="px-4 py-3">{{ __('admin.billing_review.invoice_list.readings_progress') }}</th>
                            <th class="px-4 py-3">{{ __('admin.billing_review.invoice_list.warnings_errors') }}</th>
                            <th class="px-4 py-3">{{ __('admin.billing_review.invoice_list.preview_total') }}</th>
                            <th class="px-4 py-3">{{ __('admin.billing_review.invoice_list.last_activity') }}</th>
                            <th class="px-4 py-3">{{ __('admin.billing_review.invoice_list.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($review['invoices'] as $invoice)
                            <tr wire:key="billing-review-invoice-{{ $invoice['invoice_id'] }}">
                                <td class="px-4 py-4">
                                    <div class="font-medium text-slate-950">{{ $invoice['tenant_name'] }}</div>
                                    <div class="text-xs text-slate-500">{{ $invoice['invoice_number'] }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-slate-900">{{ $invoice['property_name'] }}</div>
                                    <div class="text-xs text-slate-500">{{ $invoice['building_name'] }}</div>
                                </td>
                                <td class="px-4 py-4 text-slate-700">{{ $invoice['billing_period'] }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{{ $invoice['invoice_status_label'] }}</span>
                                </td>
                                <td class="px-4 py-4 text-slate-700">{{ $invoice['readings_progress'] }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($invoice['blocking_errors'] !== [])
                                            <span class="rounded-md bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700">{{ count($invoice['blocking_errors']) }}</span>
                                        @endif
                                        @if ($invoice['warnings'] !== [])
                                            <span class="rounded-md bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">{{ count($invoice['warnings']) }}</span>
                                        @endif
                                        @if ($invoice['blocking_errors'] === [] && $invoice['warnings'] === [])
                                            <span class="text-xs text-slate-500">{{ __('admin.billing_review.none') }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-4 font-semibold text-slate-950">{{ $invoice['preview_total'] }} {{ $invoice['currency'] }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ $invoice['last_activity_at'] }}</td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="{{ $invoice['review_url'] }}" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            {{ __('admin.billing_review.actions.review') }}
                                        </a>
                                        <button type="button" wire:click="recalculateInvoice({{ $invoice['invoice_id'] }})" class="rounded-md border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                            {{ __('admin.billing_review.actions.recalculate') }}
                                        </button>
                                        @if ($invoice['can_approve'])
                                            <button type="button" wire:click="approveInvoice({{ $invoice['invoice_id'] }})" class="rounded-md bg-slate-950 px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">
                                                {{ __('admin.billing_review.actions.approve_invoice') }}
                                            </button>
                                        @endif
                                        @if ($invoice['missing_readings'] !== [])
                                            <button type="button" wire:click="sendReminder({{ $invoice['invoice_id'] }})" class="rounded-md border border-amber-200 px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-50">
                                                {{ __('admin.billing_review.actions.send_reminder') }}
                                            </button>
                                        @endif
                                        @if ($invoice['can_send'])
                                            <button type="button" wire:click="sendInvoice({{ $invoice['invoice_id'] }})" class="rounded-md border border-emerald-200 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">
                                                {{ __('admin.billing_review.actions.send_invoice') }}
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-500">
                                    {{ __('admin.billing_review.empty') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
