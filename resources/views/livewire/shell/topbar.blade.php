@php
    $isPageContext = $context === 'page';
@endphp

<div class="fi-topbar-ctn" data-shell-topbar="true">
    @livewire(\App\Livewire\Shell\ImpersonationBanner::class)

    @if ($isPageContext)
        <header class="mb-6 rounded-[1.75rem] border border-white/60 bg-white/90 px-5 py-4 shadow-[0_20px_70px_rgba(15,23,42,0.14)] backdrop-blur">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <a href="{{ $dashboardUrl }}" wire:navigate>
                        <x-shell.brand />
                    </a>

                    @livewire(\App\Livewire\Shell\GlobalSearch::class)
                </div>

                <div class="flex items-center justify-between gap-3 sm:justify-end">
                    @if ($roleLabel)
                        <span class="hidden rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 lg:inline-flex">
                            {{ $roleLabel }}
                        </span>
                    @endif

                    @if ($showLanguageSwitcher)
                        @livewire(\App\Livewire\Shell\LanguageSwitcher::class)
                    @endif

                    @if ($user && $profileUrl)
                        <a href="{{ $profileUrl }}" wire:navigate>
                            <x-shell.user-avatar :user="$user" />
                        </a>
                    @elseif ($user)
                        <x-shell.user-avatar :user="$user" />
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            <x-heroicon-m-arrow-right-start-on-rectangle class="size-4" />
                            {{ __('dashboard.logout_button') }}
                        </button>
                    </form>
                </div>
            </div>

            @if ($heading)
                <div class="mt-4">
                    @if ($eyebrow)
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-brand-warm">{{ $eyebrow }}</p>
                    @endif

                    <h1 class="font-display text-xl tracking-tight text-slate-950">{{ $heading }}</h1>
                </div>
            @endif
        </header>
    @else
        <nav
            x-data="{ tenantMenuOpen: false }"
            x-on:keydown.escape.window="tenantMenuOpen = false"
            class="fi-topbar relative z-40 flex flex-col border-b border-slate-200 bg-white/92 shadow-sm backdrop-blur"
        >
            <div class="mx-auto flex w-full max-w-[112rem] items-center gap-3 px-4 py-3 lg:px-6">
                <a href="{{ $dashboardUrl }}" wire:navigate class="shrink-0">
                    <x-shell.brand />
                </a>

                <div class="hidden min-w-0 flex-1 lg:block">
                    @livewire(\App\Livewire\Shell\GlobalSearch::class, [], key('shell-global-search-desktop'))
                </div>

                @if ($navigationGroups !== [])
                    <button
                        type="button"
                        x-on:click="tenantMenuOpen = ! tenantMenuOpen"
                        x-bind:aria-expanded="tenantMenuOpen.toString()"
                        aria-controls="tenant-mobile-menu"
                        aria-label="{{ __('dashboard.menu') }}"
                        class="ml-auto inline-flex size-12 shrink-0 touch-manipulation items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 aria-expanded:border-slate-950 aria-expanded:bg-slate-950 aria-expanded:text-white lg:hidden"
                        data-shell-mobile-menu-trigger
                    >
                        <x-heroicon-m-bars-3 x-bind:hidden="tenantMenuOpen" class="size-6" />
                        <x-heroicon-m-x-mark hidden x-bind:hidden="! tenantMenuOpen" class="size-6" />
                        <span class="sr-only">{{ __('dashboard.menu') }}</span>
                    </button>
                @endif

                <div class="ml-auto hidden items-center gap-3 lg:flex">
                    @if ($roleLabel)
                        <span class="hidden rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 xl:inline-flex">
                            {{ $roleLabel }}
                        </span>
                    @endif

                    @if ($showLanguageSwitcher)
                        @livewire(\App\Livewire\Shell\LanguageSwitcher::class, [], key('shell-language-desktop'))
                    @endif

                    @if ($user && $profileUrl)
                        <a href="{{ $profileUrl }}" wire:navigate class="hidden sm:block">
                            <x-shell.user-avatar :user="$user" />
                        </a>
                    @elseif ($user)
                        <div class="hidden sm:block">
                            <x-shell.user-avatar :user="$user" />
                        </div>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            <x-heroicon-m-arrow-right-start-on-rectangle class="size-4" />
                            {{ __('dashboard.logout_button') }}
                        </button>
                    </form>
                </div>
            </div>

            @if ($navigationGroups !== [])
                <div
                    id="tenant-mobile-menu"
                    hidden
                    x-bind:hidden="! tenantMenuOpen"
                    x-bind:aria-hidden="(! tenantMenuOpen).toString()"
                    class="absolute inset-x-0 top-full z-50 border-t border-slate-200/80 bg-white px-3 pb-[calc(env(safe-area-inset-bottom)+1rem)] pt-3 shadow-[0_28px_70px_rgba(15,23,42,0.20)] lg:hidden"
                    data-shell-mobile-menu
                >
                    <div class="mx-auto flex max-h-[calc(100dvh-4.75rem)] w-full max-w-xl flex-col gap-3 overflow-y-auto overscroll-contain">
                        <div class="flex items-center justify-between gap-3 rounded-3xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <div class="flex min-w-0 items-center gap-3">
                                @if ($user)
                                    <x-shell.user-avatar :user="$user" compact />
                                @endif

                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-normal text-slate-500">
                                        {{ __('shell.navigation.groups.my_home') }}
                                    </p>

                                    @if ($user)
                                        <p class="truncate text-sm font-semibold text-slate-950">{{ $user->name }}</p>
                                    @endif
                                </div>
                            </div>

                            <button
                                type="button"
                                x-on:click="tenantMenuOpen = false"
                                aria-label="{{ __('dashboard.menu') }}"
                                class="inline-flex size-11 shrink-0 touch-manipulation items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                            >
                                <x-heroicon-m-x-mark class="size-5" />
                            </button>
                        </div>

                        <div class="flex flex-col gap-3 rounded-3xl border border-slate-200 bg-white p-2.5" data-shell-nav="tenant-mobile-menu">
                            @foreach ($navigationGroups as $group)
                                <section wire:key="tenant-mobile-menu-group-{{ $group->key }}" data-shell-group="{{ $group->key }}" class="flex flex-col gap-2">
                                    <p class="sr-only">
                                        {{ $group->label }}
                                    </p>

                                    <div class="flex flex-wrap gap-2.5">
                                        @foreach ($group->items as $item)
                                            <a
                                                href="{{ $item->url }}"
                                                wire:key="tenant-mobile-menu-item-{{ $group->key }}-{{ $item->routeName }}"
                                                wire:navigate
                                                x-on:click="tenantMenuOpen = false"
                                                @if ($item->active)
                                                    aria-current="page"
                                                    data-shell-current="{{ $item->routeName }}"
                                                @endif
                                                @class([
                                                    'flex min-h-[5.25rem] min-w-0 basis-[calc(50%-0.3125rem)] touch-manipulation flex-col justify-between rounded-[1.35rem] border p-3 text-left transition',
                                                    'border-slate-950 bg-slate-950 text-white shadow-[0_16px_34px_rgba(15,23,42,0.22)]' => $item->active,
                                                    'border-slate-200 bg-slate-50/80 text-slate-800 hover:border-slate-300 hover:bg-white' => ! $item->active,
                                                ])
                                            >
                                                <span class="flex items-center justify-between gap-2">
                                                    @if (filled($item->icon))
                                                        <x-dynamic-component
                                                            :component="$item->icon"
                                                            data-shell-mobile-nav-icon="{{ $item->routeName }}"
                                                            @class([
                                                                'size-6 shrink-0',
                                                                'text-white' => $item->active,
                                                                'text-slate-500' => ! $item->active,
                                                            ])
                                                        />
                                                    @endif

                                                    @if ($item->active)
                                                        <span class="size-2 rounded-full bg-brand-mint"></span>
                                                    @else
                                                        <x-heroicon-m-chevron-right class="size-4 shrink-0 text-slate-400" />
                                                    @endif
                                                </span>

                                                <span class="truncate text-sm font-semibold leading-tight">{{ $item->label }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-2.5">
                            @livewire(\App\Livewire\Shell\GlobalSearch::class, [], key('shell-global-search-mobile'))
                        </div>

                        <div class="flex items-center gap-2.5 rounded-3xl border border-slate-200 bg-slate-50 p-2.5">
                            @if ($user && $profileUrl)
                                <a
                                    href="{{ $profileUrl }}"
                                    wire:navigate
                                    x-on:click="tenantMenuOpen = false"
                                    aria-label="{{ __('shell.profile.title') }}"
                                    class="flex min-h-12 min-w-0 flex-1 touch-manipulation items-center gap-3 rounded-2xl bg-white px-3 py-2.5 text-slate-700 shadow-sm transition hover:bg-slate-100"
                                    data-shell-mobile-profile-link
                                >
                                    <x-shell.user-avatar :user="$user" compact />

                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-slate-950">{{ $user->name }}</span>
                                        @if ($roleLabel)
                                            <span class="block truncate text-xs font-semibold uppercase tracking-normal text-slate-500">{{ $roleLabel }}</span>
                                        @endif
                                    </span>
                                </a>
                            @endif

                            @if ($showLanguageSwitcher)
                                @livewire(\App\Livewire\Shell\LanguageSwitcher::class, [], key('shell-language-mobile'))
                            @endif

                            <form method="POST" action="{{ route('logout') }}" @class(['flex-1' => ! ($user && $profileUrl)])>
                                @csrf
                                @if ($user && $profileUrl)
                                    <button
                                        type="submit"
                                        aria-label="{{ __('dashboard.logout_button') }}"
                                        class="inline-flex size-12 shrink-0 touch-manipulation items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-100"
                                    >
                                        <x-heroicon-m-arrow-right-start-on-rectangle class="size-5" />
                                    </button>
                                @else
                                    <button
                                        type="submit"
                                        class="inline-flex min-h-12 w-full touch-manipulation items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-100"
                                    >
                                        <x-heroicon-m-arrow-right-start-on-rectangle class="size-5" />
                                        {{ __('dashboard.logout_button') }}
                                    </button>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>

                <div class="hidden border-t border-slate-200/80 px-4 pb-3 lg:block lg:px-6">
                    <div class="mx-auto flex w-full max-w-[112rem] justify-center">
                        <div class="flex max-w-full flex-wrap justify-center gap-2 pt-3 sm:gap-3" data-shell-nav="tenant-topbar">
                            @foreach ($navigationGroups as $group)
                                <section wire:key="tenant-topbar-group-{{ $group->key }}" data-shell-group="{{ $group->key }}" class="flex min-w-0 items-center">
                                    <p class="sr-only">
                                        {{ $group->label }}
                                    </p>

                                    <div class="flex min-w-0 flex-wrap justify-center gap-2">
                                        @foreach ($group->items as $item)
                                            <a
                                                href="{{ $item->url }}"
                                                wire:key="tenant-topbar-item-{{ $group->key }}-{{ $item->routeName }}"
                                                wire:navigate
                                                @if ($item->active)
                                                    aria-current="page"
                                                    data-shell-current="{{ $item->routeName }}"
                                                @endif
                                                @class([
                                                    'inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold transition sm:min-w-24',
                                                    'bg-brand-ink text-white shadow-[0_14px_30px_rgba(19,38,63,0.18)]' => $item->active,
                                                    'border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50' => ! $item->active,
                                                ])
                                            >
                                                @if (filled($item->icon))
                                                    <x-dynamic-component
                                                        :component="$item->icon"
                                                        data-shell-nav-icon="{{ $item->routeName }}"
                                                        @class([
                                                            'size-4 shrink-0',
                                                            'text-white' => $item->active,
                                                            'text-slate-500' => ! $item->active,
                                                        ])
                                                    />
                                                @endif
                                                {{ $item->label }}
                                            </a>
                                        @endforeach
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </nav>
    @endif
</div>
