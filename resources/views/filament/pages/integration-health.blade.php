<x-filament-panels::page>
    <div class="space-y-6" wire:poll.30s>
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-semibold text-slate-950">Integration Health</h2>
            <p class="mt-2 text-sm text-slate-600">Platform probes refresh automatically every 30 seconds.</p>
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Integration</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Status</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Summary</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Checked</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($checks as $check)
                            @php
                                $circuitBreakerTripped = data_get($check->details, 'circuit_breaker_tripped', false) || ($check->status?->value ?? null) === 'failed';
                            @endphp
                            <tr wire:key="integration-health-{{ $check->id }}">
                                <td class="px-4 py-4 align-top">
                                    <p class="font-semibold text-slate-950">{{ $check->label }}</p>
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ $check->key }}</p>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ ($check->status?->value ?? null) === 'healthy' ? 'bg-emerald-100 text-emerald-700' : (($check->status?->value ?? null) === 'degraded' ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700') }}">
                                        {{ $check->status?->label() ?? $check->status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    <p>{{ $check->summary }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $check->response_time_ms ?? 0 }} ms</p>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    {{ $check->checked_at?->diffForHumans() ?? 'Never' }}
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            wire:click="checkNow({{ $check->id }})"
                                            class="inline-flex items-center rounded-full border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-slate-400 hover:bg-slate-50"
                                        >
                                            Check Now
                                        </button>

                                        @if ($circuitBreakerTripped)
                                            <button
                                                type="button"
                                                wire:click="resetCircuitBreaker({{ $check->id }})"
                                                class="inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:border-amber-400 hover:bg-amber-100"
                                            >
                                                Reset Circuit Breaker
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-sm text-slate-500">No checks available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-xl font-semibold text-slate-950">Recent Security Violations</h2>
                <p class="mt-2 text-sm text-slate-600">Recent platform security events recorded by the application.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Summary</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Severity</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Organization</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Source</th>
                            <th class="px-4 py-3 text-left font-semibold text-slate-700">Occurred</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentViolations as $violation)
                            <tr wire:key="security-violation-{{ $violation->id }}">
                                <td class="px-4 py-4 align-top">
                                    <p class="font-semibold text-slate-950">{{ $violation->summary }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $violation->type?->label() ?? $violation->type }}</p>
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ ($violation->severity?->value ?? null) === 'critical' || ($violation->severity?->value ?? null) === 'high' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $violation->severity?->label() ?? $violation->severity }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    {{ $violation->organization?->name ?? 'Platform' }}
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    <p>{{ data_get($violation->metadata, 'url', 'Unknown source') }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $violation->ip_address ?? 'No IP captured' }}</p>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    {{ $violation->occurred_at?->diffForHumans() ?? 'Unknown' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-sm text-slate-500">No security violations recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
