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
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-semibold text-slate-950">{{ $payment['method_label'] ?? __('dashboard.not_available') }}</p>
                            @if (filled($payment['status_label'] ?? null))
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-normal text-slate-600">
                                    {{ $payment['status_label'] }}
                                </span>
                            @endif
                        </div>
                        <p class="mt-1 truncate text-xs text-slate-500">{{ $payment['payment_date_display'] ?? $payment['paid_at_display'] ?? '—' }}</p>
                        @if (filled($payment['reference'] ?? null))
                            <p class="mt-1 truncate text-xs text-slate-500">{{ __('tenant.pages.invoices.reference') }}: {{ $payment['reference'] }}</p>
                        @endif
                        @if (filled($payment['rejection_reason'] ?? null))
                            <p class="mt-1 text-xs text-red-700">{{ $payment['rejection_reason'] }}</p>
                        @endif
                        @if (! empty($payment['attachments']))
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($payment['attachments'] as $attachment)
                                    <a href="{{ $attachment['url'] }}" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-800 hover:bg-amber-100">
                                        <x-heroicon-m-paper-clip class="size-3.5" />
                                        {{ $attachment['name'] }}
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <p class="shrink-0 font-semibold text-slate-950">{{ $payment['amount_display'] ?? '—' }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @if (session()->has('payment-proof-submitted-'.$invoice->id))
        <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">
            {{ session('payment-proof-submitted-'.$invoice->id) }}
        </div>
    @endif

    @if ($canSubmitPaymentProof)
        <form wire:submit.prevent="submitPaymentProof({{ $invoice->id }})" class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
            <div class="mb-3 flex flex-col gap-1">
                <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.pages.invoices.upload_payment_proof') }}</p>
                <p class="text-sm text-slate-600">{{ __('tenant.pages.invoices.payment_reference_hint', ['reference' => $paymentForm['reference'] ?? $invoice->invoice_number]) }}</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <label class="space-y-1.5 text-sm font-medium text-slate-700">
                    <span>{{ __('tenant.pages.invoices.amount_paid') }}</span>
                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        wire:model.defer="paymentForms.{{ $invoice->id }}.amount"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                    >
                </label>

                <label class="space-y-1.5 text-sm font-medium text-slate-700">
                    <span>{{ __('tenant.pages.invoices.payment_date') }}</span>
                    <input
                        type="date"
                        wire:model.defer="paymentForms.{{ $invoice->id }}.payment_date"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                    >
                </label>

                <label class="space-y-1.5 text-sm font-medium text-slate-700">
                    <span>{{ __('tenant.pages.invoices.payment_method') }}</span>
                    <select
                        wire:model.defer="paymentForms.{{ $invoice->id }}.payment_method"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                    >
                        @foreach ($paymentMethods as $methodValue => $methodLabel)
                            <option value="{{ $methodValue }}">{{ $methodLabel }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="space-y-1.5 text-sm font-medium text-slate-700">
                    <span>{{ __('tenant.pages.invoices.reference') }}</span>
                    <input
                        type="text"
                        wire:model.defer="paymentForms.{{ $invoice->id }}.reference"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                    >
                </label>

                <label class="space-y-1.5 text-sm font-medium text-slate-700">
                    <span>{{ __('tenant.pages.invoices.transaction_id') }}</span>
                    <input
                        type="text"
                        wire:model.defer="paymentForms.{{ $invoice->id }}.transaction_id"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                    >
                </label>

                <label class="space-y-1.5 text-sm font-medium text-slate-700">
                    <span>{{ __('tenant.pages.invoices.payment_proof') }}</span>
                    <input
                        type="file"
                        wire:model="paymentProofFiles.{{ $invoice->id }}"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-slate-700 focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                    >
                </label>
            </div>

            <label class="mt-3 block space-y-1.5 text-sm font-medium text-slate-700">
                <span>{{ __('tenant.pages.invoices.tenant_comment') }}</span>
                <textarea
                    rows="2"
                    wire:model.defer="paymentForms.{{ $invoice->id }}.tenant_comment"
                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-950 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                ></textarea>
            </label>

            <div class="mt-4 flex justify-end">
                <x-tenant.action type="submit" icon="heroicon-m-arrow-up-tray">
                    {{ __('tenant.pages.invoices.submit_payment_proof') }}
                </x-tenant.action>
            </div>
        </form>
    @endif
</article>
