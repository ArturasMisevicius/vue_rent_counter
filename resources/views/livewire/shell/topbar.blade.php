@php
    $isPageContext = $context === 'page';
    $isCurrentProfileRoute = request()->routeIs('profile.edit', 'tenant.profile.edit');
@endphp

<div class="fi-topbar-ctn" data-shell-topbar="true">
    @if ($impersonation)
        <x-shell.impersonation-banner :impersonation="$impersonation" />
    @endif

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

                    @livewire(\App\Livewire\Shell\NotificationCenter::class)

                    @livewire(\App\Livewire\Shell\LanguageSwitcher::class)

                    @if ($profileUrl && ! $isCurrentProfileRoute)
                        <a
                            href="{{ $profileUrl }}"
                            wire:navigate
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            {{ __('shell.navigation.items.profile') }}
                        </a>
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
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                        >
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
        <nav class="fi-topbar border-b border-slate-200 bg-white/92 shadow-sm backdrop-blur">
            <div class="flex w-full items-center gap-3 px-4 py-3 lg:px-6">
                <button
                    type="button"
                    x-cloak
                    x-data="{}"
                    x-on:click="$store.sidebar.open()"
                    class="inline-flex size-11 items-center justify-center rounded-2xl border border-slate-200 text-slate-700 transition hover:bg-slate-50 lg:hidden"
                >
                    <span class="sr-only">{{ __('filament-panels::layout.actions.sidebar.expand.label') }}</span>
                    <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M3 5.75A.75.75 0 0 1 3.75 5h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 5.75Zm0 4.25a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 10Zm0 4.25a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 14.25Z" clip-rule="evenodd" />
                    </svg>
                </button>

                <a href="{{ $dashboardUrl }}" wire:navigate class="shrink-0">
                    <x-shell.brand />
                </a>

                <div class="hidden min-w-0 flex-1 md:block">
                    @livewire(\App\Livewire\Shell\GlobalSearch::class)
                </div>

                <div class="ml-auto flex items-center gap-3">
                    @if ($roleLabel)
                        <span class="hidden rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.22em] text-slate-500 xl:inline-flex">
                            {{ $roleLabel }}
                        </span>
                    @endif

                    @livewire(\App\Livewire\Shell\NotificationCenter::class)

                    @livewire(\App\Livewire\Shell\LanguageSwitcher::class)

                    @if ($profileUrl)
                        <a
                            href="{{ $profileUrl }}"
                            wire:navigate
                            class="hidden items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50 lg:inline-flex"
                        >
                            {{ __('shell.navigation.items.profile') }}
                        </a>
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
                            class="inline-flex items-center justify-center rounded-2xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            {{ __('dashboard.logout_button') }}
                        </button>
                    </form>
                </div>
            </div>
        </nav>
    @endif
</div>
