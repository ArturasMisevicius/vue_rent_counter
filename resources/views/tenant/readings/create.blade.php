<x-layouts.tenant
    :title="__('tenant.pages.readings.title').' · '.config('app.name', 'Tenanto')"
    :heading="__('tenant.pages.readings.heading')"
    :breadcrumbs="[
        ['label' => __('tenant.navigation.home'), 'url' => route('tenant.home')],
        ['label' => __('tenant.pages.readings.heading')],
    ]"
>
    <livewire:tenant.submit-reading-page />
</x-layouts.tenant>
