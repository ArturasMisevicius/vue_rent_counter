<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Profile Information --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    {{ __('app.profile.personal_information') }}
                </h3>
                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('app.labels.name') }}
                        </label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $user?->name ?? __('app.labels.not_available') }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('app.labels.email') }}
                        </label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $user?->email ?? __('app.labels.not_available') }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('app.labels.role') }}
                        </label>
                        <div class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $user?->role?->getLabel() ?? __('app.labels.not_available') }}
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('app.labels.status') }}
                        </label>
                        <div class="mt-1">
                            @if($user?->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    {{ __('app.labels.active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    {{ __('app.labels.inactive') }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Property Assignment --}}
        @if($property)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                        {{ __('app.profile.assigned_property') }}
                    </h3>
                    <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('app.labels.property_name') }}
                            </label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $property->name }}
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('app.labels.building') }}
                            </label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $property->building?->name ?? __('app.labels.not_available') }}
                            </div>
                        </div>
                        
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('app.labels.address') }}
                            </label>
                            <div class="mt-1 text-sm text-gray-900 dark:text-white">
                                {{ $property->address ?? $property->building?->address ?? __('app.labels.not_available') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            {{ __('app.profile.no_property_assigned') }}
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <p>{{ __('app.profile.contact_administrator') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Account Information --}}
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                    {{ __('app.profile.account_information') }}
                </h3>
                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('app.labels.member_since') }}
                        </label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $user?->created_at?->format('F j, Y') ?? __('app.labels.not_available') }}
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('app.labels.last_login') }}
                        </label>
                        <div class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $user?->last_login_at?->format('F j, Y g:i A') ?? __('app.labels.never') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>