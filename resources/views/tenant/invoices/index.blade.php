<x-layouts.tenant :title="__('tenant.pages.invoices.title').' · '.config('app.name', 'Tenanto')" :heading="__('tenant.pages.invoices.heading')">
    <section class="rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <div class="space-y-3">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ __('tenant.navigation.invoices') }}</p>
            <h2 class="font-display text-3xl tracking-tight text-slate-950">{{ __('tenant.pages.invoices.heading') }}</h2>
            <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ __('tenant.pages.invoices.description') }}</p>
        </div>

        <div class="mt-8 space-y-3">
            @forelse ($invoices as $invoice)
                <article class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-1">
                            <p class="font-semibold text-slate-950">{{ $invoice->invoice_number }}</p>
                            <p class="text-sm text-slate-500">
                                {{ optional($invoice->billing_period_start)->format('Y-m-d') }}
                                -
                                {{ optional($invoice->billing_period_end)->format('Y-m-d') }}
                            </p>
                            @if ($invoice->property)
                                <p class="text-sm text-slate-500">
                                    {{ $invoice->property->name }}
                                    @if ($invoice->property->building)
                                        · {{ $invoice->property->building->address_line_1 }}
                                    @endif
                                </p>
                            @endif
                        </div>

                        <div class="text-left sm:text-right">
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">{{ $invoice->status->value }}</p>
                            <p class="mt-1 font-display text-2xl tracking-tight text-slate-950">
                                {{ $invoice->currency }} {{ number_format((float) $invoice->total_amount, 2) }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500">Due {{ optional($invoice->due_date)->format('Y-m-d') }}</p>
                        </div>
                    </div>
                </article>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">No invoices have been issued yet.</p>
            @endforelse
        </div>
    </section>
</x-layouts.tenant>
