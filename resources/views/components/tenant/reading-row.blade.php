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
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1.1fr)_minmax(18rem,0.9fr)_minmax(12rem,0.7fr)] lg:items-start">
        <div class="flex min-w-0 items-start gap-3">
            <span class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-slate-100 text-slate-700">
                <x-heroicon-m-bolt class="size-5" />
            </span>
            <div class="min-w-0">
                <p class="font-display text-xl tracking-tight text-slate-950">{{ $row['name'] }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $row['identifier'] }} · {{ $row['unit'] }}</p>
            </div>
        </div>

        <div class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
            <p class="flex min-w-0 items-start gap-2 text-sm leading-6 text-slate-600">
                <x-heroicon-m-information-circle class="mt-0.5 size-5 shrink-0 text-slate-500" />
                <span>{{ $row['previous_message'] }}</span>
            </p>

            @if ($row['warning'])
                <div class="flex items-start gap-2 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">
                    <x-heroicon-m-exclamation-triangle class="mt-0.5 size-5 shrink-0" />
                    <span>{{ $row['warning'] }}</span>
                </div>
            @endif
        </div>

        <div class="flex w-full flex-col gap-3">
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

        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
            @if ($row['delta'] !== null)
                <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">{{ __('tenant.pages.readings.estimated_consumption') }}</p>
                <p class="mt-2 font-display text-2xl tracking-tight text-slate-950">{{ $row['delta'] }} {{ $row['unit'] }}</p>
            @else
                <p class="text-sm leading-6 text-slate-500">{{ __('tenant.pages.readings.preview_empty_short') }}</p>
            @endif
        </div>
    </div>
</article>
