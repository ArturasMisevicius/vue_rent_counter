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
<body class="text-slate-900 antialiased" data-layout="backoffice">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:top-4 focus:left-4 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
        {{ __('app.accessibility.skip_to_content') }}
    </a>
    <div id="app">
        @if(app(\App\Services\ImpersonationService::class)->isImpersonating())
            <x-impersonation-banner :impersonationService="app(\App\Services\ImpersonationService::class)" />
        @endif

        <!-- Navigation -->
        <nav class="sticky top-0 z-40 border-b border-white/40 bg-white/80 backdrop-blur-xl shadow-[0_10px_50px_rgba(15,23,42,0.08)]" x-data="{ mobileMenuOpen: false }">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-600/15 via-sky-500/10 to-indigo-500/15"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-3">
                        <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-400 text-white font-display text-lg shadow-glow transition-transform duration-300">V</span>
                            <div class="leading-tight">
                                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">{{ __('app.brand.name') }}</p>
                                <p class="font-display text-lg text-slate-900">{{ __('app.brand.product') }}</p>
                            </div>
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex md:items-center md:space-x-1">
                        @auth
                            @if(in_array($userRole, ['superadmin', 'admin', 'manager'], true))
                                @include('layouts.partials.backoffice-nav-items')
                            @endif
                        @endauth
                    </div>

                    @auth
                        <div class="hidden md:flex md:items-center md:gap-3">
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12" />
                                    </svg>
                                    {{ __('app.nav.logout') }}
                                </button>
                            </form>
                        </div>

                        <!-- Mobile menu button -->
                        <div class="flex items-center md:hidden">
                            <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <span class="sr-only">{{ __('app.accessibility.open_menu') }}</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                            </button>
                        </div>
                    @endauth
                </div>
            </div>

            <!-- Mobile menu -->
            @auth
            <div x-show="mobileMenuOpen" x-transition class="md:hidden bg-white/95 backdrop-blur border-t border-slate-200 shadow-lg">
                <div class="space-y-1 px-4 pb-4 pt-3">
                    @if(in_array($userRole, ['superadmin', 'admin', 'manager'], true))
                        @include('layouts.partials.backoffice-nav-items', ['mobile' => true])
                    @endif

                    <div class="border-t border-slate-200 pt-2 mt-2">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-slate-700 block w-full text-left px-3 py-2 rounded-lg text-base font-semibold">
                                {{ __('app.nav.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endauth
        </nav>

        <!-- Flash Messages -->
        @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <div class="relative overflow-hidden rounded-2xl border border-emerald-200/80 bg-white/85 shadow-lg shadow-emerald-200/40" role="status" aria-live="polite">
                <div class="absolute inset-0 bg-gradient-to-r from-emerald-50 via-white to-emerald-50"></div>
                <div class="relative flex items-start gap-3 p-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                        </svg>
                    </div>
                    <div class="flex-1 text-sm text-emerald-900">
                        {{ session('success') }}
                    </div>
                    <button @click="show = false" class="text-emerald-500 focus:outline-none">
                        <span class="sr-only">{{ __('app.accessibility.dismiss') }}</span>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <div class="relative overflow-hidden rounded-2xl border border-rose-200/90 bg-white/90 shadow-lg shadow-rose-200/50" role="alert" aria-live="polite">
                <div class="absolute inset-0 bg-gradient-to-r from-rose-50 via-white to-rose-50"></div>
                <div class="relative flex items-start gap-3 p-4">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M4.93 4.93l14.14 14.14M4.93 19.07 19.07 4.93" />
                        </svg>
                    </div>
                    <div class="flex-1 text-sm text-rose-900">
                        {{ session('error') }}
                    </div>
                    <button @click="show = false" class="text-rose-500 focus:outline-none">
                        <span class="sr-only">{{ __('app.accessibility.dismiss') }}</span>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- Main Content -->
        <main id="main-content" class="py-10 relative" role="main" aria-label="Main content">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            </div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
