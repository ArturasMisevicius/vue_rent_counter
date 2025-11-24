@extends('layouts.app')

@section('title', __('users.headings.create'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('users.headings.create') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('users.descriptions.index') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <x-form-input 
                        name="name" 
                        label="{{ __('users.labels.name') }}" 
                        :value="old('name')" 
                        required 
                    />

                    <x-form-input 
                        name="email" 
                        label="{{ __('users.labels.email') }}" 
                        type="email" 
                        :value="old('email')" 
                        required 
                    />

                    <x-form-select 
                        name="tenant_id" 
                        label="{{ __('users.tables.tenant') }}" 
                        :options="$tenants->pluck('name', 'id')" 
                        :selected="old('tenant_id')" 
                        required 
                    />

                    <x-form-select 
                        name="role" 
                        label="{{ __('users.labels.role') }}" 
                        :options="[
                            'admin' => __('enums.user_role.admin'),
                            'manager' => __('enums.user_role.manager'),
                            'tenant' => __('enums.user_role.tenant'),
                        ]" 
                        :selected="old('role')" 
                        required 
                    />

                    <x-form-input 
                        name="password" 
                        label="{{ __('users.labels.password') }}" 
                        type="password" 
                        required 
                    />

                    <x-form-input 
                        name="password_confirmation" 
                        label="{{ __('users.labels.password_confirmation') }}" 
                        type="password" 
                        required 
                    />
                </div>

                <div class="mt-6 flex items-center justify-end gap-x-3">
                    <a href="{{ route('admin.users.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                        {{ __('users.actions.back') }}
                    </a>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        {{ __('users.actions.create') }}
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
