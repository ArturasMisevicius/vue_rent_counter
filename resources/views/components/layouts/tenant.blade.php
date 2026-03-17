@props([
    'title' => null,
])

<x-layouts.authenticated
    :title="$title ?? config('app.name', 'Tenanto')"
    :show-tenant-navigation="true"
>
    {{ $slot }}
</x-layouts.authenticated>
