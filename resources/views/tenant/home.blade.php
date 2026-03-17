<x-layouts.app
    :title="__('dashboard.tenant_title').' · '.config('app.name', 'Tenanto')"
    :heading="__('tenant.shell.eyebrow')"
    :subtitle="__('tenant.messages.account_snapshot')"
    :show-tenant-navigation="true"
>
    <livewire:tenant.home-summary />
</x-layouts.app>
