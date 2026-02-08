@extends('layouts.tenant')

@section('title', __('tenant.profile.title'))

@section('tenant-content')
<x-profile.shell
    :title="__('tenant.profile.title')"
    :description="__('tenant.profile.description')"
>
    <x-profile.messages :error-title="__('tenant.profile.alerts.errors')" />

    <x-card :title="__('tenant.profile.account_information')">
        <p class="mb-4 text-sm text-slate-600">{{ __('tenant.profile.account_information_description') }}</p>
        <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.profile.labels.name') }}</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.profile.labels.email') }}</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.profile.labels.role') }}</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ enum_label($user->role) }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.profile.labels.created') }}</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $user->created_at->format('Y-m-d') }}</dd>
            </div>
        </dl>
    </x-card>

    <x-profile.language-card
        :languages="$languages"
        :title="__('tenant.profile.language_preference')"
        :description="__('tenant.profile.language.description')"
    />

    @if($user->property)
        <x-card :title="__('tenant.profile.assigned_property')">
            <p class="mb-4 text-sm text-slate-600">{{ __('tenant.profile.assigned_property_description') }}</p>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.profile.labels.address') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->property->address }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.profile.labels.type') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ enum_label($user->property->type) }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.profile.labels.area') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->property->area_sqm }} m²</dd>
                </div>
                @if($user->property->building)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.profile.labels.building') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $user->property->building->display_name }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.property.labels.building_address') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $user->property->building->address }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>
    @endif

    @if($user->parentUser)
        <x-card :title="__('tenant.profile.manager_contact.title')">
            <p class="mb-4 text-sm text-slate-600">{{ __('tenant.profile.manager_contact.description') }}</p>
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                @if($user->parentUser->organization_name)
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.profile.labels.organization') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $user->parentUser->organization_name }}</dd>
                    </div>
                @endif
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.profile.labels.contact_name') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $user->parentUser->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">{{ __('tenant.profile.labels.email') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900">
                        <a href="mailto:{{ $user->parentUser->email }}" class="text-indigo-700 font-semibold hover:text-indigo-800">
                            {{ $user->parentUser->email }}
                        </a>
                    </dd>
                </div>
            </dl>
        </x-card>
    @endif

    <x-card :title="__('tenant.profile.update_profile')">
        <p class="mb-4 text-sm text-slate-600">{{ __('tenant.profile.update_description') }}</p>
        <form method="POST" action="{{ route('tenant.profile.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-800">{{ __('tenant.profile.labels.name') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-rose-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-800">{{ __('tenant.profile.labels.email') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-rose-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="border-t border-slate-200 pt-4">
                <h4 class="mb-2 text-sm font-semibold text-slate-900">{{ __('tenant.profile.change_password') }}</h4>
                <p class="mb-4 text-sm text-slate-600">{{ __('tenant.profile.password_note') }}</p>

                <div class="space-y-4">
                    <div>
                        <label for="current_password" class="block text-sm font-semibold text-slate-800">{{ __('tenant.profile.labels.current_password') }}</label>
                        <input type="password" name="current_password" id="current_password" autocomplete="current-password" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('current_password') border-rose-500 @enderror">
                        @error('current_password')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-800">{{ __('tenant.profile.labels.new_password') }}</label>
                        <input type="password" name="password" id="password" autocomplete="new-password" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('password') border-rose-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold text-slate-800">{{ __('tenant.profile.labels.confirm_password') }}</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <x-button type="submit">
                    {{ __('tenant.profile.save_changes') }}
                </x-button>
            </div>
        </form>
    </x-card>
</x-profile.shell>
@endsection
