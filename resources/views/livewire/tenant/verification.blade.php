<x-tenant.page>
    <x-shared.page-header icon="heroicon-m-identification" :title="__('tenant.pages.verification.title')" :subtitle="__('tenant.pages.verification.description')" />

    <x-tenant.split>
        <x-tenant.main-panel>
            <x-tenant.section-heading
                icon="heroicon-m-identification"
                :eyebrow="__('tenant.navigation.verification')"
                :title="$overview['status_label']"
                :description="$overview['is_required'] ? __('tenant.pages.verification.required_summary', ['count' => $overview['required_count']]) : __('tenant.pages.verification.not_required')"
            />

            @if (filled($overview['rejection_reason']))
                <div class="mt-5 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm leading-6 text-rose-900">
                    <span class="font-semibold">{{ __('tenant.pages.verification.rejection_reason') }}</span>
                    {{ $overview['rejection_reason'] }}
                </div>
            @endif

            <div class="mt-5 space-y-4">
                @forelse ($overview['checklist'] as $item)
                    <article class="rounded-2xl border border-slate-200 bg-white p-5">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-base font-semibold text-slate-950">{{ $item['type_label'] }}</h3>
                                    <x-shared.status-badge :status="$item['status']" />
                                    @if ($item['is_expired'])
                                        <span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold uppercase text-amber-800 ring-1 ring-inset ring-amber-300/80">
                                            {{ __('tenant.pages.verification.expired') }}
                                        </span>
                                    @endif
                                </div>

                                @if (filled($item['expires_at']))
                                    <p class="text-sm text-slate-600">{{ __('tenant.pages.verification.expires_at', ['date' => $item['expires_at']]) }}</p>
                                @endif

                                @if (filled($item['rejection_reason']))
                                    <p class="text-sm leading-6 text-rose-800">{{ $item['rejection_reason'] }}</p>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if (filled($item['download_url']))
                                    <x-tenant.action href="{{ $item['download_url'] }}" icon="heroicon-m-arrow-down-tray">
                                        {{ __('tenant.pages.verification.download') }}
                                    </x-tenant.action>
                                @endif

                                @if (filled($item['document_id']))
                                    <x-tenant.action type="button" variant="secondary" icon="heroicon-m-arrow-path" wire:click="prepareReplacement({{ $item['document_id'] }})">
                                        {{ __('tenant.pages.verification.replace') }}
                                    </x-tenant.action>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <x-shared.empty-state
                        icon="heroicon-m-identification"
                        :title="__('tenant.pages.verification.no_requirements')"
                        :description="__('tenant.pages.verification.no_requirements_description')"
                    />
                @endforelse
            </div>
        </x-tenant.main-panel>

        <x-tenant.aside-panel>
            <x-tenant.card>
                <x-tenant.section-heading
                    icon="heroicon-m-arrow-up-tray"
                    icon-tone="white"
                    :eyebrow="$replacingDocumentId ? __('tenant.pages.verification.replacement') : __('tenant.pages.verification.upload')"
                    :title="$replacingDocumentId ? __('tenant.pages.verification.replace_document') : __('tenant.pages.verification.upload_document')"
                />

                <form wire:submit="submitDocument" class="mt-5 space-y-4">
                    <x-tenant.select-field
                        id="tenant-kyc-document-type"
                        wire:model="documentType"
                        :label="__('tenant.pages.verification.document_type')"
                        :disabled="filled($replacingDocumentId)"
                    >
                        @foreach ($overview['document_type_options'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </x-tenant.select-field>

                    <x-tenant.text-field id="tenant-kyc-document-number" wire:model="documentNumber" :label="__('tenant.pages.verification.document_number')" />
                    <x-tenant.text-field id="tenant-kyc-issued-country" wire:model="issuedCountry" :label="__('tenant.pages.verification.issued_country')" />
                    <x-tenant.text-field id="tenant-kyc-issued-at" type="date" wire:model="issuedAt" :label="__('tenant.pages.verification.issued_at')" />
                    <x-tenant.text-field id="tenant-kyc-expires-at" type="date" wire:model="expiresAt" :label="__('tenant.pages.verification.expires_on')" />

                    <div>
                        <label class="block text-sm font-semibold text-slate-800" for="tenant-kyc-file">
                            {{ __('tenant.pages.verification.file') }}
                        </label>
                        <input
                            id="tenant-kyc-file"
                            type="file"
                            wire:model="documentFile"
                            class="mt-2 block w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-xl file:border-0 file:bg-slate-900 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white"
                        >
                        @error('documentFile')
                            <x-tenant.field-error :message="$message" />
                        @enderror
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-tenant.action type="submit" icon="heroicon-m-arrow-up-tray">
                            {{ $replacingDocumentId ? __('tenant.pages.verification.submit_replacement') : __('tenant.pages.verification.submit_document') }}
                        </x-tenant.action>

                        @if (filled($replacingDocumentId))
                            <x-tenant.action type="button" variant="secondary" wire:click="cancelReplacement">
                                {{ __('admin.actions.close') }}
                            </x-tenant.action>
                        @endif
                    </div>
                </form>
            </x-tenant.card>
        </x-tenant.aside-panel>
    </x-tenant.split>
</x-tenant.page>
