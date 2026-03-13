@props(['title' => null, 'description' => null])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 shadow-lg shadow-slate-200/70 backdrop-blur-sm']) }}>
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-indigo-500 via-sky-400 to-teal-300"></div>
    <div class="px-4 py-5 sm:p-6">
        @if($title || $description)
        <div class="mb-4">
            @if($title)
            <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
            @endif
            @if($description)
            <p class="mt-1 text-sm text-slate-600">{{ $description }}</p>
            @endif
        </div>
        @endif

        {{ $slot }}
    </div>
</div>
