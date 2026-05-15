<div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="text-base font-semibold text-slate-950">{{ __('admin.meters.sections.chart') }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ __('admin.meters.chart.description') }}</p>
        </div>
        @if ($maxValue !== null)
            <p class="text-sm font-medium text-slate-500">
                {{ (new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL))->format((float) $minValue) }} - {{ (new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL))->format((float) $maxValue) }} {{ $unit }}
            </p>
        @endif
    </div>

    @if ($points !== '')
        <div class="mt-6 rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
            <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-64 w-full" role="img" aria-label="{{ __('admin.meters.sections.chart') }}">
                <defs>
                    <linearGradient id="meter-chart-gradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" stop-color="#0f172a" stop-opacity="0.18" />
                        <stop offset="100%" stop-color="#0f172a" stop-opacity="0.02" />
                    </linearGradient>
                </defs>

                <line x1="{{ $paddingX }}" y1="{{ $paddingY }}" x2="{{ $paddingX }}" y2="{{ $height - $paddingY }}" stroke="#cbd5e1" stroke-width="1" />
                <line x1="{{ $paddingX }}" y1="{{ $height - $paddingY }}" x2="{{ $width - $paddingX }}" y2="{{ $height - $paddingY }}" stroke="#cbd5e1" stroke-width="1" />

                <polyline
                    fill="none"
                    stroke="#0f172a"
                    stroke-width="3"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    points="{{ $points }}"
                />

                @foreach ($readings as $reading)
                    @php
                        $count = max($readings->count() - 1, 1);
                        $chartWidth = $width - ($paddingX * 2);
                        $chartHeight = $height - ($paddingY * 2);
                        $range = max(((float) $maxValue) - ((float) $minValue), 1.0);
                        $x = $paddingX + (($chartWidth / $count) * $loop->index);
                        $y = $paddingY + ($chartHeight - ((((float) $reading->reading_value) - (float) $maxValue + $range) / $range * $chartHeight));
                    @endphp
                    <circle cx="{{ $x }}" cy="{{ $y }}" r="4" fill="#0f172a" />
                @endforeach
            </svg>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-3 xl:grid-cols-4">
            @foreach ($readings as $reading)
                <article class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ $reading->reading_date?->locale(app()->getLocale())->translatedFormat(\App\Filament\Support\Formatting\LocalizedDateFormatter::dateFormat()) }}</p>
                    <p class="mt-2 text-lg font-semibold tracking-tight text-slate-950">
                        {{ (new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL))->format((float) $reading->reading_value) }} {{ $unit }}
                    </p>
                </article>
            @endforeach
        </div>
    @else
        <div class="mt-6 rounded-3xl border border-dashed border-slate-300 bg-slate-50/70 px-5 py-8 text-center text-sm text-slate-500">
            {{ __('admin.meters.chart.empty') }}
        </div>
    @endif
</div>
