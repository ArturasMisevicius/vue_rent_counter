<div class="relative z-40" wire:show="isOpen">
    <div class="fixed inset-0 bg-slate-950/40 backdrop-blur-sm" wire:click="close"></div>

    <div class="fixed inset-x-0 top-20 mx-auto w-full max-w-3xl px-4">
        <div class="framework-panel overflow-hidden border border-white/70 p-4" x-transition.duration.250ms>
            <div class="flex items-center gap-3 border-b border-slate-200 pb-3">
                <span class="inline-flex size-10 items-center justify-center rounded-2xl bg-linear-45 from-framework-500 to-brand-mint text-sm font-semibold text-white">⌘</span>
                <div class="min-w-0 flex-1">
                    <label for="framework-command-query" class="sr-only">Search commands</label>
                    <input
                        id="framework-command-query"
                        type="text"
                        wire:model.live="query"
                        placeholder="Search commands..."
                        class="w-full border-none bg-transparent text-sm text-slate-900 outline-none placeholder:text-slate-400"
                    />
                </div>
                <button type="button" wire:click="close" class="rounded-xl px-3 py-2 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-slate-900">
                    Close
                </button>
            </div>

            <div class="mt-4 space-y-2">
                @forelse ($this->commands as $command)
                    <button
                        type="button"
                        wire:click="run('{{ $command['url'] }}')"
                        class="group flex w-full items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-left transition hover:border-framework-300 hover:bg-slate-50"
                    >
                        <div>
                            <p class="text-sm font-semibold text-slate-950">{{ $command['label'] }}</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">{{ $command['description'] }}</p>
                        </div>
                        <span class="rounded-full border border-slate-200 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.2em] text-slate-400 transition group-hover:border-framework-300 group-hover:text-framework-500">
                            Go
                        </span>
                    </button>
                @empty
                    <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-6 text-sm text-slate-500">
                        No command matched “{{ $query }}”.
                    </p>
                @endforelse
            </div>
        </div>
    </div>
</div>
