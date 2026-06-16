<x-shell.session-expiry-monitor />

@livewire(\App\Livewire\Shell\OnboardingWizard::class)

@if (config('trace-replay.trace_bar.enabled') && auth()->user()?->isSuperadmin())
    <x-trace-replay-trace-bar />
@endif
