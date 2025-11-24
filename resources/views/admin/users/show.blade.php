@extends('layouts.app')

@section('title', __('users.headings.show'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('users.headings.show') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('users.descriptions.index') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none space-x-3">
            @can('update', $user)
            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                {{ __('users.actions.edit') }}
            </a>
            @endcan
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                {{ __('users.actions.back') }}
            </a>
        </div>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- User Information -->
        <div class="lg:col-span-2">
            <x-card title="{{ __('users.headings.information') }}">
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
                        <dt class="text-sm font-medium text-slate-500">{{ __('users.tables.tenant') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $user->tenant->name ?? __('providers.statuses.not_available') }}</dd>
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
            <div class="mt-6">
                <x-card title="{{ __('users.labels.activity_history') }}">
                    <div class="text-sm text-slate-700">
                        <p class="mb-2"><strong>{{ __('users.labels.meter_readings_entered') }}:</strong> {{ $user->meterReadings->count() }}</p>
                        <p class="text-xs text-slate-500">{{ __('users.labels.activity_hint', ['count' => $user->meterReadings->count()]) }}</p>
                    </div>
                </x-card>
            </div>
            @else
            <div class="mt-6">
                <x-card title="{{ __('users.labels.activity_history') }}">
                    <p class="text-sm text-slate-500">{{ __('users.labels.no_activity') }}</p>
                </x-card>
            </div>
            @endif
        </div>

        <!-- Quick Actions -->
        <div class="lg:col-span-1">
            <x-card title="{{ __('users.headings.quick_actions') }}">
                <div class="space-y-3">
                    @can('update', $user)
                    <a href="{{ route('admin.users.edit', $user) }}" class="block w-full rounded-md bg-white px-3 py-2 text-center text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                        {{ __('users.actions.edit') }}
                    </a>
                    @endcan
                    
                    @can('delete', $user)
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('{{ __('users.actions.delete') }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="block w-full rounded-md bg-red-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                            {{ __('users.actions.delete') }}
                        </button>
                    </form>
                    @endcan
                </div>
            </x-card>
        </div>
    </div>
</div>
@endsection
