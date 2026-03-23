<div class="relative z-50" wire:show="isOpen">
    <div class="fixed inset-0 bg-slate-950/50 backdrop-blur-sm" wire:click="close"></div>

    <div class="fixed inset-x-0 top-24 mx-auto w-full max-w-2xl px-4">
        <div {{ $attributes->class(['framework-panel border border-white/70 p-6']) }} wire:transition>
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-framework-500">Directory multi-file component</p>
                    <h3 class="text-2xl font-semibold tracking-tight text-slate-950">{{ $title }}</h3>
                    <p class="text-sm leading-6 text-slate-600">
                        This modal is powered by a folder-based Livewire component under <code>resources/views/components/framework</code>.
                    </p>
                </div>

                <button type="button" wire:click="close" class="rounded-xl px-3 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                    Close
                </button>
            </div>

            <div class="mt-5 space-y-3">
                @foreach ($this->highlights as $highlight)
                    <div class="rounded-2xl border border-slate-200 bg-white/80 px-4 py-3 text-sm leading-6 text-slate-700">
                        {{ $highlight }}
                    </div>
                @endforeach
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    wire:click="confirm"
                    class="rounded-2xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Confirm preview
                </button>
                <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400">
                    Uses <code>wire:show</code>, computed state, and targeted events.
                </p>
            </div>
        </div>
    </div>
</div>
