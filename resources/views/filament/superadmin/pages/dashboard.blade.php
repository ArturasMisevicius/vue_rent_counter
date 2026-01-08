<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Superadmin Panel - Minimal Mode
                </h3>
                <div class="mt-2 max-w-xl text-sm text-gray-500">
                    <p>The superadmin panel is running in minimal mode to prevent timeout issues.</p>
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
                                    Panel is working!
                                </h3>
                                <div class="mt-2 text-sm text-green-700">
                                    <p>Authentication is working and the panel is accessible.</p>
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
                    System Status
                </h3>
                <div class="mt-5">
                    <dl class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <div class="px-4 py-5 bg-gray-50 shadow rounded-lg overflow-hidden sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Panel Status
                            </dt>
                            <dd class="mt-1 text-3xl font-semibold text-green-600">
                                Active
                            </dd>
                        </div>
                        <div class="px-4 py-5 bg-gray-50 shadow rounded-lg overflow-hidden sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Authentication
                            </dt>
                            <dd class="mt-1 text-3xl font-semibold text-green-600">
                                Working
                            </dd>
                        </div>
                        <div class="px-4 py-5 bg-gray-50 shadow rounded-lg overflow-hidden sm:p-6">
                            <dt class="text-sm font-medium text-gray-500 truncate">
                                Mode
                            </dt>
                            <dd class="mt-1 text-3xl font-semibold text-blue-600">
                                Minimal
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Next Steps
                </h3>
                <div class="mt-2 text-sm text-gray-500">
                    <ul class="list-disc list-inside space-y-1">
                        <li>Panel is now accessible without timeouts</li>
                        <li>Gradually re-enable features in SuperadminPanelProvider</li>
                        <li>Monitor server logs for any issues</li>
                        <li>Test each feature as you re-enable it</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>