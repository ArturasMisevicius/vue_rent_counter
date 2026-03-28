@props([
    'user',
])

@php
    $initials = collect(explode(' ', trim((string) $user->name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $palette = app(\App\Filament\Support\Shell\UserAvatarColor::class)->for($user->name);
    $secondaryLines = collect([
        $user->email,
        $user->isTenant() ? $user->phone : null,
    ])->filter()->values();
@endphp

<span class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
    <span class="inline-flex size-10 items-center justify-center rounded-2xl text-sm font-semibold {{ $palette['background'] }} {{ $palette['text'] }}">
        {{ $initials }}
    </span>

    <span class="hidden text-left sm:flex sm:flex-col">
        <span class="text-sm font-semibold text-slate-900">{{ $user->name }}</span>
        @foreach ($secondaryLines as $line)
            <span class="text-xs text-slate-500">{{ $line }}</span>
        @endforeach
    </span>
</span>
