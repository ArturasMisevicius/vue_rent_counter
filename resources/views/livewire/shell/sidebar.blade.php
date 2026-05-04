<div class="h-full">
    @if ($isTenant)
        <div data-shell-nav="tenant-sidebar-disabled" class="hidden"></div>

        <x-filament-actions::modals />
    @else
    <aside
        x-data="{}"
        x-cloak="-lg"
        x-bind:class="{ 'fi-sidebar-open': $store.sidebar.isOpen }"
        class="fi-sidebar fi-main-sidebar flex h-full flex-col border-r border-white/10 bg-brand-ink text-white"
    >
        <div class="fi-sidebar-header-ctn border-b border-white/10">
            <header class="fi-sidebar-header">
                <div x-show="$store.sidebar.isOpen" class="fi-sidebar-header-logo-ctn">
                    <a href="{{ $dashboardUrl }}" wire:navigate>
                        <x-shell.brand light />
                    </a>
                </div>
            </header>
        </div>

        <nav class="fi-sidebar-nav flex-1 overflow-y-auto" data-shell-nav="sidebar">
            <div class="space-y-6 px-4 py-6">
                @foreach ($groups as $group)
                    @php($groupKey = $group->key)

                    <section wire:key="sidebar-group-{{ $groupKey }}" data-shell-group="{{ $groupKey }}" class="space-y-3">
                        <p class="px-3 text-xs font-semibold uppercase tracking-[0.26em] text-white/45">
                            {{ $group->label }}
                        </p>

                        <div class="space-y-1">
                            @foreach ($group->items as $item)
                                @php($itemRoute = $item->routeName)

                                <a
                                    href="{{ $item->url }}"
                                    wire:key="sidebar-item-{{ $groupKey }}-{{ $itemRoute }}"
                                    wire:navigate
                                    @if ($item->active)
                                        aria-current="page"
                                        data-shell-current="{{ $itemRoute }}"
                                    @endif
                                    @class([
                                        'block rounded-2xl px-3 py-3 text-sm font-semibold transition',
                                        'bg-white/14 text-white shadow-[0_16px_40px_rgba(15,23,42,0.24)]' => $item->active,
                                        'text-white/72 hover:bg-white/10 hover:text-white' => ! $item->active,
                                    ])
                                >
                                    {{ $item->label }}
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </nav>

        <div class="border-t border-white/10 px-4 py-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-center rounded-2xl border border-white/15 bg-white/8 px-4 py-3 text-sm font-semibold text-white transition hover:bg-white/12"
                >
                    {{ __('dashboard.logout_button') }}
                </button>
            </form>
        </div>
    </aside>

    <x-filament-actions::modals />
    @endif
</div>
