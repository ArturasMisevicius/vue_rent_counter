{{-- Change Details Modal --}}
<div class="space-y-6">
    {{-- Change Summary --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
            {{ __('dashboard.audit.change_summary') }}
        </h3>
        
        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('dashboard.audit.labels.model_type') }}:
                </span>
                <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                    {{ class_basename($change->modelType) }}
                </span>
            </div>
            
            <div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('dashboard.audit.labels.event') }}:
                </span>
                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    @switch($change->event)
                        @case('created')
                            bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                            @break
                        @case('updated')
                            bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100
                            @break
                        @case('deleted')
                            bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100
                            @break
                        @case('rollback')
                            bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100
                            @break
                        @default
                            bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100
                    @endswitch
                ">
                    {{ ucfirst($change->event) }}
                </span>
            </div>
            
            <div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('dashboard.audit.labels.changed_at') }}:
                </span>
                <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                    {{ $change->changedAt->format('M j, Y H:i:s') }}
                </span>
            </div>
            
            <div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ __('dashboard.audit.labels.user') }}:
                </span>
                <span class="ml-2 text-sm text-gray-900 dark:text-gray-100">
                    @if($change->userId)
                        @php
                            $user = \App\Models\User::find($change->userId);
                        @endphp
                        {{ $user ? $user->name : __('dashboard.audit.labels.unknown_user') }}
                    @else
                        {{ __('dashboard.audit.labels.system') }}
                    @endif
                </span>
            </div>
        </div>
    </div>

    {{-- Changed Fields --}}
    @if($change->newValues && count($change->newValues) > 0)
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                {{ __('dashboard.audit.changed_fields') }}
            </h3>
            
            <div class="space-y-3">
                @foreach($change->newValues as $field => $newValue)
                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-medium text-gray-900 dark:text-gray-100">
                                {{ ucfirst(str_replace('_', ' ', $field)) }}
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    {{ __('dashboard.audit.labels.old_value') }}
                                </span>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100 bg-red-50 dark:bg-red-900/20 p-2 rounded">
                                    @if(isset($change->oldValues[$field]))
                                        @if(is_array($change->oldValues[$field]))
                                            <pre class="whitespace-pre-wrap">{{ json_encode($change->oldValues[$field], JSON_PRETTY_PRINT) }}</pre>
                                        @else
                                            {{ $change->oldValues[$field] ?: __('dashboard.audit.labels.empty') }}
                                        @endif
                                    @else
                                        <em class="text-gray-500">{{ __('dashboard.audit.labels.not_set') }}</em>
                                    @endif
                                </div>
                            </div>
                            
                            <div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                                    {{ __('dashboard.audit.labels.new_value') }}
                                </span>
                                <div class="mt-1 text-sm text-gray-900 dark:text-gray-100 bg-green-50 dark:bg-green-900/20 p-2 rounded">
                                    @if(is_array($newValue))
                                        <pre class="whitespace-pre-wrap">{{ json_encode($newValue, JSON_PRETTY_PRINT) }}</pre>
                                    @else
                                        {{ $newValue ?: __('dashboard.audit.labels.empty') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Rollback Information --}}
    @if($rollbackData && $rollbackData['can_rollback'])
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-100 mb-3">
                {{ __('dashboard.audit.rollback_information') }}
            </h3>
            
            <div class="space-y-2">
                <div class="flex items-center text-sm text-blue-800 dark:text-blue-200">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                    {{ __('dashboard.audit.rollback_available') }}
                </div>
                
                @if(isset($rollbackData['warnings']) && count($rollbackData['warnings']) > 0)
                    <div class="mt-3">
                        <span class="text-sm font-medium text-blue-800 dark:text-blue-200">
                            {{ __('dashboard.audit.labels.warnings') }}:
                        </span>
                        <ul class="mt-1 text-sm text-blue-700 dark:text-blue-300 list-disc list-inside">
                            @foreach($rollbackData['warnings'] as $warning)
                                <li>{{ $warning }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Notes --}}
    @if($change->notes)
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">
                {{ __('dashboard.audit.labels.notes') }}
            </h3>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $change->notes }}</p>
            </div>
        </div>
    @endif
</div>