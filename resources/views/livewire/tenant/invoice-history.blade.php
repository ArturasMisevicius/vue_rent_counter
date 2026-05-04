@php
    $statusFilters = [
        'all' => [
            'label' => __('tenant.status.all'),
            'icon' => 'heroicon-m-rectangle-stack',
        ],
        'unpaid' => [
            'label' => __('tenant.status.unpaid'),
            'icon' => 'heroicon-m-exclamation-circle',
        ],
        'paid' => [
            'label' => __('tenant.status.paid'),
            'icon' => 'heroicon-m-check-badge',
        ],
    ];
@endphp

<x-tenant.page>
    <x-shared.page-header icon="heroicon-m-document-text" :title="__('tenant.pages.invoices.page_heading')" :subtitle="__('tenant.pages.invoices.description')">
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                @foreach ($statusFilters as $filter => $filterData)
                    <button
                        type="button"
                        wire:click="$set('selectedStatus', '{{ $filter }}')"
                        @class([
                            'inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2 text-sm font-semibold transition',
                            'bg-brand-ink text-white' => $selectedStatus === $filter,
                            'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50' => $selectedStatus !== $filter,
                        ])
                    >
                        <x-dynamic-component
                            :component="$filterData['icon']"
                            @class([
                                'size-4',
                                'text-white' => $selectedStatus === $filter,
                                'text-slate-500' => $selectedStatus !== $filter,
                            ])
                        />
                        {{ $filterData['label'] }}
                    </button>
                @endforeach
            </div>
        </x-slot:actions>
    </x-shared.page-header>

    <x-tenant.split>
        <x-tenant.main-panel>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand-ink text-white">
                        <x-heroicon-m-document-text class="size-5" />
                    </span>
                    <div class="space-y-2">
                        <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.navigation.invoices') }}</p>
                        <h2 class="font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.pages.invoices.page_heading') }}</h2>
                        <p class="text-sm leading-6 text-slate-600">{{ __('tenant.pages.invoices.description') }}</p>
                    </div>
                </div>

                <div class="lg:max-w-xs lg:min-w-72">
                    <x-shared.stat-card
                        :label="__('tenant.navigation.invoices')"
                        :value="$invoices->total()"
                        :trend="$statusFilters[$selectedStatus]['label'] ?? __('tenant.status.all')"
                        icon="heroicon-m-document-text"
                    />
                </div>
            </div>

            <div class="space-y-5">
                @forelse ($invoices as $invoice)
                    <div wire:key="tenant-invoice-{{ $invoice->id }}" class="space-y-4 rounded-[1.25rem] border border-slate-200/80 bg-slate-50/70 p-4 sm:p-5">
                        <x-shared.invoice-summary
                            :invoice="$invoice"
                            :presentation="$invoicePresentations[$invoice->id] ?? null"
                            :period-display="__('tenant.pages.invoices.period', [
                                'start' => $invoice->billing_period_start?->toDateString() ?? '—',
                                'end' => $invoice->billing_period_end?->toDateString() ?? '—',
                            ])"
                        />

                        <div class="flex justify-end">
                            <button
                                type="button"
                                wire:click="downloadPdf({{ $invoice->id }})"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                            >
                                <x-heroicon-m-arrow-down-tray class="size-4 text-slate-500" />
                                {{ __('tenant.actions.download_pdf') }}
                            </button>
                        </div>
                    </div>
                @empty
                    @if ($selectedStatus === 'unpaid')
                        <x-shared.empty-state
                            icon="heroicon-m-check-badge"
                            :title="__('tenant.status.all_paid_up')"
                            :description="__('tenant.messages.all_paid_up_detail')"
                        />
                    @else
                        <x-shared.empty-state
                            icon="heroicon-m-document-text"
                            :title="__('tenant.pages.invoices.page_heading')"
                            :description="__('tenant.messages.no_invoices_for_filter')"
                        />
                    @endif
                @endforelse
            </div>

            <div>
                {{ $invoices->links() }}
            </div>
        </x-tenant.main-panel>

        <x-tenant.aside-panel>
            <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 px-5 py-5">
                <div class="flex items-center gap-3">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-600 shadow-sm">
                        <x-heroicon-m-user-circle class="size-5" />
                    </span>
                    <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.pages.property.tenant_information') }}</p>
                </div>
                <p class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ $tenant->name }}</p>

                <div class="mt-3 space-y-2 text-sm text-slate-600">
                    @if (filled($tenant->email))
                        <p>{{ $tenant->email }}</p>
                    @endif

                    @if (filled($tenant->phone))
                        <p>{{ $tenant->phone }}</p>
                    @endif
                </div>
            </div>

            @if (filled($paymentGuidance['content'] ?? null))
                <div id="tenant-billing-guidance" class="scroll-mt-28 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-5 py-5">
                    <div class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-600 shadow-sm">
                            <x-heroicon-m-credit-card class="size-5" />
                        </span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.pages.invoices.payment_guidance') }}</p>
                            <h3 class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.pages.invoices.how_to_pay') }}</h3>
                        </div>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $paymentGuidance['content'] }}</p>
                </div>
            @else
                <div id="tenant-billing-guidance" class="scroll-mt-28">
                    <x-shared.empty-state
                        icon="heroicon-m-credit-card"
                        :title="__('tenant.pages.invoices.how_to_pay')"
                        :description="__('tenant.messages.payment_guidance_unavailable')"
                    />
                </div>
            @endif

            @if ($paymentGuidance['has_contact_details'])
                <div id="tenant-billing-contact" class="scroll-mt-28 rounded-[1.25rem] border border-slate-200 bg-white px-5 py-5">
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                            <x-heroicon-m-phone class="size-5" />
                        </span>
                        <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.shell.billing_contact') }}</p>
                    </div>

                    @if ($paymentGuidance['contact_name'])
                        <p class="mt-2 font-semibold text-slate-950">{{ $paymentGuidance['contact_name'] }}</p>
                    @endif

                    <div class="mt-3 space-y-2 text-sm text-slate-600">
                        @if ($paymentGuidance['contact_email'])
                            <p>{{ $paymentGuidance['contact_email'] }}</p>
                        @endif

                        @if ($paymentGuidance['contact_phone'])
                            <p>{{ $paymentGuidance['contact_phone'] }}</p>
                        @endif
                    </div>
                </div>
            @endif
        </x-tenant.aside-panel>
    </x-tenant.split>
</x-tenant.page>
