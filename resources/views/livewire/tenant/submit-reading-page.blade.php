<x-tenant.page>
    <x-tenant.split>
        <x-tenant.main-panel>
            <div wire:show="successMessage" wire:transition>
                @if ($successMessage)
                    <x-shared.alert type="success" :message="$successMessage" dismissable />
                @endif
            </div>

            @if ($submittedReadings !== [])
                <div class="rounded-[1.25rem] border border-emerald-200/70 bg-white px-4 py-4 shadow-sm sm:px-5 sm:py-5" data-tenant-submitted-readings>
                    <div class="flex items-center gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700">
                            <x-heroicon-m-check-circle class="size-5" />
                        </span>
                        <p class="text-xs font-semibold uppercase tracking-normal text-emerald-700">
                            {{ __('tenant.pages.readings.submitted_heading') }}
                        </p>
                    </div>

                    <div class="mt-4 flex flex-col gap-3">
                        @foreach ($submittedReadings as $reading)
                            <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:items-end sm:justify-between" wire:key="submitted-reading-{{ $reading['meter_identifier'] }}">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-950">{{ $reading['meter_name'] }}</p>
                                    <p class="mt-1 text-sm text-slate-500">{{ $reading['meter_identifier'] }}</p>
                                </div>
                                <div class="text-left sm:text-right">
                                    <p class="font-display text-2xl tracking-tight text-slate-950 sm:text-3xl">{{ $reading['value'] }} {{ $reading['unit'] }}</p>
                                    <p class="text-sm text-slate-500">{{ $reading['date'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($meters->isEmpty())
                <x-shared.empty-state
                    icon="heroicon-m-beaker"
                    :title="__('tenant.pages.readings.title')"
                    :description="__('tenant.messages.no_meters_assigned')"
                />
            @else
                <x-shared.form-section icon="heroicon-m-clipboard-document-list" :title="__('tenant.pages.readings.title')" :description="__('tenant.pages.readings.batch_description')">
                    <form wire:submit="submit" class="space-y-5" data-tenant-reading-batch-form>
                        <div class="flex flex-col gap-4 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-4 py-4 lg:flex-row lg:items-end lg:justify-between">
                            <div class="min-w-0 flex-1 space-y-2">
                                <label for="readingDate" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.readings.reading_date') }}</label>
                                <input
                                    id="readingDate"
                                    type="date"
                                    wire:model.live="readingDate"
                                    class="block min-h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                                />
                                @error('readingDate')
                                    <p class="text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="submit"
                                class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl bg-brand-ink px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-900 disabled:cursor-wait disabled:opacity-70 lg:w-auto"
                            >
                                <x-heroicon-m-paper-airplane class="size-4" />
                                {{ __('tenant.pages.readings.submit_all') }}
                            </button>
                        </div>

                        @error('readings')
                            <p class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-col gap-4">
                            @foreach ($readingRows as $row)
                                @php
                                    $meterId = (string) $row['id'];
                                @endphp

                                <article
                                    wire:key="tenant-reading-row-{{ $meterId }}"
                                    class="rounded-[1.25rem] border border-slate-200 bg-white px-4 py-4 shadow-sm transition focus-within:border-brand-mint focus-within:ring-2 focus-within:ring-brand-mint/20 sm:px-5 sm:py-5"
                                    data-tenant-reading-row
                                    data-tenant-meter-id="{{ $meterId }}"
                                >
                                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                        <div class="min-w-0 flex-1 space-y-4">
                                            <div class="flex items-start gap-3">
                                                <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                                                    <x-heroicon-m-bolt class="size-5" />
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="font-display text-xl tracking-tight text-slate-950">{{ $row['name'] }}</p>
                                                    <p class="mt-1 text-sm text-slate-500">{{ $row['identifier'] }} · {{ $row['unit'] }}</p>
                                                </div>
                                            </div>

                                            <div class="flex flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:items-start sm:justify-between">
                                                <p class="flex min-w-0 items-start gap-2 text-sm leading-6 text-slate-600">
                                                    <x-heroicon-m-information-circle class="mt-0.5 size-5 shrink-0 text-slate-500" />
                                                    <span>{{ $row['previous_message'] }}</span>
                                                </p>

                                                @if ($row['delta'] !== null)
                                                    <div class="flex shrink-0 items-center gap-2 rounded-2xl bg-white px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm">
                                                        <x-heroicon-m-calculator class="size-4 text-slate-500" />
                                                        <span>{{ __('tenant.pages.readings.estimated_consumption') }}: {{ $row['delta'] }}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            @if ($row['warning'])
                                                <div class="flex items-start gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">
                                                    <x-heroicon-m-exclamation-triangle class="mt-0.5 size-5 shrink-0" />
                                                    <span>{{ $row['warning'] }}</span>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex w-full flex-col gap-3 xl:w-[24rem]">
                                            <div class="space-y-2">
                                                <label for="reading_{{ $meterId }}_value" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.readings.reading_value') }}</label>
                                                <input
                                                    id="reading_{{ $meterId }}_value"
                                                    type="number"
                                                    step="0.001"
                                                    min="0"
                                                    inputmode="decimal"
                                                    wire:model.live.debounce.300ms="readings.{{ $meterId }}.value"
                                                    class="block min-h-12 w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                                                    placeholder="{{ __('tenant.pages.readings.value_placeholder') }}"
                                                />
                                                @error("readings.{$meterId}.value")
                                                    <p class="text-sm text-rose-600">{{ $message }}</p>
                                                @enderror
                                            </div>

                                            <div class="space-y-2">
                                                <label for="reading_{{ $meterId }}_notes" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.readings.notes') }}</label>
                                                <textarea
                                                    id="reading_{{ $meterId }}_notes"
                                                    wire:model.live.debounce.300ms="readings.{{ $meterId }}.notes"
                                                    rows="2"
                                                    class="block min-h-24 w-full resize-y rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                                                    placeholder="{{ __('tenant.pages.readings.notes_placeholder') }}"
                                                ></textarea>
                                                @error("readings.{$meterId}.notes")
                                                    <p class="text-sm text-rose-600">{{ $message }}</p>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="sticky bottom-3 z-10 flex justify-end rounded-[1.25rem] border border-slate-200 bg-white/95 px-4 py-3 shadow-[0_18px_50px_rgba(15,23,42,0.18)] backdrop-blur">
                            <button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="submit"
                                class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl bg-brand-ink px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-900 disabled:cursor-wait disabled:opacity-70 sm:w-auto"
                            >
                                <x-heroicon-m-paper-airplane class="size-4" />
                                {{ __('tenant.pages.readings.submit_all') }}
                            </button>
                        </div>
                    </form>
                </x-shared.form-section>
            @endif
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

            <x-shared.form-section icon="heroicon-m-chart-bar" :title="__('tenant.pages.readings.preview_heading')">
                @if ($selectedMeter)
                    <div class="flex items-start gap-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 px-5 py-5">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-600 shadow-sm">
                            <x-heroicon-m-bolt class="size-5" />
                        </span>
                        <div>
                            <p class="font-semibold text-slate-950">{{ $selectedMeter->name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $selectedMeter->identifier }} · {{ $selectedMeter->unit }}</p>
                        </div>
                    </div>
                @endif

                @if ($consumption)
                    <div class="space-y-4 rounded-[1.25rem] border border-slate-200 bg-white px-5 py-5">
                        <p class="flex items-start gap-2 text-sm leading-6 text-slate-600">
                            <x-heroicon-m-information-circle class="mt-0.5 size-5 shrink-0 text-slate-500" />
                            <span>{{ $consumption['message'] }}</span>
                        </p>

                        @if ($consumption['warning'])
                            <div class="flex items-start gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">
                                <x-heroicon-m-exclamation-triangle class="mt-0.5 size-5 shrink-0" />
                                <span>{{ $consumption['warning'] }}</span>
                            </div>
                        @endif

                        @if ($consumption['delta'] !== null)
                            <div class="flex items-start gap-3">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                                    <x-heroicon-m-calculator class="size-5" />
                                </span>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.pages.readings.estimated_consumption') }}</p>
                                    <p class="mt-2 font-display text-3xl tracking-tight text-slate-950">{{ $consumption['delta'] }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <x-shared.empty-state
                        icon="heroicon-m-chart-bar"
                        :title="__('tenant.pages.readings.preview_heading')"
                        :description="__('tenant.pages.readings.preview_empty')"
                    />
                @endif
            </x-shared.form-section>
        </x-tenant.aside-panel>
    </x-tenant.split>
</x-tenant.page>
