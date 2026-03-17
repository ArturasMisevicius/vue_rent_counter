<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-600">{{ __('dashboard.platform_eyebrow') }}</p>
            <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('dashboard.platform_heading') }}</h2>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ __('dashboard.platform_body') }}</p>
        </section>

        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($metrics as $metric)
                <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $metric['label'] }}</p>
                    <p class="mt-4 text-3xl font-semibold tracking-tight text-slate-950">{{ $metric['value'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">Revenue by Plan</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($revenueByPlan as $row)
                        <div class="flex items-center justify-between gap-4 rounded-2xl bg-slate-50 px-4 py-3">
                            <span class="font-medium text-slate-700">{{ $row['plan'] }}</span>
                            <span class="text-sm font-semibold text-slate-950">{{ $row['amount'] }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('dashboard.not_available') }}</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">Expiring Subscriptions</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($expiringSubscriptions as $subscription)
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="font-medium text-slate-950">{{ $subscription['organization'] }}</p>
                            <p class="text-sm text-slate-600">{{ $subscription['plan'] }} · {{ $subscription['expires_at'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('dashboard.not_available') }}</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">Recent Security Violations</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($recentSecurityViolations as $violation)
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="font-medium text-slate-950">{{ $violation['summary'] }}</p>
                            <p class="text-sm text-slate-600">{{ $violation['organization'] }} · {{ $violation['severity'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('dashboard.not_available') }}</p>
                    @endforelse
                </div>
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">Recently Created Organizations</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($recentOrganizations as $organization)
                        <div class="rounded-2xl bg-slate-50 px-4 py-3">
                            <p class="font-medium text-slate-950">{{ $organization['name'] }}</p>
                            <p class="text-sm text-slate-600">{{ $organization['slug'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('dashboard.not_available') }}</p>
                    @endforelse
                </div>
            </article>
        </section>
    </div>
</x-filament-panels::page>
