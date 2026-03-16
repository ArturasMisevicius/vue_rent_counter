@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@switch($role)
@case('superadmin')
@section('content')
@php
    $invitationStatus = $invitation->status ?? ($invitation->isPending() ? 'pending' : 'inactive');
    $invitationStatusLabel = __('organizations.pages.invitation_show.statuses.' . $invitationStatus);

    if ($invitationStatusLabel === 'organizations.pages.invitation_show.statuses.' . $invitationStatus) {
        $invitationStatusLabel = \Illuminate\Support\Str::headline($invitationStatus);
    }
@endphp
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">{{ $invitation->organization_name ?? __('organizations.pages.invitation_show.title_fallback') }}</h1>
                <p class="text-slate-600 mt-2">{{ $invitation->email }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('superadmin.dashboard') }}" class="px-4 py-2 bg-slate-300 text-slate-700 rounded hover:bg-slate-400">{{ __('app.cta.back') }}</a>
            </div>
        </div>

        <x-card>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('organizations.pages.invitation_show.labels.status') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $invitationStatusLabel }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('organizations.pages.invitation_show.labels.plan') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ enum_label($invitation->plan_type, \App\Enums\SubscriptionPlanType::class) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('organizations.pages.invitation_show.labels.expires') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $invitation->expires_at?->locale(app()->getLocale())->translatedFormat('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('organizations.pages.invitation_show.labels.token') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 font-mono break-all">{{ $invitation->token }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</div>
@endsection
@break

@default
@section('content')
@php
    $invitationStatus = $invitation->status ?? ($invitation->isPending() ? 'pending' : 'inactive');
    $invitationStatusLabel = __('organizations.pages.invitation_show.statuses.' . $invitationStatus);

    if ($invitationStatusLabel === 'organizations.pages.invitation_show.statuses.' . $invitationStatus) {
        $invitationStatusLabel = \Illuminate\Support\Str::headline($invitationStatus);
    }
@endphp
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">{{ $invitation->organization_name ?? __('organizations.pages.invitation_show.title_fallback') }}</h1>
                <p class="text-slate-600 mt-2">{{ $invitation->email }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('superadmin.dashboard') }}" class="px-4 py-2 bg-slate-300 text-slate-700 rounded hover:bg-slate-400">{{ __('app.cta.back') }}</a>
            </div>
        </div>

        <x-card>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('organizations.pages.invitation_show.labels.status') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $invitationStatusLabel }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('organizations.pages.invitation_show.labels.plan') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ enum_label($invitation->plan_type, \App\Enums\SubscriptionPlanType::class) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('organizations.pages.invitation_show.labels.expires') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $invitation->expires_at?->locale(app()->getLocale())->translatedFormat('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">{{ __('organizations.pages.invitation_show.labels.token') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 font-mono break-all">{{ $invitation->token }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</div>
@endsection
@endswitch
