<div class="space-y-6">
    {{-- Anomaly Header --}}
    <div class="flex items-start space-x-4">
        <div class="flex-shrink-0">
            @switch($anomaly['severity'])
                @case('high')
                @case('critical')
                    <div class="flex items-center justify-center w-10 h-10 bg-red-100 rounded-full">
                        <x-heroicon-o-exclamation-triangle class="w-6 h-6 text-red-600" />
                    </div>
                    @break
                @case('medium')
                    <div class="flex items-center justify-center w-10 h-10 bg-yellow-100 rounded-full">
                        <x-heroicon-o-exclamation-circle class="w-6 h-6 text-yellow-600" />
                    </div>
                    @break
                @default
                    <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-full">
                        <x-heroicon-o-information-circle class="w-6 h-6 text-blue-600" />
                    </div>
            @endswitch
        </div>
        
        <div class="flex-1 min-w-0">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ __('audit.anomaly_types.' . $anomaly['type']) }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $anomaly['description'] }}
            </p>
            <div class="mt-2 flex items-center space-x-4 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center">
                    <x-heroicon-o-clock class="w-4 h-4 mr-1" />
                    {{ $anomaly['detected_at']->format('M j, Y \a\t g:i A') }}
                </span>
                <span class="flex items-center">
                    <x-heroicon-o-flag class="w-4 h-4 mr-1" />
                    {{ ucfirst($anomaly['severity']) }} {{ __('audit.labels.severity') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Anomaly Details --}}
    @if(!empty($anomaly['details']))
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
                {{ __('audit.labels.details') }}
            </h4>
            
            <div class="space-y-4">
                @foreach($anomaly['details'] as $key => $value)
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            @if(is_array($value))
                                @if(isset($value['daily_counts']))
                                    {{-- Special handling for change frequency data --}}
                                    <div class="grid grid-cols-2 gap-4 mt-2">
                                        <div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('audit.labels.average') }}:</span>
                                            <span class="font-medium">{{ $value['average'] ?? 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('audit.labels.peak') }}:</span>
                                            <span class="font-medium">{{ $value['peak'] ?? 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('audit.labels.threshold') }}:</span>
                                            <span class="font-medium">{{ $value['threshold'] ?? 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('audit.labels.anomalous') }}:</span>
                                            <span class="font-medium {{ ($value['isAnomalous'] ?? false) ? 'text-red-600' : 'text-green-600' }}">
                                                {{ ($value['isAnomalous'] ?? false) ? __('audit.labels.yes') : __('audit.labels.no') }}
                                            </span>
                                        </div>
                                    </div>
                                @else
                                    <pre class="text-xs bg-white dark:bg-gray-900 p-2 rounded border overflow-x-auto">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                @endif
                            @else
                                <span class="font-mono">{{ $value }}</span>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Recommended Actions --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-4">
            {{ __('audit.labels.recommended_actions') }}
        </h4>
        
        <div class="space-y-3">
            @switch($anomaly['type'])
                @case('high_change_frequency')
                    <div class="flex items-start space-x-3">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5 text-blue-500 mt-0.5" />
                        <div>
                            <p class="text-sm text-gray-900 dark:text-white font-medium">
                                {{ __('audit.recommendations.investigate_changes') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('audit.recommendations.investigate_changes_desc') }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-3">
                        <x-heroicon-o-shield-check class="w-5 h-5 text-green-500 mt-0.5" />
                        <div>
                            <p class="text-sm text-gray-900 dark:text-white font-medium">
                                {{ __('audit.recommendations.review_permissions') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('audit.recommendations.review_permissions_desc') }}
                            </p>
                        </div>
                    </div>
                    @break
                    
                @case('bulk_changes')
                    <div class="flex items-start space-x-3">
                        <x-heroicon-o-user-group class="w-5 h-5 text-orange-500 mt-0.5" />
                        <div>
                            <p class="text-sm text-gray-900 dark:text-white font-medium">
                                {{ __('audit.recommendations.verify_user_actions') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('audit.recommendations.verify_user_actions_desc') }}
                            </p>
                        </div>
                    </div>
                    @break
                    
                @case('configuration_rollbacks')
                    <div class="flex items-start space-x-3">
                        <x-heroicon-o-arrow-path class="w-5 h-5 text-purple-500 mt-0.5" />
                        <div>
                            <p class="text-sm text-gray-900 dark:text-white font-medium">
                                {{ __('audit.recommendations.analyze_rollbacks') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('audit.recommendations.analyze_rollbacks_desc') }}
                            </p>
                        </div>
                    </div>
                    @break
                    
                @default
                    <div class="flex items-start space-x-3">
                        <x-heroicon-o-document-text class="w-5 h-5 text-gray-500 mt-0.5" />
                        <div>
                            <p class="text-sm text-gray-900 dark:text-white font-medium">
                                {{ __('audit.recommendations.review_logs') }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('audit.recommendations.review_logs_desc') }}
                            </p>
                        </div>
                    </div>
            @endswitch
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-6 flex justify-end space-x-3">
        <button 
            type="button" 
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            onclick="window.print()"
        >
            {{ __('audit.actions.export_details') }}
        </button>
        
        <button 
            type="button" 
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            wire:click="markAsReviewed('{{ $anomaly['id'] ?? '' }}')"
        >
            {{ __('audit.actions.mark_reviewed') }}
        </button>
    </div>
</div>