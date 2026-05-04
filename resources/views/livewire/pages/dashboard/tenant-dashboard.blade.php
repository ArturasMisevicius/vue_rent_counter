<x-tenant.page wire:poll.visible.30s="refreshSummaryOnInterval">
    @if (! ($summary['has_assignment'] ?? false))
        <section>
            <x-shared.empty-state
                icon="heroicon-m-home-modern"
                :title="$summary['empty_state_title']"
                :description="$summary['empty_state_description']"
            />
        </section>
    @else
        <x-tenant.split>
            <x-tenant.main-panel>
                <div class="flex flex-col gap-3 md:flex-row">
                    <x-tenant.card
                        :href="$summary['submit_reading_url']"
                        tone="muted"
                        class="group flex min-h-28 flex-1 items-start justify-between gap-4 border-brand-mint/40 bg-brand-mint/10 px-4 py-4 hover:border-brand-mint hover:bg-brand-mint/15"
                    >
                        <span class="space-y-2">
                            <span class="block text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.navigation.readings') }}</span>
                            <span class="block text-sm font-semibold leading-5 text-slate-950">{{ __('tenant.actions.submit_new_reading') }}</span>
                        </span>
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-ink text-white transition group-hover:bg-slate-900">
                            <x-heroicon-m-clipboard-document-list class="size-5" />
                        </span>
                    </x-tenant.card>

                    <x-tenant.card
                        :href="route('filament.admin.pages.tenant-invoice-history')"
                        tone="muted"
                        class="group flex min-h-28 flex-1 items-start justify-between gap-4 px-4 py-4"
                    >
                        <span class="space-y-2">
                            <span class="block text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.navigation.invoices') }}</span>
                            <span class="block text-sm font-semibold leading-5 text-slate-950">
                                {{ $summary['has_outstanding_balance'] ? $summary['outstanding_total_display'] : __('tenant.status.all_paid_up') }}
                            </span>
                        </span>
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-slate-700 shadow-sm transition group-hover:text-slate-950">
                            <x-heroicon-m-document-text class="size-5" />
                        </span>
                    </x-tenant.card>

                    <x-tenant.card
                        :href="$summary['property_url']"
                        tone="muted"
                        class="group flex min-h-28 flex-1 items-start justify-between gap-4 px-4 py-4"
                    >
                        <span class="space-y-2">
                            <span class="block text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.navigation.property') }}</span>
                            <span class="block text-sm font-semibold leading-5 text-slate-950">{{ $summary['assigned_property']['name'] ?? __('tenant.pages.property.title') }}</span>
                        </span>
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-slate-700 shadow-sm transition group-hover:text-slate-950">
                            <x-heroicon-m-home-modern class="size-5" />
                        </span>
                    </x-tenant.card>
                </div>

                <div class="flex flex-col gap-4 md:flex-row">
                    <div class="min-w-0 flex-1">
                        <x-shared.stat-card
                            :label="$summary['outstanding_label']"
                            :value="$summary['has_outstanding_balance'] ? $summary['outstanding_total_display'] : __('tenant.status.all_paid_up')"
                            :trend="$summary['has_outstanding_balance'] ? __('tenant.pages.home.across_invoices', ['count' => $summary['outstanding_invoice_count']]) : __('tenant.messages.all_paid_up_detail')"
                            icon="heroicon-m-banknotes"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <x-shared.stat-card
                            :label="$summary['month_heading']"
                            :value="$summary['current_month_metric']"
                            :trend="$summary['current_month_message']"
                            icon="heroicon-m-bolt"
                        />
                    </div>
                </div>

                <x-tenant.card class="px-4 py-4 sm:px-5 sm:py-5">
                    <x-tenant.section-heading
                        icon="heroicon-m-bolt"
                        icon-tone="white"
                        :eyebrow="__('tenant.pages.home.current_month_consumption')"
                        :title="__('tenant.pages.home.current_month_consumption')"
                    >
                        @if ($summary['property_address'])
                            <x-slot:actions>
                                <p class="max-w-xs text-sm leading-6 text-slate-500 sm:text-right">{{ $summary['property_address'] }}</p>
                            </x-slot:actions>
                        @endif
                    </x-tenant.section-heading>

                    <div class="mt-4 flex flex-col gap-3 md:flex-row md:flex-wrap">
                        @forelse ($summary['consumption_by_type'] as $consumption)
                            <x-tenant.detail-card
                                :label="$consumption['type']"
                                :value="$consumption['display']"
                                tone="white"
                                class="min-w-0 flex-1 rounded-2xl px-4 py-3 md:min-w-[16rem]"
                            />
                        @empty
                            <x-shared.empty-state
                                icon="heroicon-m-bolt"
                                :title="__('tenant.pages.home.current_month_consumption')"
                                :description="__('tenant.messages.no_readings_yet')"
                            />
                        @endforelse
                    </div>
                </x-tenant.card>

                <x-tenant.recent-readings :groups="$summary['recent_reading_groups']" />
            </x-tenant.main-panel>

            <x-tenant.aside-panel>
                <x-tenant.card
                    :href="route('filament.admin.pages.profile')"
                    wire-navigate
                    data-tenant-home-card="tenant-information"
                    class="group block"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-600 shadow-sm transition group-hover:text-slate-950">
                                <x-heroicon-m-user-circle class="size-5" />
                            </span>
                            <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.pages.property.tenant_information') }}</p>
                        </div>
                        <x-heroicon-m-arrow-up-right class="size-5 shrink-0 text-slate-400 transition group-hover:text-slate-700" />
                    </div>

                    @if (filled($summary['tenant_name'] ?? null))
                        <p class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ $summary['tenant_name'] }}</p>
                    @endif

                    <div class="mt-2 space-y-1 break-words text-sm text-slate-600">
                        @if (filled($summary['tenant_email'] ?? null))
                            <p>{{ $summary['tenant_email'] }}</p>
                        @endif

                        @if (filled($summary['tenant_phone'] ?? null))
                            <p>{{ $summary['tenant_phone'] }}</p>
                        @endif
                    </div>
                </x-tenant.card>

                <x-tenant.card
                    :href="route('filament.admin.pages.tenant-invoice-history').'#tenant-billing-guidance'"
                    wire-navigate
                    tone="white"
                    data-tenant-home-card="billing-guidance"
                    class="group block"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700 transition group-hover:bg-white group-hover:text-slate-950">
                                <x-heroicon-m-credit-card class="size-5" />
                            </span>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.shell.billing_guidance') }}</p>
                                <h3 class="font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.shell.payment_instructions') }}</h3>
                            </div>
                        </div>
                        <x-heroicon-m-arrow-up-right class="size-5 shrink-0 text-slate-400 transition group-hover:text-slate-700" />
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">
                        {{ $summary['payment_guidance']['content'] ?? __('tenant.messages.payment_guidance_unavailable') }}
                    </p>
                </x-tenant.card>

                @if ($summary['payment_guidance']['has_contact_details'])
                    <x-tenant.card
                        :href="route('filament.admin.pages.tenant-invoice-history').'#tenant-billing-contact'"
                        wire-navigate
                        data-tenant-home-card="billing-contact"
                        class="group block"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-600 shadow-sm transition group-hover:text-slate-950">
                                    <x-heroicon-m-phone class="size-5" />
                                </span>
                                <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.shell.billing_contact') }}</p>
                            </div>
                            <x-heroicon-m-arrow-up-right class="size-5 shrink-0 text-slate-400 transition group-hover:text-slate-700" />
                        </div>

                        @if ($summary['payment_guidance']['contact_name'])
                            <p class="mt-2 font-semibold text-slate-950">{{ $summary['payment_guidance']['contact_name'] }}</p>
                        @endif

                        <div class="mt-2 space-y-1 break-words text-sm text-slate-600">
                            @if ($summary['payment_guidance']['contact_email'])
                                <p>{{ $summary['payment_guidance']['contact_email'] }}</p>
                            @endif

                            @if ($summary['payment_guidance']['contact_phone'])
                                <p>{{ $summary['payment_guidance']['contact_phone'] }}</p>
                            @endif
                        </div>
                    </x-tenant.card>
                @endif

                @if ($summary['assigned_property']['name'])
                    <x-tenant.card
                        :href="$summary['property_url']"
                        wire-navigate
                        data-tenant-home-card="assigned-property"
                        class="group block"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-600 shadow-sm transition group-hover:text-slate-950">
                                    <x-heroicon-m-home-modern class="size-5" />
                                </span>
                                <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.shell.assigned_property') }}</p>
                            </div>
                            <x-heroicon-m-arrow-up-right class="size-5 shrink-0 text-slate-400 transition group-hover:text-slate-700" />
                        </div>
                        <p class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ $summary['assigned_property']['name'] }}</p>

                        @if ($summary['assigned_property']['building'])
                            <p class="mt-2 text-sm font-medium text-slate-700">{{ __('tenant.pages.home.building_label', ['building' => $summary['assigned_property']['building']]) }}</p>
                        @endif

                        <p class="mt-2 text-sm text-slate-500">{{ $summary['assigned_property']['address'] }}</p>
                    </x-tenant.card>
                @endif
            </x-tenant.aside-panel>
        </x-tenant.split>
    @endif
</x-tenant.page>
