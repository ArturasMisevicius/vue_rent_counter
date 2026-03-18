@php($sessionLifetimeMs = max((int) config('session.lifetime', 120), 1) * 60 * 1000)
@php($cspNonce = \Illuminate\Support\Facades\Vite::cspNonce())

<div
    data-session-expiry-monitor
    data-session-expiry-timeout="{{ $sessionLifetimeMs }}"
    data-session-expiry-title="{{ __('auth.session_expired_title') }}"
    data-session-expiry-message="{{ __('auth.session_expired') }}"
    data-session-expiry-action="{{ __('auth.login_button') }}"
></div>

@once
    <script @if($cspNonce) nonce="{{ $cspNonce }}" @endif>
        (() => {
            if (window.__tenantoSessionExpiryMonitorBooted) {
                return;
            }

            window.__tenantoSessionExpiryMonitorBooted = true;

            const selector = '[data-session-expiry-monitor]';
            const activityEvents = ['pointerdown', 'keydown', 'scroll', 'touchstart'];
            const maxDelay = 2147483647;
            let timeoutId = null;
            let monitor = null;
            let isExpired = false;

            const clearTimer = () => {
                if (timeoutId !== null) {
                    window.clearTimeout(timeoutId);
                    timeoutId = null;
                }
            };

            const dialog = () => {
                let element = document.getElementById('session-expiry-dialog');

                if (element instanceof HTMLElement) {
                    return element;
                }

                element = document.createElement('div');
                element.id = 'session-expiry-dialog';
                element.setAttribute('hidden', 'hidden');
                element.className = 'fixed inset-0 z-[999] flex items-center justify-center bg-slate-950/60 px-4';
                element.innerHTML = `
                    <div class="w-full max-w-md rounded-[1.75rem] border border-amber-200 bg-white p-6 shadow-2xl shadow-slate-950/20">
                        <div class="space-y-3">
                            <h2 data-session-expiry-dialog-title class="font-display text-2xl tracking-tight text-slate-950"></h2>
                            <p data-session-expiry-dialog-message class="text-sm leading-6 text-slate-600"></p>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <button
                                type="button"
                                data-session-expiry-dialog-action
                                class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-950/20 transition hover:bg-slate-800"
                            ></button>
                        </div>
                    </div>
                `;

                document.body.appendChild(element);

                element.querySelector('[data-session-expiry-dialog-action]')?.addEventListener('click', () => {
                    window.location.assign(window.location.href);
                });

                return element;
            };

            const showExpiredPrompt = () => {
                if (!(monitor instanceof HTMLElement) || isExpired) {
                    return;
                }

                isExpired = true;
                clearTimer();

                const element = dialog();
                const title = monitor.dataset.sessionExpiryTitle ?? 'Session expired';
                const message = monitor.dataset.sessionExpiryMessage ?? '';
                const action = monitor.dataset.sessionExpiryAction ?? 'Log In';

                element.querySelector('[data-session-expiry-dialog-title]')?.replaceChildren(document.createTextNode(title));
                element.querySelector('[data-session-expiry-dialog-message]')?.replaceChildren(document.createTextNode(message));
                element.querySelector('[data-session-expiry-dialog-action]')?.replaceChildren(document.createTextNode(action));
                element.removeAttribute('hidden');
            };

            const schedulePrompt = () => {
                if (!(monitor instanceof HTMLElement) || isExpired) {
                    return;
                }

                const timeout = Number(monitor.dataset.sessionExpiryTimeout ?? 0);

                if (! Number.isFinite(timeout) || timeout <= 0) {
                    return;
                }

                clearTimer();
                timeoutId = window.setTimeout(showExpiredPrompt, Math.min(timeout, maxDelay));
            };

            const refreshMonitor = () => {
                monitor = document.querySelector(selector);
                isExpired = false;

                const element = document.getElementById('session-expiry-dialog');

                if (element instanceof HTMLElement) {
                    element.setAttribute('hidden', 'hidden');
                }

                schedulePrompt();
            };

            activityEvents.forEach((eventName) => {
                document.addEventListener(eventName, () => {
                    if (! isExpired) {
                        schedulePrompt();
                    }
                }, { passive: true });
            });

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && ! isExpired) {
                    schedulePrompt();
                }
            });

            document.addEventListener('livewire:navigated', refreshMonitor);

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', refreshMonitor, { once: true });
            } else {
                refreshMonitor();
            }
        })();
    </script>
@endonce
