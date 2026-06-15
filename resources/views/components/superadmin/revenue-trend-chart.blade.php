@if (! $chartData['has_data'])
    <p class="mt-4 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-6 text-sm text-slate-500">
        {{ __('dashboard.not_available') }}
    </p>
@else
    <div x-data="{ tooltip: null }" class="mt-6">
        <div class="flex flex-wrap gap-3">
            @foreach ($chartData['series'] as $line)
                <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-slate-600">
                    <span class="size-2.5 rounded-full" style="background-color: {{ $line['color'] }}"></span>
                    <span>{{ $line['label'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="relative mt-6 overflow-x-auto">
            <svg viewBox="0 0 {{ $chartData['width'] }} {{ $chartData['height'] }}" class="min-w-[680px]">
                @foreach ($chartData['ticks'] as $tick)
                    <line x1="{{ $chartData['padding_left'] }}" y1="{{ $tick['y'] }}" x2="{{ $chartData['width'] - $chartData['padding_right'] }}" y2="{{ $tick['y'] }}" stroke="#e2e8f0" stroke-width="1" />
                    <text x="0" y="{{ $tick['y'] + 4 }}" fill="#64748b" font-size="11">{{ $tick['value'] }}</text>
                @endforeach

                @foreach ($chartData['labels'] as $label)
                    <text x="{{ $label['x'] }}" y="{{ $chartData['height'] - 10 }}" fill="#64748b" font-size="11" text-anchor="middle">{{ $label['label'] }}</text>
                @endforeach

                @foreach ($chartData['series'] as $line)
                    <polyline
                        fill="none"
                        stroke="{{ $line['color'] }}"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        points="{{ $line['polyline'] }}"
                    />

                    @foreach ($line['points'] as $point)
                        <circle
                            cx="{{ $point['x'] }}"
                            cy="{{ $point['y'] }}"
                            r="5"
                            fill="{{ $line['color'] }}"
                            class="cursor-pointer stroke-white"
                            stroke-width="2"
                            x-on:mouseenter="tooltip = @js(['plan' => $line['label'], 'month' => $point['month'], 'value' => $point['formatted'], 'x' => $point['x'], 'y' => $point['y']])"
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
