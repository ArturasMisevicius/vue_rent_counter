@props([])

<div
    {{ $attributes
        ->merge(['data-tenant-layout-section' => 'split'])
        ->class('flex flex-col gap-5 xl:flex-row xl:items-start') }}
>
    {{ $slot }}
</div>
