{{-- Global Search Header Component for Filament Admin Panel --}}
@if(auth()->check() && auth()->user()->role === \App\Enums\UserRole::SUPERADMIN)
    <div class="fi-global-search-header flex items-center justify-between w-full">
        {{-- Left side: Logo/Brand (if needed) --}}
        <div class="flex-shrink-0">
            {{-- This space can be used for additional branding or navigation --}}
        </div>

        {{-- Center: Global Search --}}
        <div class="flex-1 max-w-lg mx-4">
            @livewire('global-search-component')
        </div>

        {{-- Right side: User menu and other actions --}}
        <div class="flex-shrink-0">
            {{-- This space is reserved for user menu and other header actions --}}
        </div>
    </div>
@endif