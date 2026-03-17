<x-layouts.authenticated
    :title="$title ?? config('app.name', 'Tenanto')"
    :eyebrow="__('tenant.shell.eyebrow')"
    :heading="$heading ?? config('app.name', 'Tenanto')"
    :show-tenant-navigation="true"
>
    {{ $slot }}
</x-layouts.authenticated>
