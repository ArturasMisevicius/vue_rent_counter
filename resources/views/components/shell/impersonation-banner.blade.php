@php
    $impersonation = app(\App\Support\Auth\ImpersonationManager::class)->bannerData();
@endphp

@if ($impersonation)
    <div class="relative z-50 border-b border-amber-300/70 bg-amber-400/95 px-4 py-3 text-slate-950 shadow-sm">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
            <p class="text-sm font-semibold">
                {{ __('shell.impersonation_message', ['name' => $impersonation['name'], 'email' => $impersonation['email']]) }}
            </p>

            <form method="POST" action="{{ route('impersonation.stop') }}">
                @csrf

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-full border border-slate-950/15 bg-white/70 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-white"
                >
                    {{ __('shell.stop_impersonating') }}
                </button>
            </form>
        </div>
    </div>
@endif
