<div>
    <aside
        x-data="{}"
        x-cloak
        x-bind:class="{ 'fi-sidebar-open': $store.sidebar.isOpen }"
        class="fi-sidebar fi-main-sidebar"
    >
        <div class="fi-sidebar-header-ctn">
            <header class="fi-sidebar-header border-b border-slate-200/70 bg-white/92 px-4 py-4 backdrop-blur-xl">
                <div class="w-full">
                    <x-shell.brand :href="filament()->getHomeUrl()" />
                </div>
            </header>
        </div>

        <nav class="fi-sidebar-nav bg-white/88 px-4 py-6 backdrop-blur-xl">
            <ul class="space-y-8">
                @foreach ($groups as $group)
                    <li class="space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">{{ $group->label }}</p>

                        <ul class="space-y-2">
                            @foreach ($group->items as $item)
                                <li>
                                    <a
                                        href="{{ $item->url }}"
                                        data-navigation-route="{{ $item->routeName }}"
                                        data-current-page="{{ $item->isActive ? 'true' : 'false' }}"
                                        data-navigation-state="{{ $item->routeName }}:{{ $item->isActive ? 'true' : 'false' }}"
                                        @class([
                                            'flex items-center rounded-2xl px-4 py-3 text-sm font-semibold transition',
                                            'bg-brand-ink text-white shadow-sm' => $item->isActive,
                                            'text-slate-600 hover:bg-slate-50 hover:text-slate-950' => ! $item->isActive,
                                        ])
                                    >
                                        {{ $item->label }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        </nav>

        <div class="fi-sidebar-footer border-t border-slate-200/70 bg-white/92 p-4 backdrop-blur-xl">
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="flex w-full items-center justify-center rounded-2xl border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                >
                    {{ __('shell.log_out') }}
                </button>
            </form>
        </div>
    </aside>

    <x-filament-actions::modals />
</div>
