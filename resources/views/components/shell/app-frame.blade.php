<div class="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,rgba(248,205,116,0.22),transparent_22%),linear-gradient(180deg,#fff8eb_0%,#f8f4ea_44%,#eef7f5_100%)] text-slate-950">
    <div class="absolute inset-x-0 top-0 h-72 bg-[radial-gradient(circle_at_top_left,rgba(62,197,173,0.18),transparent_38%)]"></div>
    <div class="absolute -left-20 top-28 size-56 rounded-full bg-brand-warm/15 blur-3xl"></div>
    <div class="absolute -right-16 top-20 size-64 rounded-full bg-brand-mint/18 blur-3xl"></div>

    <header class="fixed inset-x-0 top-0 z-40 border-b border-white/70 bg-white/78 backdrop-blur-xl">
        <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-4 sm:px-6">
            <x-shell.brand :href="$dashboardUrl" />

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
                class="ml-auto inline-flex size-11 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-600 shadow-sm md:hidden"
                aria-label="{{ __('shell.search_placeholder') }}"
            >
                <svg class="size-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.5 14.5-3.5-3.5m1.75-4.25a5 5 0 1 1-10 0 5 5 0 0 1 10 0Z" />
                </svg>
            </button>

            <div class="hidden items-center gap-3 md:flex">
                <div class="inline-flex items-center rounded-full border border-slate-200 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-[0.24em] text-slate-500 shadow-sm">
                    EN
                </div>

                @if ($user)
                    <x-shell.user-avatar :user="$user" />
                @endif
            </div>
        </div>
    </header>

    <div class="relative mx-auto min-h-screen max-w-6xl px-4 pb-28 pt-28 sm:px-6">
        {{ $slot }}
    </div>

    @if ($showTenantNavigation)
        <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-white/70 bg-white/92 backdrop-blur-xl">
            <div class="mx-auto flex max-w-4xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
                <div class="flex min-w-0 flex-1 items-center justify-center">
                    <span class="inline-flex items-center gap-2 rounded-full bg-brand-ink px-4 py-2 text-sm font-semibold text-white shadow-sm">
                        <span class="inline-flex size-6 items-center justify-center rounded-full bg-white/12 text-xs">H</span>
                        <span>{{ __('shell.home') }}</span>
                    </span>
                </div>

                <div class="flex min-w-0 flex-1 items-center justify-center">
                    <span class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-sm font-semibold text-slate-500">
                        <span class="inline-flex size-6 items-center justify-center rounded-full bg-slate-100 text-xs">P</span>
                        <span>{{ __('shell.profile') }}</span>
                    </span>
                </div>
            </div>
        </nav>
    @endif
</div>
