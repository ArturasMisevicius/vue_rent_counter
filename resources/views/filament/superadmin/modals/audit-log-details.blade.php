{{-- Audit Log Details Modal Content --}}
<div class="space-y-6">
    {{-- Header Information --}}
    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h4 class="text-sm font-medium text-gray-900">{{ __('shared.audit.fields.basic_info') }}</h4>
                <dl class="mt-2 space-y-1">
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('shared.audit.fields.timestamp') }}:</dt>
                        <dd class="text-gray-900">{{ $auditLog->created_at->format('M j, Y H:i:s') }}</dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('shared.audit.fields.action') }}:</dt>
                        <dd>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                  style="background-color: {{ $auditLog->action->getColor() }}20; color: {{ $auditLog->action->getColor() }};">
                                {{ $auditLog->action->getLabel() }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('shared.audit.fields.shared') }}:</dt>
                        <dd class="text-gray-900">{{ $auditLog->admin?->name ?? __('shared.audit.values.system') }}</dd>
                    </div>
                </dl>
            </div>
            
            <div>
                <h4 class="text-sm font-medium text-gray-900">{{ __('shared.audit.fields.technical_info') }}</h4>
                <dl class="mt-2 space-y-1">
                    <div class="flex justify-between text-sm">
                        <dt class="text-gray-500">{{ __('shared.audit.fields.ip_address') }}:</dt>
                        <dd class="text-gray-900 font-mono">{{ $auditLog->ip_address ?? __('shared.audit.values.unknown') }}</dd>
                    </div>
                    @if($auditLog->impersonation_session_id)
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ __('shared.audit.fields.impersonation') }}:</dt>
                            <dd>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ __('shared.audit.values.impersonated') }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500">{{ __('shared.audit.fields.session_id') }}:</dt>
                            <dd class="text-gray-900 font-mono text-xs">{{ $auditLog->impersonation_session_id }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    {{-- Target Information --}}
    @if($auditLog->target_type && $auditLog->target_id)
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <h4 class="text-sm font-medium text-blue-900 mb-2">{{ __('shared.audit.fields.target_info') }}</h4>
            <dl class="space-y-1">
                <div class="flex justify-between text-sm">
                    <dt class="text-blue-700">{{ __('shared.audit.fields.target_type') }}:</dt>
                    <dd class="text-blue-900">{{ class_basename($auditLog->target_type) }}</dd>
                </div>
                <div class="flex justify-between text-sm">
                    <dt class="text-blue-700">{{ __('shared.audit.fields.target_id') }}:</dt>
                    <dd class="text-blue-900">#{{ $auditLog->target_id }}</dd>
                </div>
                @if($auditLog->tenant_id)
                    <div class="flex justify-between text-sm">
                        <dt class="text-blue-700">{{ __('shared.audit.fields.tenant_id') }}:</dt>
                        <dd class="text-blue-900">#{{ $auditLog->tenant_id }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    @endif

    {{-- Changes Information --}}
    @if($auditLog->changes && count($auditLog->changes) > 0)
        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <h4 class="text-sm font-medium text-green-900 mb-2">{{ __('shared.audit.fields.changes') }}</h4>
            <div class="bg-white rounded border border-green-200 p-3">
                <pre class="text-xs text-green-800 whitespace-pre-wrap overflow-x-auto">{{ json_encode($auditLog->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    @endif

    {{-- User Agent Information --}}
    @if($auditLog->user_agent)
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <h4 class="text-sm font-medium text-gray-900 mb-2">{{ __('shared.audit.fields.user_agent') }}</h4>
            <p class="text-xs text-gray-600 font-mono break-all">{{ $auditLog->user_agent }}</p>
        </div>
    @endif
</div>