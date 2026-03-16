@php
    $panelId = $panelId ?? 'admin';
    $accounts = $accounts ?? [];
    $demoPassword = $demoPassword ?? 'password';
@endphp

<x-filament::section compact icon="heroicon-o-user-group" :heading="__('app.auth.login_page.test_users')">
    <div class="space-y-4 text-sm">
        @if (filled($accounts))
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-gray-500 dark:text-gray-400">
                        {{ __('app.auth.login_page.available_accounts') }}
                    </p>
                    <p class="mt-1 text-gray-600 dark:text-gray-300">
                        {{ __('app.auth.login_page.click_hint') }}
                    </p>
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('app.auth.login_page.default_password') }}
                    <code class="rounded-md bg-gray-100 px-2 py-1 font-mono text-gray-900 dark:bg-white/10 dark:text-white">{{ $demoPassword }}</code>
                </p>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white/80 shadow-sm dark:border-white/10 dark:bg-white/5">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-white/10">
                        <thead class="bg-gray-50/80 dark:bg-white/5">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ __('app.labels.name') }}
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ __('app.labels.email') }}
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ __('app.auth.login_page.password_column') }}
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    {{ __('app.labels.role') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach ($accounts as $account)
                                @php
                                    $isRecommended = $account['panel'] === $panelId;
                                    $roleClasses = match ($account['role_key']) {
                                        'superadmin' => 'bg-danger-50 text-danger-700 ring-danger-600/20 dark:bg-danger-500/10 dark:text-danger-300',
                                        'admin' => 'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-500/10 dark:text-primary-300',
                                        'manager' => 'bg-info-50 text-info-700 ring-info-600/20 dark:bg-info-500/10 dark:text-info-300',
                                        default => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-500/10 dark:text-success-300',
                                    };
                                @endphp

                                <tr
                                    data-role-login
                                    data-current-panel="{{ $panelId }}"
                                    data-panel="{{ $account['panel'] }}"
                                    data-route="{{ $account['route'] }}"
                                    data-email="{{ $account['email'] }}"
                                    data-password="{{ $account['password'] }}"
                                    @class([
                                        'cursor-pointer align-top transition hover:bg-gray-50/90 dark:hover:bg-white/5',
                                        'bg-primary-50/70 dark:bg-primary-500/10' => $isRecommended,
                                    ])
                                >
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 text-xs font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">
                                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr((string) $account['name'], 0, 1)) }}
                                            </div>

                                            <div class="min-w-0">
                                                <div class="font-semibold text-gray-950 dark:text-white">
                                                    {{ $account['name'] }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        <code class="rounded-md bg-gray-100 px-2 py-1 font-mono text-xs text-gray-900 dark:bg-white/10 dark:text-white">
                                            {{ $account['email'] }}
                                        </code>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        <code class="rounded-md bg-gray-100 px-2 py-1 font-mono text-xs text-gray-900 dark:bg-white/10 dark:text-white">
                                            {{ $account['password'] }}
                                        </code>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span @class([
                                            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                                            $roleClasses,
                                        ])>
                                            {{ $account['role'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 text-xs text-gray-500 dark:border-white/10 dark:text-gray-400">
                    <span>
                        {{ __('app.auth.login_page.total_users') }}
                        <span class="font-semibold text-gray-950 dark:text-white">{{ count($accounts) }}</span>
                    </span>

                    <span>{{ __('app.auth.login_page.click_hint') }}</span>
                </div>
            </div>
        @else
            <p class="text-gray-600 dark:text-gray-300">
                No active role-based accounts were found for quick login.
            </p>
        @endif
    </div>
</x-filament::section>

@once
    @push('scripts')
        <script>
            (() => {
                const selectors = {
                    email: [
                        'input[name="email"]',
                        'input[name="data.email"]',
                        'input[name="data[email]"]',
                        'input[type="email"]',
                        'input[id="data.email"]',
                        'input[id$="email"]',
                    ],
                    password: [
                        'input[name="password"]',
                        'input[name="data.password"]',
                        'input[name="data[password]"]',
                        'input[type="password"]',
                        'input[id="data.password"]',
                        'input[id$="password"]',
                    ],
                };

                const dispatchInputEvents = (element) => {
                    ['input', 'change'].forEach((eventName) => {
                        element.dispatchEvent(new Event(eventName, { bubbles: true }));
                    });
                };

                const findInput = (inputSelectors) => {
                    for (const selector of inputSelectors) {
                        const element = document.querySelector(selector);

                        if (element) {
                            return element;
                        }
                    }

                    return null;
                };

                const fillCredentials = (email, password) => {
                    const emailInput = findInput(selectors.email);
                    const passwordInput = findInput(selectors.password);

                    if (!emailInput || !passwordInput) {
                        return false;
                    }

                    emailInput.value = email;
                    dispatchInputEvents(emailInput);

                    passwordInput.value = password;
                    dispatchInputEvents(passwordInput);

                    return emailInput.value === email && passwordInput.value === password;
                };

                const applyPrefillFromUrl = () => {
                    const params = new URLSearchParams(window.location.search);
                    const email = params.get('prefill_email');
                    const password = params.get('prefill_password');

                    if (!email || !password) {
                        return;
                    }

                    let attempts = 0;
                    const maxAttempts = 20;

                    const attemptFill = () => {
                        attempts += 1;

                        const isFilled = fillCredentials(email, password);

                        if (attempts < maxAttempts) {
                            window.setTimeout(attemptFill, 120);
                            return;
                        }

                        if (!isFilled) {
                            return;
                        }

                        params.delete('prefill_email');
                        params.delete('prefill_password');

                        const query = params.toString();
                        const cleanUrl = `${window.location.pathname}${query ? `?${query}` : ''}${window.location.hash}`;
                        window.history.replaceState({}, '', cleanUrl);
                    };

                    attemptFill();
                };

                document.addEventListener('click', (event) => {
                    const target = event.target.closest('[data-role-login]');

                    if (!target) {
                        return;
                    }

                    event.preventDefault();

                    const email = target.dataset.email ?? '';
                    const password = target.dataset.password ?? '';
                    const route = target.dataset.route ?? '';

                    if (route) {
                        const targetUrl = new URL(route, window.location.origin);
                        const isSamePath = targetUrl.pathname === window.location.pathname;

                        if (! isSamePath) {
                            targetUrl.searchParams.set('prefill_email', email);
                            targetUrl.searchParams.set('prefill_password', password);
                            window.location.assign(targetUrl.toString());

                            return;
                        }
                    }

                    fillCredentials(email, password);
                });

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', applyPrefillFromUrl, { once: true });
                } else {
                    applyPrefillFromUrl();
                }

                document.addEventListener('livewire:init', applyPrefillFromUrl);
                document.addEventListener('livewire:initialized', applyPrefillFromUrl);
                document.addEventListener('livewire:navigated', applyPrefillFromUrl);
            })();
        </script>
    @endpush
@endonce
