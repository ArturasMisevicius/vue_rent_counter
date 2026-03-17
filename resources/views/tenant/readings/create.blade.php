<x-layouts.app
    :title="__('tenant.pages.readings.title').' · '.config('app.name', 'Tenanto')"
    :heading="__('tenant.pages.readings.heading')"
    :subtitle="__('tenant.pages.readings.description')"
    :show-tenant-navigation="true"
    :breadcrumbs="[
        ['label' => __('tenant.navigation.home'), 'url' => route('tenant.home')],
        ['label' => __('tenant.pages.readings.heading')],
    ]"
>
    <livewire:tenant.submit-reading-page />
</x-layouts.app>
