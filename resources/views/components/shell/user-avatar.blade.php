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
    $avatarUrl = filled($user->avatar_path) && \Illuminate\Support\Facades\Route::has('profile.avatar.show')
        ? route('profile.avatar.show', ['v' => $user->avatar_updated_at?->getTimestamp() ?? $user->updated_at?->getTimestamp()])
        : null;
@endphp

<span class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-white px-3 py-2 shadow-sm" data-shell-user-avatar>
    @if ($avatarUrl)
        <img
            src="{{ $avatarUrl }}"
            alt=""
            class="size-10 rounded-2xl object-cover"
            data-shell-user-avatar-image
        >
    @else
        <span class="inline-flex size-10 items-center justify-center rounded-2xl text-sm font-semibold {{ $palette['background'] }} {{ $palette['text'] }}">
            {{ $initials }}
        </span>
    @endif

    <span class="hidden text-left sm:flex sm:flex-col">
        <span class="text-sm font-semibold text-slate-900">{{ $user->name }}</span>
    </span>
</span>
