<x-layouts.tenant
    :title="__('tenant.pages.readings.title').' · '.config('app.name', 'Tenanto')"
    :breadcrumbs="$breadcrumbs"
>
    @livewire(\App\Livewire\Tenant\SubmitReadingPage::class)
</x-layouts.tenant>
