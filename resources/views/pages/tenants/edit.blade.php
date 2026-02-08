@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('admin')
@extends('layouts.app')

@section('title', __('tenants.pages.admin_form.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('common.edit') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('tenants.headings.account') }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mt-4 rounded-md bg-red-50 p-4">
            <div class="text-sm text-red-700">
                <ul role="list" class="list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="mt-8">
        <form action="{{ route('admin.tenants.update', $tenant) }}" method="POST" class="space-y-6">
            @csrf
            @method('PATCH')

            <x-card>
                <div class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium leading-6 text-slate-900">{{ __('tenants.labels.name') }}</label>
                        <div class="mt-2">
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $tenant->name) }}"
                                required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium leading-6 text-slate-900">{{ __('tenants.labels.email') }}</label>
                        <div class="mt-2">
                            <input
                                type="email"
                                name="email"
                                id="email"
                                value="{{ old('email', $tenant->email) }}"
                                required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            >
                        </div>
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-x-6">
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-sm font-semibold leading-6 text-slate-900">{{ __('tenants.pages.admin_form.actions.cancel') }}</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    {{ __('tenants.pages.admin_form.actions.submit') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
@break

@default
@extends('layouts.app')

@section('title', __('tenants.pages.admin_form.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('common.edit') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('tenants.headings.account') }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mt-4 rounded-md bg-red-50 p-4">
            <div class="text-sm text-red-700">
                <ul role="list" class="list-disc space-y-1 pl-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="mt-8">
        <form action="{{ route('admin.tenants.update', $tenant) }}" method="POST" class="space-y-6">
            @csrf
            @method('PATCH')

            <x-card>
                <div class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium leading-6 text-slate-900">{{ __('tenants.labels.name') }}</label>
                        <div class="mt-2">
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name', $tenant->name) }}"
                                required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            >
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium leading-6 text-slate-900">{{ __('tenants.labels.email') }}</label>
                        <div class="mt-2">
                            <input
                                type="email"
                                name="email"
                                id="email"
                                value="{{ old('email', $tenant->email) }}"
                                required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                            >
                        </div>
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-x-6">
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-sm font-semibold leading-6 text-slate-900">{{ __('tenants.pages.admin_form.actions.cancel') }}</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    {{ __('tenants.pages.admin_form.actions.submit') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
@endswitch
