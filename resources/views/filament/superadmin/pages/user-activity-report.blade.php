<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Activity Summary --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            {{ $this->activitySummaryInfolist() }}
        </div>

        {{-- Activity Timeline Table --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="p-6 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    {{ __('superadmin.users.sections.activity_timeline') }}
                </h3>
                <p class="mt-1 text-sm text-gray-600">
                    {{ __('superadmin.users.descriptions.activity_timeline') }}
                </p>
            </div>
            
            <div class="p-6">
                {{ $this->table }}
            </div>
        </div>
    </div>
</x-filament-panels::page>