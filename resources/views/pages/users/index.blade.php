@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@switch($role)
@case('admin')
@section('title', __('users.headings.index'))

@section('content')
<x-backoffice.page
    :title="__('users.headings.index')"
    :description="__('users.descriptions.index')"
>
    <x-slot name="actions">
        @can('create', App\Models\User::class)
            <x-button :href="route('admin.users.create')">
                {{ __('users.actions.add') }}
            </x-button>
        @endcan
    </x-slot>

    <x-card>
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-4 lg:grid-cols-[1fr_16rem_auto] lg:items-end">
            <x-form-input
                name="search"
                :label="__('users.actions.filter')"
                :value="request('search')"
                :placeholder="__('users.labels.name') . '/' . __('users.labels.email')"
            />

            <x-form-select
                name="role"
                :label="__('users.labels.role')"
                :options="[
                    'manager' => __('enums.user_role.manager'),
                    'tenant' => __('enums.user_role.tenant'),
                ]"
                :selected="request('role')"
                :placeholder="__('users.actions.clear')"
            />

            <div class="flex flex-wrap gap-2 lg:justify-end">
                <x-button type="submit">
                    {{ __('users.actions.filter') }}
                </x-button>
                @if(request()->hasAny(['search', 'role']))
                    <x-button variant="secondary" :href="route('admin.users.index')">
                        {{ __('users.actions.clear') }}
                    </x-button>
                @endif
            </div>
        </form>
    </x-card>

    <div class="hidden sm:block">
        <x-data-table caption="Users list">
            <x-slot name="header">
                <tr>
                    <x-sortable-header column="name" label="{{ __('users.tables.name') }}" class="py-3.5 pl-4 pr-3 sm:pl-6" />
                    <x-sortable-header column="email" label="{{ __('users.tables.email') }}" />
                    <x-sortable-header column="role" label="{{ __('users.tables.role') }}" />
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('users.tables.shared') }}</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">{{ __('users.tables.actions') }}</span>
                    </th>
                </tr>
            </x-slot>

            @forelse($users as $user)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                        {{ $user->name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $user->email }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <x-status-badge :status="$user->role->value">
                            {{ ucfirst($user->role->value) }}
                        </x-status-badge>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $user->tenant->name ?? __('providers.statuses.not_available') }}
                    </td>
                    <td class="py-4 pl-3 pr-4 sm:pr-6">
                        <div class="flex justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.users.show', $user)" class="px-3 py-1.5 text-xs">
                                {{ __('users.actions.view') }}
                            </x-button>
                            @can('update', $user)
                                <x-button :href="route('admin.users.edit', $user)" class="px-3 py-1.5 text-xs">
                                    {{ __('users.actions.edit') }}
                                </x-button>
                            @endcan
                            @can('delete', $user)
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('{{ __('users.actions.delete') }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <x-button variant="danger" type="submit" class="px-3 py-1.5 text-xs">
                                        {{ __('users.actions.delete') }}
                                    </x-button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500">
                        {{ __('users.empty.users') }}
                    </td>
                </tr>
            @endforelse
        </x-data-table>
    </div>

    <div class="space-y-3 sm:hidden">
        @forelse($users as $user)
            <x-card class="px-4 py-3">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                    <p class="text-xs text-slate-600">{{ $user->email }}</p>
                    <p class="text-xs text-slate-600">{{ __('users.labels.role') }}: {{ enum_label($user->role) }}</p>
                    <p class="text-xs text-slate-600">{{ __('users.tables.shared') }}: {{ $user->tenant->name ?? __('providers.statuses.not_available') }}</p>
                </div>

                <div class="mt-3 flex flex-col gap-2">
                    <x-button variant="secondary" :href="route('admin.users.show', $user)">
                        {{ __('users.actions.view') }}
                    </x-button>
                    @can('update', $user)
                        <x-button :href="route('admin.users.edit', $user)">
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
        @empty
            <x-card class="border-dashed text-center text-sm text-slate-600">
                {{ __('users.empty.users') }}
            </x-card>
        @endforelse
    </div>

    <div>
        {{ $users->links() }}
    </div>
</x-backoffice.page>
@endsection
@break

@default
@section('title', __('users.headings.index'))

@section('content')
<x-backoffice.page
    :title="__('users.headings.index')"
    :description="__('users.descriptions.index')"
>
    <x-slot name="actions">
        @can('create', App\Models\User::class)
            <x-button :href="route('admin.users.create')">
                {{ __('users.actions.add') }}
            </x-button>
        @endcan
    </x-slot>

    <x-card>
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid gap-4 lg:grid-cols-[1fr_16rem_auto] lg:items-end">
            <x-form-input
                name="search"
                :label="__('users.actions.filter')"
                :value="request('search')"
                :placeholder="__('users.labels.name') . '/' . __('users.labels.email')"
            />

            <x-form-select
                name="role"
                :label="__('users.labels.role')"
                :options="[
                    'manager' => __('enums.user_role.manager'),
                    'tenant' => __('enums.user_role.tenant'),
                ]"
                :selected="request('role')"
                :placeholder="__('users.actions.clear')"
            />

            <div class="flex flex-wrap gap-2 lg:justify-end">
                <x-button type="submit">
                    {{ __('users.actions.filter') }}
                </x-button>
                @if(request()->hasAny(['search', 'role']))
                    <x-button variant="secondary" :href="route('admin.users.index')">
                        {{ __('users.actions.clear') }}
                    </x-button>
                @endif
            </div>
        </form>
    </x-card>

    <div class="hidden sm:block">
        <x-data-table caption="Users list">
            <x-slot name="header">
                <tr>
                    <x-sortable-header column="name" label="{{ __('users.tables.name') }}" class="py-3.5 pl-4 pr-3 sm:pl-6" />
                    <x-sortable-header column="email" label="{{ __('users.tables.email') }}" />
                    <x-sortable-header column="role" label="{{ __('users.tables.role') }}" />
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('users.tables.shared') }}</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                        <span class="sr-only">{{ __('users.tables.actions') }}</span>
                    </th>
                </tr>
            </x-slot>

            @forelse($users as $user)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                        {{ $user->name }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $user->email }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <x-status-badge :status="$user->role->value">
                            {{ ucfirst($user->role->value) }}
                        </x-status-badge>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $user->tenant->name ?? __('providers.statuses.not_available') }}
                    </td>
                    <td class="py-4 pl-3 pr-4 sm:pr-6">
                        <div class="flex justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.users.show', $user)" class="px-3 py-1.5 text-xs">
                                {{ __('users.actions.view') }}
                            </x-button>
                            @can('update', $user)
                                <x-button :href="route('admin.users.edit', $user)" class="px-3 py-1.5 text-xs">
                                    {{ __('users.actions.edit') }}
                                </x-button>
                            @endcan
                            @can('delete', $user)
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" onsubmit="return confirm('{{ __('users.actions.delete') }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <x-button variant="danger" type="submit" class="px-3 py-1.5 text-xs">
                                        {{ __('users.actions.delete') }}
                                    </x-button>
                                </form>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-3 py-8 text-center text-sm text-slate-500">
                        {{ __('users.empty.users') }}
                    </td>
                </tr>
            @endforelse
        </x-data-table>
    </div>

    <div class="space-y-3 sm:hidden">
        @forelse($users as $user)
            <x-card class="px-4 py-3">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                    <p class="text-xs text-slate-600">{{ $user->email }}</p>
                    <p class="text-xs text-slate-600">{{ __('users.labels.role') }}: {{ enum_label($user->role) }}</p>
                    <p class="text-xs text-slate-600">{{ __('users.tables.shared') }}: {{ $user->tenant->name ?? __('providers.statuses.not_available') }}</p>
                </div>

                <div class="mt-3 flex flex-col gap-2">
                    <x-button variant="secondary" :href="route('admin.users.show', $user)">
                        {{ __('users.actions.view') }}
                    </x-button>
                    @can('update', $user)
                        <x-button :href="route('admin.users.edit', $user)">
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
        @empty
            <x-card class="border-dashed text-center text-sm text-slate-600">
                {{ __('users.empty.users') }}
            </x-card>
        @endforelse
    </div>

    <div>
        {{ $users->links() }}
    </div>
</x-backoffice.page>
@endsection
@endswitch
