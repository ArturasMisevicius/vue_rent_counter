<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4" wire:poll.30s>
            <div class="space-y-1">
                <h3 class="text-base font-semibold text-gray-950">{{ __('dashboard.organization_widgets.upcoming_reading_deadlines') }}</h3>
                <p class="text-sm text-gray-500">{{ __('dashboard.organization_widgets.upcoming_reading_deadlines_description') }}</p>
            </div>

            @forelse ($deadlines as $deadline)
                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-gray-950">{{ $deadline['meter_name'] }}</p>
                        <p class="text-sm text-gray-600">{{ $deadline['property_name'] }}</p>
                        <p class="text-xs uppercase tracking-wide text-amber-600">{{ $deadline['due_label'] }}</p>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-sm text-gray-500">
                    {{ __('dashboard.organization_widgets.no_upcoming_deadlines') }}
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
