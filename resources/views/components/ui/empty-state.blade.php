<x-filament::empty-state>
    <x-slot name="heading">
        {{ $heading }}
    </x-slot>

    @if (filled($description ?? null))
        {{ $description }}
    @endif

    @if (filled($actionLabel ?? null) && filled($actionUrl ?? null))
        <x-slot name="footer">
            <x-filament::button
                tag="a"
                :href="$actionUrl"
                icon="heroicon-m-plus"
            >
                {{ $actionLabel }}
            </x-filament::button>
        </x-slot>
    @endif
</x-filament::empty-state>
