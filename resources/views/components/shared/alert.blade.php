@props([
    'type' => 'info',
    'message',
    'dismissable' => false,
])

@php
    $styles = [
        'info' => 'border-sky-200 bg-sky-50 text-sky-900',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-900',
        'danger' => 'border-rose-200 bg-rose-50 text-rose-900',
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
    ];

    $style = $styles[$type] ?? $styles['info'];
@endphp

<div
    x-data="{ open: true }"
    x-show="open"
    class="rounded-[1.75rem] border px-5 py-4 text-sm shadow-sm {{ $style }}"
>
    <div class="flex items-start justify-between gap-4">
        <p class="leading-6">{{ $message }}</p>

        @if ($dismissable)
            <button
                type="button"
                x-on:click="open = false"
                class="inline-flex size-8 shrink-0 items-center justify-center rounded-full border border-current/10 text-current transition hover:bg-white/40"
            >
                <span class="sr-only">{{ __('filament-actions::view.single.modal.actions.close.label') }}</span>
                <span aria-hidden="true" class="text-lg leading-none">&times;</span>
            </button>
        @endif
    </div>
</div>
