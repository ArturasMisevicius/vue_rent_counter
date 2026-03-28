<div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
    <x-shared.page-header
        :title="__('tenant.pages.readings.heading')"
        :subtitle="__('tenant.pages.readings.description')"
        class="lg:col-span-2"
    />

    <section class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <div wire:show="successMessage" wire:transition>
            @if ($successMessage)
                <x-shared.alert type="success" :message="$successMessage" dismissable />
            @endif
        </div>

        <div wire:show="submittedReading" wire:transition>
            @if ($submittedReading)
                <div class="rounded-[1.75rem] border border-emerald-200/70 bg-white px-5 py-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">{{ __('tenant.pages.readings.submitted_heading') }}</p>
                    <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div class="space-y-1">
                            <p class="font-semibold text-slate-950">{{ $submittedReading['meter_name'] }}</p>
                            <p class="text-sm text-slate-500">{{ $submittedReading['meter_identifier'] }}</p>
                        </div>
                        <div class="text-left sm:text-right">
                            <p class="font-display text-3xl tracking-tight text-slate-950">{{ $submittedReading['value'] }} {{ $submittedReading['unit'] }}</p>
                            <p class="text-sm text-slate-500">{{ $submittedReading['date'] }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if ($submittedReading)
            <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5 text-sm leading-6 text-slate-600">
                {{ __('tenant.pages.readings.description') }}
            </div>
        @elseif ($meters->isEmpty())
            <x-shared.empty-state
                icon="heroicon-m-beaker"
                :title="__('tenant.pages.readings.title')"
                :description="__('tenant.messages.no_meters_assigned')"
            />
        @else
            <x-shared.form-section :title="__('tenant.pages.readings.title')" :description="__('tenant.pages.readings.description')">
                <form wire:submit="submit" class="space-y-5">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="space-y-2">
                            <label for="meterId" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.readings.meter') }}</label>
                            <select
                                id="meterId"
                                wire:model.live="meterId"
                                @disabled($meterSelectionLocked)
                                class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                            >
                                @unless ($meterSelectionLocked)
                                    <option value="">{{ __('tenant.pages.readings.meter_select') }}</option>
                                @endunless
                                @foreach ($meters as $meter)
                                    <option wire:key="meter-option-{{ $meter->id }}" value="{{ $meter->id }}">{{ $meter->name }} · {{ $meter->identifier }}</option>
                                @endforeach
                            </select>
                            @if ($meterSelectionLocked)
                                <p class="text-sm text-slate-500">{{ __('tenant.pages.readings.meter_locked') }}</p>
                            @endif
                            @error('meterId')
                                <p class="text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="readingDate" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.readings.reading_date') }}</label>
                            <input
                                id="readingDate"
                                type="date"
                                wire:model.live="readingDate"
                                class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                            />
                            @error('readingDate')
                                <p class="text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="readingValue" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.readings.reading_value') }}</label>
                        <input
                            id="readingValue"
                            type="number"
                            step="0.001"
                            min="0"
                            wire:model.live="readingValue"
                            class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                            placeholder="0.000"
                        />
                        @error('readingValue')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="notes" class="text-sm font-semibold text-slate-700">{{ __('tenant.pages.readings.notes') }}</label>
                        <textarea
                            id="notes"
                            wire:model.live="notes"
                            rows="4"
                            class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
                            placeholder="{{ __('tenant.pages.readings.notes_placeholder') }}"
                        ></textarea>
                        @error('notes')
                            <p class="text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="submit"
                        class="inline-flex items-center justify-center rounded-2xl bg-brand-ink px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-900"
                    >
                        {{ __('tenant.pages.readings.title') }}
                    </button>
                </form>
            </x-shared.form-section>
        @endif
    </section>

    <aside class="space-y-6 rounded-[2rem] border border-white/60 bg-white/92 p-8 shadow-[0_28px_90px_rgba(15,23,42,0.18)] backdrop-blur">
        <div class="rounded-[1.75rem] border border-slate-200 bg-slate-50 px-5 py-5">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.property.tenant_information') }}</p>
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

        <x-shared.form-section :title="__('tenant.pages.readings.preview_heading')">
            @if ($selectedMeter)
                <div class="rounded-[1.75rem] bg-slate-50 px-5 py-5">
                    <p class="font-semibold text-slate-950">{{ $selectedMeter->name }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ $selectedMeter->identifier }} · {{ $selectedMeter->unit }}</p>
                </div>
            @endif

            @if ($consumption)
                <div class="space-y-4 rounded-[1.75rem] border border-slate-200 bg-white px-5 py-5">
                    <p class="text-sm leading-6 text-slate-600">{{ $consumption['message'] }}</p>

                    @if ($consumption['warning'])
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">
                            {{ $consumption['warning'] }}
                        </div>
                    @endif

                    @if ($consumption['delta'] !== null)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-500">{{ __('tenant.pages.readings.estimated_consumption') }}</p>
                            <p class="mt-2 font-display text-3xl tracking-tight text-slate-950">{{ $consumption['delta'] }}</p>
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
    </aside>
</div>
