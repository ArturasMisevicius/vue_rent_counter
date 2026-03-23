<div
    x-data="{
        dismiss() {
            $wire.dismissSearch();
            this.$nextTick(() => this.$refs.input?.focus());
        },
        focusFirstResult() {
            const [first] = this.resultItems();

            first?.focus();
        },
        focusRelativeResult(current, direction) {
            const items = this.resultItems();
            const currentIndex = items.indexOf(current);

            if (currentIndex === -1) {
                if (direction > 0) {
                    items[0]?.focus();
                } else {
                    this.$refs.input?.focus();
                }

                return;
            }

            const nextItem = items[currentIndex + direction];

            if (nextItem) {
                nextItem.focus();

                return;
            }

            if (direction < 0 && currentIndex === 0) {
                this.$refs.input?.focus();
            }
        },
        resultItems() {
            return Array.from(this.$el.querySelectorAll('[data-search-result=item]'));
        },
    }"
    class="relative w-full max-w-xl"
    data-shell-search="global"
>
    <label class="sr-only" for="global-search">{{ __('shell.search.label') }}</label>
    <span class="sr-only">{{ __('shell.search.placeholder') }}</span>

    <input
        id="global-search"
        type="search"
        x-ref="input"
        wire:model.live.debounce.{{ (int) config('tenanto.shell.search_debounce_ms', 300) }}ms="query"
        x-on:focus="$wire.openOverlay()"
        x-on:keydown.down.prevent="focusFirstResult()"
        x-on:keydown.escape.prevent="dismiss()"
        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 outline-none transition placeholder:text-slate-500 focus:border-brand-warm focus:bg-white"
        placeholder="{{ __('shell.search.placeholder') }}"
        autocomplete="off"
    />

    <div
        wire:show="open"
        wire:cloak
        wire:transition
        class="absolute left-0 right-0 top-full z-20 mt-2 rounded-[1.5rem] border border-slate-200 bg-white p-3 shadow-[0_20px_70px_rgba(15,23,42,0.16)]"
    >
        @if ($hasResults)
            <div class="space-y-4">
                @foreach ($results as $groupKey => $groupResults)
                    <section wire:key="search-group-{{ $groupKey }}" class="space-y-2">
                        <p class="px-1 text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ $groupLabels[$groupKey] ?? $groupKey }}</p>

                        @foreach ($groupResults as $result)
                            @if ($result['url'])
                                <a
                                    href="{{ $result['url'] }}"
                                    wire:key="search-result-{{ $groupKey }}-{{ md5(json_encode($result)) }}"
                                    wire:navigate
                                    data-search-result="item"
                                    x-on:keydown.down.prevent="focusRelativeResult($el, 1)"
                                    x-on:keydown.up.prevent="focusRelativeResult($el, -1)"
                                    x-on:keydown.escape.prevent="dismiss()"
                                    class="block rounded-[1.25rem] border border-slate-200 bg-slate-50/70 px-4 py-3 outline-none transition hover:border-slate-300 hover:bg-slate-50 focus:border-brand-warm focus:bg-white focus:ring-2 focus:ring-brand-warm/20"
                                >
                                    <p class="text-sm font-semibold text-slate-950">{{ $result['title'] }}</p>
                                    @if ($result['subtitle'])
                                        <p class="mt-1 text-sm text-slate-500">{{ $result['subtitle'] }}</p>
                                    @endif
                                </a>
                            @endif
                        @endforeach
                    </section>
                @endforeach
            </div>
        @else
            <div class="rounded-[1.25rem] border border-dashed border-slate-200 bg-slate-50/70 px-4 py-4">
                <p class="text-sm font-semibold text-slate-700">{{ __('shell.search.empty.heading') }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ __('shell.search.empty.body') }}</p>
            </div>
        @endif
    </div>
</div>
