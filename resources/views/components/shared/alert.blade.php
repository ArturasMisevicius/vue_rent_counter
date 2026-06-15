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
