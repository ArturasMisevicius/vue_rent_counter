<div class="space-y-3">
    @forelse ($events as $event)
        <div class="rounded-lg border border-slate-200 bg-white p-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm font-semibold text-slate-950">{{ $event->action?->label() ?? $event->action?->value }}</p>
                <p class="text-xs text-slate-500">
                    {{ $event->occurred_at?->locale(app()->getLocale())->translatedFormat(\App\Filament\Support\Formatting\LocalizedDateFormatter::dateTimeFormat()) }}
                </p>
            </div>

            <p class="mt-1 text-sm text-slate-600">{{ $event->description }}</p>

            @if ($event->actor)
                <p class="mt-1 text-xs text-slate-500">{{ __('admin.tenant_documents.history.actor', ['name' => $event->actor->name]) }}</p>
            @endif
        </div>
    @empty
        <p class="text-sm text-slate-600">{{ __('admin.tenant_documents.history.empty') }}</p>
    @endforelse
</div>
