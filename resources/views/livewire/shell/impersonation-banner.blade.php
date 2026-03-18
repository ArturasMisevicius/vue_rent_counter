<div>
    @if ($impersonation)
        <div class="mb-4 rounded-[1.5rem] border border-amber-200 bg-amber-50/95 px-5 py-4 text-amber-950 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="space-y-1">
                    <p class="text-sm font-semibold uppercase tracking-[0.22em] text-amber-700">{{ __('shell.impersonation.eyebrow') }}</p>
                    <p class="text-sm font-semibold">{{ __('shell.impersonation.heading') }}</p>
                    <p class="text-sm text-amber-800">
                        {{ $impersonation['name'] }}
                        <span class="text-amber-700">·</span>
                        {{ $impersonation['email'] }}
                    </p>
                </div>

                <button
                    type="button"
                    wire:click="stopImpersonating"
                    class="inline-flex items-center justify-center rounded-2xl border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-900 transition hover:border-amber-400 hover:bg-amber-100"
                >
                    {{ __('shell.impersonation.actions.stop') }}
                </button>
            </div>
        </div>
    @endif
</div>
