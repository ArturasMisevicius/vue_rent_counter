@props(['title' => null])

<div {{ $attributes->class('rounded-2xl border border-slate-200 bg-white p-5 shadow-sm') }}>
    @if($title)
        <h3 class="mb-4 flex items-center gap-2 text-base font-semibold text-slate-900">
            <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
            {{ $title }}
        </h3>
    @endif

    {{ $slot }}
</div>
