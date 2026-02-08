{{-- Rollback History Modal --}}
<div class="space-y-6">
    {{-- Header --}}
    <div class="border-b border-gray-200 pb-4">
        <h3 class="text-lg font-medium text-gray-900">
            {{ __('dashboard.audit.rollback_history') }}
        </h3>
        <p class="mt-1 text-sm text-gray-600">
            {{ __('dashboard.audit.rollback_history_description') }}
        </p>
    </div>

    {{-- Summary Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-900">{{ __('dashboard.audit.total_rollbacks') }}</p>
                    <p class="text-2xl font-semibold text-blue-600">{{ $rollbacks->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-900">{{ __('dashboard.audit.recent_rollbacks') }}</p>
                    <p class="text-2xl font-semibold text-green-600">
                        {{ $rollbacks->where('performed_at', '>=', now()->subDays(7))->count() }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 rounded-lg p-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-yellow-900">{{ __('dashboard.audit.most_active_user') }}</p>
                    <p class="text-sm font-semibold text-yellow-600">
                        @if($rollbacks->isNotEmpty())
                            @php
                                $mostActiveUserId = $rollbacks->groupBy('performed_by')->map->count()->sortDesc()->keys()->first();
                                $mostActiveUser = $mostActiveUserId ? \App\Models\User::find($mostActiveUserId) : null;
                            @endphp
                            {{ $mostActiveUser?->name ?? __('dashboard.audit.labels.unknown_user') }}
                        @else
                            {{ __('dashboard.audit.labels.none') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Rollback History Table --}}
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
            <h4 class="text-sm font-medium text-gray-900">
                {{ __('dashboard.audit.rollback_timeline') }}
            </h4>
        </div>

        @if($rollbacks->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('dashboard.audit.no_rollbacks') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('dashboard.audit.no_rollbacks_description') }}</p>
            </div>
        @else
            <ul class="divide-y divide-gray-200">
                @foreach($rollbacks->take(20) as $rollback)
                    <li class="px-4 py-4 hover:bg-gray-50">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                {{-- Rollback Icon --}}
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 rounded-full bg-yellow-100 flex items-center justify-center">
                                        <svg class="h-4 w-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                                        </svg>
                                    </div>
                                </div>

                                {{-- Rollback Details --}}
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center space-x-2">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ class_basename($rollback['model_type'] ?? 'Unknown') }} #{{ $rollback['model_id'] ?? 'N/A' }}
                                        </p>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                            {{ __('dashboard.audit.events.rollback') }}
                                        </span>
                                    </div>
                                    
                                    <div class="mt-1 flex items-center space-x-4 text-sm text-gray-500">
                                        <span class="flex items-center">
                                            <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            @php
                                                $user = $rollback['performed_by'] ? \App\Models\User::find($rollback['performed_by']) : null;
                                            @endphp
                                            {{ $user?->name ?? __('dashboard.audit.labels.system') }}
                                        </span>
                                        
                                        <span class="flex items-center">
                                            <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            {{ \Carbon\Carbon::parse($rollback['performed_at'])->format('M j, Y H:i') }}
                                        </span>
                                    </div>

                                    @if(!empty($rollback['reason']))
                                        <p class="mt-1 text-sm text-gray-600">
                                            <span class="font-medium">{{ __('dashboard.audit.labels.reason') }}:</span>
                                            {{ $rollback['reason'] }}
                                        </p>
                                    @endif

                                    @if(!empty($rollback['fields_rolled_back']))
                                        <div class="mt-2">
                                            <p class="text-xs text-gray-500 mb-1">{{ __('dashboard.audit.labels.fields_rolled_back') }}:</p>
                                            <div class="flex flex-wrap gap-1">
                                                @foreach(array_slice($rollback['fields_rolled_back'], 0, 5) as $field)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                        {{ $field }}
                                                    </span>
                                                @endforeach
                                                @if(count($rollback['fields_rolled_back']) > 5)
                                                    <span class="text-xs text-gray-500">
                                                        +{{ count($rollback['fields_rolled_back']) - 5 }} {{ __('dashboard.audit.labels.more') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Original Change Info --}}
                            @if(!empty($rollback['original_change']))
                                <div class="flex-shrink-0 text-right">
                                    <p class="text-xs text-gray-500">{{ __('dashboard.audit.labels.original_change') }}</p>
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $rollback['original_change']['event'] ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($rollback['original_change']['changed_at'])->format('M j, Y') }}
                                    </p>
                                </div>
                            @endif
                        </div>

                        {{-- Timeline Connector (except for last item) --}}
                        @if(!$loop->last)
                            <div class="ml-4 mt-4">
                                <div class="border-l-2 border-gray-200 h-4"></div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>

            {{-- Show More Link --}}
            @if($rollbacks->count() > 20)
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200 text-center">
                    <p class="text-sm text-gray-600">
                        {{ __('dashboard.audit.showing_recent_rollbacks', ['count' => 20, 'total' => $rollbacks->count()]) }}
                    </p>
                </div>
            @endif
        @endif
    </div>

    {{-- Rollback Patterns Analysis --}}
    @if($rollbacks->isNotEmpty())
        <div class="bg-blue-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-blue-900 mb-3">
                {{ __('dashboard.audit.rollback_patterns') }}
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-blue-800">
                        <span class="font-medium">{{ __('dashboard.audit.most_common_day') }}:</span>
                        @php
                            $dayPattern = $rollbacks->groupBy(function($rollback) {
                                return \Carbon\Carbon::parse($rollback['performed_at'])->format('l');
                            })->map->count()->sortDesc();
                        @endphp
                        {{ $dayPattern->keys()->first() ?? __('dashboard.audit.labels.none') }}
                    </p>
                    
                    <p class="text-blue-800 mt-1">
                        <span class="font-medium">{{ __('dashboard.audit.average_per_week') }}:</span>
                        {{ round($rollbacks->count() / max(1, $rollbacks->pluck('performed_at')->map(fn($date) => \Carbon\Carbon::parse($date)->weekOfYear)->unique()->count()), 1) }}
                    </p>
                </div>
                
                <div>
                    <p class="text-blue-800">
                        <span class="font-medium">{{ __('dashboard.audit.most_rolled_back_type') }}:</span>
                        @php
                            $typePattern = $rollbacks->groupBy('model_type')->map->count()->sortDesc();
                        @endphp
                        {{ $typePattern->keys()->first() ? class_basename($typePattern->keys()->first()) : __('dashboard.audit.labels.none') }}
                    </p>
                    
                    <p class="text-blue-800 mt-1">
                        <span class="font-medium">{{ __('dashboard.audit.rollback_success_rate') }}:</span>
                        100% {{-- All rollbacks in history were successful --}}
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>