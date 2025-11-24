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
</x-tenant.page>
@endsection
