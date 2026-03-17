@props([
    'id',
    'title',
])

<div
    x-data="{ open: false, modalId: @js($id) }"
    x-on:open-shared-modal.window="if ($event.detail?.id === modalId) open = true"
    x-on:close-shared-modal.window="if ($event.detail?.id === modalId) open = false"
    x-on:keydown.escape.window="open = false"
    x-cloak
>
    <div
        x-show="open"
        class="fixed inset-0 z-40 bg-slate-950/55 backdrop-blur-sm"
        x-on:click="open = false"
    ></div>

    <div
        x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $id }}-title"
    >
        <div
            x-on:click.stop
            class="w-full max-w-3xl rounded-[2rem] border border-white/60 bg-white p-6 shadow-[0_40px_120px_rgba(15,23,42,0.24)]"
        >
            <div class="flex items-start justify-between gap-4 border-b border-slate-200/80 pb-4">
                <h2 id="{{ $id }}-title" class="font-display text-2xl tracking-tight text-slate-950">{{ $title }}</h2>

                <button
                    type="button"
                    x-on:click="open = false"
                    class="inline-flex size-10 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:bg-slate-50 hover:text-slate-950"
                >
                    <span class="sr-only">{{ __('filament-actions::view.single.modal.actions.close.label') }}</span>
                    <span aria-hidden="true" class="text-lg leading-none">&times;</span>
                </button>
            </div>

            <div class="mt-6">
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
