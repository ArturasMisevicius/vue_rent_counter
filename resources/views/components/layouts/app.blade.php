@props([
    'title' => config('app.name', 'Tenanto'),
    'heading' => null,
    'subtitle' => null,
    'showTenantNavigation' => false,
    'breadcrumbs' => [],
])

<x-shell.app-frame :title="$title" :show-tenant-navigation="$showTenantNavigation" :breadcrumbs="$breadcrumbs">
    @if (filled($heading) || filled($subtitle) || isset($actions))
        <x-shared.page-header :title="$heading ?? $title" :subtitle="$subtitle">
            @isset($actions)
                <x-slot:actions>
                    {{ $actions }}
                </x-slot:actions>
            @endisset
        </x-shared.page-header>
    @endif

    {{ $slot }}
</x-shell.app-frame>
