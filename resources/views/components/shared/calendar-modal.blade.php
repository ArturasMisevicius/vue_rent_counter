@props([
    'id',
    'label',
    'value' => '',
    'displayValue' => null,
    'mode' => 'date',
    'min' => null,
    'max' => null,
    'includeSeconds' => false,
    'minuteStep' => 1,
])

@php
    use App\Filament\Support\Formatting\LocalizedDateFormatter;

    $locale = app()->getLocale();
    $weekStartsOn = in_array($locale, ['lt', 'ru', 'es'], true) ? 1 : 0;
    $hasTime = $mode === 'datetime';
    $selectedDisplay = $displayValue
        ?? (filled($value)
            ? ($hasTime ? LocalizedDateFormatter::dateTime($value) : LocalizedDateFormatter::date($value))
            : __('calendar.no_date_selected'));
    $wireModelAttributes = $attributes->whereStartsWith('wire:model');
    $rootAttributes = $attributes->whereDoesntStartWith('wire:model');
@endphp

<div
    data-calendar-picker
    data-locale="{{ $locale }}"
    data-week-starts-on="{{ $weekStartsOn }}"
    data-mode="{{ $hasTime ? 'datetime' : 'date' }}"
    data-include-seconds="{{ $includeSeconds ? 'true' : 'false' }}"
    data-min-date="{{ $min }}"
    data-max-date="{{ $max }}"
    data-empty-label="{{ __('calendar.no_date_selected') }}"
    data-select-date-label="{{ __('calendar.select_date') }}"
    {{ $rootAttributes->class(['space-y-2']) }}
>
    <label for="{{ $id }}" class="text-sm font-semibold text-slate-700">{{ $label }}</label>

    <input
        id="{{ $id }}"
        type="hidden"
        value="{{ $value }}"
        data-calendar-input
        {{ $wireModelAttributes }}
    />

    <button
        type="button"
        data-calendar-trigger
        aria-haspopup="dialog"
        aria-expanded="false"
        class="flex min-h-12 w-full items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left text-sm text-slate-900 shadow-sm transition hover:border-brand-mint focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30"
    >
        <span class="flex min-w-0 items-center gap-3">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                <x-heroicon-m-calendar-days class="size-5" />
            </span>
            <span class="min-w-0">
                <span data-calendar-display class="block truncate font-semibold">{{ $selectedDisplay }}</span>
                <span class="mt-0.5 block text-xs text-slate-500">{{ __('calendar.open') }}</span>
            </span>
        </span>
        <x-heroicon-m-chevron-down class="size-5 shrink-0 text-slate-400" />
    </button>

    <dialog
        data-calendar-dialog
        aria-labelledby="{{ $id }}_calendar_title"
        class="fixed inset-0 m-auto max-h-[calc(100dvh-2rem)] w-[calc(100vw-2rem)] max-w-md overflow-y-auto rounded-[1.75rem] border border-white/70 bg-white p-0 text-slate-950 shadow-[0_34px_110px_rgba(15,23,42,0.26)] backdrop:bg-slate-950/55 backdrop:backdrop-blur-sm"
    >
        <div class="flex flex-col gap-4 p-4 sm:p-5">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-normal text-brand-warm">{{ $label }}</p>
                    <h2 id="{{ $id }}_calendar_title" class="mt-1 font-display text-2xl tracking-tight text-slate-950">
                        {{ $hasTime ? __('calendar.choose_datetime') : __('calendar.choose_date') }}
                    </h2>
                </div>

                <button
                    type="button"
                    data-calendar-close
                    class="inline-flex size-11 shrink-0 items-center justify-center rounded-2xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
                >
                    <x-heroicon-m-x-mark class="size-5" />
                    <span class="sr-only">{{ __('calendar.close') }}</span>
                </button>
            </div>

            <div class="rounded-[1.25rem] border border-slate-200 bg-slate-50 p-3">
                <div class="flex items-center justify-between gap-3">
                    <button
                        type="button"
                        data-calendar-previous
                        class="inline-flex size-11 items-center justify-center rounded-2xl bg-white text-slate-600 shadow-sm transition hover:text-slate-950 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <x-heroicon-m-chevron-left class="size-5" />
                        <span class="sr-only">{{ __('calendar.previous_month') }}</span>
                    </button>

                    <p data-calendar-month class="text-center font-display text-xl tracking-tight text-slate-950"></p>

                    <button
                        type="button"
                        data-calendar-next
                        class="inline-flex size-11 items-center justify-center rounded-2xl bg-white text-slate-600 shadow-sm transition hover:text-slate-950 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        <x-heroicon-m-chevron-right class="size-5" />
                        <span class="sr-only">{{ __('calendar.next_month') }}</span>
                    </button>
                </div>

                <div data-calendar-weekdays class="mt-4 grid grid-cols-7 gap-1"></div>
                <div data-calendar-days class="mt-2 grid grid-cols-7 gap-1"></div>
            </div>

            @if ($hasTime)
                <div class="rounded-[1.25rem] border border-slate-200 bg-white px-4 py-4">
                    <p class="text-sm font-semibold text-slate-950">{{ __('calendar.time') }}</p>
                    <div class="mt-3 flex items-end gap-3">
                        <label for="{{ $id }}_hour" class="min-w-0 flex-1 space-y-1 text-sm font-medium text-slate-700">
                            <span>{{ __('calendar.hour') }}</span>
                            <input id="{{ $id }}_hour" data-calendar-hour type="number" min="0" max="23" step="1" inputmode="numeric" class="min-h-11 w-full rounded-2xl border border-slate-200 px-3 text-sm text-slate-900 focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30">
                        </label>

                        <label for="{{ $id }}_minute" class="min-w-0 flex-1 space-y-1 text-sm font-medium text-slate-700">
                            <span>{{ __('calendar.minute') }}</span>
                            <input id="{{ $id }}_minute" data-calendar-minute type="number" min="0" max="59" step="{{ $minuteStep }}" inputmode="numeric" class="min-h-11 w-full rounded-2xl border border-slate-200 px-3 text-sm text-slate-900 focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30">
                        </label>

                        @if ($includeSeconds)
                            <label for="{{ $id }}_second" class="min-w-0 flex-1 space-y-1 text-sm font-medium text-slate-700">
                                <span>{{ __('calendar.second') }}</span>
                                <input id="{{ $id }}_second" data-calendar-second type="number" min="0" max="59" step="1" inputmode="numeric" class="min-h-11 w-full rounded-2xl border border-slate-200 px-3 text-sm text-slate-900 focus:border-brand-mint focus:outline-none focus:ring-2 focus:ring-brand-mint/30">
                            </label>
                        @endif
                    </div>
                </div>
            @endif

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="min-w-0 text-sm text-slate-600">
                    <span class="font-semibold text-slate-950">{{ __('calendar.selected_date') }}:</span>
                    <span data-calendar-selected>{{ $selectedDisplay }}</span>
                </p>

                <div class="flex gap-2">
                    <button
                        type="button"
                        data-calendar-today
                        class="inline-flex min-h-11 items-center justify-center rounded-2xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        {{ __('calendar.today') }}
                    </button>

                    <button
                        type="button"
                        data-calendar-close
                        class="inline-flex min-h-11 items-center justify-center rounded-2xl bg-brand-ink px-4 text-sm font-semibold text-white transition hover:bg-slate-900"
                    >
                        {{ __('calendar.done') }}
                    </button>
                </div>
            </div>
        </div>
    </dialog>
</div>
