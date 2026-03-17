<div class="relative min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top,rgba(248,205,116,0.22),transparent_22%),linear-gradient(180deg,#fff8eb_0%,#f8f4ea_44%,#eef7f5_100%)] text-slate-950">
    <div class="absolute inset-x-0 top-0 h-72 bg-[radial-gradient(circle_at_top_left,rgba(62,197,173,0.18),transparent_38%)]"></div>
    <div class="absolute -left-20 top-28 size-56 rounded-full bg-brand-warm/15 blur-3xl"></div>
    <div class="absolute -right-16 top-20 size-64 rounded-full bg-brand-mint/18 blur-3xl"></div>

    @livewire(\App\Livewire\Shell\Topbar::class)

    <div class="relative mx-auto min-h-screen max-w-6xl px-4 pb-28 pt-28 sm:px-6">
        {{ $slot }}
    </div>

    @if ($showTenantNavigation)
        @livewire(\App\Livewire\Shell\TenantBottomNavigation::class)
    @endif
</div>
