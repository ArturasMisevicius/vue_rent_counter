<x-tenant.page>
    <x-shared.page-header icon="heroicon-m-document-text" :title="__('tenant.pages.documents.title')" :subtitle="__('tenant.pages.documents.description')" />

    <x-tenant.split>
        <x-tenant.main-panel>
            <x-tenant.section-heading
                icon="heroicon-m-document-text"
                :eyebrow="__('tenant.navigation.documents')"
                :title="__('tenant.pages.documents.rental_contracts')"
                :description="__('tenant.pages.documents.rental_contracts_description')"
            />

            <div class="mt-5 space-y-4">
                @forelse ($contracts as $contract)
                    <article id="rental-contract-{{ $contract['id'] }}" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold text-slate-950">{{ $contract['contract_number'] }}</h3>
                                    <span class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">{{ $contract['status'] }}</span>
                                </div>

                                <dl class="grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                                    <div>
                                        <dt class="font-medium text-slate-500">{{ __('tenant.pages.documents.property') }}</dt>
                                        <dd class="mt-1 text-slate-900">{{ $contract['property'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-slate-500">{{ __('tenant.pages.documents.period') }}</dt>
                                        <dd class="mt-1 text-slate-900">{{ $contract['period'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-slate-500">{{ __('tenant.pages.documents.rent_amount') }}</dt>
                                        <dd class="mt-1 text-slate-900">{{ $contract['rent_amount'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-slate-500">{{ __('tenant.pages.documents.deposit_amount') }}</dt>
                                        <dd class="mt-1 text-slate-900">{{ $contract['deposit_amount'] }}</dd>
                                    </div>
                                </dl>

                                @if (filled($contract['tenant_visible_notes']))
                                    <p class="text-sm leading-6 text-slate-600">{{ $contract['tenant_visible_notes'] }}</p>
                                @endif
                            </div>

                            @if ($contract['download_url'])
                                <x-tenant.action href="{{ $contract['download_url'] }}" icon="heroicon-m-arrow-down-tray">
                                    {{ __('tenant.pages.documents.download_contract') }}
                                </x-tenant.action>
                            @endif
                        </div>
                    </article>
                @empty
                    <x-shared.empty-state
                        icon="heroicon-m-document-text"
                        :title="__('tenant.pages.documents.no_contracts')"
                        :description="__('tenant.pages.documents.no_contracts_description')"
                    />
                @endforelse
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
        </x-tenant.aside-panel>
    </x-tenant.split>
</x-tenant.page>
