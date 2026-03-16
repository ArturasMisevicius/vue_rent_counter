<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('dashboard.utility_analytics') }}
        </x-slot>

        <div class="space-y-6">
            {{-- Efficiency Trends --}}
            <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('dashboard.efficiency_trends') }}
                </h4>
                
                @forelse($this->getAnalyticsData()['efficiency_trends'] ?? [] as $trend)
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-3">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $trend['service'] }}
                                </h5>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    ({{ $trend['unit'] }})
                                </span>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <span class="text-xs px-2 py-1 rounded-full 
                                    {{ $trend['trend'] === 'increasing' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : '' }}
                                    {{ $trend['trend'] === 'decreasing' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : '' }}
                                    {{ $trend['trend'] === 'stable' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' : '' }}
                                ">
                                    {{ __('dashboard.trend_' . $trend['trend']) }}
                                </span>
                                
                                @if($trend['change_percentage'] != 0)
                                    <span class="text-xs font-medium 
                                        {{ $trend['change_percentage'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}
                                    ">
                                        {{ $trend['change_percentage'] > 0 ? '+' : '' }}{{ $trend['change_percentage'] }}%
                                    </span>
                                @endif
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-6 gap-2 mt-3">
                            @foreach($trend['data'] as $data)
                                <div class="text-center">
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                        {{ $data['month'] }}
                                    </div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        €{{ number_format($data['cost'], 0) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ number_format($data['consumption'], 0) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('dashboard.no_efficiency_data') }}
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- Cost Predictions --}}
            <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('dashboard.cost_predictions') }}
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @forelse($this->getAnalyticsData()['cost_predictions'] ?? [] as $prediction)
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $prediction['service'] }}
                                </h5>
                                <span class="text-xs px-2 py-1 rounded-full 
                                    {{ $prediction['confidence'] === 'high' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : '' }}
                                    {{ $prediction['confidence'] === 'medium' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' : '' }}
                                    {{ $prediction['confidence'] === 'low' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : '' }}
                                ">
                                    {{ __('dashboard.confidence_' . $prediction['confidence']) }}
                                </span>
                            </div>
                            
                            <div class="space-y-2">
                                <div>
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        €{{ number_format($prediction['predicted_monthly_cost'], 2) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('dashboard.monthly_prediction') }}
                                    </p>
                                </div>
                                
                                <div>
                                    <p class="text-sm font-semibold text-blue-600 dark:text-blue-400">
                                        €{{ number_format($prediction['predicted_yearly_cost'], 0) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ __('dashboard.yearly_prediction') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('dashboard.no_prediction_data') }}
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Usage Patterns --}}
            <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('dashboard.usage_patterns') }}
                </h4>
                
                @forelse($this->getAnalyticsData()['usage_patterns'] ?? [] as $pattern)
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 mb-4">
                        <div class="flex items-center justify-between mb-3">
                            <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $pattern['service'] }}
                            </h5>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('dashboard.peak_usage') }}: {{ $pattern['peak_day'] }} / {{ $pattern['peak_month'] }}
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            {{-- Weekly Pattern --}}
                            <div>
                                <h6 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('dashboard.weekly_pattern') }}
                                </h6>
                                <div class="space-y-1">
                                    @foreach($pattern['day_of_week_usage'] as $dayUsage)
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-600 dark:text-gray-400">
                                                {{ $dayUsage['day'] }}
                                            </span>
                                            <span class="font-medium text-gray-900 dark:text-white">
                                                {{ number_format($dayUsage['average_usage'], 1) }} {{ $pattern['unit'] }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            
                            {{-- Monthly Trend --}}
                            <div>
                                <h6 class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    {{ __('dashboard.monthly_trend') }}
                                </h6>
                                <div class="space-y-1">
                                    @foreach(array_slice($pattern['monthly_usage'], -3) as $monthUsage)
                                        <div class="flex items-center justify-between text-xs">
                                            <span class="text-gray-600 dark:text-gray-400">
                                                {{ $monthUsage['month'] }}
                                            </span>
                                            <span class="font-medium text-gray-900 dark:text-white">
                                                {{ number_format($monthUsage['total_usage'], 0) }} {{ $pattern['unit'] }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('dashboard.no_pattern_data') }}
                        </p>
                    </div>
                @endforelse
            </div>

            {{-- Recommendations --}}
            <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
                    {{ __('dashboard.recommendations') }}
                </h4>
                
                <div class="space-y-3">
                    @forelse($this->getAnalyticsData()['recommendations'] ?? [] as $recommendation)
                        <div class="flex items-start space-x-3 p-3 rounded-lg
                            {{ $recommendation['priority'] === 'high' ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : '' }}
                            {{ $recommendation['priority'] === 'medium' ? 'bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800' : '' }}
                            {{ $recommendation['priority'] === 'low' ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : '' }}
                        ">
                            <div class="flex-shrink-0 mt-0.5">
                                <div class="w-2 h-2 rounded-full
                                    {{ $recommendation['priority'] === 'high' ? 'bg-red-500' : '' }}
                                    {{ $recommendation['priority'] === 'medium' ? 'bg-yellow-500' : '' }}
                                    {{ $recommendation['priority'] === 'low' ? 'bg-blue-500' : '' }}
                                "></div>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $recommendation['title'] }}
                                    </h5>
                                    <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $recommendation['service'] }}
                                    </span>
                                </div>
                                
                                <p class="text-xs text-gray-600 dark:text-gray-400 mb-2">
                                    {{ $recommendation['description'] }}
                                </p>
                                
                                <button class="text-xs font-medium 
                                    {{ $recommendation['priority'] === 'high' ? 'text-red-700 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300' : '' }}
                                    {{ $recommendation['priority'] === 'medium' ? 'text-yellow-700 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-300' : '' }}
                                    {{ $recommendation['priority'] === 'low' ? 'text-blue-700 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300' : '' }}
                                ">
                                    {{ $recommendation['action'] }} →
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('dashboard.no_recommendations') }}
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>