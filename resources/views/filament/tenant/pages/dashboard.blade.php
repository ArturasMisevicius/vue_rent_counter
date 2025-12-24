<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Quick Stats Header --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($this->getQuickStats() as $key => $value)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                @switch($key)
                                    @case('total_properties')
                                        <x-heroicon-o-building-office class="h-6 w-6 text-gray-400" />
                                        @break
                                    @case('active_services')
                                        <x-heroicon-o-cog-6-tooth class="h-6 w-6 text-blue-400" />
                                        @break
                                    @case('current_month_readings')
                                        <x-heroicon-o-chart-bar class="h-6 w-6 text-green-400" />
                                        @break
                                    @case('pending_readings')
                                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-yellow-400" />
                                        @break
                                @endswitch
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                                        {{ __('dashboard.stats.' . $key) }}
                                    </dt>
                                    <dd class="text-lg font-medium text-gray-900 dark:text-white">
                                        {{ number_format($value) }}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Utility Services Overview --}}
        @if($this->getUtilityBreakdown())
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                        {{ __('dashboard.utility_services_overview') }}
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($this->getUtilityBreakdown() as $service)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $service['name'] }}
                                    </h4>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $service['meter_count'] }} {{ __('dashboard.meters') }}
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($service['total_consumption'], 2) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $service['unit'] }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Recent Activity --}}
        @if($this->getRecentActivity())
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                        {{ __('dashboard.recent_activity') }}
                    </h3>
                    <div class="flow-root">
                        <ul class="-mb-8">
                            @foreach($this->getRecentActivity() as $index => $activity)
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white dark:ring-gray-800">
                                                    <x-heroicon-o-chart-bar class="h-4 w-4 text-white" />
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="text-sm text-gray-900 dark:text-white">
                                                        {{ $activity['title'] }}
                                                    </p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $activity['description'] }}
                                                    </p>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ number_format($activity['value'], 2) }} {{ $activity['unit'] }}
                                                    </p>
                                                </div>
                                                <div class="text-right text-sm whitespace-nowrap text-gray-500 dark:text-gray-400">
                                                    <time datetime="{{ $activity['created_at']->toISOString() }}">
                                                        {{ $activity['created_at']->diffForHumans() }}
                                                    </time>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Widgets Grid --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
            @foreach($this->getWidgets() as $widget)
                @livewire($widget)
            @endforeach
        </div>
    </div>
</x-filament-panels::page>