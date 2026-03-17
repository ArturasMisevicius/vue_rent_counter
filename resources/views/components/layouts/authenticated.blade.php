@props([
    'title' => null,
    'eyebrow' => null,
    'heading' => null,
    'showTenantNavigation' => false,
    'breadcrumbs' => [],
])

<x-shell.app-frame
    :title="$title"
    :eyebrow="$eyebrow"
    :heading="$heading"
    :show-tenant-navigation="$showTenantNavigation"
    :breadcrumbs="$breadcrumbs"
>
    {{ $slot }}
</x-shell.app-frame>
