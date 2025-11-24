@props(['type' => 'info', 'dismissible' => true])

@if($type === 'success')
    <div 
        {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl border border-emerald-200/80 bg-white/85 shadow-lg shadow-emerald-200/40']) }}
        @if($dismissible)
            x-data="{ show: true }" 
            x-show="show"
            x-init="setTimeout(() => show = false, 5000)"
        @endif
        role="status" 
        aria-live="polite"
    >
        <div class="absolute inset-0 bg-gradient-to-r from-emerald-50 via-white to-emerald-50"></div>
        <div class="relative flex items-start gap-3 p-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7" />
                </svg>
            </div>
            <div class="flex-1 text-sm text-emerald-900">
                {{ $slot }}
            </div>
            @if($dismissible)
                <button @click="show = false" class="text-emerald-500 focus:outline-none">
                    <span class="sr-only">{{ __('app.accessibility.dismiss') }}</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                    </svg>
                </button>
            @endif
        </div>
    </div>
@elseif($type === 'error')
    <div 
        {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl border border-rose-200/90 bg-white/90 shadow-lg shadow-rose-200/50']) }}
        @if($dismissible)
            x-data="{ show: true }" 
            x-show="show"
            x-init="setTimeout(() => show = false, 5000)"
        @endif
        role="alert" 
        aria-live="polite"
    >
        <div class="absolute inset-0 bg-gradient-to-r from-rose-50 via-white to-rose-50"></div>
        <div class="relative flex items-start gap-3 p-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-100 text-rose-700">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M4.93 4.93l14.14 14.14M4.93 19.07 19.07 4.93" />
                </svg>
            </div>
            <div class="flex-1 text-sm text-rose-900">
                {{ $slot }}
            </div>
            @if($dismissible)
                <button @click="show = false" class="text-rose-500 focus:outline-none">
                    <span class="sr-only">{{ __('app.accessibility.dismiss') }}</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                    </svg>
                </button>
            @endif
        </div>
    </div>
@elseif($type === 'warning')
    <div 
        {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl border border-amber-200/90 bg-white/90 shadow-lg shadow-amber-200/50']) }}
        @if($dismissible)
            x-data="{ show: true }" 
            x-show="show"
            x-init="setTimeout(() => show = false, 5000)"
        @endif
        role="status" 
        aria-live="polite"
    >
        <div class="absolute inset-0 bg-gradient-to-r from-amber-50 via-white to-amber-50"></div>
        <div class="relative flex items-start gap-3 p-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
            <div class="flex-1 text-sm text-amber-900">
                {{ $slot }}
            </div>
            @if($dismissible)
                <button @click="show = false" class="text-amber-500 focus:outline-none">
                    <span class="sr-only">{{ __('app.accessibility.dismiss') }}</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                    </svg>
                </button>
            @endif
        </div>
    </div>
@else
    <div 
        {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl border border-blue-200/90 bg-white/90 shadow-lg shadow-blue-200/50']) }}
        @if($dismissible)
            x-data="{ show: true }" 
            x-show="show"
            x-init="setTimeout(() => show = false, 5000)"
        @endif
        role="status" 
        aria-live="polite"
    >
        <div class="absolute inset-0 bg-gradient-to-r from-blue-50 via-white to-blue-50"></div>
        <div class="relative flex items-start gap-3 p-4">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-700">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
            </div>
            <div class="flex-1 text-sm text-blue-900">
                {{ $slot }}
            </div>
            @if($dismissible)
                <button @click="show = false" class="text-blue-500 focus:outline-none">
                    <span class="sr-only">{{ __('app.accessibility.dismiss') }}</span>
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M6 18L18 6" />
                    </svg>
                </button>
            @endif
        </div>
    </div>
@endif
