<div class="relative w-full max-w-xl" data-shell-search="global">
    <label class="sr-only" for="global-search">{{ __('shell.search.label') }}</label>
    <span class="sr-only">{{ __('shell.search.placeholder') }}</span>

    <input
        id="global-search"
        type="search"
        wire:model.live.debounce.{{ (int) config('tenanto.shell.search_debounce_ms', 300) }}ms="query"
        x-data="{}"
        x-on:focus="$wire.openOverlay()"
        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-500 focus:border-brand-warm focus:bg-white"
        placeholder="{{ __('shell.search.placeholder') }}"
        autocomplete="off"
    />

    @if ($open)
        <div class="absolute left-0 right-0 top-full z-20 mt-2 rounded-[1.5rem] border border-slate-200 bg-white p-3 shadow-[0_20px_70px_rgba(15,23,42,0.16)]">
            <div class="space-y-4">
                @foreach ($groupLabels as $groupKey => $groupLabel)
                    <section class="space-y-2">
                        <p class="px-1 text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ $groupLabel }}</p>

                        @forelse ($results[$groupKey] ?? [] as $result)
                            @if ($result['url'])
                                <a
                                    href="{{ $result['url'] }}"
                                    wire:navigate
                                    class="block rounded-[1.25rem] border border-slate-200 bg-slate-50/70 px-4 py-3 transition hover:border-slate-300 hover:bg-slate-50"
                                >
                                    <p class="text-sm font-semibold text-slate-950">{{ $result['title'] }}</p>
                                    @if ($result['subtitle'])
                                        <p class="mt-1 text-sm text-slate-500">{{ $result['subtitle'] }}</p>
                                    @endif
                                </a>
                            @endif
                        @empty
                            <div class="rounded-[1.25rem] border border-dashed border-slate-200 bg-slate-50/70 px-4 py-4">
                                <p class="text-sm font-semibold text-slate-700">{{ __('shell.search.empty.heading') }}</p>
                                <p class="mt-1 text-sm text-slate-500">{{ __('shell.search.empty.body') }}</p>
                            </div>
                        @endforelse
                    </section>
                @endforeach
            </div>
        </div>
    @endif
</div>
