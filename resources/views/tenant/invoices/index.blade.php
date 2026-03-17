<x-layouts.tenant
    :title="__('tenant.pages.invoices.title').' · '.config('app.name', 'Tenanto')"
    :heading="__('tenant.pages.invoices.heading')"
    :breadcrumbs="[
        ['label' => __('tenant.navigation.home'), 'url' => route('tenant.home')],
        ['label' => __('tenant.pages.invoices.heading')],
    ]"
>
    <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
        <section class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ __('tenant.navigation.invoices') }}</p>
                    <h2 class="font-display text-3xl tracking-tight text-slate-950">{{ __('tenant.pages.invoices.page_heading') }}</h2>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ __('tenant.pages.invoices.description') }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ([
                        'all' => __('tenant.status.all'),
                        'unpaid' => __('tenant.status.unpaid'),
                        'paid' => __('tenant.status.paid'),
                    ] as $filter => $label)
                        <a
                            href="{{ route('tenant.invoices.index', $filter === 'all' ? [] : ['status' => $filter]) }}"
                            @class([
                                'inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-semibold transition',
                                'bg-brand-ink text-white' => $selectedStatus === $filter,
                                'border border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50' => $selectedStatus !== $filter,
                            ])
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="space-y-4">
                @forelse ($invoices as $invoice)
                    @php
                        $balanceDue = max((float) $invoice->total_amount - (float) $invoice->amount_paid, 0);
                        $statusLabel = match ($invoice->status) {
                            \App\Enums\InvoiceStatus::OVERDUE => __('tenant.status.overdue'),
                            \App\Enums\InvoiceStatus::PAID => __('tenant.status.paid'),
                            default => __('tenant.status.unpaid'),
                        };
                    @endphp

                    <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-display text-2xl tracking-tight text-slate-950">{{ $invoice->invoice_number }}</h3>
                                    <span @class([
                                        'inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]',
                                        'bg-rose-100 text-rose-900' => $invoice->status === \App\Enums\InvoiceStatus::OVERDUE,
                                        'bg-amber-100 text-amber-900' => $invoice->status !== \App\Enums\InvoiceStatus::OVERDUE && $balanceDue > 0,
                                        'bg-emerald-100 text-emerald-900' => $balanceDue === 0.0,
                                    ])>
                                        {{ $statusLabel }}
                                    </span>
                                </div>

                                <p class="text-sm text-slate-500">
                                    {{ __('tenant.pages.invoices.period', [
                                        'start' => $invoice->billing_period_start->format('Y-m-d'),
                                        'end' => $invoice->billing_period_end->format('Y-m-d'),
                                    ]) }}
                                </p>

                                @if ($invoice->property)
                                    <p class="text-sm text-slate-500">{{ $invoice->property->name }} · {{ $invoice->property->address }}</p>
                                @endif
                            </div>

                            <div class="space-y-2 text-left md:text-right">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.invoices.balance_due') }}</p>
                                <p class="font-display text-3xl tracking-tight text-slate-950">{{ $invoice->currency }} {{ number_format($balanceDue, 2) }}</p>
                                <p class="text-sm text-slate-500">{{ __('tenant.pages.invoices.total', ['amount' => number_format((float) $invoice->total_amount, 2)]) }}</p>

                                @if ($invoice->document_path)
                                    <a
                                        href="{{ route('tenant.invoices.download', $invoice) }}"
                                        class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white"
                                    >
                                        {{ __('tenant.actions.download_pdf') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    @if ($selectedStatus === 'unpaid')
                        <div class="rounded-[1.75rem] border border-dashed border-emerald-300 bg-emerald-50 px-5 py-6">
                            <p class="font-semibold text-emerald-900">{{ __('tenant.status.all_paid_up') }}</p>
                            <p class="mt-2 text-sm text-emerald-800">{{ __('tenant.messages.all_paid_up_detail') }}</p>
                        </div>
                    @else
                        <p class="rounded-[1.75rem] border border-dashed border-slate-300 px-5 py-6 text-sm text-slate-500">{{ __('tenant.messages.no_invoices_for_filter') }}</p>
                    @endif
                @endforelse
            </div>

            <div>
                {{ $invoices->links() }}
            </div>
        </section>

        <aside class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.invoices.payment_guidance') }}</p>
                <h3 class="font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.pages.invoices.how_to_pay') }}</h3>
                <p class="text-sm leading-6 text-slate-600">
                    {{ $paymentGuidance['content'] ?? __('tenant.messages.payment_guidance_unavailable') }}
                </p>
            </div>

            @if ($paymentGuidance['has_contact_details'])
                <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.shell.billing_contact') }}</p>

                    @if ($paymentGuidance['contact_name'])
                        <p class="mt-2 font-semibold text-slate-950">{{ $paymentGuidance['contact_name'] }}</p>
                    @endif

                    <div class="mt-2 space-y-1 text-sm text-slate-600">
                        @if ($paymentGuidance['contact_email'])
                            <p>{{ $paymentGuidance['contact_email'] }}</p>
                        @endif

                        @if ($paymentGuidance['contact_phone'])
                            <p>{{ $paymentGuidance['contact_phone'] }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </aside>
    </div>
</x-layouts.tenant>
