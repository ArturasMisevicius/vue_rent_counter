@props(['type' => 'info', 'dismissible' => true])

@php
    $variants = [
        'success' => [
            'container' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
            'icon' => 'text-emerald-600',
            'dismiss' => 'hover:bg-emerald-100',
        ],
        'error' => [
            'container' => 'border-rose-200 bg-rose-50 text-rose-900',
            'icon' => 'text-rose-600',
            'dismiss' => 'hover:bg-rose-100',
        ],
        'warning' => [
            'container' => 'border-amber-200 bg-amber-50 text-amber-900',
            'icon' => 'text-amber-600',
            'dismiss' => 'hover:bg-amber-100',
        ],
        'info' => [
            'container' => 'border-indigo-200 bg-indigo-50 text-indigo-900',
            'icon' => 'text-indigo-600',
            'dismiss' => 'hover:bg-indigo-100',
        ],
    ];

    $variant = $variants[$type] ?? $variants['info'];
@endphp

<div
    {{ $attributes->class("rounded-xl border p-4 shadow-sm {$variant['container']}") }}
    @if($dismissible)
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 5000)"
    @endif
    role="{{ $type === 'error' ? 'alert' : 'status' }}"
    aria-live="polite"
>
    <div class="flex items-start gap-3">
        <div class="mt-0.5 shrink-0 {{ $variant['icon'] }}">
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

        <div class="min-w-0 flex-1 text-sm leading-6">
            {{ $slot }}
        </div>

        @if($dismissible)
            <button
                @click="show = false"
                class="rounded-md p-1 text-current/70 transition hover:text-current focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-current/30 {{ $variant['dismiss'] }}"
            >
                <span class="sr-only">{{ __('app.accessibility.dismiss') }}</span>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                </svg>
            </button>
        @endif
    </div>
</div>
