<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4" wire:poll.30s>
            <div class="space-y-1">
                <h3 class="text-base font-semibold text-gray-950">{{ __('dashboard.organization_widgets.recent_invoices') }}</h3>
                <p class="text-sm text-gray-500">{{ __('dashboard.organization_widgets.recent_invoices_description') }}</p>
            </div>

            @forelse ($invoices as $invoice)
                <div class="rounded-2xl border border-gray-200 bg-white px-4 py-3">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold text-gray-950">{{ $invoice['number'] }}</p>
                            <p class="text-sm text-gray-600">{{ $invoice['tenant'] }}</p>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ $invoice['property'] }}</p>
                        </div>

                        <div class="text-right">
                            <p class="text-sm font-semibold text-gray-950">{{ $invoice['amount'] }}</p>
                            <p class="text-xs uppercase tracking-wide text-gray-500">{{ $invoice['status'] }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-4 py-6 text-sm text-gray-500">
                    {{ __('dashboard.organization_widgets.no_recent_invoices') }}
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
