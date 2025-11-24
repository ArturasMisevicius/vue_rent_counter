@extends('layouts.app')

@section('title', __('tenants.pages.admin_form.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('tenants.pages.admin_form.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('tenants.pages.admin_form.subtitle') }}</p>
        </div>
    </div>

    @if($errors->any())
        <div class="mt-4 rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">{{ __('tenants.pages.admin_form.errors_title') }}</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul role="list" class="list-disc space-y-1 pl-5">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-8">
        <form action="{{ route('admin.tenants.store') }}" method="POST" class="space-y-6">
            @csrf

            <x-card>
                <div class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium leading-6 text-slate-900">{{ __('tenants.pages.admin_form.labels.name') }}</label>
                        <div class="mt-2">
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium leading-6 text-slate-900">{{ __('tenants.pages.admin_form.labels.email') }}</label>
                        <div class="mt-2">
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ __('tenants.pages.admin_form.notes.credentials_sent') }}</p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium leading-6 text-slate-900">{{ __('tenants.pages.admin_form.labels.password') }}</label>
                        <div class="mt-2">
                            <input type="password" name="password" id="password" required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium leading-6 text-slate-900">{{ __('tenants.pages.admin_form.labels.password_confirmation') }}</label>
                        <div class="mt-2">
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                        </div>
                    </div>

                    <div>
                        <label for="property_id" class="block text-sm font-medium leading-6 text-slate-900">{{ __('tenants.pages.admin_form.labels.property') }}</label>
                        <div class="mt-2">
                            <select name="property_id" id="property_id" required
                                class="block w-full rounded-md border-0 py-1.5 text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                                <option value="">{{ __('tenants.pages.admin_form.placeholders.property') }}</option>
                                @foreach($properties as $property)
                                    <option value="{{ $property->id }}" {{ old('property_id') == $property->id ? 'selected' : '' }}>
                                        {{ $property->address }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @if($properties->isEmpty())
                            <p class="mt-1 text-sm text-red-600">{{ __('tenants.pages.admin_form.notes.no_properties') }}</p>
                        @endif
                    </div>
                </div>
            </x-card>

            <div class="flex items-center justify-end gap-x-6">
                <a href="{{ route('admin.tenants.index') }}" class="text-sm font-semibold leading-6 text-slate-900">{{ __('tenants.pages.admin_form.actions.cancel') }}</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                    {{ __('tenants.pages.admin_form.actions.submit') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
