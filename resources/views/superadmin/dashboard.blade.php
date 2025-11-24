@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto">
    <section class="relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white/90 shadow-xl shadow-slate-200/70 backdrop-blur-sm">
        <div class="pointer-events-none absolute inset-0 opacity-60">
            <div class="absolute -left-10 -top-16 h-52 w-52 rounded-full bg-indigo-500/10 blur-3xl"></div>
            <div class="absolute -right-12 top-12 h-40 w-40 rounded-full bg-sky-400/10 blur-3xl"></div>
        </div>
        <div class="relative p-6 sm:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-indigo-500">Superadmin Space</p>
                    <h1 class="text-2xl font-bold text-slate-900 sm:text-3xl">{{ __('superadmin.dashboard.title') }}</h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ __('superadmin.dashboard.subtitle') }}</p>
                </div>
            </div>

            <div class="mt-6 space-y-6">
                {{-- Quick Actions --}}
                <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-5 shadow-sm">
                    <h2 class="text-base font-semibold text-slate-900 mb-4">{{ __('superadmin.dashboard.quick_actions.title') }}</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <a href="{{ route('superadmin.organizations.create') }}" class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white/95 p-5 shadow-sm transition hover:shadow-md">
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-indigo-500/5 via-transparent to-sky-400/5"></div>
                            <div class="relative flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900">{{ __('superadmin.dashboard.quick_actions.create_organization') }}</h3>
                                    <p class="text-sm text-slate-600">Add new organization</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('superadmin.organizations.index') }}" class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white/95 p-5 shadow-sm transition hover:shadow-md">
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-indigo-500/5 via-transparent to-sky-400/5"></div>
                            <div class="relative flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900">{{ __('superadmin.dashboard.quick_actions.manage_organizations') }}</h3>
                                    <p class="text-sm text-slate-600">View all organizations</p>
                                </div>
                            </div>
                        </a>

                        <a href="{{ route('superadmin.subscriptions.index') }}" class="relative overflow-hidden rounded-xl border border-slate-200/80 bg-white/95 p-5 shadow-sm transition hover:shadow-md">
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-indigo-500/5 via-transparent to-sky-400/5"></div>
                            <div class="relative flex items-center gap-4">
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-slate-900">{{ __('superadmin.dashboard.quick_actions.manage_subscriptions') }}</h3>
                                    <p class="text-sm text-slate-600">Manage subscriptions</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                {{-- Subscription Statistics --}}
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200/80 bg-white/95 px-5 py-4 shadow-sm">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('superadmin.dashboard.stats.total_subscriptions') }}</dt>
                        <dd class="mt-2 text-3xl font-bold text-slate-900">{{ $totalSubscriptions }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white/95 px-5 py-4 shadow-sm">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('superadmin.dashboard.stats.active_subscriptions') }}</dt>
                        <dd class="mt-2 text-3xl font-bold text-emerald-600">{{ $activeSubscriptions }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white/95 px-5 py-4 shadow-sm">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('superadmin.dashboard.stats.expired_subscriptions') }}</dt>
                        <dd class="mt-2 text-3xl font-bold text-rose-600">{{ $expiredSubscriptions }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white/95 px-5 py-4 shadow-sm">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('superadmin.dashboard.stats.suspended_subscriptions') }}</dt>
                        <dd class="mt-2 text-3xl font-bold text-amber-600">{{ $suspendedSubscriptions }}</dd>
                    </div>
                </div>

                {{-- Expiring Subscriptions Alert --}}
                @if($expiringSubscriptions->count() > 0)
                <div class="rounded-2xl border border-amber-200/80 bg-amber-50/50 p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-amber-900">{{ __('superadmin.dashboard.expiring_subscriptions.title') }}</h3>
                            <p class="text-sm text-amber-800 mt-1">{{ __('superadmin.dashboard.expiring_subscriptions.alert', ['count' => $expiringSubscriptions->count()]) }}</p>
                            <div class="mt-4 space-y-2">
                                @foreach($expiringSubscriptions as $subscription)
                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 bg-white p-3 rounded-xl border border-amber-100">
                                    <div>
                                        <span class="text-sm font-semibold text-slate-900">{{ $subscription->user->organization_name }}</span>
                                        <span class="text-sm text-slate-600 ml-2">({{ $subscription->user->email }})</span>
                                    </div>
                                    <div class="text-left sm:text-right">
                                        <span class="text-xs text-slate-600">{{ __('superadmin.dashboard.expiring_subscriptions.expires') }}</span>
                                        <span class="text-sm font-semibold text-amber-700 ml-1">{{ $subscription->expires_at->format('M d, Y') }}</span>
                                        <span class="text-xs text-slate-600 ml-2">({{ $subscription->expires_at->diffForHumans() }})</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Organization Statistics --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-5 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-900 mb-4">{{ __('superadmin.dashboard.organizations.title') }}</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">{{ __('superadmin.dashboard.organizations.total') }}</span>
                                <span class="text-2xl font-bold text-slate-900">{{ $totalOrganizations }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">{{ __('superadmin.dashboard.organizations.active') }}</span>
                                <span class="text-2xl font-bold text-emerald-600">{{ $activeOrganizations }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">{{ __('superadmin.dashboard.organizations.inactive') }}</span>
                                <span class="text-2xl font-bold text-rose-600">{{ $totalOrganizations - $activeOrganizations }}</span>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('superadmin.organizations.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                {{ __('superadmin.dashboard.organizations.view_all') }} →
                            </a>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-5 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-900 mb-4">{{ __('superadmin.dashboard.subscription_plans.title') }}</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">{{ __('superadmin.dashboard.subscription_plans.basic') }}</span>
                                <span class="text-2xl font-bold text-slate-900">{{ $subscriptionsByPlan['basic'] ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">{{ __('superadmin.dashboard.subscription_plans.professional') }}</span>
                                <span class="text-2xl font-bold text-slate-900">{{ $subscriptionsByPlan['professional'] ?? 0 }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-slate-600">{{ __('superadmin.dashboard.subscription_plans.enterprise') }}</span>
                                <span class="text-2xl font-bold text-slate-900">{{ $subscriptionsByPlan['enterprise'] ?? 0 }}</span>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route('superadmin.subscriptions.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700">
                                {{ __('superadmin.dashboard.subscription_plans.view_all') }} →
                            </a>
                        </div>
                    </div>
                </div>

                {{-- System-wide Usage Metrics --}}
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200/80 bg-white/95 px-5 py-4 shadow-sm">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('superadmin.dashboard.stats.total_properties') }}</dt>
                        <dd class="mt-2 text-3xl font-bold text-slate-900">{{ $totalProperties }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white/95 px-5 py-4 shadow-sm">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('superadmin.dashboard.stats.total_buildings') }}</dt>
                        <dd class="mt-2 text-3xl font-bold text-slate-900">{{ $totalBuildings }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white/95 px-5 py-4 shadow-sm">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('superadmin.dashboard.stats.total_tenants') }}</dt>
                        <dd class="mt-2 text-3xl font-bold text-slate-900">{{ $totalTenants }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white/95 px-5 py-4 shadow-sm">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('superadmin.dashboard.stats.total_invoices') }}</dt>
                        <dd class="mt-2 text-3xl font-bold text-slate-900">{{ $totalInvoices }}</dd>
                    </div>
                </div>

                {{-- Top Organizations --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-5 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-900 mb-4">{{ __('superadmin.dashboard.organizations.top_by_properties') }}</h2>
                        <div class="space-y-3">
                            @forelse($topOrganizations as $org)
                            <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">{{ $org->organization_name }}</div>
                                    <div class="text-xs text-slate-600">{{ $org->email }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-indigo-600">{{ $org->properties_count }}</div>
                                    <div class="text-xs text-slate-600">{{ __('superadmin.dashboard.organizations.properties_count') }}</div>
                                </div>
                            </div>
                            @empty
                            <p class="text-sm text-slate-500 text-center py-4">{{ __('superadmin.dashboard.organizations.no_organizations') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200/80 bg-white/95 p-5 shadow-sm">
                        <h2 class="text-base font-semibold text-slate-900 mb-4">{{ __('superadmin.dashboard.recent_activity.title') }}</h2>
                        <div class="space-y-3">
                            @forelse($recentActivity as $admin)
                            <div class="flex justify-between items-center p-3 bg-slate-50 rounded-xl">
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">{{ $admin->organization_name }}</div>
                                    <div class="text-xs text-slate-600">{{ $admin->email }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-slate-600">{{ __('superadmin.dashboard.recent_activity.last_activity') }}</div>
                                    <div class="text-xs font-semibold text-slate-900">{{ $admin->updated_at->diffForHumans() }}</div>
                                </div>
                            </div>
                            @empty
                            <p class="text-sm text-slate-500 text-center py-4">{{ __('superadmin.dashboard.recent_activity.no_activity') }}</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
