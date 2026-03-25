@props(['chart'])

@php
    $labels = $chart['labels'] ?? [];
    $series = $chart['series'] ?? [];
    $currencyFormatter = new NumberFormatter(app()->getLocale(), NumberFormatter::CURRENCY);
    $integerFormatter = new NumberFormatter(app()->getLocale(), NumberFormatter::DECIMAL);
    $integerFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);
    $integerFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 0);
    $allPoints = collect($series)->flatMap(fn (array $line): array => $line['points'] ?? []);
    $hasData = $allPoints->contains(fn (float|int $value): bool => $value > 0);
    $maxValue = max(1, (float) ($allPoints->max() ?? 0));
    $tickCount = 4;
    $width = 760;
    $height = 300;
    $paddingLeft = 56;
    $paddingRight = 18;
    $paddingTop = 20;
    $paddingBottom = 42;
    $plotWidth = $width - $paddingLeft - $paddingRight;
    $plotHeight = $height - $paddingTop - $paddingBottom;
    $labelCount = max(1, count($labels) - 1);
    $ticks = collect(range(0, $tickCount))->map(function (int $index) use ($maxValue, $tickCount): float {
        return round($maxValue - (($maxValue / $tickCount) * $index), 2);
    });
@endphp

@if (! $hasData)
    <p class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-6 text-sm text-slate-500">
        {{ __('dashboard.not_available') }}
    </p>
@else
    <div x-data="{ tooltip: null }" class="mt-6">
        <div class="flex flex-wrap gap-3">
            @foreach ($series as $line)
                <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">
                    <span class="size-2.5 rounded-full" style="background-color: {{ $line['color'] }}"></span>
                    <span>{{ $line['label'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="relative mt-6 overflow-x-auto">
            <svg viewBox="0 0 {{ $width }} {{ $height }}" class="min-w-[680px]">
                @foreach ($ticks as $index => $tick)
                    @php
                        $y = $paddingTop + (($plotHeight / $tickCount) * $index);
                    @endphp
                    <line x1="{{ $paddingLeft }}" y1="{{ $y }}" x2="{{ $width - $paddingRight }}" y2="{{ $y }}" stroke="#e2e8f0" stroke-width="1" />
                    <text x="0" y="{{ $y + 4 }}" fill="#64748b" font-size="11">{{ $currencyFormatter->formatCurrency((float) $tick, 'EUR') }}</text>
                @endforeach

                @foreach ($labels as $index => $label)
                    @php
                        $x = $paddingLeft + (($plotWidth / $labelCount) * $index);
                    @endphp
                    <text x="{{ $x }}" y="{{ $height - 10 }}" fill="#64748b" font-size="11" text-anchor="middle">{{ $label }}</text>
                @endforeach

                @foreach ($series as $line)
                    @php
                        $points = collect($line['points'])->map(function (float $value, int $index) use ($labelCount, $labels, $line, $maxValue, $paddingLeft, $paddingTop, $plotHeight, $plotWidth) {
                            $x = $paddingLeft + (($plotWidth / $labelCount) * $index);
                            $normalizedValue = $maxValue <= 0 ? 0 : ($value / $maxValue);
                            $y = $paddingTop + $plotHeight - ($plotHeight * $normalizedValue);

                            return [
                                'x' => round($x, 2),
                                'y' => round($y, 2),
                                'month' => $labels[$index] ?? '',
                                'formatted' => $line['formatted'][$index] ?? $currencyFormatter->formatCurrency(0, 'EUR'),
                            ];
                        })->all();
                        $polyline = collect($points)->map(fn (array $point): string => "{$point['x']},{$point['y']}")->implode(' ');
                    @endphp

                    <polyline
                        fill="none"
                        stroke="{{ $line['color'] }}"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        points="{{ $polyline }}"
                    />

                    @foreach ($points as $point)
                        <circle
                            cx="{{ $point['x'] }}"
                            cy="{{ $point['y'] }}"
                            r="5"
                            fill="{{ $line['color'] }}"
                            class="cursor-pointer stroke-white"
                            stroke-width="2"
                            x-on:mouseenter="tooltip = { plan: '{{ addslashes($line['label']) }}', month: '{{ addslashes($point['month']) }}', value: '{{ addslashes($point['formatted']) }}', x: {{ $point['x'] }}, y: {{ $point['y'] }} }"
                            x-on:mouseleave="tooltip = null"
                        />
                    @endforeach
                @endforeach
            </svg>

            <div
                x-cloak
                x-show="tooltip"
                x-transition.opacity
                class="pointer-events-none absolute z-10 w-48 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-xl"
                :style="tooltip ? `left:${Math.max(16, tooltip.x - 72)}px; top:${Math.max(12, tooltip.y - 68)}px;` : ''"
            >
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400" x-text="tooltip?.plan"></p>
                <p class="mt-1 text-sm font-semibold text-slate-900" x-text="tooltip?.month"></p>
                <p class="mt-1 text-sm text-slate-600" x-text="tooltip?.value"></p>
            </div>
        </div>
    </div>
@endif
