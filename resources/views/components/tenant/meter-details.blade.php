<x-tenant.stack>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-tenant.stat-card label="Meter Type" :value="enum_label($meter->type)" />
        <x-tenant.stat-card label="Latest Reading" :value="$latestReading ? number_format($latestReading->value, 2) . ' ' . $unit : 'Not recorded yet'" :value-color="$latestReading ? 'text-indigo-700' : 'text-slate-500'" />
        <x-tenant.stat-card label="Last Updated" :value="$latestReading ? $latestReading->reading_date->format('Y-m-d') : '—'" />
    </div>

    @php
        $trendChartId = 'meter-trend-' . $meter->id;
        $usageChartId = 'meter-usage-' . $meter->id;
    @endphp

    <x-tenant.section-card title="Usage trend" description="Interactive view of your most recent readings.">
        @if($chartReadings->isEmpty())
            <p class="text-sm text-slate-600">Add readings to see your consumption trend.</p>
        @else
            <div class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
                <div class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-sky-50 p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Last {{ $chartReadings->count() }} readings</p>
                            <p class="text-sm text-slate-600">Hover the chart to inspect exact values.</p>
                        </div>
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-100">
                            <span class="h-2 w-2 rounded-full bg-indigo-500"></span>
                            {{ $unit }}
                        </div>
                    </div>
                    <div class="mt-4 h-64">
                        <canvas id="{{ $trendChartId }}" role="img" aria-label="Meter reading line chart"></canvas>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Latest</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($latestReading->value, 2) }} {{ $unit }}</p>
                        <p class="text-xs text-slate-500">on {{ $latestReading->reading_date->format('Y-m-d') }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Average</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($averageValue, 2) }} {{ $unit }}</p>
                        <p class="text-xs text-slate-500">Across {{ $chartReadings->count() }} readings</p>
                    </div>
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/80 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-indigo-600">Change vs previous</p>
                        <p class="mt-1 text-2xl font-bold text-indigo-700">
                            {{ !is_null($delta) ? '+' . number_format($delta, 2) : '—' }} {{ $unit }}
                        </p>
                        <p class="text-xs text-indigo-600">Positive differences only</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Range</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($maxValue - $minValue, 2) }} {{ $unit }}</p>
                        <p class="text-xs text-slate-500">Min {{ number_format($minValue, 2) }} / Max {{ number_format($maxValue, 2) }}</p>
                    </div>
                </div>
            </div>
        @endif
    </x-tenant.section-card>

    <x-tenant.section-card title="Monthly usage (last 12 months)" description="Computed from differences between consecutive readings.">
        @if($monthlyChart->isEmpty())
            <p class="text-sm text-slate-600">Need at least two readings to calculate monthly usage.</p>
        @else
            <div class="grid gap-6 lg:grid-cols-[1.5fr_auto]">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Consumption</p>
                        <p class="text-xs text-slate-500">Max: {{ number_format($maxMonthly, 2) }} {{ $unit }}</p>
                    </div>
                    <div class="mt-3 h-64">
                        <canvas id="{{ $usageChartId }}" role="img" aria-label="Monthly consumption bar chart"></canvas>
                    </div>
                </div>
                <div class="grid w-full max-w-xs gap-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total (12m)</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($totalUsage, 2) }} {{ $unit }}</p>
                        <p class="text-xs text-slate-500">Average {{ number_format($totalUsage / max(count($usageValues), 1), 2) }} {{ $unit }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Latest month</p>
                        <p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($monthlyChart->last(), 2) }} {{ $unit }}</p>
                        <p class="text-xs text-slate-500">{{ \Carbon\Carbon::createFromFormat('Y-m', $monthlyChart->keys()->last())->format('M Y') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </x-tenant.section-card>

    <x-tenant.section-card title="{{ __('tenant.meter_details.overview_title') }}" description="{{ __('tenant.meter_details.overview_description') }}">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-tenant.stack gap="4">
                <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('tenant.meter_details.serial_number') }}</p>
                    <p class="mt-1 text-lg font-semibold text-slate-900">{{ $meter->serial_number }}</p>
                </div>

                <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('tenant.meter_details.property') }}</p>
                    <p class="mt-1 text-sm text-slate-900">
                        @if($meter->property)
                            <a href="{{ route('tenant.property.show') }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $meter->property->address }}
                            </a>
                        @else
                            <span class="text-slate-500">{{ __('tenant.meter_details.not_assigned') }}</span>
                        @endif
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('tenant.meter_details.installed') }}</p>
                        <p class="mt-1 text-sm text-slate-900">
                            {{ $meter->installation_date ? $meter->installation_date->format('Y-m-d') : __('tenant.meter_details.not_specified') }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200/80 bg-white px-4 py-3 shadow-sm">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('tenant.meter_details.zones') }}</p>
                        <div class="mt-2">
                            @if($meter->supports_zones)
                                <x-status-badge status="active">{{ __('tenant.meter_details.supports_zones') }}</x-status-badge>
                            @else
                                <x-status-badge status="inactive">{{ __('tenant.meter_details.single_zone') }}</x-status-badge>
                            @endif
                        </div>
                    </div>
                </div>
            </x-tenant.stack>

            <div class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-sky-50 p-6 shadow-lg shadow-indigo-100">
                <div class="absolute right-4 top-4 h-20 w-20 rounded-full bg-indigo-500/10 blur-3xl"></div>
                <div class="relative">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600">Latest reading</p>
                    @if($latestReading)
                        <div class="mt-3 flex items-baseline gap-3">
                            <span class="text-4xl font-bold text-slate-900">{{ number_format($latestReading->value, 2) }}</span>
                            <span class="text-sm font-semibold text-slate-500">{{ $unit }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600">{{ __('tenant.meter_details.recorded_on', ['date' => $latestReading->reading_date->format('Y-m-d')]) }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @if($latestReading->zone)
                                <span class="inline-flex items-center rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                    {{ __('tenant.meter_details.zone') }}: {{ $latestReading->zone }}
                                </span>
                            @endif
                            @if(!is_null($delta))
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                    {{ __('tenant.meter_details.delta', ['value' => number_format($delta, 2), 'unit' => $unit]) }}
                                </span>
                            @endif
                        </div>
                    @else
                        <p class="mt-2 text-sm text-slate-700">{{ __('tenant.meter_details.empty_primary') }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ __('tenant.meter_details.empty_secondary') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </x-tenant.section-card>

    <x-tenant.section-card title="{{ __('tenant.meter_details.recent_title') }}" description="{{ __('tenant.meter_details.recent_description') }}">
        @if($meter->readings->isEmpty())
            <p class="text-sm text-slate-600">{{ __('tenant.meter_details.empty_primary') }}</p>
        @else
            <div class="hidden sm:block overflow-hidden rounded-xl border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('tenant.meter_details.table.date') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('tenant.meter_details.table.value') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('tenant.meter_details.table.change') }}</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ __('tenant.meter_details.table.zone') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @php $previousValue = null; @endphp
                        @foreach($meter->readings as $reading)
                            <tr>
                                <td class="px-4 py-3 text-sm font-semibold text-slate-900">
                                    {{ $reading->reading_date->format('Y-m-d') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ number_format($reading->value, 2) }} {{ $unit }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $previousValue !== null ? '+' . number_format(max($previousValue - $reading->value, 0), 2) . ' ' . $unit : '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ $reading->zone ?? '—' }}
                                </td>
                            </tr>
                            @php $previousValue = $reading->value; @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>
            <x-tenant.stack gap="3" class="sm:hidden">
                @php $previousValue = null; @endphp
                @foreach($meter->readings as $reading)
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-900">{{ $reading->reading_date->format('Y-m-d') }}</p>
                            <p class="text-xs font-semibold text-slate-500">{{ $reading->zone ?? '—' }}</p>
                        </div>
                        <p class="mt-1 text-sm text-slate-700">{{ __('tenant.meter_details.mobile.value') }} <span class="font-semibold">{{ number_format($reading->value, 2) }} {{ $unit }}</span></p>
                        <p class="mt-1 text-sm text-slate-700">
                            {{ __('tenant.meter_details.mobile.change') }}
                            <span class="font-semibold">{{ $previousValue !== null ? '+' . number_format(max($previousValue - $reading->value, 0), 2) . ' ' . $unit : '—' }}</span>
                        </p>
                    </div>
                    @php $previousValue = $reading->value; @endphp
                @endforeach
            </x-tenant.stack>
        @endif
    </x-tenant.section-card>

    @if($chartReadings->isNotEmpty() || $monthlyChart->isNotEmpty())
        @push('scripts')
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const ChartLib = window.Chart;
                    if (!ChartLib) return;

                    const trendCtx = document.getElementById('{{ $trendChartId }}');
                    if (trendCtx) {
                        const context = trendCtx.getContext('2d');
                        const gradient = context.createLinearGradient(0, 0, 0, trendCtx.height || 240);
                        gradient.addColorStop(0, 'rgba(79, 70, 229, 0.18)');
                        gradient.addColorStop(1, 'rgba(79, 70, 229, 0.02)');

                        new ChartLib(trendCtx, {
                            type: 'line',
                            data: {
                                labels: @json($trendLabels),
                                datasets: [{
                                    label: '{{ __('tenant.meter_details.chart_label', ['unit' => $unit]) }}',
                                    data: @json($trendValues),
                                    borderColor: 'rgb(79, 70, 229)',
                                    backgroundColor: gradient,
                                    fill: true,
                                    tension: 0.35,
                                    borderWidth: 2.5,
                                    pointRadius: 3.5,
                                    pointHoverRadius: 6,
                                    pointBackgroundColor: '#ffffff',
                                    pointBorderColor: 'rgb(79, 70, 229)',
                                    pointBorderWidth: 2,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        backgroundColor: 'rgba(15,23,42,0.9)',
                                        borderWidth: 0,
                                        padding: 12,
                                        displayColors: false,
                                        callbacks: {
                                            label: ctx => `${ctx.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} {{ $unit }}`
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        ticks: {
                                            color: '#475569',
                                            maxRotation: 0
                                        },
                                        grid: { display: false }
                                    },
                                    y: {
                                        ticks: {
                                            color: '#475569',
                                            callback: value => value.toLocaleString()
                                        },
                                        grid: {
                                            color: 'rgba(148, 163, 184, 0.2)',
                                            borderDash: [6, 4]
                                        }
                                    }
                                }
                            }
                        });
                    }

                    const usageCtx = document.getElementById('{{ $usageChartId }}');
                    if (usageCtx && {{ !empty($usageValues) ? 'true' : 'false' }}) {
                        new ChartLib(usageCtx, {
                            type: 'bar',
                            data: {
                                labels: @json($usageLabels),
                                datasets: [{
                                    label: 'Monthly usage ({{ $unit }})',
                                    data: @json($usageValues),
                                    backgroundColor: 'rgba(56, 189, 248, 0.7)',
                                    hoverBackgroundColor: 'rgba(79, 70, 229, 0.9)',
                                    borderRadius: 8,
                                    barThickness: 24
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: { display: false },
                                    tooltip: {
                                        backgroundColor: 'rgba(15,23,42,0.9)',
                                        displayColors: false,
                                        padding: 12,
                                        callbacks: {
                                            label: ctx => `${ctx.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} {{ $unit }}`
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        ticks: {
                                            color: '#475569',
                                            maxRotation: 0
                                        },
                                        grid: { display: false }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            color: '#475569',
                                            callback: value => value.toLocaleString()
                                        },
                                        grid: {
                                            color: 'rgba(148,163,184,0.15)',
                                            borderDash: [6, 4]
                                        }
                                    }
                                }
                            }
                        });
                    }
                });
            </script>
        @endpush
    @endif
</x-tenant.stack>
