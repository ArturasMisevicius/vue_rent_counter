<x-tenant.page wire:poll.120s>
    <x-tenant.split>
        <x-tenant.main-panel>
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <x-tenant.section-heading
                    :eyebrow="__('tenant.shell.summary_eyebrow')"
                    :title="__('tenant.pages.home.greeting', ['name' => $summary['tenant_name']])"
                    :description="__('tenant.messages.account_snapshot')"
                    class="[&_h2]:text-4xl"
                />

                <div class="flex flex-wrap gap-3">
                    @if ($summary['current_invoice'] ?? null)
                        <x-tenant.action :href="$summary['submit_reading_url']" variant="primary" data-tenant-home-submit-readings="true">
                            {{ __('tenant.actions.submit_readings') }}
                        </x-tenant.action>
                    @else
                        <span data-tenant-reading-request-status="true" class="inline-flex min-h-11 items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600">
                            <x-heroicon-m-clock class="size-4 shrink-0" />
                            {{ __('tenant.pages.home.waiting_for_reading_request_metric') }}
                        </span>
                    @endif

                    <x-tenant.action :href="$summary['property_url']">
                        {{ __('tenant.pages.property.title') }}
                    </x-tenant.action>
                </div>
            </div>

            @if ($summary['current_invoice'] ?? null)
                <x-tenant.card :href="$summary['submit_reading_url']" tone="warning" data-tenant-current-invoice="true" class="block">
                    <x-tenant.section-heading
                        icon="heroicon-m-document-text"
                        icon-tone="warning"
                        :eyebrow="__('tenant.pages.home.current_invoice')"
                        :title="$summary['current_invoice']['number']"
                        :description="__('tenant.pages.home.current_invoice_description')"
                    />

                    <div class="mt-4 flex flex-col gap-3 text-sm leading-6 text-amber-900 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1">
                            <p>{{ $summary['current_invoice']['period'] }}</p>
                            <p>{{ $summary['current_invoice']['due'] }}</p>
                        </div>

                        <span class="inline-flex items-center justify-center rounded-2xl bg-white px-4 py-2 font-semibold text-amber-800 shadow-sm">
                            {{ __('tenant.actions.submit_readings') }}
                        </span>
                    </div>
                </x-tenant.card>
            @elseif ($summary['has_assignment'] ?? false)
                <x-tenant.card tone="white" data-tenant-reading-request-waiting="true">
                    <x-tenant.section-heading
                        icon="heroicon-m-clock"
                        icon-tone="soft"
                        :eyebrow="__('tenant.pages.home.month_heading')"
                        :title="__('tenant.pages.home.waiting_for_reading_request_metric')"
                        :description="__('tenant.pages.home.waiting_for_reading_request_message')"
                    />
                </x-tenant.card>
            @endif

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

            <x-tenant.recent-readings :groups="$summary['recent_reading_groups']" />
        </x-tenant.main-panel>

        <x-tenant.aside-panel>
            <x-tenant.card>
                <x-tenant.section-heading
                    icon="heroicon-m-user-circle"
                    icon-tone="white"
                    :eyebrow="__('tenant.pages.property.tenant_information')"
                    :title="$summary['tenant_name']"
                />

                <div class="mt-2 space-y-1 break-words text-sm text-slate-600">
                    @if (filled($summary['tenant_email'] ?? null))
                        <p>{{ $summary['tenant_email'] }}</p>
                    @endif

                    @if (filled($summary['tenant_phone'] ?? null))
                        <p>{{ $summary['tenant_phone'] }}</p>
                    @endif
                </div>
            </x-tenant.card>

            <x-tenant.card tone="white">
                <x-tenant.section-heading
                    icon="heroicon-m-credit-card"
                    icon-tone="soft"
                    :eyebrow="__('tenant.shell.billing_guidance')"
                    :title="__('tenant.shell.payment_instructions')"
                />
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    {{ $summary['payment_guidance']['content'] ?? __('tenant.messages.payment_guidance_unavailable') }}
                </p>
            </x-tenant.card>

            <x-tenant.card>
                <x-tenant.section-heading
                    icon="heroicon-m-identification"
                    icon-tone="white"
                    :eyebrow="__('tenant.navigation.verification')"
                    :title="$summary['kyc_verification']['status_label']"
                    :description="$summary['kyc_verification']['message']"
                />

                <div class="mt-4">
                    <x-tenant.action :href="$summary['kyc_verification']['url']" icon="heroicon-m-identification">
                        {{ __('tenant.pages.verification.title') }}
                    </x-tenant.action>
                </div>
            </x-tenant.card>

            @if ($summary['payment_guidance']['has_contact_details'])
                <x-tenant.card>
                    <x-tenant.section-heading
                        icon="heroicon-m-phone"
                        icon-tone="white"
                        :eyebrow="__('tenant.shell.billing_contact')"
                        :title="$summary['payment_guidance']['contact_name'] ?: __('tenant.shell.billing_contact')"
                    />

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

            @if ($summary['property_name'])
                <x-tenant.card>
                    <x-tenant.section-heading
                        icon="heroicon-m-home-modern"
                        icon-tone="white"
                        :eyebrow="__('tenant.shell.assigned_property')"
                        :title="$summary['property_name']"
                        :description="$summary['property_address']"
                    />
                </x-tenant.card>
            @endif
        </x-tenant.aside-panel>
    </x-tenant.split>
</x-tenant.page>
