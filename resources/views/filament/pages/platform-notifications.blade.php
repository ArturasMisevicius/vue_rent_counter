<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-semibold uppercase tracking-[0.24em] text-amber-600">Platform notifications</p>
            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">Platform Notifications</h1>
            <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                Review the latest notifications addressed to the superadmin control plane.
            </p>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            @livewire(\App\Livewire\Shell\NotificationCenter::class, [], key('platform-notifications-center'))
        </div>
    </div>
</x-filament-panels::page>
