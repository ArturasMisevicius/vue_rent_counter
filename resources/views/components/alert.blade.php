@props(['type' => 'info', 'dismissible' => true])

@php
    $variantClasses = [
        'success' => 'ds-alert--success',
        'error' => 'ds-alert--error',
        'warning' => 'ds-alert--warning',
        'info' => 'ds-alert--info',
    ];

    $variant = $variantClasses[$type] ?? $variantClasses['info'];
@endphp

<div
    {{ $attributes->merge(['class' => "ds-alert {$variant}"]) }}
    @if($dismissible)
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 5000)"
    @endif
    role="{{ $type === 'error' ? 'alert' : 'status' }}"
    aria-live="polite"
>
    <div class="ds-alert__inner">
        <div class="ds-alert__icon">
            @if($type === 'success')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                </svg>
            @elseif($type === 'error')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M4.93 4.93l14.14 14.14M4.93 19.07 19.07 4.93" />
                </svg>
            @elseif($type === 'warning')
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            @else
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
            @endif
        </div>

        <div class="ds-alert__content">
            {{ $slot }}
        </div>

        @if($dismissible)
            <button @click="show = false" class="ds-alert__dismiss">
                <span class="sr-only">{{ __('app.accessibility.dismiss') }}</span>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                </svg>
            </button>
        @endif
    </div>
</div>
