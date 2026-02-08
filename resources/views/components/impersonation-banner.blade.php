@props(['impersonationService'])

@if($impersonationService->isImpersonating())
<div id="impersonation-banner" class="impersonation-banner bg-yellow-500 text-white px-4 py-3 shadow-lg sticky top-0 z-50" role="alert">
    <span class="sr-only">You are currently impersonating</span>
    <div class="container mx-auto flex items-center justify-between">
        <div class="flex items-center space-x-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <p class="font-bold">
                    {{ __('app.impersonation.mode_active') }}
                </p>
                <p class="text-sm">
                    {{ __('app.impersonation.you_are_impersonating') }} <strong>{{ $impersonationService->getTargetUser()?->name }}</strong> ({{ $impersonationService->getTargetUser()?->email }})
                    @if(($impersonationService->getImpersonationData()['reason'] ?? null))
                        - {{ __('app.impersonation.reason') }}: {{ $impersonationService->getImpersonationData()['reason'] }}
                    @endif
                </p>
                <p class="text-xs mt-1">
                    {{ __('app.impersonation.started_at') }}: {{ \Carbon\Carbon::parse($impersonationService->getImpersonationData()['started_at'] ?? now())->format('Y-m-d H:i:s') }}
                    {{ __('app.impersonation.by') }} {{ $impersonationService->getSuperadmin()?->name }}
                </p>
            </div>
        </div>
        <form method="POST" action="{{ route('superadmin.impersonation.end') }}">
            @csrf
            <button type="submit" class="bg-white text-yellow-600 px-4 py-2 rounded font-semibold hover:bg-yellow-50 transition">
                {{ __('app.impersonation.end') }}
            </button>
        </form>
    </div>
</div>
@endif
