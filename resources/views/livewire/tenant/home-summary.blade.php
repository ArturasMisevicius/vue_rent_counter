<div wire:poll.120s class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
    <section class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">Tenant Summary</p>
                <h2 class="font-display text-4xl tracking-tight text-slate-950">Hello, {{ $summary['tenant_name'] }}</h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">Your account snapshot is scoped to your assigned property and latest billing activity.</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ $summary['submit_reading_url'] }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-brand-ink px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900"
                >
                    Submit New Reading
                </a>
                @if ($summary['property_url'])
                    <a
                        href="{{ $summary['property_url'] }}"
                        class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        My Property
                    </a>
                @endif
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <article class="rounded-[1.75rem] bg-slate-950 px-5 py-5 text-white">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-mint/90">{{ $summary['outstanding_label'] }}</p>
                @if ($summary['has_outstanding_balance'])
                    <p class="mt-3 font-display text-3xl tracking-tight">EUR {{ number_format($summary['outstanding_total'], 2) }}</p>
                    <p class="mt-2 text-sm text-slate-300">Across {{ $summary['outstanding_invoice_count'] }} invoices</p>
                @else
                    <p class="mt-3 font-display text-3xl tracking-tight">All paid up</p>
                    <p class="mt-2 text-sm text-slate-300">No outstanding invoices are waiting for payment.</p>
                @endif
            </article>

            <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $summary['month_heading'] }}</p>
                <p class="mt-3 font-display text-3xl tracking-tight text-slate-950">{{ $summary['month_heading'] }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ $summary['current_month_message'] }}</p>
            </article>
        </div>

        <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Recent Readings</p>
                    <h3 class="mt-2 font-display text-2xl tracking-tight text-slate-950">Recent Readings</h3>
                </div>
                @if ($summary['property_address'])
                    <p class="max-w-xs text-right text-sm text-slate-500">{{ $summary['property_address'] }}</p>
                @endif
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($summary['recent_readings'] as $reading)
                    <div wire:key="reading-{{ $reading['id'] }}" class="flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-4 py-3">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $reading['meter_name'] ?? $reading['meter_identifier'] }}</p>
                            <p class="text-sm text-slate-500">{{ $reading['meter_identifier'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-slate-950">{{ $reading['value'] }} {{ $reading['unit'] }}</p>
                            <p class="text-sm text-slate-500">{{ $reading['date'] }}</p>
                        </div>
                    </div>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-300 px-4 py-5 text-sm text-slate-500">No readings have been submitted yet.</p>
                @endforelse
            </div>
        </article>
    </section>

    <aside class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Billing Guidance</p>
            <h3 class="font-display text-2xl tracking-tight text-slate-950">Payment Instructions</h3>
            <p class="text-sm leading-6 text-slate-600">{{ $summary['payment_instructions'] }}</p>
        </div>

        @if ($summary['property_name'])
            <div class="rounded-[1.75rem] bg-slate-50 px-5 py-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">Assigned Property</p>
                <p class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ $summary['property_name'] }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $summary['property_address'] }}</p>
            </div>
        @endif
    </aside>
</div>
