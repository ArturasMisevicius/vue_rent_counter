@php
    $panelId = $panelId ?? 'admin';

    $panelRoutes = [
        'admin' => 'filament.admin.auth.login',
        'superadmin' => 'filament.superadmin.auth.login',
        'tenant' => 'filament.tenant.auth.login',
    ];

    $accounts = [
        [
            'role' => 'Superadmin',
            'email' => 'superadmin@example.com',
            'password' => 'password',
            'panel' => 'superadmin',
        ],
        [
            'role' => 'Admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'panel' => 'admin',
        ],
        [
            'role' => 'Manager',
            'email' => 'manager@example.com',
            'password' => 'password',
            'panel' => 'admin',
        ],
        [
            'role' => 'Tenant',
            'email' => 'tenant.alina@example.com',
            'password' => 'password',
            'panel' => 'tenant',
        ],
    ];
@endphp

<x-filament::section compact icon="heroicon-o-beaker" heading="Demo Accounts">
    <div class="space-y-2 text-sm">
        @foreach ($accounts as $account)
            @php
                $isRecommended = $account['panel'] === $panelId;
            @endphp

            <div
                @class([
                    'rounded-xl border p-3',
                    'border-primary-500/40 bg-primary-50/70 dark:bg-primary-500/10' => $isRecommended,
                    'border-gray-200 dark:border-white/10' => ! $isRecommended,
                ])
            >
                <div class="font-semibold text-gray-900 dark:text-gray-100">
                    {{ $account['role'] }}
                    @if ($isRecommended)
                        <span class="text-primary-600 dark:text-primary-400">(recommended for this login)</span>
                    @endif
                </div>

                <div class="mt-1 text-gray-700 dark:text-gray-300">
                    <span class="font-medium">Email:</span> {{ $account['email'] }}
                </div>

                <div class="text-gray-700 dark:text-gray-300">
                    <span class="font-medium">Password:</span> {{ $account['password'] }}
                </div>

                <div class="mt-1">
                    <a
                        href="{{ route($panelRoutes[$account['panel']]) }}"
                        class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                    >
                        Open {{ ucfirst($account['panel']) }} login
                    </a>
                </div>
            </div>
        @endforeach

        <p class="text-xs text-gray-500 dark:text-gray-400">
            These accounts are seeded by <code>ComprehensiveTenantSeeder</code> for local demo/testing.
        </p>
    </div>
</x-filament::section>
