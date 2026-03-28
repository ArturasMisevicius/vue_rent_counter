@props([
    'invoice',
    'presentation' => null,
    'periodDisplay' => null,
])

@php
    /** @var \App\Models\Invoice $invoice */
    $presentation = is_array($presentation) ? $presentation : [];
    $lineItems = $presentation['items'] ?? [];
    $payments = $presentation['payments'] ?? [];
    $resolvedPeriodDisplay = $periodDisplay ?: __('tenant.pages.invoices.period', [
        'start' => $presentation['billing_period_start_display'] ?? '—',
        'end' => $presentation['billing_period_end_display'] ?? '—',
    ]);
@endphp

<article class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-6 shadow-[0_28px_90px_rgba(15,23,42,0.14)] backdrop-blur sm:p-8">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="space-y-3">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="font-display text-3xl tracking-tight text-slate-950">{{ $presentation['invoice_number'] ?? $invoice->invoice_number }}</h2>
                <x-shared.status-badge :status="$invoice->status" :model="$invoice" />
            </div>

            <div class="grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                <p>{{ $resolvedPeriodDisplay }}</p>
                <p>{{ __('admin.invoices.fields.due_date') }}: {{ $presentation['due_date_display'] ?? '—' }}</p>
                <p>{{ __('tenant.navigation.property') }}: {{ $presentation['property_name'] ?? '—' }}</p>
                <p>{{ __('admin.invoices.fields.tenant') }}: {{ $presentation['tenant_name'] ?? '—' }}</p>
                @if (filled($presentation['building_name'] ?? null))
                    <p>{{ __('admin.invoices.fields.building') }}: {{ $presentation['building_name'] }}</p>
                @endif
            </div>
        </div>

        <div class="grid gap-3 sm:min-w-64">
            <x-shared.stat-card :label="__('admin.invoices.fields.total_amount')" :value="$presentation['total_amount_display'] ?? '—'" icon="heroicon-m-banknotes" />
            <x-shared.stat-card :label="__('admin.invoices.fields.amount_paid')" :value="$presentation['paid_amount_display'] ?? '—'" :trend="__('tenant.pages.invoices.balance_due', ['amount' => $presentation['outstanding_amount_display'] ?? '—'])" icon="heroicon-m-check-badge" />
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.invoices.fields.items') }}</p>
            <h3 class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ __('admin.invoices.fields.items') }}</h3>
        </div>

        <x-shared.data-table-wrapper>
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/90 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                    <tr>
                        <th class="px-4 py-3">{{ __('admin.invoices.fields.description') }}</th>
                        <th class="px-4 py-3">{{ __('admin.invoices.fields.quantity') }}</th>
                        <th class="px-4 py-3">{{ __('admin.invoices.fields.unit_price') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('admin.invoices.fields.total_amount') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($lineItems as $item)
                        <tr>
                            <td class="px-4 py-4 text-slate-700">{{ $item['description'] ?? __('dashboard.not_available') }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $item['quantity'] ?? '—' }}{{ filled($item['unit'] ?? null) ? ' '.$item['unit'] : '' }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $item['unit_price_display'] ?? '—' }}</td>
                            <td class="px-4 py-4 text-right font-semibold text-slate-950">{{ ($presentation['currency'] ?? '').' '.($item['total_display'] ?? '—') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8">
                                <x-shared.empty-state
                                    icon="heroicon-m-document-text"
                                    :title="__('admin.invoices.fields.items')"
                                    :description="__('tenant.messages.no_invoices_for_filter')"
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-shared.data-table-wrapper>
    </div>

    <div class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="space-y-4 rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.invoices.payment_guidance') }}</p>
            <dl class="grid gap-3 text-sm text-slate-600">
                <div class="flex items-center justify-between gap-4">
                    <dt>{{ __('admin.invoices.fields.subtotal') }}</dt>
                    <dd class="font-semibold text-slate-950">{{ $presentation['subtotal_display'] ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt>{{ __('admin.invoices.fields.amount_paid') }}</dt>
                    <dd class="font-semibold text-slate-950">{{ $presentation['paid_amount_display'] ?? '—' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-slate-200 pt-3">
                    <dt class="font-semibold text-slate-950">{{ __('tenant.pages.invoices.balance_due') }}</dt>
                    <dd class="font-display text-2xl tracking-tight text-slate-950">{{ $presentation['outstanding_amount_display'] ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="space-y-4 rounded-[1.75rem] border border-slate-200 bg-white px-5 py-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('admin.invoices.fields.payment_reference') }}</p>
                <h3 class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.pages.invoices.how_to_pay') }}</h3>
            </div>

            @forelse ($payments as $payment)
                <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $payment['method_label'] ?? __('dashboard.not_available') }}</p>
                            <p class="text-sm text-slate-500">{{ $payment['paid_at_display'] ?? '—' }}</p>
                        </div>
                        <p class="font-semibold text-slate-950">{{ $payment['amount_display'] ?? '—' }}</p>
                    </div>

                    @if (filled($payment['reference'] ?? null) || filled($payment['notes'] ?? null))
                        <div class="mt-3 space-y-1 text-sm text-slate-600">
                            @if (filled($payment['reference'] ?? null))
                                <p>{{ __('admin.invoices.fields.payment_reference') }}: {{ $payment['reference'] }}</p>
                            @endif

                            @if (filled($payment['notes'] ?? null))
                                <p>{{ $payment['notes'] }}</p>
                            @endif
                        </div>
                    @endif
                </article>
            @empty
                <x-shared.empty-state
                    icon="heroicon-m-credit-card"
                    :title="__('admin.invoices.fields.amount_paid')"
                    :description="__('tenant.messages.payment_guidance_unavailable')"
                />
            @endforelse
        </div>
    </div>
</article>
