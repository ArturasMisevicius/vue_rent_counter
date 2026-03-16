<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('dashboard.real_time_costs') }}
        </x-slot>

        <div class="space-y-4">
            {{-- Daily Projection --}}
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('dashboard.today_projection') }}
                    </h4>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $this->getDailyProjection()['percentage'] }}% {{ __('dashboard.complete') }}
                    </span>
                </div>
                
                <div class="flex items-end space-x-4">
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            €{{ number_format($this->getDailyProjection()['current'], 2) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('dashboard.current') }}
                        </p>
                    </div>
                    
                    <div class="text-right">
                        <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                            €{{ number_format($this->getDailyProjection()['projected'], 2) }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('dashboard.projected') }}
                        </p>
                    </div>
                </div>
                
                <div class="mt-3">
                    <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div 
                            class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                            style="width: {{ $this->getDailyProjection()['percentage'] }}%"
                        ></div>
                    </div>
                </div>
            </div>

            {{-- Service Breakdown --}}
            <div class="space-y-3">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ __('dashboard.service_breakdown') }}
                </h4>
                
                @forelse($this->getRealTimeCosts() as $cost)
                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full bg-{{ $cost['color'] }}-500"></div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $cost['service'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ number_format($cost['latest_reading'], 2) }} {{ $cost['unit'] }}
                                </p>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                €{{ number_format($cost['daily_cost'], 2) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                €{{ number_format($cost['monthly_estimate'], 0) }}/{{ __('dashboard.month') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('dashboard.no_recent_readings') }}
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- Last Updated --}}
            @if($this->getRealTimeCosts())
                <div class="text-center pt-2 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('dashboard.last_updated') }}: 
                        {{ collect($this->getRealTimeCosts())->max('reading_date')?->diffForHumans() ?? __('dashboard.never') }}
                    </p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>