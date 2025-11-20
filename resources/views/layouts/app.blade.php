<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Vilnius Utilities Billing')</title>
    
    <!-- Tailwind CSS via CDN (for development) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js via CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @stack('styles')
</head>
<body class="bg-gray-100">
    <div id="app">
        <!-- Navigation -->
        <nav class="bg-indigo-600" x-data="{ mobileMenuOpen: false }">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="{{ url('/') }}" class="text-xl font-bold text-white">
                                Vilnius Utilities
                            </a>
                        </div>
                        
                        <!-- Desktop Navigation -->
                        <div class="hidden md:ml-6 md:flex md:space-x-4">
                            @auth
                                @php
                                    $currentRoute = Route::currentRouteName();
                                    $activeClass = 'bg-indigo-700 text-white';
                                    $inactiveClass = 'text-white hover:bg-indigo-500';
                                @endphp
                                
                                {{-- Admin Navigation --}}
                                @if(auth()->user()->role->value === 'admin')
                                    <a href="{{ route('admin.dashboard') }}" class="{{ str_starts_with($currentRoute, 'admin.dashboard') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Dashboard
                                    </a>
                                    <a href="{{ route('admin.users.index') }}" class="{{ str_starts_with($currentRoute, 'admin.users') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Users
                                    </a>
                                    <a href="{{ route('admin.providers.index') }}" class="{{ str_starts_with($currentRoute, 'admin.providers') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Providers
                                    </a>
                                    <a href="{{ route('admin.tariffs.index') }}" class="{{ str_starts_with($currentRoute, 'admin.tariffs') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Tariffs
                                    </a>
                                    <a href="{{ route('admin.settings.index') }}" class="{{ str_starts_with($currentRoute, 'admin.settings') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Settings
                                    </a>
                                    <a href="{{ route('admin.audit.index') }}" class="{{ str_starts_with($currentRoute, 'admin.audit') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Audit
                                    </a>
                                @endif
                                
                                {{-- Manager Navigation --}}
                                @if(auth()->user()->role->value === 'manager')
                                    <a href="{{ route('manager.dashboard') }}" class="{{ str_starts_with($currentRoute, 'manager.dashboard') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Dashboard
                                    </a>
                                    <a href="{{ route('manager.properties.index') }}" class="{{ str_starts_with($currentRoute, 'manager.properties') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Properties
                                    </a>
                                    <a href="{{ route('manager.buildings.index') }}" class="{{ str_starts_with($currentRoute, 'manager.buildings') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Buildings
                                    </a>
                                    <a href="{{ route('manager.meters.index') }}" class="{{ str_starts_with($currentRoute, 'manager.meters') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Meters
                                    </a>
                                    <a href="{{ route('manager.meter-readings.index') }}" class="{{ str_starts_with($currentRoute, 'manager.meter-readings') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Readings
                                    </a>
                                    <a href="{{ route('manager.invoices.index') }}" class="{{ str_starts_with($currentRoute, 'manager.invoices') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Invoices
                                    </a>
                                    <a href="{{ route('manager.reports.index') }}" class="{{ str_starts_with($currentRoute, 'manager.reports') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Reports
                                    </a>
                                @endif
                                
                                {{-- Tenant Navigation --}}
                                @if(auth()->user()->role->value === 'tenant')
                                    <a href="{{ route('tenant.dashboard') }}" class="{{ str_starts_with($currentRoute, 'tenant.dashboard') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Dashboard
                                    </a>
                                    <a href="{{ route('tenant.property.show') }}" class="{{ str_starts_with($currentRoute, 'tenant.property') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        My Property
                                    </a>
                                    <a href="{{ route('tenant.meters.index') }}" class="{{ str_starts_with($currentRoute, 'tenant.meters') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Meters
                                    </a>
                                    <a href="{{ route('tenant.meter-readings.index') }}" class="{{ str_starts_with($currentRoute, 'tenant.meter-readings') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Readings
                                    </a>
                                    <a href="{{ route('tenant.invoices.index') }}" class="{{ str_starts_with($currentRoute, 'tenant.invoices') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Invoices
                                    </a>
                                    <a href="{{ route('tenant.profile.show') }}" class="{{ str_starts_with($currentRoute, 'tenant.profile') ? $activeClass : $inactiveClass }} px-3 py-2 rounded-md text-sm font-medium inline-flex items-center">
                                        Profile
                                    </a>
                                @endif
                            @endauth
                        </div>
                    </div>
                    
                    @auth
                    <div class="hidden md:flex md:items-center">
                        <span class="text-white text-sm mr-4">
                            {{ auth()->user()->name }} 
                            <span class="text-indigo-200">({{ ucfirst(auth()->user()->role->value) }})</span>
                        </span>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-white hover:bg-indigo-500 px-3 py-2 rounded-md text-sm font-medium">
                                Logout
                            </button>
                        </form>
                    </div>
                    
                    <!-- Mobile menu button -->
                    <div class="flex items-center md:hidden">
                        <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="inline-flex items-center justify-center p-2 rounded-md text-indigo-200 hover:text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
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
            <div x-show="mobileMenuOpen" x-transition class="md:hidden">
                <div class="space-y-1 px-2 pb-3 pt-2">
                    @php
                        $currentRoute = Route::currentRouteName();
                        $mobileActiveClass = 'bg-indigo-700 text-white';
                        $mobileInactiveClass = 'text-white hover:bg-indigo-500';
                    @endphp
                    
                    {{-- Admin Mobile Navigation --}}
                    @if(auth()->user()->role->value === 'admin')
                        <a href="{{ route('admin.dashboard') }}" class="{{ str_starts_with($currentRoute, 'admin.dashboard') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Dashboard
                        </a>
                        <a href="{{ route('admin.users.index') }}" class="{{ str_starts_with($currentRoute, 'admin.users') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Users
                        </a>
                        <a href="{{ route('admin.providers.index') }}" class="{{ str_starts_with($currentRoute, 'admin.providers') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Providers
                        </a>
                        <a href="{{ route('admin.tariffs.index') }}" class="{{ str_starts_with($currentRoute, 'admin.tariffs') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Tariffs
                        </a>
                        <a href="{{ route('admin.settings.index') }}" class="{{ str_starts_with($currentRoute, 'admin.settings') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Settings
                        </a>
                        <a href="{{ route('admin.audit.index') }}" class="{{ str_starts_with($currentRoute, 'admin.audit') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Audit
                        </a>
                    @endif
                    
                    {{-- Manager Mobile Navigation --}}
                    @if(auth()->user()->role->value === 'manager')
                        <a href="{{ route('manager.dashboard') }}" class="{{ str_starts_with($currentRoute, 'manager.dashboard') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Dashboard
                        </a>
                        <a href="{{ route('manager.properties.index') }}" class="{{ str_starts_with($currentRoute, 'manager.properties') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Properties
                        </a>
                        <a href="{{ route('manager.buildings.index') }}" class="{{ str_starts_with($currentRoute, 'manager.buildings') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Buildings
                        </a>
                        <a href="{{ route('manager.meters.index') }}" class="{{ str_starts_with($currentRoute, 'manager.meters') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Meters
                        </a>
                        <a href="{{ route('manager.meter-readings.index') }}" class="{{ str_starts_with($currentRoute, 'manager.meter-readings') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Readings
                        </a>
                        <a href="{{ route('manager.invoices.index') }}" class="{{ str_starts_with($currentRoute, 'manager.invoices') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Invoices
                        </a>
                        <a href="{{ route('manager.reports.index') }}" class="{{ str_starts_with($currentRoute, 'manager.reports') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Reports
                        </a>
                    @endif
                    
                    {{-- Tenant Mobile Navigation --}}
                    @if(auth()->user()->role->value === 'tenant')
                        <a href="{{ route('tenant.dashboard') }}" class="{{ str_starts_with($currentRoute, 'tenant.dashboard') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Dashboard
                        </a>
                        <a href="{{ route('tenant.property.show') }}" class="{{ str_starts_with($currentRoute, 'tenant.property') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            My Property
                        </a>
                        <a href="{{ route('tenant.meters.index') }}" class="{{ str_starts_with($currentRoute, 'tenant.meters') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Meters
                        </a>
                        <a href="{{ route('tenant.meter-readings.index') }}" class="{{ str_starts_with($currentRoute, 'tenant.meter-readings') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Readings
                        </a>
                        <a href="{{ route('tenant.invoices.index') }}" class="{{ str_starts_with($currentRoute, 'tenant.invoices') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Invoices
                        </a>
                        <a href="{{ route('tenant.profile.show') }}" class="{{ str_starts_with($currentRoute, 'tenant.profile') ? $mobileActiveClass : $mobileInactiveClass }} block px-3 py-2 rounded-md text-base font-medium">
                            Profile
                        </a>
                    @endif
                    
                    <div class="border-t border-indigo-500 pt-2 mt-2">
                        <div class="px-3 py-2 text-white text-sm">
                            {{ auth()->user()->name }} <span class="text-indigo-200">({{ ucfirst(auth()->user()->role->value) }})</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-white hover:bg-indigo-500 block w-full text-left px-3 py-2 rounded-md text-base font-medium">
                                Logout
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
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
                <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </button>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
                <button @click="show = false" class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </button>
            </div>
        </div>
        @endif

        <!-- Main Content -->
        <main class="py-6">
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
