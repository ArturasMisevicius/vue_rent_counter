<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-white/70 bg-white/92 backdrop-blur-xl">
    <div class="mx-auto flex max-w-4xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
        @foreach ($items as $item)
            <a
                href="{{ $item->url }}"
                data-navigation-route="{{ $item->routeName }}"
                data-current-page="{{ $item->isActive ? 'true' : 'false' }}"
                data-navigation-state="{{ $item->routeName }}:{{ $item->isActive ? 'true' : 'false' }}"
                @class([
                    'flex min-w-0 flex-1 items-center justify-center rounded-full px-4 py-2 text-sm font-semibold transition',
                    'bg-brand-ink text-white shadow-sm' => $item->isActive,
                    'text-slate-500 hover:bg-white hover:text-slate-950' => ! $item->isActive,
                ])
            >
                {{ $item->label }}
            </a>
        @endforeach
    </div>
</nav>
