<div>
    <aside
        x-data="{}"
        x-cloak="-lg"
        x-bind:class="{ 'fi-sidebar-open': $store.sidebar.isOpen }"
        class="fi-sidebar fi-main-sidebar border-r border-white/10 bg-brand-ink text-white"
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

        <nav class="fi-sidebar-nav" data-shell-nav="sidebar">
            <div class="space-y-6 px-4 py-6">
                @foreach ($groups as $group)
                    @php($groupKey = \Illuminate\Support\Str::slug($group->getLabel() ?? 'group-'.$loop->index))

                    <section wire:key="sidebar-group-{{ $groupKey }}" data-shell-group="{{ $groupKey }}" class="space-y-3">
                        <p class="px-3 text-xs font-semibold uppercase tracking-[0.26em] text-white/45">
                            {{ $group->getLabel() }}
                        </p>

                        <div class="space-y-1">
                            @foreach ($group->getItems() as $item)
                                @php($itemRoute = $item->getExtraAttributes()['data-shell-route'] ?? $item->getUrl())

                                <a
                                    href="{{ $item->getUrl() }}"
                                    wire:key="sidebar-item-{{ $groupKey }}-{{ $itemRoute }}"
                                    wire:navigate
                                    @if ($item->isActive())
                                        aria-current="page"
                                        data-shell-current="{{ $itemRoute }}"
                                    @endif
                                    @class([
                                        'block rounded-2xl px-3 py-3 text-sm font-semibold transition',
                                        'bg-white/14 text-white shadow-[0_16px_40px_rgba(15,23,42,0.24)]' => $item->isActive(),
                                        'text-white/72 hover:bg-white/10 hover:text-white' => ! $item->isActive(),
                                    ])
                                >
                                    {{ $item->getLabel() }}
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </nav>
    </aside>

    <x-filament-actions::modals />
</div>
