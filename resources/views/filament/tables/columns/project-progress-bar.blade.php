<div class="min-w-36">
    <div class="mb-1 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
        <span>{{ __('admin.projects.columns.progress') }}</span>
        <span>{{ \App\Filament\Support\View\BladeViewData::progressPercentage($record->completion_percentage ?? 0) }}%</span>
    </div>
    <div class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
        <div class="h-full rounded-full bg-primary-500" style="width: {{ \App\Filament\Support\View\BladeViewData::progressPercentage($record->completion_percentage ?? 0) }}%"></div>
    </div>
</div>
