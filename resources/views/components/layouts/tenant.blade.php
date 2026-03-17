@props([
    'title' => null,
    'heading' => null,
    'breadcrumbs' => [],
])

<x-layouts.authenticated
    :title="$title ?? config('app.name', 'Tenanto')"
    :eyebrow="__('tenant.shell.eyebrow')"
    :heading="$heading ?? config('app.name', 'Tenanto')"
    :show-tenant-navigation="true"
    :breadcrumbs="$breadcrumbs"
>
    {{ $slot }}
</x-layouts.authenticated>
