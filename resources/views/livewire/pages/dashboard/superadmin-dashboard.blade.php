<div wire:poll.visible.60s="refreshDashboardOnInterval" class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-600">{{ __('dashboard.platform_overview.eyebrow') }}</p>
        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('dashboard.platform_overview.heading') }}</h1>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
            {{ __('dashboard.platform_overview.description') }}
        </p>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach ($dashboard['metrics'] as $metric)
            <x-shared.stat-card
                :label="$metric['label']"
                :value="$metric['value']"
                :icon="$metric['icon']"
                :trend="$metric['trend']"
                :trend-direction="$metric['trend_direction']"
                :trend-tone="$metric['trend_tone']"
                :value-tone="$metric['value_tone']"
            />
        @endforeach
    </section>

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(0,1fr)]">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">{{ __('dashboard.platform_sections.revenue_by_plan') }}</h2>
            <p class="mt-2 text-sm text-slate-500">{{ __('dashboard.platform_sections.revenue_by_plan_description') }}</p>

            <x-superadmin.revenue-trend-chart :chart="$dashboard['revenueByPlan']" />
        </article>

        <div class="space-y-6">
            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950">{{ __('dashboard.platform_sections.expiring_subscriptions') }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ __('dashboard.platform_sections.expiring_subscriptions_description') }}</p>

                <div class="mt-4 space-y-3">
                    @forelse ($dashboard['expiringSubscriptions']['rows'] as $subscription)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                            <p class="font-medium text-slate-950">{{ $subscription['organization'] }}</p>
                            <p class="text-sm text-slate-600">{{ $subscription['plan'] }} · {{ $subscription['expires_at'] }}</p>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-6 text-sm text-slate-500">
                            {{ __('dashboard.platform_sections.expiring_subscriptions_empty') }}
                        </p>
                    @endforelse
                </div>

                @if ($dashboard['expiringSubscriptions']['has_more'])
                    <div class="mt-4">
                        <a
                            href="{{ $dashboard['expiringSubscriptions']['view_all_url'] }}"
                            wire:navigate
                            class="text-sm font-semibold text-amber-700 transition hover:text-amber-800"
                        >
                            {{ __('dashboard.platform_actions.view_all') }}
                        </a>
                    </div>
                @endif
            </article>

            <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-950">{{ __('dashboard.platform_sections.recent_security_violations') }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ __('dashboard.platform_sections.recent_security_violations_description') }}</p>

                <div class="mt-4 space-y-3">
                    @forelse ($dashboard['recentSecurityViolations'] as $violation)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                            <p class="font-medium text-slate-950">{{ $violation['type'] }}</p>
                            <p class="mt-1 text-sm text-slate-600">{{ $violation['ip_address'] }} · {{ $violation['severity'] }} · {{ $violation['occurred_ago'] }}</p>
                        </div>
                    @empty
                        <p class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-6 text-sm text-slate-500">
                            {{ __('dashboard.platform_sections.recent_security_violations_empty') }}
                        </p>
                    @endforelse
                </div>
            </article>
        </div>
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold text-slate-950">{{ __('dashboard.platform_sections.recent_organizations') }}</h2>
                <p class="mt-2 text-sm text-slate-500">{{ __('dashboard.platform_sections.recent_organizations_description') }}</p>
            </div>

            @if (filled($dashboard['recentOrganizations']['export_url']))
                <a
                    href="{{ $dashboard['recentOrganizations']['export_url'] }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                >
                    {{ __('dashboard.platform_actions.export_csv') }}
                </a>
            @endif
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                        <th class="py-3 pr-4 font-semibold">{{ __('dashboard.platform_recent_organizations.columns.name') }}</th>
                        <th class="py-3 pr-4 font-semibold">{{ __('dashboard.platform_recent_organizations.columns.owner_email') }}</th>
                        <th class="py-3 pr-4 font-semibold">{{ __('dashboard.platform_recent_organizations.columns.plan_type') }}</th>
                        <th class="py-3 pr-4 font-semibold">{{ __('dashboard.platform_recent_organizations.columns.subscription_status') }}</th>
                        <th class="py-3 pr-4 font-semibold">{{ __('dashboard.platform_recent_organizations.columns.properties_count') }}</th>
                        <th class="py-3 pr-4 font-semibold">{{ __('dashboard.platform_recent_organizations.columns.tenants_count') }}</th>
                        <th class="py-3 font-semibold">{{ __('dashboard.platform_recent_organizations.columns.date_created') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($dashboard['recentOrganizations']['rows'] as $organization)
                        <tr class="align-top text-slate-700">
                            <td class="py-4 pr-4">
                                <a href="{{ $organization['url'] }}" wire:navigate class="font-semibold text-slate-950 transition hover:text-amber-700">
                                    {{ $organization['name'] }}
                                </a>
                            </td>
                            <td class="py-4 pr-4">{{ $organization['owner_email'] }}</td>
                            <td class="py-4 pr-4">{{ $organization['plan_type'] }}</td>
                            <td class="py-4 pr-4">{{ $organization['subscription_status'] }}</td>
                            <td class="py-4 pr-4">{{ $organization['properties_count'] }}</td>
                            <td class="py-4 pr-4">{{ $organization['tenants_count'] }}</td>
                            <td class="py-4">{{ $organization['date_created'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-sm text-slate-500">
                                {{ __('dashboard.platform_sections.recent_organizations_empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
