<div wire:poll.30s class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-600">{{ __('dashboard.organization_eyebrow') }}</p>
        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('dashboard.organization_heading') }}</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ __('dashboard.organization_body') }}</p>

        <div class="mt-6">
            <a
                href="{{ route('filament.admin.pages.reports') }}"
                wire:navigate
                class="inline-flex items-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
            >
                {{ __('shell.navigation.items.reports') }}
            </a>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <x-shared.stat-card
            :label="__('dashboard.organization_metrics.total_properties')"
            :value="(string) $dashboard['metrics']['total_properties']"
            :trend="__('dashboard.organization_metrics.total_properties_trend')"
            icon="heroicon-m-building-office-2"
        />
        <x-shared.stat-card
            :label="__('dashboard.organization_metrics.active_tenants')"
            :value="(string) $dashboard['metrics']['active_tenants']"
            :trend="__('dashboard.organization_metrics.active_tenants_trend')"
            icon="heroicon-m-users"
        />
        <x-shared.stat-card
            :label="__('dashboard.organization_metrics.draft_invoices')"
            :value="(string) $dashboard['metrics']['draft_invoices']"
            :trend="__('dashboard.organization_metrics.draft_invoices_trend')"
            icon="heroicon-m-document-text"
        />
        <x-shared.stat-card
            :label="__('dashboard.organization_metrics.revenue_this_month')"
            :value="$dashboard['metrics']['revenue_this_month']"
            :trend="__('dashboard.organization_metrics.revenue_this_month_trend')"
            icon="heroicon-m-banknotes"
        />
    </section>

    @if ($showSubscriptionUsage)
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="space-y-1">
                <h3 class="text-lg font-semibold text-slate-950">{{ __('dashboard.organization_usage.heading') }}</h3>
                <p class="text-sm text-slate-500">{{ __('dashboard.organization_usage.description') }}</p>
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($dashboard['subscription_usage'] as $item)
                    <x-shared.stat-card
                        :label="$item['label']"
                        :value="$item['value']"
                        icon="heroicon-m-swatch"
                    />
                @endforeach
            </div>
        </section>
    @endif

    <section class="grid gap-6 xl:grid-cols-2">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="space-y-1">
                <h3 class="text-lg font-semibold text-slate-950">{{ __('dashboard.organization_widgets.recent_invoices') }}</h3>
                <p class="text-sm text-slate-500">{{ __('dashboard.organization_widgets.recent_invoices_description') }}</p>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($dashboard['recent_invoices'] as $invoice)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <p class="text-sm font-semibold text-slate-950">{{ $invoice['number'] }}</p>
                                <p class="text-sm text-slate-600">{{ $invoice['tenant'] }}</p>
                                <p class="text-xs uppercase tracking-wide text-slate-500">{{ $invoice['property'] }}</p>
                            </div>

                            <div class="text-right">
                                <p class="text-sm font-semibold text-slate-950">{{ $invoice['amount'] }}</p>
                                <p class="text-xs uppercase tracking-wide text-slate-500">{{ $invoice['status'] }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <x-shared.empty-state
                        icon="heroicon-m-document-text"
                        :title="__('dashboard.organization_widgets.recent_invoices')"
                        :description="__('dashboard.organization_widgets.no_recent_invoices')"
                    />
                @endforelse
            </div>
        </article>

        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="space-y-1">
                <h3 class="text-lg font-semibold text-slate-950">{{ __('dashboard.organization_widgets.upcoming_reading_deadlines') }}</h3>
                <p class="text-sm text-slate-500">{{ __('dashboard.organization_widgets.upcoming_reading_deadlines_description') }}</p>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($dashboard['upcoming_reading_deadlines'] as $deadline)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-950">{{ $deadline['meter_name'] }}</p>
                        <p class="text-sm text-slate-600">{{ $deadline['property_name'] }}</p>
                        <p class="text-xs uppercase tracking-wide text-amber-600">{{ $deadline['due_label'] }}</p>
                    </div>
                @empty
                    <x-shared.empty-state
                        icon="heroicon-m-clipboard-document-list"
                        :title="__('dashboard.organization_widgets.upcoming_reading_deadlines')"
                        :description="__('dashboard.organization_widgets.no_upcoming_deadlines')"
                    />
                @endforelse
            </div>
        </article>
    </section>
</div>
