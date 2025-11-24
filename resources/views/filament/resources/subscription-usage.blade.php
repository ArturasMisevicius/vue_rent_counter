<div class="space-y-6">
    @php
        $user = $record->user;
        $propertiesCount = $user->properties()->count();
        $tenantsCount = \App\Models\Tenant::whereHas('properties', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count();
        
        $propertiesPercentage = $record->max_properties > 0 ? round(($propertiesCount / $record->max_properties) * 100, 1) : 0;
        $tenantsPercentage = $record->max_tenants > 0 ? round(($tenantsCount / $record->max_tenants) * 100, 1) : 0;
    @endphp

    <div>
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('filament.resources.subscription_usage.usage_title') }}</h3>
        
        <!-- Properties Usage -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('filament.resources.subscription_usage.properties') }}</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $propertiesCount }} / {{ $record->max_properties }}
                    <span class="text-xs">({{ $propertiesPercentage }}%)</span>
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                <div class="h-2.5 rounded-full transition-all duration-300
                    @if($propertiesPercentage >= 90) bg-red-600
                    @elseif($propertiesPercentage >= 75) bg-yellow-600
                    @else bg-green-600
                    @endif"
                    style="width: {{ min($propertiesPercentage, 100) }}%">
                </div>
            </div>
            @if($propertiesPercentage >= 90)
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                    {{ __('filament.resources.subscription_usage.approaching_limit') }}
                </p>
            @endif
        </div>

        <!-- Tenants Usage -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('filament.resources.subscription_usage.tenants') }}</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $tenantsCount }} / {{ $record->max_tenants }}
                    <span class="text-xs">({{ $tenantsPercentage }}%)</span>
                </span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                <div class="h-2.5 rounded-full transition-all duration-300
                    @if($tenantsPercentage >= 90) bg-red-600
                    @elseif($tenantsPercentage >= 75) bg-yellow-600
                    @else bg-green-600
                    @endif"
                    style="width: {{ min($tenantsPercentage, 100) }}%">
                </div>
            </div>
            @if($tenantsPercentage >= 90)
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                    {{ __('filament.resources.subscription_usage.approaching_limit') }}
                </p>
            @endif
        </div>
    </div>

    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">{{ __('filament.resources.subscription_usage.subscription_details') }}</h3>
        <dl class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('filament.resources.subscription_usage.plan_type') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ enum_label($record->plan_type, \App\Enums\SubscriptionPlanType::class) }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('filament.resources.subscription_usage.status') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                        @if($record->status === \App\Enums\SubscriptionStatus::ACTIVE->value) bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20
                        @elseif($record->status === \App\Enums\SubscriptionStatus::EXPIRED->value) bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-500/10 dark:text-red-400 dark:ring-red-500/20
                        @elseif($record->status === \App\Enums\SubscriptionStatus::SUSPENDED->value) bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-400 dark:ring-yellow-500/20
                        @else bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-500/10 dark:text-gray-400 dark:ring-gray-500/20
                        @endif">
                        {{ enum_label($record->status, \App\Enums\SubscriptionStatus::class) }}
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('filament.resources.subscription_usage.start_date') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $record->starts_at->format('M d, Y') }}
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('filament.resources.subscription_usage.expiry_date') }}</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                    {{ $record->expires_at->format('M d, Y') }}
                    @if($record->daysUntilExpiry() > 0)
                        <span class="text-xs text-gray-500">{{ __('filament.resources.subscription_usage.days_left', ['days' => $record->daysUntilExpiry()]) }}</span>
                    @elseif($record->daysUntilExpiry() === 0)
                        <span class="text-xs text-yellow-600">{{ __('filament.resources.subscription_usage.expires_today') }}</span>
                    @else
                        <span class="text-xs text-red-600">{{ __('filament.resources.subscription_usage.expired_days_ago', ['days' => abs($record->daysUntilExpiry())]) }}</span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>

    @if($propertiesPercentage >= 80 || $tenantsPercentage >= 80)
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                        {{ __('filament.resources.subscription_usage.limit_warning_title') }}
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                        <p>{{ __('filament.resources.subscription_usage.limit_warning_body') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
