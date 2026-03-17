<x-layouts.tenant
    :title="__('tenant.pages.invoices.title').' · '.config('app.name', 'Tenanto')"
    :breadcrumbs="$breadcrumbs"
>
    <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
        <section class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ __('tenant.navigation.invoices') }}</p>
                    <h2 class="font-display text-3xl tracking-tight text-slate-950">{{ __('tenant.pages.invoices.heading') }}</h2>
                    <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ __('tenant.pages.invoices.description') }}</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    @foreach ([
                        'all' => 'All',
                        'outstanding' => 'Outstanding',
                        'paid' => 'Paid',
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
                        $statusLabel = $balanceDue > 0 ? 'Outstanding' : 'Paid';
                    @endphp

                    <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
                        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="font-display text-2xl tracking-tight text-slate-950">{{ $invoice->invoice_number }}</h3>
                                    <span @class([
                                        'inline-flex rounded-full px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em]',
                                        'bg-amber-100 text-amber-900' => $balanceDue > 0,
                                        'bg-emerald-100 text-emerald-900' => $balanceDue === 0.0,
                                    ])>
                                        {{ $statusLabel }}
                                    </span>
                                </div>

                                <p class="text-sm text-slate-500">
                                    {{ $invoice->billing_period_start->format('Y-m-d') }} to {{ $invoice->billing_period_end->format('Y-m-d') }}
                                </p>

                                @if ($invoice->property)
                                    <p class="text-sm text-slate-500">{{ $invoice->property->name }} · {{ $invoice->property->address }}</p>
                                @endif
                            </div>

                            <div class="space-y-2 text-left md:text-right">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Balance Due</p>
                                <p class="font-display text-3xl tracking-tight text-slate-950">{{ $invoice->currency }} {{ number_format($balanceDue, 2) }}</p>
                                <p class="text-sm text-slate-500">Total {{ number_format((float) $invoice->total_amount, 2) }}</p>

                                @if ($invoice->document_path)
                                    <a
                                        href="{{ route('tenant.invoices.download', $invoice) }}"
                                        class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-white"
                                    >
                                        Download PDF
                                    </a>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <x-ui.empty-state
                        heading="No invoices match the selected filter."
                        body="Try a different filter or check back once new invoices are issued to your tenant account."
                    />
                @endforelse
            </div>

            <div>
                {{ $invoices->links() }}
            </div>
        </section>

        <aside class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
            <div class="space-y-2">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Payment Guidance</p>
                <h3 class="font-display text-2xl tracking-tight text-slate-950">How To Pay</h3>
                <p class="text-sm leading-6 text-slate-600">{{ $paymentInstructions }}</p>
            </div>
        </aside>
    </div>
</x-layouts.tenant>
