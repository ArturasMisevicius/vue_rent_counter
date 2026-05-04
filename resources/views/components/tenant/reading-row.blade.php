@props([
    'meterId',
    'row',
])

<article
    {{ $attributes
        ->merge([
            'data-tenant-reading-row' => true,
            'data-tenant-meter-id' => $meterId,
        ])
        ->class('rounded-3xl border border-slate-200 bg-white px-4 py-4 shadow-sm transition focus-within:border-brand-mint focus-within:ring-2 focus-within:ring-brand-mint/20 sm:px-5 sm:py-5') }}
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
            <x-tenant.text-field
                id="reading_{{ $meterId }}_value"
                type="number"
                step="0.001"
                min="0.001"
                inputmode="decimal"
                wire:model.live.debounce.300ms="readings.{{ $meterId }}.value"
                :label="__('tenant.pages.readings.reading_value')"
                :errors="$errors->get('readings.'.$meterId.'.value')"
                placeholder="{{ __('tenant.pages.readings.value_placeholder') }}"
            />

            <x-tenant.text-field
                id="reading_{{ $meterId }}_notes"
                as="textarea"
                wire:model.live.debounce.300ms="readings.{{ $meterId }}.notes"
                rows="2"
                :label="__('tenant.pages.readings.notes')"
                :errors="$errors->get('readings.'.$meterId.'.notes')"
                placeholder="{{ __('tenant.pages.readings.notes_placeholder') }}"
            />
        </div>
    </div>
</article>
