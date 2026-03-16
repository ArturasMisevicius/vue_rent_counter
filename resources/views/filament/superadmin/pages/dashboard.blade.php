<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ __('app.superadmin_status.minimal_mode.title') }}
                </h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500">
                    <p>{{ __('app.superadmin_status.minimal_mode.description') }}</p>
                </div>
                <div class="mt-5">
                    <div class="rounded-md bg-green-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800">
                                    {{ __('app.superadmin_status.minimal_mode.success_title') }}
                                </h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>{{ __('app.superadmin_status.minimal_mode.success_body') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ __('app.superadmin_status.system_status') }}
                </h3>
                <div class="mt-5">
                    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <div class="px-4 py-5 bg-gray-50 shadow rounded-lg overflow-hidden sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                {{ __('app.superadmin_status.minimal_mode.panel_status') }}
                            </dt>
                            <dd class="mt-1 text-3xl font-semibold text-green-600">
                                {{ __('app.superadmin_status.values.active') }}
                            </dd>
                        </div>
                        <div class="px-4 py-5 bg-gray-50 shadow rounded-lg overflow-hidden sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                {{ __('app.superadmin_status.minimal_mode.authentication') }}
                            </dt>
                            <dd class="mt-1 text-3xl font-semibold text-green-600">
                                {{ __('app.superadmin_status.values.working') }}
                            </dd>
                        </div>
                        <div class="px-4 py-5 bg-gray-50 shadow rounded-lg overflow-hidden sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                {{ __('app.superadmin_status.minimal_mode.mode') }}
                            </dt>
                            <dd class="mt-1 text-3xl font-semibold text-blue-600">
                                {{ __('app.superadmin_status.values.minimal') }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    {{ __('app.superadmin_status.next_steps') }}
                </h3>
                <div class="mt-2 text-sm text-gray-500">
                    <ul class="list-disc list-inside space-y-1">
                        <li>{{ __('app.superadmin_status.minimal_mode.next_steps.accessible') }}</li>
                        <li>{{ __('app.superadmin_status.minimal_mode.next_steps.reenable') }}</li>
                        <li>{{ __('app.superadmin_status.minimal_mode.next_steps.logs') }}</li>
                        <li>{{ __('app.superadmin_status.minimal_mode.next_steps.test') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
