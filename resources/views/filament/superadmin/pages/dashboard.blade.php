<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
        <!-- System Overview Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                {{ __('app.labels.system_overview') }}
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ __('app.labels.total_organizations') }}</span>
                    <span class="text-sm font-medium">{{ $this->getTotalOrganizations() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ __('app.labels.active_subscriptions') }}</span>
                    <span class="text-sm font-medium">{{ $this->getActiveSubscriptions() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">{{ __('app.labels.total_users') }}</span>
                    <span class="text-sm font-medium">{{ $this->getTotalUsers() }}</span>
                </div>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                {{ __('app.labels.quick_actions') }}
            </h3>
            <div class="space-y-3">
                <button type="button" 
                        class="block w-full text-center bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 transition-colors">
                    {{ __('app.actions.create_organization') }}
                </button>
                <button type="button" 
                        class="block w-full text-center bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 transition-colors">
                    {{ __('app.actions.manage_subscriptions') }}
                </button>
                <button type="button" 
                        class="block w-full text-center bg-purple-600 text-white py-2 px-4 rounded-md hover:bg-purple-700 transition-colors">
                    {{ __('app.actions.manage_users') }}
                </button>
            </div>
        </div>

        <!-- System Health Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                {{ __('app.labels.system_health') }}
            </h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">{{ __('app.labels.database_status') }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ __('app.status.healthy') }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">{{ __('app.labels.cache_status') }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ __('app.status.active') }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">{{ __('app.labels.queue_status') }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ __('app.status.running') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>