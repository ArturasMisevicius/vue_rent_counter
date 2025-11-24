@extends('layouts.app')

@section('title', __('users.headings.index'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item :href="route('admin.dashboard')">{{ __('app.nav.dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">{{ __('users.labels.users') }}</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('users.headings.index') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('users.descriptions.index') }}</p>
        </div>
        @can('create', App\Models\User::class)
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            <a href="{{ route('admin.users.create') }}" class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                {{ __('users.actions.add') }}
            </a>
        </div>
        @endcan
    </div>

    {{-- Search and Filter Form --}}
    <div class="mt-6 bg-white shadow rounded-lg p-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="space-y-4 sm:space-y-0 sm:flex sm:items-end sm:space-x-4">
            <div class="flex-1">
                <label for="search" class="block text-sm font-medium text-slate-700">{{ __('users.actions.filter') }}</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" 
                       placeholder="{{ __('users.labels.name') }}/{{ __('users.labels.email') }}"
                       class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div class="sm:w-48">
                <label for="role" class="block text-sm font-medium text-slate-700">{{ __('users.labels.role') }}</label>
                <select name="role" id="role" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">{{ __('users.actions.clear') }}</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>{{ __('enums.user_role.admin') }}</option>
                    <option value="manager" {{ request('role') === 'manager' ? 'selected' : '' }}>{{ __('enums.user_role.manager') }}</option>
                    <option value="tenant" {{ request('role') === 'tenant' ? 'selected' : '' }}>{{ __('enums.user_role.tenant') }}</option>
                </select>
            </div>
            <div class="flex space-x-2">
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('users.actions.filter') }}
                </button>
                @if(request()->hasAny(['search', 'role']))
                <a href="{{ route('admin.users.index') }}" class="inline-flex items-center px-4 py-2 border border-slate-300 text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('users.actions.clear') }}
                </a>
                @endif
            </div>
        </form>
    </div>

    <div class="mt-8">
        <div class="hidden sm:block">
            <x-data-table caption="Users list">
                <x-slot name="header">
                    <tr>
                        <x-sortable-header column="name" label="{{ __('users.tables.name') }}" class="py-3.5 pl-4 pr-3 sm:pl-6" />
                        <x-sortable-header column="email" label="{{ __('users.tables.email') }}" />
                        <x-sortable-header column="role" label="{{ __('users.tables.role') }}" />
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('users.tables.tenant') }}</th>
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
                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                        <a href="{{ route('admin.users.show', $user) }}" class="text-indigo-600 hover:text-indigo-900 mr-4">
                            {{ __('users.actions.view') }}
                        </a>
                        @can('update', $user)
                        <a href="{{ route('admin.users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-900 mr-4">
                            {{ __('users.actions.edit') }}
                        </a>
                        @endcan
                        @can('delete', $user)
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('users.actions.delete') }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">
                                {{ __('users.actions.delete') }}
                            </button>
                        </form>
                        @endcan
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

        <div class="sm:hidden space-y-3">
            @forelse($users as $user)
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $user->name }}</p>
                        <p class="text-xs text-slate-600">{{ $user->email }}</p>
                        <p class="text-xs text-slate-600 mt-1">{{ __('users.labels.role') }}: {{ enum_label($user->role) }}</p>
                        <p class="text-xs text-slate-600">{{ __('users.tables.tenant') }}: {{ $user->tenant->name ?? __('providers.statuses.not_available') }}</p>
                    </div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('admin.users.show', $user) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:border-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('users.actions.view') }}
                    </a>
                    @can('update', $user)
                    <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('users.actions.edit') }}
                    </a>
                    @endcan
                    @can('delete', $user)
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline w-full" onsubmit="return confirm('{{ __('users.actions.delete') }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-rose-200 bg-white px-3 py-2 text-sm font-semibold text-rose-700 shadow-sm transition hover:border-rose-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500">
                            {{ __('users.actions.delete') }}
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
            @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                {{ __('users.empty.users') }}
            </div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection
