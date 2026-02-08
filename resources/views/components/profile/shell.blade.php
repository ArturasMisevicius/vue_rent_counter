@props([
    'title',
    'description' => null,
])

<div class="mx-auto w-full max-w-5xl">
    <div class="space-y-6 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-lg shadow-slate-200/60 sm:p-8">
        <div class="space-y-2 border-b border-slate-100 pb-4">
            <h1 class="text-2xl font-semibold text-slate-900 sm:text-3xl">{{ $title }}</h1>
            @if($description)
                <p class="text-sm text-slate-600 sm:text-base">{{ $description }}</p>
            @endif
        </div>

        {{ $slot }}
    </div>
</div>
