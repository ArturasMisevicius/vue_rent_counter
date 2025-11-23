<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Message --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                Welcome back, {{ auth()->user()->name }}!
            </h2>
            <p class="mt-2 text-gray-600 dark:text-gray-400">
                @if(auth()->user()->role === \App\Enums\UserRole::ADMIN)
                    Manage your properties, tenants, and billing from this dashboard.
                @elseif(auth()->user()->role === \App\Enums\UserRole::MANAGER)
                    Monitor meter readings, invoices, and property operations.
                @else
                    View your property details, meter readings, and invoices.
                @endif
            </p>
        </div>

        {{-- Stats Widgets --}}
        @if($this->getWidgets())
            <x-filament-widgets::widgets
                :widgets="$this->getWidgets()"
                :columns="$this->getColumns()"
            />
        @endif

        {{-- Quick Actions --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Quick Actions
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @if(auth()->user()->role === \App\Enums\UserRole::ADMIN)
                    <a href="{{ route('filament.admin.resources.properties.index') }}" 
                       class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">Properties</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Manage properties</div>
                        </div>
                    </a>

                    <a href="{{ route('filament.admin.resources.buildings.index') }}" 
                       class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">Buildings</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Manage buildings</div>
                        </div>
                    </a>

                    <a href="{{ route('filament.admin.resources.invoices.index') }}" 
                       class="flex items-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition">
                        <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">Invoices</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Manage invoices</div>
                        </div>
                    </a>

                    <a href="{{ route('filament.admin.resources.users.index') }}" 
                       class="flex items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <div>
                            <div class="font-semibold text-gray-900 dark:text-white">Users</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">Manage users</div>
                        </div>
                    </a>
                @endif
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                Recent Activity
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                Activity tracking coming soon...
            </p>
        </div>
    </div>
</x-filament-panels::page>
