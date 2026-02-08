{{-- Rollback Details Modal --}}
<div class="space-y-6">
    {{-- Rollback Summary --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
            {{ __('dashboard.audit.rollback_summary') }}
        </h3>
        
        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('dashboard.audit.labels.performed_at') }}:
                </span>
                <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                    {{ \Carbon\Carbon::parse($rollback['performed_at'])->format('M j, Y H:i:s') }}
                </span>
            </div>
            
            <div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('dashboard.audit.labels.performed_by') }}:
                </span>
                <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                    @if($rollback['performed_by'])
                        @php
                            $user = \App\Models\User::find($rollback['performed_by']);
                        @endphp
                        {{ $user ? $user->name : __('dashboard.audit.labels.unknown_user') }}
                    @else
                        {{ __('dashboard.audit.labels.system') }}
                    @endif
                </span>
            </div>
            
            <div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('dashboard.audit.labels.model_type') }}:
                </span>
                <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                    {{ class_basename($rollback['model_type'] ?? 'Unknown') }}
                </span>
            </div>
            
            <div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('dashboard.audit.labels.model_id') }}:
                </span>
                <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                    #{{ $rollback['model_id'] ?? 'N/A' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Rollback Reason --}}
    @if(isset($rollback['reason']) && $rollback['reason'])
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                {{ __('dashboard.audit.labels.rollback_reason') }}
            </h3>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                <p class="text-sm text-yellow-800 dark:text-yellow-200">{{ $rollback['reason'] }}</p>
            </div>
        </div>
    @endif

    {{-- Fields Rolled Back --}}
    @if(isset($rollback['fields_rolled_back']) && count($rollback['fields_rolled_back']) > 0)
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                {{ __('dashboard.audit.fields_rolled_back') }}
            </h3>
            
            <div class="space-y-3">
                @foreach($rollback['fields_rolled_back'] as $field)
                    <div class="flex items-center p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm text-blue-800 dark:text-blue-200">
                            {{ ucfirst(str_replace('_', ' ', $field)) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Original Change Information --}}
    @if(isset($rollback['original_change']) && $rollback['original_change'])
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                {{ __('dashboard.audit.original_change_info') }}
            </h3>
            
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ __('dashboard.audit.labels.event') }}:
                        </span>
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @switch($rollback['original_change']['event'] ?? '')
                                @case('created')
                                    bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                                    @break
                                @case('updated')
                                    bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100
                                    @break
                                @case('deleted')
                                    bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100
                                    @break
                                @default
                                    bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100
                            @endswitch
                        ">
                            {{ ucfirst($rollback['original_change']['event'] ?? 'Unknown') }}
                        </span>
                    </div>
                    
                    <div>
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                            {{ __('dashboard.audit.labels.changed_at') }}:
                        </span>
                        <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                            {{ \Carbon\Carbon::parse($rollback['original_change']['changed_at'])->format('M j, Y H:i:s') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Impact Analysis --}}
    @if($impactAnalysis)
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                {{ __('dashboard.audit.impact_analysis') }}
            </h3>
            
            <div class="space-y-4">
                {{-- Affected Systems --}}
                @if(isset($impactAnalysis['affected_systems']) && count($impactAnalysis['affected_systems']) > 0)
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('dashboard.audit.affected_systems') }}
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($impactAnalysis['affected_systems'] as $system)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100">
                                    {{ $system }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Warnings --}}
                @if(isset($impactAnalysis['warnings']) && count($impactAnalysis['warnings']) > 0)
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('dashboard.audit.labels.warnings') }}
                        </h4>
                        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                            <ul class="text-sm text-yellow-800 dark:text-yellow-200 list-disc list-inside space-y-1">
                                @foreach($impactAnalysis['warnings'] as $warning)
                                    <li>{{ $warning }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                {{-- Mitigation Steps --}}
                @if(isset($impactAnalysis['mitigation_steps']) && count($impactAnalysis['mitigation_steps']) > 0)
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('dashboard.audit.mitigation_steps') }}
                        </h4>
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                            <ol class="text-sm text-blue-800 dark:text-blue-200 list-decimal list-inside space-y-1">
                                @foreach($impactAnalysis['mitigation_steps'] as $step)
                                    <li>{{ $step }}</li>
                                @endforeach
                            </ol>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Rollback Status --}}
    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span class="text-sm font-medium text-green-800 dark:text-green-200">
                {{ __('dashboard.audit.rollback_completed_successfully') }}
            </span>
        </div>
    </div>
</div>