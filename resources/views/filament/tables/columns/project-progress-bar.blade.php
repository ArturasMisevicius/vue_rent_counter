@php
    $percentage = max(0, min(100, (int) ($record->completion_percentage ?? 0)));
@endphp

<div class="min-w-36">
    <div class="mb-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
        <span>Progress</span>
        <span>{{ $percentage }}%</span>
    </div>
    <div class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
        <div class="h-full rounded-full bg-primary-500" style="width: {{ $percentage }}%"></div>
    </div>
</div>
