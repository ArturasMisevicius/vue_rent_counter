<div class="space-y-4">
    <div>
        <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('filament.widgets.activity_details.title') }}</h3>
        <dl class="mt-2 divide-y divide-gray-200 dark:divide-gray-700">
            <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('filament.widgets.activity_details.action') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                        @if($record->action === 'created') bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20
                        @elseif($record->action === 'updated') bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-500/10 dark:text-blue-400 dark:ring-blue-500/20
                        @elseif($record->action === 'deleted') bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20
                        @else bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-500/10 dark:text-gray-400 dark:ring-gray-500/20
                        @endif">
                        {{ $record->action }}
                    </span>
                </dd>
            </div>
            <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('filament.widgets.activity_details.resource') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                    {{ class_basename($record->resource_type) }} #{{ $record->resource_id }}
                </dd>
            </div>
            <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('filament.widgets.activity_details.user') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                    {{ $record->user?->name ?? __('filament.widgets.activity_details.system') }}
                </dd>
            </div>
            <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('filament.widgets.activity_details.organization') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                    {{ $record->organization?->name ?? __('filament.widgets.activity_details.not_available') }}
                </dd>
            </div>
            <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('filament.widgets.activity_details.timestamp') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                    {{ $record->created_at->format('M d, Y H:i:s') }}
                </dd>
            </div>
            <div class="py-2 sm:grid sm:grid-cols-3 sm:gap-4">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('filament.widgets.activity_details.ip_address') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 sm:col-span-2 sm:mt-0">
                    {{ $record->ip_address ?? __('filament.widgets.activity_details.not_available') }}
                </dd>
            </div>
        </dl>
    </div>

    @if($record->before_data || $record->after_data)
        <div>
            <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('filament.widgets.activity_details.changes') }}</h3>
            <div class="mt-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
                @if($record->before_data)
                    <div>
                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('filament.widgets.activity_details.before') }}</h4>
                        <pre class="text-xs bg-gray-50 dark:bg-gray-800 rounded p-2 overflow-auto max-h-48">{{ json_encode($record->before_data, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
                @if($record->after_data)
                    <div>
                        <h4 class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('filament.widgets.activity_details.after') }}</h4>
                        <pre class="text-xs bg-gray-50 dark:bg-gray-800 rounded p-2 overflow-auto max-h-48">{{ json_encode($record->after_data, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
