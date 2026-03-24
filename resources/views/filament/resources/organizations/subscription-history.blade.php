<div class="space-y-6">
    <section>
        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Payments</h3>

        <div class="mt-3 space-y-3">
            @forelse ($subscription->payments as $payment)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}
                    </p>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $payment->duration?->label() ?? 'Custom duration' }} paid {{ $payment->paid_at?->format('d M Y') ?? 'Unknown date' }}
                    </p>
                    <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">
                        {{ $payment->reference }}
                    </p>
                </div>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-500">No payment history recorded.</p>
            @endforelse
        </div>
    </section>

    <section>
        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">Renewals</h3>

        <div class="mt-3 space-y-3">
            @forelse ($subscription->renewals as $renewal)
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ str($renewal->method)->title() }} renewal
                    </p>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $renewal->old_expires_at?->format('d M Y') ?? 'Unknown' }} to {{ $renewal->new_expires_at?->format('d M Y') ?? 'Unknown' }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $renewal->user?->name ?? 'System' }} · {{ str($renewal->period)->replace('_', ' ')->title() }}
                    </p>
                </div>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-500">No renewal history recorded.</p>
            @endforelse
        </div>
    </section>
</div>
