@extends('layouts.tenant')

@section('title', __('tenant.profile.title'))

@section('tenant-content')
<x-tenant.page :title="__('tenant.profile.title')" :description="__('tenant.profile.description')">
    <x-tenant.quick-actions />

    @if(session('success'))
        <x-tenant.alert type="success">
            {{ session('success') }}
        </x-tenant.alert>
    @endif

    <x-tenant.section-card :title="__('tenant.profile.account_information')">
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
    </x-tenant.section-card>

    <x-tenant.section-card :title="__('tenant.profile.language_preference')">
        <form method="POST" action="{{ route('locale.set') }}">
            @csrf
            <x-tenant.stack gap="4">
                <div>
                    <label for="locale" class="block text-sm font-semibold text-slate-800">{{ __('tenant.profile.language.select') }}</label>
                    <select name="locale" id="locale" onchange="this.form.submit()" class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach(\App\Models\Language::query()->where('is_active', true)->orderBy('display_order')->get() as $language)
                            <option value="{{ $language->code }}" {{ $language->code === app()->getLocale() ? 'selected' : '' }}>
                                {{ $language->native_name ?? $language->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-sm text-slate-600">{{ __('tenant.profile.language.note') }}</p>
                </div>
            </x-tenant.stack>
        </form>
    </x-tenant.section-card>

    @if($user->property)
    <x-tenant.section-card :title="__('tenant.profile.assigned_property')">
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
    </x-tenant.section-card>
    @endif

    @if($user->parentUser)
    <x-tenant.section-card :title="__('tenant.profile.manager_contact.title')" :description="__('tenant.profile.manager_contact.description')">
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
    </x-tenant.section-card>
    @endif

    <x-tenant.section-card :title="__('tenant.profile.update_profile')" :description="__('tenant.profile.update_description')">
        <form method="POST" action="{{ route('tenant.profile.update') }}">
            @csrf
            @method('PUT')
            
            <x-tenant.stack gap="4">
                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-800">{{ __('tenant.profile.labels.name') }}</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                           class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('name') border-rose-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-semibold text-slate-800">{{ __('tenant.profile.labels.email') }}</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                           class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('email') border-rose-500 @enderror">
                    @error('email')
                        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="border-t border-slate-200 pt-4">
                    <h4 class="text-sm font-semibold text-slate-900 mb-3">{{ __('tenant.profile.change_password') }}</h4>
                    <p class="text-sm text-slate-600 mb-4">{{ __('tenant.profile.password_note') }}</p>
                    
                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm font-semibold text-slate-800">{{ __('tenant.profile.labels.current_password') }}</label>
                            <input type="password" name="current_password" id="current_password" autocomplete="current-password"
                                   class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('current_password') border-rose-500 @enderror">
                            @error('current_password')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-slate-800">{{ __('tenant.profile.labels.new_password') }}</label>
                            <input type="password" name="password" id="password" autocomplete="new-password"
                                   class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('password') border-rose-500 @enderror">
                            @error('password')
                                <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-slate-800">{{ __('tenant.profile.labels.confirm_password') }}</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password"
                                   class="mt-1 block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('tenant.profile.save_changes') }}
                    </button>
                </div>
            </x-tenant.stack>
        </form>
    </x-tenant.section-card>
</x-tenant.page>
@endsection
