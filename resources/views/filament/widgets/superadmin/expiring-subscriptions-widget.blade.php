<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4">
        <h3 class="text-base font-semibold text-slate-950">Expiring Subscriptions</h3>
        <p class="text-sm text-slate-500">Subscriptions reaching their end date in the next 30 days.</p>
    </div>

    <div class="space-y-3">
        @forelse ($subscriptions as $subscription)
            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                <p class="font-semibold text-slate-950">{{ $subscription->organization?->name }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $subscription->plan?->label() }} • {{ $subscription->expires_at?->toFormattedDateString() }}</p>
            </article>
        @empty
            <p class="text-sm text-slate-500">No subscriptions are expiring soon.</p>
        @endforelse
    </div>
</div>
