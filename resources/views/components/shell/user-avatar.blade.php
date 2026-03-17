@props([
    'user',
])

@php
    $initials = collect(explode(' ', trim((string) $user->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $palette = app(\App\Support\Shell\UserAvatarColor::class)->for($user->name);
@endphp

<span class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
    <span class="inline-flex size-10 items-center justify-center rounded-2xl text-sm font-semibold {{ $palette['background'] }} {{ $palette['text'] }}">
        {{ $initials }}
    </span>

    <span class="hidden text-left sm:flex sm:flex-col">
        <span class="text-sm font-semibold text-slate-900">{{ $user->name }}</span>
        <span class="text-xs text-slate-500">{{ $user->email }}</span>
    </span>
</span>
