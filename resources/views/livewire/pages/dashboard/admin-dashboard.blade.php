<div wire:poll.visible.30s="refreshDashboardOnInterval" class="space-y-6">
    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <p class="text-sm font-semibold uppercase tracking-[0.24em] text-emerald-600">{{ __('dashboard.organization_eyebrow') }}</p>
        <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ __('dashboard.organization_heading') }}</h2>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{{ __('dashboard.organization_body') }}</p>
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
            :label="__('dashboard.organization_metrics.pending_invoices')"
            :value="(string) $dashboard['metrics']['pending_invoices']"
            :trend="__('dashboard.organization_metrics.pending_invoices_trend')"
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

            <div class="mt-5 grid gap-4 md:grid-cols-2">
                @foreach ($dashboard['subscription_usage'] as $usage)
                    @php($barColor = match ($usage['tone']) {
                        'danger' => 'bg-rose-500',
                        'warning' => 'bg-amber-500',
                        default => 'bg-slate-900',
                    })

                    <article class="rounded-2xl border border-slate-200 px-4 py-4">
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-sm font-semibold text-slate-950">{{ $usage['label'] }}</p>
                            <p class="text-sm text-slate-600">{{ $usage['summary'] }}</p>
                        </div>

                        <div class="mt-3 h-3 rounded-full bg-slate-100">
                            <div class="{{ $barColor }} h-3 rounded-full transition-all" style="width: {{ $usage['percent'] }}%"></div>
                        </div>

                        @if ($usage['limit_reached'])
                            <div class="mt-3 flex items-start justify-between gap-4">
                                <p class="text-sm font-medium text-rose-600">{{ $usage['message'] }}</p>
                                <a
                                    href="{{ route('filament.admin.pages.settings') }}#subscription"
                                    wire:navigate
                                    class="inline-flex shrink-0 items-center justify-center rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white"
                                >
                                    {{ __('dashboard.organization_usage.upgrade_action') }}
                                </a>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    <section class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
        <article class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div class="space-y-1">
                    <h3 class="text-lg font-semibold text-slate-950">{{ __('dashboard.organization_widgets.recent_invoices') }}</h3>
                    <p class="text-sm text-slate-500">{{ __('dashboard.organization_widgets.recent_invoices_description') }}</p>
                </div>

                <a
                    href="{{ route('filament.admin.resources.invoices.index') }}"
                    wire:navigate
                    class="text-sm font-semibold text-slate-700 transition hover:text-slate-950"
                >
                    {{ __('dashboard.organization_widgets.view_all') }}
                </a>
            </div>

            <div class="mt-4 space-y-3">
                @forelse ($dashboard['recent_invoices'] as $invoice)
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 space-y-1">
                                <a
                                    href="{{ route('filament.admin.resources.invoices.view', $invoice['id']) }}"
                                    wire:navigate
                                    class="text-sm font-semibold text-slate-950 transition hover:text-slate-700"
                                >
                                    {{ $invoice['tenant'] }}
                                </a>
                                <p class="font-mono text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ $invoice['number'] }}
                                </p>
                                <p class="text-sm text-slate-600">{{ $invoice['property'] }}</p>
                                <p class="text-xs uppercase tracking-wide text-slate-500">{{ $invoice['billing_period'] }}</p>
                            </div>

                            <div class="space-y-2 text-right">
                                <p class="text-sm font-semibold text-slate-950">{{ $invoice['amount'] }}</p>
                                <span class="inline-flex rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">
                                    {{ $invoice['status'] }}
                                </span>

                                @if ($invoice['can_process_payment'])
                                    <div>
                                        <a
                                            href="{{ route('filament.admin.resources.invoices.view', $invoice['id']) }}"
                                            wire:navigate
                                            class="text-sm font-semibold text-emerald-700 transition hover:text-emerald-900"
                                        >
                                            {{ __('dashboard.organization_widgets.process_payment') }}
                                        </a>
                                    </div>
                                @endif
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
                    @php($toneClass = match ($deadline['tone']) {
                        'danger' => 'text-rose-600',
                        'warning' => 'text-amber-600',
                        default => 'text-slate-500',
                    })

                    <a
                        href="{{ route('filament.admin.resources.meter-readings.create', ['meter' => $deadline['meter_id']]) }}"
                        wire:navigate
                        class="block rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-slate-300 hover:bg-white"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <p class="font-mono text-sm font-semibold text-slate-950">{{ $deadline['meter_identifier'] }}</p>
                                <p class="text-sm text-slate-600">{{ $deadline['property_name'] }}</p>
                            </div>

                            <p class="text-sm font-semibold {{ $toneClass }}">{{ $deadline['due_label'] }}</p>
                        </div>
                    </a>
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
