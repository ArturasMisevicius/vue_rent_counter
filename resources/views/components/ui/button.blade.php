<button 
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $classes()]) }}
    @if($disabled) disabled @endif
>
    @if($loading)
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"></circle>
            <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
        </svg>
    @endif
    {{ $slot }}
</button>
