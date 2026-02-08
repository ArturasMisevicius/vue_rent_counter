{{-- User Activity Summary Component --}}
<div class="space-y-4">
    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-blue-900">{{ __('shared.users.fields.total_sessions') }}</p>
                    <p class="text-2xl font-semibold text-blue-600">{{ $activityReport->totalSessions }}</p>
                </div>
            </div>
        </div>

        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-yellow-900">{{ __('shared.users.fields.audit_entries') }}</p>
                    <p class="text-2xl font-semibold text-yellow-600">{{ $activityReport->auditLogEntries }}</p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-900">{{ __('shared.users.fields.last_activity') }}</p>
                    <p class="text-sm font-semibold text-green-600">
                        {{ $activityReport->lastLoginAt ? $activityReport->lastLoginAt->diffForHumans() : __('shared.users.values.never') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Recent Sessions Preview --}}
    @if(count($activityReport->recentSessions) > 0)
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <h4 class="text-sm font-medium text-gray-900 mb-3">{{ __('shared.users.sections.recent_sessions') }}</h4>
            <div class="space-y-2">
                @foreach(array_slice($activityReport->recentSessions, 0, 5) as $session)
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                            <span class="text-gray-600">
                                {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->format('M j, Y H:i') }}
                            </span>
                        </div>
                        <span class="text-gray-500 text-xs">
                            {{ \Illuminate\Support\Str::limit($session->ip_address ?? 'Unknown IP', 15) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Organization Activity Preview --}}
    @if(count($activityReport->organizationActivity) > 0)
        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
            <h4 class="text-sm font-medium text-purple-900 mb-3">{{ __('shared.users.sections.organization_activity') }}</h4>
            <div class="space-y-2">
                @foreach(array_slice($activityReport->organizationActivity, 0, 3) as $activity)
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center space-x-2">
                            <div class="w-2 h-2 bg-purple-400 rounded-full"></div>
                            <span class="text-purple-700">
                                {{ $activity['description'] ?? __('shared.users.values.activity_logged') }}
                            </span>
                        </div>
                        <span class="text-purple-500 text-xs">
                            {{ \Carbon\Carbon::parse($activity['created_at'])->diffForHumans() }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>