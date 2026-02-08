@props(['type' => 'info', 'title' => null])

@php
$tones = [
    'warning' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'text' => 'text-amber-900', 'icon' => 'text-amber-500', 'accent' => 'bg-amber-400'],
    'error' => ['bg' => 'bg-rose-50', 'border' => 'border-rose-200', 'text' => 'text-rose-900', 'icon' => 'text-rose-500', 'accent' => 'bg-rose-500'],
    'success' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-900', 'icon' => 'text-emerald-500', 'accent' => 'bg-emerald-500'],
    'info' => ['bg' => 'bg-sky-50', 'border' => 'border-sky-200', 'text' => 'text-sky-900', 'icon' => 'text-sky-500', 'accent' => 'bg-sky-500'],
];

$icons = [
    'warning' => 'M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z',
    'error' => 'M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z',
    'success' => 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z',
    'info' => 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z',
];

$tone = $tones[$type] ?? $tones['info'];
$iconPath = $icons[$type] ?? $icons['info'];
@endphp

<div {{ $attributes->merge(['class' => "relative overflow-hidden rounded-xl border {$tone['border']} {$tone['bg']} shadow-sm"]) }}>
    <div class="absolute left-0 top-0 h-full w-1 {{ $tone['accent'] }}"></div>
    <div class="flex gap-3 p-4 sm:p-5 {{ $tone['text'] }}">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 {{ $tone['icon'] }}" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="{{ $iconPath }}" clip-rule="evenodd" />
            </svg>
        </div>
        <div class="flex-1">
            @if($title)
            <h3 class="text-sm font-semibold leading-6">{{ $title }}</h3>
            @endif
            <div class="{{ $title ? 'mt-2' : '' }} text-sm leading-6">
                {{ $slot }}
            </div>
        </div>
        @isset($action)
        <div class="flex-shrink-0">
            {{ $action }}
        </div>
        @endisset
    </div>
</div>
