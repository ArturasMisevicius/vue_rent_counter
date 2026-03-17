@php
    $showsSidebarToggle = request()->routeIs('filament.admin.*');
@endphp

<div class="fi-topbar-ctn">
    <nav class="fi-topbar border-b border-white/70 bg-white/80 shadow-sm backdrop-blur-xl">
        @if ($showsSidebarToggle)
            <button
                type="button"
                class="fi-topbar-open-sidebar-btn inline-flex size-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm lg:hidden"
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.open()"
                x-show="! $store.sidebar.isOpen"
            >
                <span class="sr-only">{{ __('filament-panels::layout.actions.sidebar.expand.label') }}</span>
                <svg class="size-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h14M3 10h14M3 15h14" />
                </svg>
            </button>

            <button
                type="button"
                class="fi-topbar-close-sidebar-btn inline-flex size-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm lg:hidden"
                x-cloak
                x-data="{}"
                x-on:click="$store.sidebar.close()"
                x-show="$store.sidebar.isOpen"
            >
                <span class="sr-only">{{ __('filament-panels::layout.actions.sidebar.collapse.label') }}</span>
                <svg class="size-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5l10 10M15 5 5 15" />
                </svg>
            </button>
        @endif

        <div class="fi-topbar-start min-w-0 flex-1">
            <x-shell.brand :href="$dashboardUrl" />
        </div>

        <div class="fi-topbar-end flex min-w-0 flex-1 items-center justify-end gap-3">
            <button
                type="button"
                class="hidden min-w-0 flex-1 items-center gap-3 rounded-full border border-slate-200 bg-slate-50/90 px-4 py-3 text-left text-sm text-slate-500 shadow-sm transition hover:border-slate-300 hover:bg-white md:flex"
            >
                <span class="inline-flex size-8 items-center justify-center rounded-full bg-white text-slate-400 ring-1 ring-slate-200">
                    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.5 14.5-3.5-3.5m1.75-4.25a5 5 0 1 1-10 0 5 5 0 0 1 10 0Z" />
                    </svg>
                </span>
                <span>{{ __('shell.search_placeholder') }}</span>
            </button>

            <button
                type="button"
                class="inline-flex size-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm md:hidden"
                aria-label="{{ __('shell.search_placeholder') }}"
            >
                <svg class="size-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.5 14.5-3.5-3.5m1.75-4.25a5 5 0 1 1-10 0 5 5 0 0 1 10 0Z" />
                </svg>
            </button>

            @livewire(\App\Livewire\Shell\LanguageSwitcher::class)

            @livewire(\App\Livewire\Shell\NotificationCenter::class)

            @if ($user)
                <details class="relative">
                    <summary class="list-none cursor-pointer">
                        <x-shell.user-avatar :user="$user" />
                    </summary>

                    <div class="absolute right-0 top-[calc(100%+0.75rem)] z-50 w-64 rounded-3xl border border-slate-200 bg-white p-4 shadow-2xl shadow-slate-950/10">
                        <div class="space-y-1 border-b border-slate-100 pb-4">
                            <p class="text-sm font-semibold text-slate-950">{{ $user->name }}</p>
                            <p class="text-xs text-slate-500">{{ $user->role->label() }}</p>
                        </div>

                        <div class="mt-4 space-y-2">
                            <a
                                href="{{ route('profile.edit') }}"
                                class="flex items-center rounded-2xl px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                            >
                                {{ __('shell.my_profile') }}
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <button
                                    type="submit"
                                    class="flex w-full items-center rounded-2xl px-3 py-2 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-50 hover:text-slate-950"
                                >
                                    {{ __('shell.log_out') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </details>
            @endif
        </div>
    </nav>
</div>
