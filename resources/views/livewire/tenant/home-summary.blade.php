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
                    <x-tenant.action :href="$summary['submit_reading_url']" variant="primary">
                        {{ __('tenant.actions.submit_new_reading') }}
                    </x-tenant.action>

                    <x-tenant.action :href="$summary['property_url']">
                        {{ __('tenant.pages.property.title') }}
                    </x-tenant.action>
                </div>
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
