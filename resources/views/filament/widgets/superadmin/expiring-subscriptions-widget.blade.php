<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="mb-4">
        <h3 class="text-base font-semibold text-slate-950">{{ __('dashboard.platform_sections.expiring_subscriptions') }}</h3>
        <p class="text-sm text-slate-500">{{ __('dashboard.platform_sections.expiring_subscriptions_description') }}</p>
    </div>

    <div class="space-y-3">
        @forelse ($subscriptions as $subscription)
            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                <p class="font-semibold text-slate-950">{{ $subscription->organization?->name }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $subscription->plan?->label() }} • {{ $subscription->expires_at?->locale(app()->getLocale())->isoFormat('ll') }}</p>
            </article>
        @empty
            <p class="text-sm text-slate-500">{{ __('dashboard.platform_sections.expiring_subscriptions_empty') }}</p>
        @endforelse
    </div>
</div>
