<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @php
                $totalNotifications = \App\Models\PlatformNotification::count();
                $sentNotifications = \App\Models\PlatformNotification::sent()->count();
                $scheduledNotifications = \App\Models\PlatformNotification::scheduled()->count();
                $failedNotifications = \App\Models\PlatformNotification::failed()->count();
            @endphp

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-document-text class="h-8 w-8 text-gray-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Notifications</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($totalNotifications) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-check-circle class="h-8 w-8 text-green-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sent</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($sentNotifications) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-clock class="h-8 w-8 text-yellow-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Scheduled</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($scheduledNotifications) }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <x-heroicon-o-x-circle class="h-8 w-8 text-red-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Failed</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ number_format($failedNotifications) }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Table --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>