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

<article class="rounded-[1.5rem] border border-slate-200/80 bg-white p-4 shadow-[0_18px_50px_rgba(15,23,42,0.08)] sm:p-5" data-tenant-invoice-card>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 space-y-3">
            <div class="flex flex-wrap items-center gap-2.5">
                <h2 class="truncate font-display text-2xl tracking-tight text-slate-950">
                    {{ $presentation['invoice_number'] ?? $invoice->invoice_number }}
                </h2>
                <x-shared.status-badge :status="$invoice->status" :model="$invoice" />
            </div>

            <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm text-slate-600">
                <span class="inline-flex items-center gap-2">
                    <x-heroicon-m-calendar-days class="size-4 text-slate-400" />
                    {{ $resolvedPeriodDisplay }}
                </span>
                <span class="inline-flex items-center gap-2">
                    <x-heroicon-m-clock class="size-4 text-slate-400" />
                    {{ __('admin.invoices.fields.due_date') }}: {{ $presentation['due_date_display'] ?? '—' }}
                </span>
                <span class="inline-flex items-center gap-2">
                    <x-heroicon-m-home-modern class="size-4 text-slate-400" />
                    {{ $presentation['property_name'] ?? '—' }}
                </span>
            </div>
        </div>

        @if (trim((string) $slot) !== '')
            <div class="flex shrink-0 justify-start sm:justify-end">
                {{ $slot }}
            </div>
        @endif
    </div>

    <dl class="mt-4 flex flex-col gap-2.5 sm:flex-row">
        <div class="flex min-h-20 flex-1 flex-col justify-between rounded-2xl bg-slate-50 px-4 py-3">
            <dt class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('admin.invoices.fields.total_amount') }}</dt>
            <dd class="font-display text-2xl tracking-tight text-slate-950">{{ $presentation['total_amount_display'] ?? '—' }}</dd>
        </div>

        <div class="flex min-h-20 flex-1 flex-col justify-between rounded-2xl bg-slate-50 px-4 py-3">
            <dt class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.pages.invoices.paid_so_far') }}</dt>
            <dd class="font-display text-2xl tracking-tight text-slate-950">{{ $presentation['paid_amount_display'] ?? '—' }}</dd>
        </div>

        <div class="flex min-h-20 flex-1 flex-col justify-between rounded-2xl bg-brand-ink px-4 py-3 text-white">
            <dt class="text-xs font-semibold uppercase tracking-normal text-white/70">{{ __('tenant.pages.invoices.balance_due') }}</dt>
            <dd class="font-display text-2xl tracking-tight">{{ $presentation['outstanding_amount_display'] ?? '—' }}</dd>
        </div>
    </dl>

    @if ($lineItems !== [])
        <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3" data-tenant-invoice-lines>
            <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.pages.invoices.charge_details') }}</p>

            <div class="mt-2 divide-y divide-slate-200">
                @foreach ($lineItems as $item)
                    <div class="flex items-start justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-slate-950">{{ $item['description'] ?? __('dashboard.not_available') }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $item['quantity'] ?? '—' }}{{ filled($item['unit'] ?? null) ? ' '.$item['unit'] : '' }}
                                @if (filled($item['unit_price_display'] ?? null))
                                    · {{ $item['unit_price_display'] }}
                                @endif
                            </p>
                        </div>

                        <p class="shrink-0 text-sm font-semibold text-slate-950">{{ $item['total_display'] ?? '—' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($payments !== [])
        <div class="mt-4 flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.pages.invoices.payment_activity') }}</p>

            @foreach ($payments as $payment)
                <div class="flex items-start justify-between gap-4 text-sm">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-950">{{ $payment['method_label'] ?? __('dashboard.not_available') }}</p>
                        <p class="mt-1 truncate text-xs text-slate-500">{{ $payment['paid_at_display'] ?? '—' }}</p>
                    </div>
                    <p class="shrink-0 font-semibold text-slate-950">{{ $payment['amount_display'] ?? '—' }}</p>
                </div>
            @endforeach
        </div>
    @endif
</article>
