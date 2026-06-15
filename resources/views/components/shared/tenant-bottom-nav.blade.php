@if ($user?->isTenant())
    <nav
        data-tenant-bottom-nav
        aria-label="{{ __('shell.navigation.groups.my_home') }}"
        class="fixed inset-x-0 bottom-[calc(env(safe-area-inset-bottom)+0.75rem)] z-30 px-3 sm:px-4 lg:hidden"
    >
        <div class="mx-auto max-w-2xl rounded-[1.5rem] border border-white/70 bg-slate-950/92 p-2 text-white shadow-[0_24px_80px_rgba(15,23,42,0.32)] backdrop-blur-xl">
            <div class="flex items-stretch justify-between gap-1.5">
                @foreach ($items as $item)
                    <a
                        data-tenant-bottom-link="{{ $item['key'] }}"
                        href="{{ route($item['route']) }}"
                        wire:navigate
                        @class([
                            'flex min-h-[4.75rem] min-w-0 flex-1 touch-manipulation flex-col items-center justify-center gap-2 rounded-[1.1rem] px-1.5 py-2.5 text-center transition sm:min-h-20 sm:px-2',
                            'bg-brand-mint text-slate-950 shadow-[0_18px_34px_rgba(62,197,173,0.24)]' => $item['is_active'],
                            'text-white/75 hover:bg-white/10 hover:text-white' => ! $item['is_active'],
                        ])
                    >
                        <x-dynamic-component :component="$item['icon']" class="{{ $item['is_active'] ? 'size-6' : 'size-6 opacity-90' }}" />
                        <span class="max-w-full truncate text-[0.68rem] font-semibold uppercase leading-tight tracking-normal sm:text-xs">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </nav>
@endif
