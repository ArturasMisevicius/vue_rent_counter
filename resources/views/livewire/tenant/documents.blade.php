<x-tenant.page>
    <x-shared.page-header icon="heroicon-m-document-duplicate" :title="__('tenant.pages.documents.title')" :subtitle="__('tenant.pages.documents.description')">
        <x-slot:actions>
            <div class="flex max-w-full gap-2 overflow-x-auto pb-1">
                @foreach ($filters as $category => $filter)
                    <x-tenant.action
                        type="button"
                        :variant="$selectedCategory === $category ? 'primary' : 'secondary'"
                        wire:click="$set('selectedCategory', '{{ $category }}')"
                        class="shrink-0"
                    >
                        <x-dynamic-component
                            :component="$filter['icon']"
                            @class([
                                'size-4',
                                'text-white' => $selectedCategory === $category,
                                'text-slate-500' => $selectedCategory !== $category,
                            ])
                        />
                        <span>{{ $filter['label'] }}</span>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-xs',
                            'bg-white/15 text-white' => $selectedCategory === $category,
                            'bg-slate-100 text-slate-500' => $selectedCategory !== $category,
                        ])>{{ $filter['count'] }}</span>
                    </x-tenant.action>
                @endforeach
            </div>
        </x-slot:actions>
    </x-shared.page-header>

    <x-tenant.split>
        <x-tenant.main-panel>
            <x-tenant.section-heading
                icon="heroicon-m-document-duplicate"
                :eyebrow="__('tenant.navigation.documents')"
                :title="__('tenant.pages.documents.library')"
                :description="__('tenant.pages.documents.library_description')"
            />

            <div class="mt-5 space-y-4">
                @forelse ($documents as $document)
                    <article id="tenant-document-{{ $document['id'] }}" wire:key="tenant-document-{{ $document['id'] }}" class="scroll-mt-28 rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 space-y-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold text-slate-950">{{ $document['title'] }}</h3>
                                    <x-shared.status-badge :status="$document['status']" />

                                    @if ($document['is_expired'])
                                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-amber-800 ring-1 ring-inset ring-amber-300/80">
                                            {{ __('tenant.pages.documents.expired_badge') }}
                                        </span>
                                    @endif
                                </div>

                                <dl class="grid gap-3 text-sm text-slate-600 sm:grid-cols-2">
                                    <div>
                                        <dt class="font-medium text-slate-500">{{ __('tenant.pages.documents.type') }}</dt>
                                        <dd class="mt-1 text-slate-900">{{ $document['document_type_label'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-slate-500">{{ __('tenant.pages.documents.property') }}</dt>
                                        <dd class="mt-1 text-slate-900">{{ $document['property'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-slate-500">{{ __('tenant.pages.documents.file') }}</dt>
                                        <dd class="mt-1 text-slate-900">{{ $document['file_name'] }} · {{ $document['file_size'] }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-slate-500">{{ __('tenant.pages.documents.uploaded_at') }}</dt>
                                        <dd class="mt-1 text-slate-900">{{ $document['uploaded_at'] }}</dd>
                                    </div>
                                </dl>

                                @if (filled($document['description']))
                                    <p class="text-sm leading-6 text-slate-600">{{ $document['description'] }}</p>
                                @endif

                                @if ($document['is_rejected_kyc'] && filled($document['rejection_reason']))
                                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm leading-6 text-rose-900">
                                        <span class="font-semibold">{{ __('tenant.pages.documents.rejection_reason') }}</span>
                                        {{ $document['rejection_reason'] }}
                                    </div>
                                @endif
                            </div>

                            <x-tenant.action href="{{ $document['download_url'] }}" icon="heroicon-m-arrow-down-tray">
                                {{ __('tenant.pages.documents.download') }}
                            </x-tenant.action>
                        </div>
                    </article>
                @empty
                    <x-shared.empty-state
                        icon="heroicon-m-document-duplicate"
                        :title="__('tenant.pages.documents.no_documents')"
                        :description="__('tenant.pages.documents.no_documents_description')"
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
