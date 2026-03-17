@props([
    'title' => null,
    'breadcrumbs' => [],
])

<x-layouts.authenticated
    :title="$title ?? config('app.name', 'Tenanto')"
    :breadcrumbs="$breadcrumbs"
    :show-tenant-navigation="true"
>
    {{ $slot }}
</x-layouts.authenticated>
