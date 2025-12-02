<button 
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $classes()]) }}
    @if($disabled) disabled @endif
>
    @if($loading)
        <span class="loading loading-spinner"></span>
    @endif
    {{ $slot }}
</button>
