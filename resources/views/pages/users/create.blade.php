@extends('layouts.app')

@section('title', __('users.headings.create'))

@section('content')
<x-backoffice.page
    :title="__('users.headings.create')"
    :description="__('users.descriptions.index')"
>
    <x-slot name="actions">
        <x-button variant="secondary" :href="route('admin.users.index')">
            {{ __('users.actions.back') }}
        </x-button>
    </x-slot>

    <x-card class="max-w-2xl">
        <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
            @csrf

            <x-form-input
                name="name"
                :label="__('users.labels.name')"
                :value="old('name')"
                required
            />

            <x-form-input
                name="email"
                :label="__('users.labels.email')"
                type="email"
                :value="old('email')"
                required
            />

            <x-form-select
                name="tenant_id"
                :label="__('users.tables.shared')"
                :options="$tenants->pluck('name', 'id')"
                :selected="old('tenant_id')"
                required
            />

            <x-form-select
                name="role"
                :label="__('users.labels.role')"
                :options="[
                    'admin' => __('enums.user_role.shared'),
                    'manager' => __('enums.user_role.shared'),
                    'tenant' => __('enums.user_role.shared'),
                ]"
                :selected="old('role')"
                required
            />

            <x-form-input
                name="password"
                :label="__('users.labels.password')"
                type="password"
                required
            />

            <x-form-input
                name="password_confirmation"
                :label="__('users.labels.password_confirmation')"
                type="password"
                required
            />

            <div class="flex justify-end">
                <x-button type="submit">
                    {{ __('users.actions.create') }}
                </x-button>
            </div>
        </form>
    </x-card>
</x-backoffice.page>
@endsection
