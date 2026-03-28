<div wire:poll.120s class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
    <section class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="space-y-2">
                <p class="text-sm font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ __('tenant.shell.summary_eyebrow') }}</p>
                <h2 class="font-display text-4xl tracking-tight text-slate-950">{{ __('tenant.pages.home.greeting', ['name' => $summary['tenant_name']]) }}</h2>
                <p class="max-w-2xl text-sm leading-6 text-slate-600">{{ __('tenant.messages.account_snapshot') }}</p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a
                    href="{{ $summary['submit_reading_url'] }}"
                    class="inline-flex items-center justify-center rounded-2xl bg-brand-ink px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-900"
                >
                    {{ __('tenant.actions.submit_new_reading') }}
                </a>
                <a
                    href="{{ $summary['property_url'] }}"
                    class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                >
                    {{ __('tenant.pages.property.title') }}
                </a>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <x-shared.stat-card
                :label="$summary['outstanding_label']"
                :value="$summary['has_outstanding_balance'] ? $summary['outstanding_total_display'] : __('tenant.status.all_paid_up')"
                :trend="$summary['has_outstanding_balance'] ? __('tenant.pages.home.across_invoices', ['count' => $summary['outstanding_invoice_count']]) : __('tenant.messages.all_paid_up_detail')"
                icon="heroicon-m-banknotes"
            />

            <x-shared.stat-card
                :label="$summary['month_heading']"
                :value="$summary['current_month_metric']"
                :trend="$summary['current_month_message']"
                icon="heroicon-m-bolt"
            />
        </div>

        <article class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.home.recent_readings') }}</p>
                    <h3 class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.pages.home.recent_readings') }}</h3>
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
                    <x-shared.empty-state
                        icon="heroicon-m-beaker"
                        :title="__('tenant.pages.home.recent_readings')"
                        :description="__('tenant.messages.no_readings_yet')"
                    />
                @endforelse
            </div>
        </article>
    </section>

    <aside class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.property.tenant_information') }}</p>

            @if (filled($summary['tenant_name'] ?? null))
                <p class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ $summary['tenant_name'] }}</p>
            @endif

            <div class="mt-2 space-y-1 text-sm text-slate-600">
                @if (filled($summary['tenant_email'] ?? null))
                    <p>{{ $summary['tenant_email'] }}</p>
                @endif

                @if (filled($summary['tenant_phone'] ?? null))
                    <p>{{ $summary['tenant_phone'] }}</p>
                @endif
            </div>
        </div>

        <div class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.shell.billing_guidance') }}</p>
            <h3 class="font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.shell.payment_instructions') }}</h3>
            <p class="text-sm leading-6 text-slate-600">
                {{ $summary['payment_guidance']['content'] ?? __('tenant.messages.payment_guidance_unavailable') }}
            </p>
        </div>

        @if ($summary['payment_guidance']['has_contact_details'])
            <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.shell.billing_contact') }}</p>

                @if ($summary['payment_guidance']['contact_name'])
                    <p class="mt-2 font-semibold text-slate-950">{{ $summary['payment_guidance']['contact_name'] }}</p>
                @endif

                <div class="mt-2 space-y-1 text-sm text-slate-600">
                    @if ($summary['payment_guidance']['contact_email'])
                        <p>{{ $summary['payment_guidance']['contact_email'] }}</p>
                    @endif

                    @if ($summary['payment_guidance']['contact_phone'])
                        <p>{{ $summary['payment_guidance']['contact_phone'] }}</p>
                    @endif
                </div>
            </div>
        @endif

        @if ($summary['property_name'])
            <div class="rounded-[1.75rem] bg-slate-50 px-5 py-5">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.shell.assigned_property') }}</p>
                <p class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ $summary['property_name'] }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ $summary['property_address'] }}</p>
            </div>
        @endif
    </aside>
</div>
