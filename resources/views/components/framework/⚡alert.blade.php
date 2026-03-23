<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Livewire\Component;

new class extends Component
{
    public string $type = 'info';

    /**
     * @return array{panel: string, badge: string}
     */
    public function tones(): array
    {
        return Arr::get([
            'info' => [
                'panel' => 'border-framework-300 bg-white/90 text-slate-700',
                'badge' => 'bg-framework-500 text-white',
            ],
            'success' => [
                'panel' => 'border-emerald-200 bg-emerald-50/90 text-emerald-900',
                'badge' => 'bg-emerald-600 text-white',
            ],
            'warning' => [
                'panel' => 'border-amber-200 bg-amber-50/90 text-amber-950',
                'badge' => 'bg-amber-500 text-slate-950',
            ],
        ], $this->type, [
            'panel' => 'border-slate-200 bg-slate-50 text-slate-700',
            'badge' => 'bg-slate-900 text-white',
        ]);
    }
};
?>

<div
    data-framework-alert
    {{ $attributes->class([
        'rounded-[1.5rem] border p-5 shadow-sm transition',
        $this->tones()['panel'],
    ]) }}
>
    <div class="flex items-start gap-4">
        <span class="{{ $this->tones()['badge'] }} inline-flex rounded-full px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.24em]">
            {{ strtoupper($type) }}
        </span>

        <div class="min-w-0 flex-1 space-y-2">
            @if ($slots->has('title'))
                <h3 class="text-sm font-semibold text-slate-950">{{ $slots['title'] }}</h3>
            @endif

            <div class="text-sm leading-6">
                {{ $slot }}
            </div>
        </div>
    </div>

    <style>
        [data-framework-alert] a {
            text-decoration: underline;
            text-underline-offset: 0.2em;
        }
    </style>
</div>
