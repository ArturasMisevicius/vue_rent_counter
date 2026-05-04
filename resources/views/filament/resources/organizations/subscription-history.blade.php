<div class="space-y-6">
    <section>
        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">{{ __('superadmin.organizations.subscription_history.payments') }}</h3>

        <div class="mt-3 space-y-3">
            @forelse ($subscription->payments as $payment)
                @php
                    $durationValue = $payment->duration?->value;
                    $durationLabel = $durationValue !== null
                        ? __("enums.subscription_duration.{$durationValue}")
                        : __('superadmin.organizations.subscription_history.custom_duration');
                @endphp
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ \App\Filament\Support\Formatting\EuMoneyFormatter::format($payment->amount, $payment->currency) }}
                    </p>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ __('superadmin.organizations.subscription_history.paid_on', [
                            'duration' => $durationLabel,
                            'date' => $payment->paid_at?->locale(app()->getLocale())->isoFormat('ll') ?? __('superadmin.organizations.subscription_history.unknown_date'),
                        ]) }}
                    </p>
                    <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-400">
                        {{ $payment->reference }}
                    </p>
                </div>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-500">{{ __('superadmin.organizations.subscription_history.empty_payments') }}</p>
            @endforelse
        </div>
    </section>

    <section>
        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-500">{{ __('superadmin.organizations.subscription_history.renewals') }}</h3>

        <div class="mt-3 space-y-3">
            @forelse ($subscription->renewals as $renewal)
                @php
                    $methodKey = "superadmin.organizations.subscription_history.methods.{$renewal->method}";
                    $methodLabel = __($methodKey);
                    $periodKey = "enums.subscription_duration.{$renewal->period}";
                    $periodLabel = __($periodKey);
                @endphp
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ __('superadmin.organizations.subscription_history.renewal_title', [
                            'method' => $methodLabel === $methodKey ? __('superadmin.organizations.subscription_history.unknown') : $methodLabel,
                        ]) }}
                    </p>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ __('superadmin.organizations.subscription_history.period_range', [
                            'from' => $renewal->old_expires_at?->locale(app()->getLocale())->isoFormat('ll') ?? __('superadmin.organizations.subscription_history.unknown'),
                            'to' => $renewal->new_expires_at?->locale(app()->getLocale())->isoFormat('ll') ?? __('superadmin.organizations.subscription_history.unknown'),
                        ]) }}
                    </p>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ $renewal->user?->name ?? __('superadmin.organizations.subscription_history.system') }}
                        ·
                        {{ $periodLabel === $periodKey ? __('superadmin.organizations.subscription_history.custom_duration') : $periodLabel }}
                    </p>
                </div>
            @empty
                <p class="rounded-2xl border border-dashed border-slate-200 px-4 py-3 text-sm text-slate-500">{{ __('superadmin.organizations.subscription_history.empty_renewals') }}</p>
            @endforelse
        </div>
    </section>
</div>
