<x-filament-panels::page>
    {{-- Welcome Message --}}
    <div class="mb-6 bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                {{ __('app.dashboard.welcome_tenant') }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('app.dashboard.tenant_description') }}
            </p>
        </div>
    </div>
</x-filament-panels::page>