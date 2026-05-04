<div class="space-y-4">
    @if ($entries === [])
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.projects.overview.no_audit_entries') }}</p>
    @else
        @foreach ($entries as $entry)
            <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $entry['action'] }}</p>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $entry['occurred_at'] }}</span>
                </div>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $entry['description'] }}</p>
                <p class="mt-2 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $entry['actor'] }}</p>
            </div>
        @endforeach
    @endif
</div>
