<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Organization</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $record->organization?->name ?? 'N/A' }}
            </p>
        </div>
        
        <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">User</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $record->user?->name ?? 'System' }}
                @if($record->user?->email)
                    <br><span class="text-xs">{{ $record->user->email }}</span>
                @endif
            </p>
        </div>
        
        <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Action</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->action }}</p>
        </div>
        
        <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Resource</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ class_basename($record->resource_type) }}
                @if($record->resource_id)
                    #{{ $record->resource_id }}
                @endif
            </p>
        </div>
        
        <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">IP Address</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $record->ip_address ?? 'N/A' }}</p>
        </div>
        
        <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">Timestamp</h4>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ $record->created_at->format('Y-m-d H:i:s') }}
                <br><span class="text-xs">{{ $record->created_at->diffForHumans() }}</span>
            </p>
        </div>
    </div>
    
    @if($record->metadata && !empty($record->metadata))
        <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Metadata</h4>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ json_encode($record->metadata, JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
    @endif
    
    @if($record->user_agent)
        <div>
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">User Agent</h4>
            <p class="text-xs text-gray-600 dark:text-gray-400 break-all">{{ $record->user_agent }}</p>
        </div>
    @endif
</div>