<x-filament-panels::page>
    <x-tenant.page>
        <x-shared.page-header
            icon="heroicon-m-user-circle"
            :eyebrow="__('shell.profile.eyebrow')"
            :title="__('shell.profile.title')"
            :subtitle="__('shell.profile.description')"
        />

        @include('filament.pages.partials.account-profile-sections')
    </x-tenant.page>
</x-filament-panels::page>
