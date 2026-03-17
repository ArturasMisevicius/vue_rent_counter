<x-layouts.tenant :title="__('dashboard.tenant_title').' · '.config('app.name', 'Tenanto')">
    @livewire(\App\Livewire\Tenant\HomeSummary::class)
</x-layouts.tenant>
