<x-filament-panels::page>
    <div class="space-y-6">
        <section class="border-b border-slate-200 pb-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl space-y-2">
                    <p class="text-sm font-semibold text-emerald-700">{{ __('help.pages.admin.eyebrow') }}</p>
                    <h2 class="text-3xl font-semibold tracking-tight text-slate-950">{{ __('help.pages.admin.heading') }}</h2>
                    <p class="text-sm leading-6 text-slate-600">{{ __('help.pages.admin.description') }}</p>
                </div>

                <label class="w-full max-w-md">
                    <span class="sr-only">{{ __('help.search.label') }}</span>
                    <input
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('help.search.placeholder') }}"
                        class="w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-950 shadow-sm outline-none transition focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"
                    />
                </label>
            </div>
        </section>

        <section class="flex flex-wrap gap-2">
            <a
                href="{{ route('filament.admin.pages.help') }}"
                wire:navigate
                class="rounded-lg border px-3 py-2 text-sm font-semibold transition {{ $category === null ? 'border-emerald-600 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300' }}"
            >
                {{ __('help.filters.all_categories') }}
            </a>

            @forelse ($this->categories as $option)
                <a
                    href="{{ route('filament.admin.pages.help', ['category' => $option['value']]) }}"
                    wire:navigate
                    class="rounded-lg border px-3 py-2 text-sm font-semibold transition {{ $category === $option['value'] ? 'border-emerald-600 bg-emerald-50 text-emerald-800' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300' }}"
                >
                    {{ $option['label'] }}
                    <span class="ml-1 text-xs text-slate-500">{{ $option['count'] }}</span>
                </a>
            @empty
                <span class="text-sm text-slate-500">{{ __('help.empty.no_categories') }}</span>
            @endforelse
        </section>

        @if ($pageKey !== null && $this->contextArticles !== [])
            <section class="space-y-3 rounded-lg border border-emerald-200 bg-emerald-50 p-5">
                <div class="flex items-center gap-2">
                    <x-heroicon-m-question-mark-circle class="size-5 text-emerald-700" />
                    <h3 class="text-base font-semibold text-emerald-950">{{ __('help.context.related_heading') }}</h3>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    @forelse ($this->contextArticles as $article)
                        <a
                            href="#help-{{ $article['slug'] }}"
                            class="rounded-lg border border-emerald-200 bg-white p-4 text-sm font-semibold text-emerald-800 hover:border-emerald-300"
                        >
                            {{ $article['title'] }}
                        </a>
                    @empty
                        <p class="text-sm text-emerald-800">{{ __('help.context.empty.description') }}</p>
                    @endforelse
                </div>
            </section>
        @endif

        <section class="grid gap-4 xl:grid-cols-2">
            @forelse ($this->articles as $article)
                <article id="help-{{ $article['slug'] }}" class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <h3 class="text-lg font-semibold text-slate-950">{{ $article['title'] }}</h3>
                        <span class="inline-flex w-fit rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                            {{ $article['category_label'] }}
                        </span>
                    </div>
                    <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $article['body'] }}</p>
                </article>
            @empty
                <x-shared.empty-state
                    icon="heroicon-m-book-open"
                    :title="__('help.empty.no_results_heading')"
                    :description="__('help.empty.no_results_description')"
                />
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
