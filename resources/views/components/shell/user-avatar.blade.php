@props([
    'user',
])

@php
    $initials = collect(preg_split('/\s+/', trim($user->name)) ?: [])
        ->filter()
        ->take(2)
        ->map(fn (string $segment): string => mb_strtoupper(mb_substr($segment, 0, 1)))
        ->implode('');

    $palette = app(\App\Support\Shell\UserAvatarColor::class)->for($user->name);
@endphp

<span class="inline-flex items-center gap-3 rounded-full border border-slate-200 bg-white px-2.5 py-2 shadow-sm">
    <span class="{{ $palette['background'] }} {{ $palette['ring'] }} {{ $palette['text'] }} inline-flex size-10 items-center justify-center rounded-full text-sm font-semibold ring-1">
        {{ $initials }}
    </span>

    <span class="hidden text-left md:flex md:flex-col">
        <span class="max-w-40 truncate text-sm font-semibold text-slate-950">{{ $user->name }}</span>
        <span class="text-xs text-slate-500">{{ $user->role->label() }}</span>
    </span>
</span>
