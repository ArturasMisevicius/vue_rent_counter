@php
    $varianceToneClasses = [
        'danger' => 'bg-danger-50 text-danger-700 ring-danger-200 dark:bg-danger-500/10 dark:text-danger-300 dark:ring-danger-500/30',
        'success' => 'bg-success-50 text-success-700 ring-success-200 dark:bg-success-500/10 dark:text-success-300 dark:ring-success-500/30',
        'info' => 'bg-info-50 text-info-700 ring-info-200 dark:bg-info-500/10 dark:text-info-300 dark:ring-info-500/30',
        'gray' => 'bg-gray-50 text-gray-700 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10',
        'warning' => 'bg-warning-50 text-warning-700 ring-warning-200 dark:bg-warning-500/10 dark:text-warning-300 dark:ring-warning-500/30',
    ];

    $scheduleToneClass = $varianceToneClasses[$schedule['variance_tone']] ?? $varianceToneClasses['gray'];
    $budgetToneClass = $varianceToneClasses[$budget['variance_tone']] ?? $varianceToneClasses['gray'];
@endphp

<div class="space-y-6">
    <section class="grid gap-6 xl:grid-cols-[2fr,1fr]">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Project identity</h2>
                <span class="rounded-full px-3 py-1 text-xs font-medium ring-1 {{ $scheduleToneClass }}">{{ $schedule['variance_label'] }}</span>
            </div>

            <dl class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($identity as $item)
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $item['label'] }}</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $item['value'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Metadata</h2>

            @if ($metadata === [])
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No metadata recorded.</p>
            @else
                <dl class="mt-4 space-y-3">
                    @foreach ($metadata as $item)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $item['key'] }}</dt>
                            <dd class="mt-1 break-words text-sm text-gray-900 dark:text-white">{{ $item['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.2fr,0.8fr]">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Project narrative</h2>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</p>
                    <div class="prose prose-sm mt-3 max-w-none dark:prose-invert">{!! $details['description'] !!}</div>
                </div>

                <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Internal notes</p>
                    <div class="prose prose-sm mt-3 max-w-none dark:prose-invert">{!! $details['notes'] !!}</div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Contractor & cancellation</h2>

            <dl class="mt-4 space-y-4">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">External contractor</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $details['external_contractor'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Contractor contact</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $details['contractor_contact'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Contractor reference</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $details['contractor_reference'] }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Cancellation reason</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $details['cancellation_reason'] }}</dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Schedule health</h2>
                <span class="rounded-full px-3 py-1 text-xs font-medium ring-1 {{ $scheduleToneClass }}">{{ $schedule['variance_label'] }}</span>
            </div>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-xl border border-dashed border-gray-300 p-4 dark:border-white/10">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Estimated start</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $schedule['estimated_start_date'] }}</p>
                    </div>
                    <div class="rounded-xl border border-dashed border-gray-300 p-4 dark:border-white/10">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Actual start</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $schedule['actual_start_date'] }}</p>
                    </div>
                    <div class="rounded-xl border border-dashed border-gray-300 p-4 dark:border-white/10">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Estimated end</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $schedule['estimated_end_date'] }}</p>
                    </div>
                    <div class="rounded-xl border border-dashed border-gray-300 p-4 dark:border-white/10">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Actual end</p>
                        <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $schedule['actual_end_date'] }}</p>
                    </div>
                </div>

                <div>
                    <div class="mb-2 flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                        <span>Completion</span>
                        <span>{{ $schedule['completion_percentage'] }}%</span>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                        <div class="h-full rounded-full bg-primary-500" style="width: {{ $schedule['completion_percentage'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Budget health</h2>
                <span class="rounded-full px-3 py-1 text-xs font-medium ring-1 {{ $budgetToneClass }}">{{ $budget['variance_label'] }}</span>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                    <span>Budget</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $budget['budget_amount'] }}</span>
                </div>
                <div class="flex items-center justify-between text-sm text-gray-600 dark:text-gray-300">
                    <span>Actual cost</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $budget['actual_cost'] }}</span>
                </div>

                <div class="space-y-3 rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <div>
                        <div class="mb-2 flex items-center justify-between text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <span>Budget</span>
                            <span>{{ $budget['budget_amount'] }}</span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                            <div class="h-full rounded-full bg-info-500" style="width: {{ max(6, $budget['budget_bar_width']) }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <span>Actual</span>
                            <span>{{ $budget['actual_cost'] }}</span>
                        </div>
                        <div class="h-3 overflow-hidden rounded-full bg-gray-200 dark:bg-white/10">
                            <div class="h-full rounded-full {{ $budget['variance_tone'] === 'danger' ? 'bg-danger-500' : 'bg-success-500' }}" style="width: {{ max(6, $budget['actual_bar_width']) }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Team</h2>

            @if ($team === [])
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No team members assigned.</p>
            @else
                <div class="mt-4 space-y-3">
                    @foreach ($team as $member)
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3 dark:bg-white/5">
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $member['name'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $member['email'] }}</p>
                            </div>
                            <span class="rounded-full bg-gray-200 px-3 py-1 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-300">{{ $member['role'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Tasks summary</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $tasks['total'] }} total</span>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($tasks['columns'] as $column)
                    <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                        <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $column['label'] }}</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-950 dark:text-white">{{ $column['count'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.3fr,1fr]">
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Recent activity</h2>
                <span class="text-sm text-gray-500 dark:text-gray-400">Last {{ count($recentActivity) }}</span>
            </div>

            @if ($recentActivity === [])
                <p class="text-sm text-gray-500 dark:text-gray-400">No audit activity recorded yet.</p>
            @else
                <div class="space-y-3">
                    @foreach ($recentActivity as $entry)
                        <div class="rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $entry['action'] }}</p>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $entry['occurred_at'] }}</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $entry['description'] }}</p>
                            <p class="mt-2 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $entry['actor'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($costBreakdown !== null)
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Cost breakdown</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $costBreakdown['affected_tenants_count'] }} tenants</span>
                </div>

                <div class="mb-4 rounded-xl bg-gray-50 p-4 dark:bg-white/5">
                    <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Projected share per tenant</p>
                    <p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">{{ $costBreakdown['share_label'] }}</p>
                </div>

                @if ($costBreakdown['rows'] === [])
                    <p class="text-sm text-gray-500 dark:text-gray-400">No active tenants are currently affected by this passthrough.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($costBreakdown['rows'] as $row)
                            <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $row['tenant'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $row['building'] }} · {{ $row['property'] }}</p>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $row['share'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif
    </section>
</div>
