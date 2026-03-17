@php
    $searchDebounce = (int) config('tenanto.search.debounce', 300);
@endphp

<div
    class="min-w-0"
    x-data="{ open: $wire.entangle('isOpen') }"
    x-on:shell-search-close.window="$wire.closeSearch()"
>
    <button
        type="button"
        wire:click="openSearch"
        class="hidden min-w-0 flex-1 items-center gap-3 rounded-full border border-slate-200 bg-slate-50/90 px-4 py-3 text-left text-sm text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-white md:flex"
    >
        <span class="inline-flex size-8 items-center justify-center rounded-full bg-white text-slate-400 ring-1 ring-slate-200">
            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.5 14.5-3.5-3.5m1.75-4.25a5 5 0 1 1-10 0 5 5 0 0 1 10 0Z" />
            </svg>
        </span>
        <span>{{ __('shell.search_placeholder') }}</span>
    </button>

    <button
        type="button"
        wire:click="openSearch"
        class="inline-flex size-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm md:hidden"
        aria-label="{{ __('shell.search_placeholder') }}"
    >
        <svg class="size-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m14.5 14.5-3.5-3.5m1.75-4.25a5 5 0 1 1-10 0 5 5 0 0 1 10 0Z" />
        </svg>
    </button>

    @if ($isOpen)
        <section class="absolute left-1/2 top-[calc(100%+0.75rem)] z-50 w-[min(42rem,calc(100vw-2rem))] -translate-x-1/2 rounded-[1.9rem] border border-slate-200 bg-white p-4 shadow-2xl shadow-slate-950/10">
            <div class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 px-4 py-3">
                <input
                    type="search"
                    wire:model.live.debounce.{{ $searchDebounce }}ms="query"
                    class="w-full border-none bg-transparent p-0 text-sm text-slate-950 outline-none placeholder:text-slate-400 focus:ring-0"
                    placeholder="{{ __('shell.search_placeholder') }}"
                />
            </div>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @foreach ($this->groupLabels as $groupKey => $groupLabel)
                    @php
                        $groupResults = data_get($this->groupedResults, $groupKey, []);
                    @endphp

                    <section class="rounded-[1.5rem] border border-slate-200 bg-slate-50/70 p-4">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ $groupLabel }}</h3>
                            <span class="text-xs text-slate-400">{{ count($groupResults) }}</span>
                        </div>

                        <div class="mt-3 space-y-2">
                            @forelse ($groupResults as $result)
                                <a
                                    href="{{ $result->url }}"
                                    class="block rounded-[1.25rem] border border-white bg-white px-4 py-3 transition hover:border-slate-200 hover:bg-slate-50"
                                >
                                    <p class="text-sm font-semibold text-slate-950">{{ $result->label }}</p>
                                    <p class="mt-1 text-sm text-slate-600">{{ $result->detail }}</p>
                                    <p class="mt-2 text-[0.7rem] font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $result->typeLabel }}</p>
                                </a>
                            @empty
                                <div class="rounded-[1.25rem] border border-dashed border-slate-200 bg-white/70 px-4 py-4 text-sm text-slate-500">
                                    {{ filled($query) ? __('shell.search_empty_group') : __('shell.search_prompt') }}
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        </section>
    @endif
</div>
