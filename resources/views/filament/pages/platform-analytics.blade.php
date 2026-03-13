<x-filament-panels::page>
    {{-- Organization Analytics Section --}}
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold mb-4">{{ __('platform_analytics.sections.organization') }}</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Growth Chart --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.organization_growth') }}</h3>
                    <canvas id="organizationGrowthChart" class="w-full" style="max-height: 300px;"></canvas>
                </div>

                {{-- Plan Distribution --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.plan_distribution') }}</h3>
                    <canvas id="planDistributionChart" class="w-full" style="max-height: 300px;"></canvas>
                </div>

                {{-- Active vs Inactive --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.active_vs_inactive') }}</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-600">
                                {{ $this->getOrganizationAnalytics()['activeInactive']['active'] }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('platform_analytics.labels.active') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-red-600">
                                {{ $this->getOrganizationAnalytics()['activeInactive']['inactive'] }}
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('platform_analytics.labels.inactive') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Top Organizations --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.top_organizations') }}</h3>
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('platform_analytics.cards.by_properties') }}</h4>
                            <div class="space-y-1">
                                @foreach(array_slice($this->getOrganizationAnalytics()['topOrganizations']['byProperties'], 0, 5) as $org)
                                    <div class="flex justify-between text-sm">
                                        <span class="truncate">{{ $org['name'] }}</span>
                                        <span class="font-medium">{{ $org['count'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Subscription Analytics Section --}}
        <div>
            <h2 class="text-xl font-semibold mb-4">{{ __('platform_analytics.sections.subscription') }}</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Renewal Rate --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.renewal_rate') }}</h3>
                    <div class="text-center">
                        <div class="text-4xl font-bold text-blue-600">
                            {{ $this->getSubscriptionAnalytics()['renewalRate']['rate'] }}%
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <div class="text-gray-600 dark:text-gray-400">{{ __('platform_analytics.labels.renewed') }}</div>
                                <div class="text-xl font-semibold text-green-600">
                                    {{ $this->getSubscriptionAnalytics()['renewalRate']['renewed'] }}
                                </div>
                            </div>
                            <div>
                                <div class="text-gray-600 dark:text-gray-400">{{ __('platform_analytics.labels.expired') }}</div>
                                <div class="text-xl font-semibold text-red-600">
                                    {{ $this->getSubscriptionAnalytics()['renewalRate']['expired'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Expiry Forecast --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.expiry_forecast') }}</h3>
                    <canvas id="expiryForecastChart" class="w-full" style="max-height: 300px;"></canvas>
                </div>

                {{-- Plan Changes --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.plan_changes') }}</h3>
                    <canvas id="planChangesChart" class="w-full" style="max-height: 300px;"></canvas>
                </div>

                {{-- Subscription Lifecycle --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.subscription_lifecycle') }}</h3>
                    <canvas id="lifecycleChart" class="w-full" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>

        {{-- Usage Analytics Section --}}
        <div>
            <h2 class="text-xl font-semibold mb-4">{{ __('platform_analytics.sections.usage') }}</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Usage Totals --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.platform_totals') }}</h3>
                    <div class="grid grid-cols-2 gap-4">
                        @php $totals = $this->getUsageAnalytics()['totals']; @endphp
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ number_format($totals['properties']) }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('platform_analytics.labels.properties') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ number_format($totals['buildings']) }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('platform_analytics.labels.buildings') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-purple-600">{{ number_format($totals['meters']) }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('platform_analytics.labels.meters') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-orange-600">{{ number_format($totals['invoices']) }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('platform_analytics.labels.invoices') }}</div>
                        </div>
                    </div>
                </div>

                {{-- Growth Trends --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.property_growth') }}</h3>
                    <canvas id="usageGrowthChart" class="w-full" style="max-height: 300px;"></canvas>
                </div>

                {{-- Feature Usage --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.feature_usage') }}</h3>
                    <canvas id="featureUsageChart" class="w-full" style="max-height: 300px;"></canvas>
                </div>

                {{-- Peak Activity Times --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.peak_activity') }}</h3>
                    <canvas id="peakActivityChart" class="w-full" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>

        {{-- User Analytics Section --}}
        <div>
            <h2 class="text-xl font-semibold mb-4">{{ __('platform_analytics.sections.user') }}</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Users by Role --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.users_by_role') }}</h3>
                    <canvas id="usersByRoleChart" class="w-full" style="max-height: 300px;"></canvas>
                </div>

                {{-- Active Users --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.active_users') }}</h3>
                    <div class="space-y-4">
                        @php $activeUsers = $this->getUserAnalytics()['activeUsers']; @endphp
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('platform_analytics.labels.last_7_days') }}</span>
                            <span class="text-2xl font-bold text-blue-600">{{ number_format($activeUsers['last7Days']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('platform_analytics.labels.last_30_days') }}</span>
                            <span class="text-2xl font-bold text-green-600">{{ number_format($activeUsers['last30Days']) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('platform_analytics.labels.last_90_days') }}</span>
                            <span class="text-2xl font-bold text-purple-600">{{ number_format($activeUsers['last90Days']) }}</span>
                        </div>
                    </div>
                </div>

                {{-- Login Frequency --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.login_frequency') }}</h3>
                    <canvas id="loginFrequencyChart" class="w-full" style="max-height: 300px;"></canvas>
                </div>

                {{-- User Growth --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">{{ __('platform_analytics.cards.user_growth') }}</h3>
                    <canvas id="userGrowthChart" class="w-full" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const orgAnalytics = @json($this->getOrganizationAnalytics());

            // Organization Growth Chart
            const growthCtx = document.getElementById('organizationGrowthChart');
            if (growthCtx) {
                new Chart(growthCtx, {
                    type: 'line',
                    data: {
                        labels: orgAnalytics.growth.labels,
                        datasets: [{
                            label: @json(__('platform_analytics.charts.total_organizations')),
                            data: orgAnalytics.growth.data,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }

            // Plan Distribution Chart
            const planCtx = document.getElementById('planDistributionChart');
            if (planCtx) {
                new Chart(planCtx, {
                    type: 'pie',
                    data: {
                        labels: orgAnalytics.planDistribution.labels,
                        datasets: [{
                            data: orgAnalytics.planDistribution.data,
                            backgroundColor: [
                                'rgb(59, 130, 246)',
                                'rgb(16, 185, 129)',
                                'rgb(245, 158, 11)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            // Subscription Analytics
            const subAnalytics = @json($this->getSubscriptionAnalytics());

            // Expiry Forecast Chart
            const expiryCtx = document.getElementById('expiryForecastChart');
            if (expiryCtx) {
                new Chart(expiryCtx, {
                    type: 'bar',
                    data: {
                        labels: subAnalytics.expiryForecast.map(item => item.week),
                        datasets: [{
                            label: @json(__('platform_analytics.charts.expiring_subscriptions')),
                            data: subAnalytics.expiryForecast.map(item => item.count),
                            backgroundColor: 'rgba(239, 68, 68, 0.5)',
                            borderColor: 'rgb(239, 68, 68)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }

            // Plan Changes Chart
            const planChangesCtx = document.getElementById('planChangesChart');
            if (planChangesCtx && subAnalytics.planChanges.labels.length > 0) {
                new Chart(planChangesCtx, {
                    type: 'line',
                    data: {
                        labels: subAnalytics.planChanges.labels,
                        datasets: [{
                            label: @json(__('platform_analytics.charts.plan_changes')),
                            data: subAnalytics.planChanges.data,
                            borderColor: 'rgb(16, 185, 129)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }

            // Lifecycle Chart
            const lifecycleCtx = document.getElementById('lifecycleChart');
            if (lifecycleCtx) {
                new Chart(lifecycleCtx, {
                    type: 'doughnut',
                    data: {
                        labels: subAnalytics.lifecycle.labels,
                        datasets: [{
                            data: subAnalytics.lifecycle.data,
                            backgroundColor: [
                                'rgb(16, 185, 129)',
                                'rgb(245, 158, 11)',
                                'rgb(239, 68, 68)',
                                'rgb(59, 130, 246)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            // Usage Analytics
            const usageAnalytics = @json($this->getUsageAnalytics());

            // Usage Growth Chart
            const usageGrowthCtx = document.getElementById('usageGrowthChart');
            if (usageGrowthCtx && usageAnalytics.growth.daily.labels.length > 0) {
                new Chart(usageGrowthCtx, {
                    type: 'line',
                    data: {
                        labels: usageAnalytics.growth.daily.labels,
                        datasets: [{
                            label: @json(__('platform_analytics.charts.new_properties')),
                            data: usageAnalytics.growth.daily.data,
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }

            // Feature Usage Chart
            const featureUsageCtx = document.getElementById('featureUsageChart');
            if (featureUsageCtx && usageAnalytics.featureUsage.labels.length > 0) {
                new Chart(featureUsageCtx, {
                    type: 'bar',
                    data: {
                        labels: usageAnalytics.featureUsage.labels.map(label => 
                            label.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
                        ),
                        datasets: [{
                            label: @json(__('platform_analytics.charts.usage_count')),
                            data: usageAnalytics.featureUsage.data,
                            backgroundColor: 'rgba(139, 92, 246, 0.5)',
                            borderColor: 'rgb(139, 92, 246)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        indexAxis: 'y',
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            x: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // Peak Activity Chart
            const peakActivityCtx = document.getElementById('peakActivityChart');
            if (peakActivityCtx) {
                new Chart(peakActivityCtx, {
                    type: 'bar',
                    data: {
                        labels: usageAnalytics.peakActivity.labels,
                        datasets: [{
                            label: @json(__('platform_analytics.charts.activity_count')),
                            data: usageAnalytics.peakActivity.data,
                            backgroundColor: 'rgba(16, 185, 129, 0.5)',
                            borderColor: 'rgb(16, 185, 129)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // User Analytics
            const userAnalytics = @json($this->getUserAnalytics());

            // Users by Role Chart
            const usersByRoleCtx = document.getElementById('usersByRoleChart');
            if (usersByRoleCtx) {
                new Chart(usersByRoleCtx, {
                    type: 'doughnut',
                    data: {
                        labels: userAnalytics.byRole.labels,
                        datasets: [{
                            data: userAnalytics.byRole.data,
                            backgroundColor: [
                                'rgb(239, 68, 68)',
                                'rgb(59, 130, 246)',
                                'rgb(16, 185, 129)',
                                'rgb(245, 158, 11)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            // Login Frequency Chart
            const loginFrequencyCtx = document.getElementById('loginFrequencyChart');
            if (loginFrequencyCtx) {
                new Chart(loginFrequencyCtx, {
                    type: 'bar',
                    data: {
                        labels: userAnalytics.loginFrequency.labels,
                        datasets: [{
                            label: @json(__('platform_analytics.charts.users')),
                            data: userAnalytics.loginFrequency.data,
                            backgroundColor: [
                                'rgba(16, 185, 129, 0.5)',
                                'rgba(59, 130, 246, 0.5)',
                                'rgba(245, 158, 11, 0.5)',
                                'rgba(156, 163, 175, 0.5)'
                            ],
                            borderColor: [
                                'rgb(16, 185, 129)',
                                'rgb(59, 130, 246)',
                                'rgb(245, 158, 11)',
                                'rgb(156, 163, 175)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }

            // User Growth Chart
            const userGrowthCtx = document.getElementById('userGrowthChart');
            if (userGrowthCtx) {
                new Chart(userGrowthCtx, {
                    type: 'line',
                    data: {
                        labels: userAnalytics.userGrowth.labels,
                        datasets: [{
                            label: @json(__('platform_analytics.charts.total_users')),
                            data: userAnalytics.userGrowth.data,
                            borderColor: 'rgb(139, 92, 246)',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-filament-panels::page>
