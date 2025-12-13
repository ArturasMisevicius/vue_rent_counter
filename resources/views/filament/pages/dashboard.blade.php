<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Dashboard Customization (Superadmin only) --}}
        @if(auth()->user()->isSuperadmin())
            <livewire:dashboard-customization />
        @endif

        {{-- Welcome Message --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white">
                {{ __('filament.pages.dashboard.welcome', ['name' => auth()->user()->name]) }}
            </h2>
            <p class="mt-2 text-slate-600 dark:text-slate-400">
                @if(auth()->user()->role === \App\Enums\UserRole::ADMIN)
                    {{ __('filament.pages.dashboard.admin_description') }}
                @elseif(auth()->user()->role === \App\Enums\UserRole::MANAGER)
                    {{ __('filament.pages.dashboard.manager_description') }}
                @elseif(auth()->user()->isSuperadmin())
                    {{ __('Manage the entire platform with comprehensive tools and analytics.') }}
                @else
                    {{ __('filament.pages.dashboard.tenant_description') }}
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
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                {{ __('filament.pages.dashboard.quick_actions') }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @if(auth()->user()->role === \App\Enums\UserRole::ADMIN)
                    <a href="{{ route('filament.admin.resources.properties.index') }}" 
                       class="flex items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-white">{{ __('filament.pages.dashboard.cards.properties.title') }}</div>
                            <div class="text-sm text-slate-600 dark:text-slate-400">{{ __('filament.pages.dashboard.cards.properties.description') }}</div>
                        </div>
                    </a>

                    <a href="{{ route('filament.admin.resources.buildings.index') }}" 
                       class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-white">{{ __('filament.pages.dashboard.cards.buildings.title') }}</div>
                            <div class="text-sm text-slate-600 dark:text-slate-400">{{ __('filament.pages.dashboard.cards.buildings.description') }}</div>
                        </div>
                    </a>

                    <a href="{{ route('filament.admin.resources.invoices.index') }}" 
                       class="flex items-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/30 transition">
                        <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-white">{{ __('filament.pages.dashboard.cards.invoices.title') }}</div>
                            <div class="text-sm text-slate-600 dark:text-slate-400">{{ __('filament.pages.dashboard.cards.invoices.description') }}</div>
                        </div>
                    </a>

                    <a href="{{ route('filament.admin.resources.users.index') }}" 
                       class="flex items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <div>
                            <div class="font-semibold text-slate-900 dark:text-white">{{ __('filament.pages.dashboard.cards.users.title') }}</div>
                            <div class="text-sm text-slate-600 dark:text-slate-400">{{ __('filament.pages.dashboard.cards.users.description') }}</div>
                        </div>
                    </a>
                @endif
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">
                {{ __('filament.pages.dashboard.recent_activity_title') }}
            </h3>
            <p class="text-slate-600 dark:text-slate-400">
                {{ __('filament.pages.dashboard.recent_activity_body') }}
            </p>
        </div>
    </div>
</x-filament-panels::page>
