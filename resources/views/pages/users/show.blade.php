@php
    $role = auth()->user()?->role?->value;
    $isAdmin = $role === 'admin';
    $backRoute = $isAdmin ? route('admin.users.index') : route('superadmin.dashboard');
    $organizationName = isset($organization) && $organization
        ? $organization->name
        : ($user->tenant->name ?? __('providers.statuses.not_available'));
@endphp

@extends('layouts.app')

@section('title', __('users.headings.show'))

@section('content')
<x-backoffice.page
    :title="__('users.headings.show')"
    :description="__('users.descriptions.index')"
>
    <x-slot name="actions">
        @if($isAdmin)
            @can('update', $user)
                <x-button :href="route('admin.users.edit', $user)">
                    {{ __('users.actions.edit') }}
                </x-button>
            @endcan
        @endif

        <x-button variant="secondary" :href="$backRoute">
            {{ __('users.actions.back') }}
        </x-button>
    </x-slot>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 space-y-6">
            <x-card :title="__('users.headings.information')">
                <dl class="divide-y divide-slate-200">
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('users.labels.name') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $user->name }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('users.labels.email') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $user->email }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('users.labels.role') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                            <x-status-badge :status="$user->role->value">
                                {{ enum_label($user->role) }}
                            </x-status-badge>
                        </dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('users.tables.shared') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $organizationName }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('users.labels.created_at') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $user->created_at->format('Y-m-d H:i') }}</dd>
                    </div>
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('users.labels.updated_at') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $user->updated_at->format('Y-m-d H:i') }}</dd>
                    </div>
                </dl>
            </x-card>

            @if($user->meterReadings->isNotEmpty())
                <x-card :title="__('users.labels.activity_history')">
                    <div class="text-sm text-slate-700">
                        <p class="mb-2"><strong>{{ __('users.labels.meter_readings_entered') }}:</strong> {{ $user->meterReadings->count() }}</p>
                        <p class="text-xs text-slate-500">{{ __('users.labels.activity_hint', ['count' => $user->meterReadings->count()]) }}</p>
                    </div>
                </x-card>
            @else
                <x-card :title="__('users.labels.activity_history')">
                    <p class="text-sm text-slate-500">{{ __('users.labels.no_activity') }}</p>
                </x-card>
            @endif
        </div>

        @if($isAdmin)
            <div class="lg:col-span-1">
                <x-card :title="__('users.headings.quick_actions')">
                    <div class="space-y-3">
                        @can('update', $user)
                            <x-button variant="secondary" :href="route('admin.users.edit', $user)" class="w-full">
                                {{ __('users.actions.edit') }}
                            </x-button>
                        @endcan

                        @can('delete', $user)
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('{{ __('users.actions.delete') }}?');">
                                @csrf
                                @method('DELETE')
                                <x-button variant="danger" type="submit" class="w-full">
                                    {{ __('users.actions.delete') }}
                                </x-button>
                            </form>
                        @endcan
                    </div>
                </x-card>
            </div>
        @endif
    </div>
</x-backoffice.page>
@endsection
