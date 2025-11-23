<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vilnius Utilities Billing')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Manrope"', 'system-ui', 'sans-serif'],
                        display: ['"Space Grotesk"', '"Manrope"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        midnight: '#0f172a',
                        skyline: '#38bdf8',
                        indigoInk: '#6366f1',
                    },
                    boxShadow: {
                        glow: '0 15px 45px rgba(99, 102, 241, 0.25)',
                    },
                },
            },
        };
    </script>

    <!-- Tailwind CSS via CDN (for development) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js via CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        :root {
            color-scheme: light;
        }

        body {
            font-family: 'Manrope', system-ui, -apple-system, sans-serif;
            background:
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.12), transparent 25%),
                radial-gradient(circle at 90% 10%, rgba(56, 189, 248, 0.12), transparent 22%),
                radial-gradient(circle at 40% 80%, rgba(56, 189, 248, 0.08), transparent 30%),
                linear-gradient(135deg, #f8fafc 0%, #eef2ff 35%, #f8fafc 100%);
            min-height: 100vh;
        }
    </style>

    @stack('styles')
</head>
<body class="text-slate-900 antialiased">
    <div id="app">
        <!-- Navigation -->
        <nav class="sticky top-0 z-40 border-b border-white/40 bg-white/80 backdrop-blur-xl shadow-[0_10px_50px_rgba(15,23,42,0.08)]" x-data="{ mobileMenuOpen: false }">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-600/15 via-sky-500/10 to-indigo-500/15"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center gap-3">
                        <a href="{{ url('/') }}" class="flex items-center gap-3 group">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-sky-400 text-white font-display text-lg shadow-glow transition-transform duration-300 group-hover:-translate-y-1">V</span>
                            <div class="leading-tight">
                                <p class="text-[11px] uppercase tracking-[0.22em] text-slate-500">Vilnius Utilities</p>
                                <p class="font-display text-lg text-slate-900">Rent Counter</p>
                            </div>
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex md:items-center md:space-x-1">
                        @auth
                            @php
                                $currentRoute = Route::currentRouteName();
                                $activeClass = 'bg-gradient-to-r from-indigo-500 to-sky-500 text-white shadow-md shadow-indigo-500/30';
                                $inactiveClass = 'text-slate-700 hover:text-slate-900 hover:bg-slate-100';
                            @endphp

                            {{-- Admin Navigation --}}
                            @if(auth()->user()->role->value === 'admin')
                                <a href="{{ route('admin.dashboard') }}" class="{{ str_starts_with($currentRoute, 'admin.dashboard') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.dashboard') }}
                                </a>
                                <a href="{{ route('admin.users.index') }}" class="{{ str_starts_with($currentRoute, 'admin.users') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.users') }}
                                </a>
                                <a href="{{ route('admin.providers.index') }}" class="{{ str_starts_with($currentRoute, 'admin.providers') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.providers') }}
                                </a>
                                <a href="{{ route('admin.tariffs.index') }}" class="{{ str_starts_with($currentRoute, 'admin.tariffs') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.tariffs') }}
                                </a>
                                <a href="{{ route('admin.settings.index') }}" class="{{ str_starts_with($currentRoute, 'admin.settings') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.settings') }}
                                </a>
                                <a href="{{ route('admin.audit.index') }}" class="{{ str_starts_with($currentRoute, 'admin.audit') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.audit') }}
                                </a>
                            @endif

                            {{-- Manager Navigation --}}
                            @if(auth()->user()->role->value === 'manager')
                                <a href="{{ route('manager.dashboard') }}" class="{{ str_starts_with($currentRoute, 'manager.dashboard') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.dashboard') }}
                                </a>
                                <a href="{{ route('manager.properties.index') }}" class="{{ str_starts_with($currentRoute, 'manager.properties') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.properties') }}
                                </a>
                                <a href="{{ route('manager.buildings.index') }}" class="{{ str_starts_with($currentRoute, 'manager.buildings') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.buildings') }}
                                </a>
                                <a href="{{ route('manager.meters.index') }}" class="{{ str_starts_with($currentRoute, 'manager.meters') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.meters') }}
                                </a>
                                <a href="{{ route('manager.meter-readings.index') }}" class="{{ str_starts_with($currentRoute, 'manager.meter-readings') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.readings') }}
                                </a>
                                <a href="{{ route('manager.invoices.index') }}" class="{{ str_starts_with($currentRoute, 'manager.invoices') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.invoices') }}
                                </a>
                                <a href="{{ route('manager.reports.index') }}" class="{{ str_starts_with($currentRoute, 'manager.reports') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.reports') }}
                                </a>
                            @endif

                            {{-- Tenant Navigation --}}
                            @if(auth()->user()->role->value === 'tenant')
                                <a href="{{ route('tenant.dashboard') }}" class="{{ str_starts_with($currentRoute, 'tenant.dashboard') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.dashboard') }}
                                </a>
                                <a href="{{ route('tenant.property.show') }}" class="{{ str_starts_with($currentRoute, 'tenant.property') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.properties') }}
                                </a>
                                <a href="{{ route('tenant.meters.index') }}" class="{{ str_starts_with($currentRoute, 'tenant.meters') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.meters') }}
                                </a>
                                <a href="{{ route('tenant.meter-readings.index') }}" class="{{ str_starts_with($currentRoute, 'tenant.meter-readings') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.readings') }}
                                </a>
                                <a href="{{ route('tenant.invoices.index') }}" class="{{ str_starts_with($currentRoute, 'tenant.invoices') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.invoices') }}
                                </a>
                                <a href="{{ route('tenant.profile.show') }}" class="{{ str_starts_with($currentRoute, 'tenant.profile') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-lg text-sm font-semibold inline-flex items-center transition">
                                    {{ __('app.nav.profile') }}
                                </a>
                            @endif
                        @endauth
                    </div>

                    @auth
                        @php
                            $languages = \App\Models\Language::query()->where('is_active', true)->orderBy('display_order')->get();
                            $currentLocale = app()->getLocale();
                            $canSwitchLocale = Route::has('locale.set');
                        @endphp
                        <div class="hidden md:flex md:items-center md:gap-3">
                            <span class="inline-flex items-center gap-2 rounded-full bg-slate-900 text-white px-3 py-1.5 text-sm font-semibold shadow-md shadow-slate-900/20">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-white/15 border border-white/10 font-display text-sm">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                                <span>{{ auth()->user()->name }}</span>
                                <span class="text-slate-200 text-xs font-medium">({{ enum_label(auth()->user()->role) }})</span>
                            </span>
                            @if($canSwitchLocale)
                                <form method="POST" action="{{ route('locale.set') }}">
                                    @csrf
                                    <select name="locale" onchange="this.form.submit()" class="bg-white/80 border border-slate-200 text-sm rounded-lg px-2.5 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                        @foreach($languages as $language)
                                            <option value="{{ $language->code }}" {{ $language->code === $currentLocale ? 'selected' : '' }}>
                                                {{ $language->native_name ?? $language->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 transition hover:-translate-y-0.5 hover:shadow-md hover:border-slate-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12" />
                                    </svg>
                                    {{ __('app.nav.logout') }}
                                </button>
                            </form>
                        </div>

                        <!-- Mobile menu button -->
                        <div class="flex items-center md:hidden">
                            <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-slate-700 hover:text-slate-900 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <span class="sr-only">Open main menu</span>
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
                    @php
                        $currentRoute = Route::currentRouteName();
                        $mobileActiveClass = 'bg-gradient-to-r from-indigo-500 to-sky-500 text-white shadow-indigo-500/30';
                        $mobileInactiveClass = 'text-slate-700 hover:text-slate-900 hover:bg-slate-100';
                    @endphp

                    {{-- Admin Mobile Navigation --}}
                    @if(auth()->user()->role->value === 'admin')
                        <a href="{{ route('admin.dashboard') }}" class="{{ str_starts_with($currentRoute, 'admin.dashboard') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.dashboard') }}
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="{{ str_starts_with($currentRoute, 'admin.users') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.users') }}
                        </a>
                        <a href="{{ route('admin.providers.index') }}" class="{{ str_starts_with($currentRoute, 'admin.providers') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.providers') }}
                        </a>
                        <a href="{{ route('admin.tariffs.index') }}" class="{{ str_starts_with($currentRoute, 'admin.tariffs') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.tariffs') }}
                        </a>
                        <a href="{{ route('admin.settings.index') }}" class="{{ str_starts_with($currentRoute, 'admin.settings') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.settings') }}
                        </a>
                        <a href="{{ route('admin.audit.index') }}" class="{{ str_starts_with($currentRoute, 'admin.audit') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.audit') }}
                        </a>
                    @endif

                    {{-- Manager Mobile Navigation --}}
                    @if(auth()->user()->role->value === 'manager')
                        <a href="{{ route('manager.dashboard') }}" class="{{ str_starts_with($currentRoute, 'manager.dashboard') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.dashboard') }}
                        </a>
                        <a href="{{ route('manager.properties.index') }}" class="{{ str_starts_with($currentRoute, 'manager.properties') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.properties') }}
                        </a>
                        <a href="{{ route('manager.buildings.index') }}" class="{{ str_starts_with($currentRoute, 'manager.buildings') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.buildings') }}
                        </a>
                        <a href="{{ route('manager.meters.index') }}" class="{{ str_starts_with($currentRoute, 'manager.meters') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.meters') }}
                        </a>
                        <a href="{{ route('manager.meter-readings.index') }}" class="{{ str_starts_with($currentRoute, 'manager.meter-readings') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.readings') }}
                        </a>
                        <a href="{{ route('manager.invoices.index') }}" class="{{ str_starts_with($currentRoute, 'manager.invoices') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.invoices') }}
                        </a>
                        <a href="{{ route('manager.reports.index') }}" class="{{ str_starts_with($currentRoute, 'manager.reports') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.reports') }}
                        </a>
                    @endif

                    {{-- Tenant Mobile Navigation --}}
                    @if(auth()->user()->role->value === 'tenant')
                        <a href="{{ route('tenant.dashboard') }}" class="{{ str_starts_with($currentRoute, 'tenant.dashboard') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.dashboard') }}
                        </a>
                        <a href="{{ route('tenant.property.show') }}" class="{{ str_starts_with($currentRoute, 'tenant.property') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.properties') }}
                        </a>
                        <a href="{{ route('tenant.meters.index') }}" class="{{ str_starts_with($currentRoute, 'tenant.meters') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.meters') }}
                        </a>
                        <a href="{{ route('tenant.meter-readings.index') }}" class="{{ str_starts_with($currentRoute, 'tenant.meter-readings') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.readings') }}
                        </a>
                        <a href="{{ route('tenant.invoices.index') }}" class="{{ str_starts_with($currentRoute, 'tenant.invoices') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.invoices') }}
                        </a>
                        <a href="{{ route('tenant.profile.show') }}" class="{{ str_starts_with($currentRoute, 'tenant.profile') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-lg text-base font-semibold">
                            {{ __('app.nav.profile') }}
                        </a>
                    @endif

                    <div class="border-t border-slate-200 pt-2 mt-2">
                        <div class="px-3 py-2 text-sm font-semibold text-slate-800 flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-500 text-white font-display">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <div class="leading-tight">
                                <p>{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500">{{ enum_label(auth()->user()->role) }}</p>
                            </div>
                        </div>
                        @if($canSwitchLocale)
                            <form method="POST" action="{{ route('locale.set') }}" class="px-3 py-2">
                                @csrf
                                <select name="locale" onchange="this.form.submit()" class="w-full bg-white border border-slate-200 text-sm rounded-lg px-2.5 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                    @foreach($languages as $language)
                                        <option value="{{ $language->code }}" {{ $language->code === $currentLocale ? 'selected' : '' }}>
                                            {{ $language->native_name ?? $language->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-slate-700 hover:text-slate-900 hover:bg-slate-100 block w-full text-left px-3 py-2 rounded-lg text-base font-semibold">
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
            <div class="relative overflow-hidden rounded-2xl border border-emerald-200/80 bg-white/85 shadow-lg shadow-emerald-200/40" role="alert">
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
                    <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 focus:outline-none">
                        <span class="sr-only">Dismiss</span>
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
            <div class="relative overflow-hidden rounded-2xl border border-rose-200/90 bg-white/90 shadow-lg shadow-rose-200/50" role="alert">
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
                    <button @click="show = false" class="text-rose-500 hover:text-rose-700 focus:outline-none">
                        <span class="sr-only">Dismiss</span>
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        @endif

        <!-- Main Content -->
        <main class="py-10 relative">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Breadcrumbs -->
                @auth
                    @php
                        $breadcrumbs = \App\Helpers\BreadcrumbHelper::generate();
                    @endphp
                    
                    @if(count($breadcrumbs) > 0)
                        <x-breadcrumbs>
                            @foreach($breadcrumbs as $breadcrumb)
                                <x-breadcrumb-item 
                                    :href="$breadcrumb['url']" 
                                    :active="$breadcrumb['active']"
                                >
                                    {{ $breadcrumb['label'] }}
                                </x-breadcrumb-item>
                            @endforeach
                        </x-breadcrumbs>
                    @endif
                @endauth
            </div>
            
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
