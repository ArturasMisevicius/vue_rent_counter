@php
    $statusFilters = [
        'all' => __('tenant.status.all'),
        'unpaid' => __('tenant.status.unpaid'),
        'paid' => __('tenant.status.paid'),
    ];
@endphp

<div class="space-y-6">
    <x-shared.page-header :title="__('tenant.pages.invoices.page_heading')" :subtitle="__('tenant.pages.invoices.description')">
        <x-slot:actions>
            <div class="flex flex-wrap gap-2">
                @foreach ($statusFilters as $filter => $label)
                    <button
                        type="button"
                        wire:click="$set('selectedStatus', '{{ $filter }}')"
                        @class([
                            'inline-flex items-center justify-center rounded-2xl px-4 py-2 text-sm font-semibold transition',
                            'bg-brand-ink text-white' => $selectedStatus === $filter,
                            'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50' => $selectedStatus !== $filter,
                        ])
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </x-slot:actions>
    </x-shared.page-header>

    <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
        <section class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-6 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur sm:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.navigation.invoices') }}</p>
                    <h2 class="font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.pages.invoices.page_heading') }}</h2>
                    <p class="text-sm leading-6 text-slate-600">{{ __('tenant.pages.invoices.description') }}</p>
                </div>

                <div class="lg:max-w-xs lg:min-w-72">
                    <x-shared.stat-card
                        :label="__('tenant.navigation.invoices')"
                        :value="$invoices->total()"
                        :trend="$statusFilters[$selectedStatus] ?? __('tenant.status.all')"
                        icon="heroicon-m-document-text"
                    />
                </div>
            </div>

            <div class="space-y-5">
                @forelse ($invoices as $invoice)
                    <div wire:key="tenant-invoice-{{ $invoice->id }}" class="space-y-4 rounded-[1.75rem] border border-slate-200/80 bg-slate-50/70 p-4 sm:p-5">
                        <x-shared.invoice-summary :invoice="$invoice" :presentation="$invoicePresentations[$invoice->id] ?? null" />

                        <div class="flex justify-end">
                            <button
                                type="button"
                                wire:click="downloadPdf({{ $invoice->id }})"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                            >
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
        </section>

        <aside class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-6 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur sm:p-8">
            @if (filled($paymentGuidance['content'] ?? null))
                <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.invoices.payment_guidance') }}</p>
                    <h3 class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ __('tenant.pages.invoices.how_to_pay') }}</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $paymentGuidance['content'] }}</p>
                </div>
            @else
                <x-shared.empty-state
                    icon="heroicon-m-credit-card"
                    :title="__('tenant.pages.invoices.how_to_pay')"
                    :description="__('tenant.messages.payment_guidance_unavailable')"
                />
            @endif

            @if ($paymentGuidance['has_contact_details'])
                <div class="rounded-[1.75rem] border border-slate-200 bg-white px-5 py-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.shell.billing_contact') }}</p>

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
        </aside>
    </div>
</div>
