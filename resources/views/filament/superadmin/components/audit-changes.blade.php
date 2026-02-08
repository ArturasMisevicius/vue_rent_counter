{{-- Audit Changes Component --}}
@if($changes && count($changes) > 0)
    <div class="space-y-4">
        @foreach($changes as $key => $value)
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-start justify-between">
                    <h5 class="text-sm font-medium text-gray-900 mb-2">
                        {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $key)) }}
                    </h5>
                </div>
                
                <div class="space-y-2">
                    @if(is_array($value))
                        {{-- Handle array values --}}
                        @if(isset($value['old']) || isset($value['new']))
                            {{-- Before/After format --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @if(isset($value['old']))
                                    <div class="bg-red-50 rounded border border-red-200 p-3">
                                        <div class="text-xs font-medium text-red-800 mb-1">{{ __('shared.audit.values.before') }}</div>
                                        <div class="text-sm text-red-700">
                                            @if(is_array($value['old']))
                                                <pre class="text-xs whitespace-pre-wrap">{{ json_encode($value['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @else
                                                {{ $value['old'] ?? __('shared.audit.values.empty') }}
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                
                                @if(isset($value['new']))
                                    <div class="bg-green-50 rounded border border-green-200 p-3">
                                        <div class="text-xs font-medium text-green-800 mb-1">{{ __('shared.audit.values.after') }}</div>
                                        <div class="text-sm text-green-700">
                                            @if(is_array($value['new']))
                                                <pre class="text-xs whitespace-pre-wrap">{{ json_encode($value['new'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                            @else
                                                {{ $value['new'] ?? __('shared.audit.values.empty') }}
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            {{-- Generic array display --}}
                            <div class="bg-blue-50 rounded border border-blue-200 p-3">
                                <pre class="text-xs text-blue-800 whitespace-pre-wrap">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        @endif
                    @else
                        {{-- Handle scalar values --}}
                        <div class="bg-blue-50 rounded border border-blue-200 p-3">
                            <div class="text-sm text-blue-700">
                                {{ $value ?? __('shared.audit.values.empty') }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-8 text-gray-500">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <p class="mt-2 text-sm">{{ __('shared.audit.values.no_changes') }}</p>
    </div>
@endif