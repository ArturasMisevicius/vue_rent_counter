<x-tenant.page>
    <x-tenant.split>
        <x-tenant.main-panel>
            <div wire:show="successMessage" wire:transition>
                @if ($successMessage)
                    <x-shared.alert type="success" :message="$successMessage" dismissable />
                @endif
            </div>

            @if ($submittedReadings !== [])
                <x-tenant.card tone="success" data-tenant-submitted-readings>
                    <x-tenant.section-heading
                        icon="heroicon-m-check-circle"
                        icon-tone="success"
                        :eyebrow="__('tenant.pages.readings.submitted_heading')"
                        :title="__('tenant.pages.readings.submitted_heading')"
                        class="[&_h2]:sr-only"
                    />

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
                </x-tenant.card>
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
                        <x-tenant.card class="flex flex-col gap-4 px-4 py-4 lg:flex-row lg:items-end lg:justify-between">
                            <div class="min-w-0 flex-1 space-y-2">
                                <x-shared.calendar-modal
                                    id="readingDate"
                                    :label="__('tenant.pages.readings.reading_date')"
                                    :value="$readingDate"
                                    :display-value="$readingDateDisplay"
                                    :max="now()->toDateString()"
                                    wire:model.live="readingDate"
                                />
                                @foreach ($errors->get('readingDate') as $message)
                                    <x-tenant.field-error :message="$message" />
                                @endforeach
                            </div>

                            <x-tenant.action
                                type="submit"
                                variant="primary"
                                icon="heroicon-m-paper-airplane"
                                wire:loading.attr="disabled"
                                wire:target="submit"
                                class="min-h-12 w-full px-5 py-3 lg:w-auto"
                            >
                                {{ __('tenant.pages.readings.submit_all') }}
                            </x-tenant.action>
                        </x-tenant.card>

                        @foreach ($errors->get('readings') as $message)
                            <p class="flex items-start gap-2 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700">
                                <x-heroicon-m-exclamation-circle class="mt-0.5 size-4 shrink-0" />
                                <span>{{ $message }}</span>
                            </p>
                        @endforeach

                        <div class="flex flex-col gap-4">
                            @foreach ($readingRows as $row)
                                @php
                                    $meterId = (string) $row['id'];
                                @endphp

                                <x-tenant.reading-row
                                    :meter-id="$meterId"
                                    :row="$row"
                                    wire:key="tenant-reading-row-{{ $meterId }}"
                                />
                            @endforeach
                        </div>

                        <x-tenant.card tone="white" class="sticky bottom-3 z-10 flex justify-end bg-white/95 px-4 py-3 shadow-[0_18px_50px_rgba(15,23,42,0.18)] backdrop-blur">
                            <x-tenant.action
                                type="submit"
                                variant="primary"
                                icon="heroicon-m-paper-airplane"
                                wire:loading.attr="disabled"
                                wire:target="submit"
                                class="min-h-12 w-full px-5 py-3 sm:w-auto"
                            >
                                {{ __('tenant.pages.readings.submit_all') }}
                            </x-tenant.action>
                        </x-tenant.card>
                    </form>
                </x-shared.form-section>
            @endif
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

            <x-shared.form-section icon="heroicon-m-chart-bar" :title="__('tenant.pages.readings.preview_heading')">
                @if ($selectedMeter)
                    <x-tenant.card class="flex items-start gap-3">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-white text-slate-600 shadow-sm">
                            <x-heroicon-m-bolt class="size-5" />
                        </span>
                        <div>
                            <p class="font-semibold text-slate-950">{{ $selectedMeterName }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $selectedMeter->identifier }} · {{ $selectedMeter->unit }}</p>
                        </div>
                    </x-tenant.card>
                @endif

                @if ($consumption)
                    <x-tenant.card tone="white" class="space-y-4">
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
                    </x-tenant.card>
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
