<div class="p-6">
    <h1 class="text-2xl font-bold mb-4">{{ __('app.superadmin_status.emergency_mode.title') }}</h1>
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
        <strong>{{ __('app.superadmin_status.emergency_mode.notice_title') }}</strong> {{ __('app.superadmin_status.emergency_mode.notice_body') }}
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white p-4 rounded shadow">
            <h2 class="font-semibold mb-2">{{ __('app.superadmin_status.system_status') }}</h2>
            <p class="text-green-600">{{ __('app.superadmin_status.emergency_mode.status.panel_accessible') }}</p>
            <p class="text-green-600">{{ __('app.superadmin_status.emergency_mode.status.authentication') }}</p>
            <p class="text-green-600">{{ __('app.superadmin_status.emergency_mode.status.database') }}</p>
        </div>
        <div class="bg-white p-4 rounded shadow">
            <h2 class="font-semibold mb-2">{{ __('app.superadmin_status.next_steps') }}</h2>
            <ul class="text-sm space-y-1">
                <li>{{ __('app.superadmin_status.emergency_mode.next_steps.reenable') }}</li>
                <li>{{ __('app.superadmin_status.emergency_mode.next_steps.performance') }}</li>
                <li>{{ __('app.superadmin_status.emergency_mode.next_steps.logs') }}</li>
            </ul>
        </div>
    </div>
</div>
