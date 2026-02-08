@props([
    'title',
    'description' => null,
])

<div {{ $attributes->class('rounded-2xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60') }}>
    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
            <p class="text-sm font-semibold text-slate-900">{{ $title }}</p>
            @if($description)
                <p class="mt-1 text-xs text-slate-500">{{ $description }}</p>
            @endif
        </div>
        <span class="rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-semibold text-indigo-700">{{ __('dashboard.shared.badge') }}</span>
    </div>
    <div class="px-5 py-4">
        {{ $slot }}
    </div>
</div>
