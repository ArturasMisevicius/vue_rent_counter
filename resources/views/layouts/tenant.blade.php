<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('app.meta.default_title'))</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-slate-50 text-slate-900 antialiased" data-layout="tenant">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:top-4 focus:left-4 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-sky-500">
        {{ __('app.accessibility.skip_to_content') }}
    </a>

    <div id="app">
        @if(app(\App\Services\ImpersonationService::class)->isImpersonating())
            <x-impersonation-banner :impersonationService="app(\App\Services\ImpersonationService::class)" />
        @endif

        <nav class="sticky top-0 z-40 border-b border-sky-100/80 bg-white/90 backdrop-blur-xl shadow-sm" x-data="{ mobileMenuOpen: false }">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ route('tenant.dashboard') }}" class="inline-flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-cyan-400 text-sm font-semibold text-white">T</span>
                    <div class="leading-tight">
                        <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">{{ __('app.brand.name') }}</p>
                        <p class="text-lg font-semibold text-slate-900">{{ __('app.brand.product') }}</p>
                    </div>
                </a>

                <div class="hidden items-center gap-1 md:flex">
                    <a href="{{ route('tenant.dashboard') }}" class="{{ request()->routeIs('tenant.dashboard') ? 'bg-sky-500 text-white' : 'text-slate-700' }} rounded-lg px-3 py-2 text-sm font-semibold">{{ __('app.nav.dashboard') }}</a>
                    <a href="{{ route('tenant.property.show') }}" class="{{ request()->routeIs('tenant.property.*') ? 'bg-sky-500 text-white' : 'text-slate-700' }} rounded-lg px-3 py-2 text-sm font-semibold">{{ __('app.nav.properties') }}</a>
                    <a href="{{ route('tenant.meters.index') }}" class="{{ request()->routeIs('tenant.meters.*') ? 'bg-sky-500 text-white' : 'text-slate-700' }} rounded-lg px-3 py-2 text-sm font-semibold">{{ __('app.nav.meters') }}</a>
                    <a href="{{ route('tenant.meter-readings.index') }}" class="{{ request()->routeIs('tenant.meter-readings.*') ? 'bg-sky-500 text-white' : 'text-slate-700' }} rounded-lg px-3 py-2 text-sm font-semibold">{{ __('app.nav.readings') }}</a>
                    <a href="{{ route('tenant.invoices.index') }}" class="{{ request()->routeIs('tenant.invoices.*') ? 'bg-sky-500 text-white' : 'text-slate-700' }} rounded-lg px-3 py-2 text-sm font-semibold">{{ __('app.nav.invoices') }}</a>
                    <a href="{{ route('tenant.profile.show') }}" class="{{ request()->routeIs('tenant.profile.*') ? 'bg-sky-500 text-white' : 'text-slate-700' }} rounded-lg px-3 py-2 text-sm font-semibold">{{ __('app.nav.profile') }}</a>
                </div>

                <div class="hidden items-center gap-2 md:flex">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700">
                            {{ __('app.nav.logout') }}
                        </button>
                    </form>
                </div>

                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center rounded-md p-2 text-slate-700 md:hidden">
                    <span class="sr-only">{{ __('app.accessibility.open_menu') }}</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>

            <div x-show="mobileMenuOpen" x-transition class="border-t border-slate-200 bg-white md:hidden">
                <div class="space-y-1 px-4 py-3">
                    <a href="{{ route('tenant.dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700">{{ __('app.nav.dashboard') }}</a>
                    <a href="{{ route('tenant.property.show') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700">{{ __('app.nav.properties') }}</a>
                    <a href="{{ route('tenant.meters.index') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700">{{ __('app.nav.meters') }}</a>
                    <a href="{{ route('tenant.meter-readings.index') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700">{{ __('app.nav.readings') }}</a>
                    <a href="{{ route('tenant.invoices.index') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700">{{ __('app.nav.invoices') }}</a>
                    <a href="{{ route('tenant.profile.show') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-700">{{ __('app.nav.profile') }}</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-700">
                            {{ __('app.nav.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        @if(session('success'))
            <div class="mx-auto mt-4 max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="mx-auto mt-4 max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        <main id="main-content" class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                @yield('tenant-content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
