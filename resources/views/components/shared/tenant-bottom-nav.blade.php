@php
    $user = auth()->user();
@endphp

@if ($user?->isTenant())
    @php
        $items = [
            [
                'key' => 'home',
                'label' => __('tenant.navigation.home'),
                'icon' => 'heroicon-m-home',
                'route' => 'filament.admin.pages.tenant-dashboard',
                'patterns' => [
                    'filament.admin.pages.tenant-dashboard',
                    'filament.admin.pages.tenant-property-details',
                    'tenant.home',
                    'tenant.property.show',
                ],
            ],
            [
                'key' => 'readings',
                'label' => __('tenant.navigation.readings'),
                'icon' => 'heroicon-m-clipboard-document-list',
                'route' => 'filament.admin.pages.tenant-submit-meter-reading',
                'patterns' => [
                    'filament.admin.pages.tenant-submit-meter-reading',
                    'tenant.readings.create',
                ],
            ],
            [
                'key' => 'invoices',
                'label' => __('tenant.navigation.invoices'),
                'icon' => 'heroicon-m-document-text',
                'route' => 'filament.admin.pages.tenant-invoice-history',
                'patterns' => [
                    'filament.admin.pages.tenant-invoice-history',
                    'tenant.invoices.index',
                ],
            ],
            [
                'key' => 'profile',
                'label' => __('shell.navigation.items.profile'),
                'icon' => 'heroicon-m-user-circle',
                'route' => 'filament.admin.pages.profile',
                'patterns' => [
                    'filament.admin.pages.profile',
                    'tenant.profile.edit',
                    'profile.edit',
                ],
            ],
        ];
    @endphp

    <nav
        data-tenant-bottom-nav
        aria-label="{{ __('shell.navigation.groups.my_home') }}"
        class="fixed inset-x-0 bottom-4 z-30 px-4 lg:hidden"
    >
        <div class="mx-auto max-w-md rounded-[2rem] border border-white/70 bg-slate-950/90 p-2 text-white shadow-[0_24px_80px_rgba(15,23,42,0.32)] backdrop-blur-xl">
            <div class="grid grid-cols-4 gap-2">
                @foreach ($items as $item)
                    @php($isActive = request()->routeIs(...$item['patterns']))

                    <a
                        data-tenant-bottom-link="{{ $item['key'] }}"
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        @class([
                            'flex min-h-18 flex-col items-center justify-center gap-2 rounded-[1.4rem] px-2 py-3 text-center transition',
                            'bg-brand-mint text-slate-950 shadow-[0_18px_34px_rgba(62,197,173,0.24)]' => $isActive,
                            'text-white/74 hover:bg-white/8 hover:text-white' => ! $isActive,
                        ])
                    >
                        <x-dynamic-component :component="$item['icon']" class="{{ $isActive ? 'size-5' : 'size-5 opacity-90' }}" />
                        <span class="text-[0.68rem] font-semibold uppercase tracking-[0.18em]">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </nav>
@endif
