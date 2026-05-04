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
                    <x-tenant.action
                        type="button"
                        :variant="$selectedStatus === $filter ? 'primary' : 'secondary'"
                        wire:click="$set('selectedStatus', '{{ $filter }}')"
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
                    </x-tenant.action>
                @endforeach
            </div>
        </x-slot:actions>
    </x-shared.page-header>

    <x-tenant.split>
        <x-tenant.main-panel>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <x-tenant.section-heading
                    icon="heroicon-m-document-text"
                    :eyebrow="__('tenant.navigation.invoices')"
                    :title="__('tenant.pages.invoices.page_heading')"
                    :description="__('tenant.pages.invoices.description')"
                />

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
                    <div wire:key="tenant-invoice-{{ $invoice->id }}">
                        <x-tenant.invoice-card
                            :invoice="$invoice"
                            :presentation="$invoicePresentations[$invoice->id] ?? null"
                            :period-display="__('tenant.pages.invoices.period', [
                                'start' => $invoice->billing_period_start?->toDateString() ?? '—',
                                'end' => $invoice->billing_period_end?->toDateString() ?? '—',
                            ])"
                        >
                            <x-tenant.action
                                type="button"
                                wire:click="downloadPdf({{ $invoice->id }})"
                                icon="heroicon-m-arrow-down-tray"
                            >
                                {{ __('tenant.actions.download_pdf') }}
                            </x-tenant.action>
                        </x-tenant.invoice-card>
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
            <x-tenant.card>
                <x-tenant.section-heading
                    icon="heroicon-m-user-circle"
                    icon-tone="white"
                    :eyebrow="__('tenant.pages.property.tenant_information')"
                    :title="$tenant->name"
                />

                <div class="mt-3 space-y-2 text-sm text-slate-600">
                    @if (filled($tenant->email))
                        <p>{{ $tenant->email }}</p>
                    @endif

                    @if (filled($tenant->phone))
                        <p>{{ $tenant->phone }}</p>
                    @endif
                </div>
            </x-tenant.card>

            @if (filled($paymentGuidance['content'] ?? null))
                <x-tenant.card id="tenant-billing-guidance" class="scroll-mt-28">
                    <x-tenant.section-heading
                        icon="heroicon-m-credit-card"
                        icon-tone="white"
                        :eyebrow="__('tenant.pages.invoices.payment_guidance')"
                        :title="__('tenant.pages.invoices.how_to_pay')"
                    />
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $paymentGuidance['content'] }}</p>
                </x-tenant.card>
            @else
                <x-tenant.card id="tenant-billing-guidance" tone="warning" class="scroll-mt-28">
                    <x-tenant.section-heading
                        icon="heroicon-m-credit-card"
                        icon-tone="warning"
                        :eyebrow="__('tenant.pages.invoices.payment_guidance')"
                        :title="__('tenant.pages.invoices.payment_guidance_pending')"
                    />
                    <p class="mt-3 text-sm leading-6 text-slate-700">{{ __('tenant.messages.payment_guidance_unavailable') }}</p>

                    @if ($paymentGuidance['has_contact_details'])
                        <x-tenant.action href="#tenant-billing-contact" variant="warning" icon="heroicon-m-phone" class="mt-4">
                            {{ __('tenant.pages.invoices.view_billing_contact') }}
                        </x-tenant.action>
                    @endif
                </x-tenant.card>
            @endif

            @if ($paymentGuidance['has_contact_details'])
                <x-tenant.card id="tenant-billing-contact" tone="white" class="scroll-mt-28">
                    <x-tenant.section-heading
                        icon="heroicon-m-phone"
                        icon-tone="soft"
                        :eyebrow="__('tenant.shell.billing_contact')"
                        :title="$paymentGuidance['contact_name'] ?: __('tenant.shell.billing_contact')"
                    />

                    <div class="mt-3 space-y-2 text-sm text-slate-600">
                        @if ($paymentGuidance['contact_email'])
                            <p>{{ $paymentGuidance['contact_email'] }}</p>
                        @endif

                        @if ($paymentGuidance['contact_phone'])
                            <p>{{ $paymentGuidance['contact_phone'] }}</p>
                        @endif
                    </div>
                </x-tenant.card>
            @endif
        </x-tenant.aside-panel>
    </x-tenant.split>
</x-tenant.page>
