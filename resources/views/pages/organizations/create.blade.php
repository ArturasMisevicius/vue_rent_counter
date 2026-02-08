@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@switch($role)
@case('superadmin')
@section('content')
@php
    $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::EUROPE);
    $selectedTimezone = old('timezone', 'Europe/Vilnius');
    if (!in_array($selectedTimezone, $timezones, true)) {
        array_unshift($timezones, $selectedTimezone);
    }
@endphp
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900">Create Organization</h1>
        </div>

        <x-card>
            <form method="POST" action="{{ route('superadmin.organizations.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="name">Name</label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded" id="name" name="name" type="text" value="{{ old('name') }}" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="slug">Slug</label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded" id="slug" name="slug" type="text" value="{{ old('slug') }}" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="email">Email</label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded" id="email" name="email" type="email" value="{{ old('email') }}" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" for="max_properties">Max Properties</label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded" id="max_properties" name="max_properties" type="number" min="1" value="{{ old('max_properties', 100) }}" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" for="max_users">Max Users</label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded" id="max_users" name="max_users" type="number" min="1" value="{{ old('max_users', 10) }}" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="plan">Plan</label>
                    <select class="w-full px-3 py-2 border border-slate-300 rounded" id="plan" name="plan" required>
                        @foreach(\App\Enums\SubscriptionPlan::cases() as $plan)
                            <option value="{{ $plan->value }}" @selected(old('plan') === $plan->value)>{{ ucfirst($plan->value) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" for="timezone">Timezone</label>
                        <select class="w-full px-3 py-2 border border-slate-300 rounded" id="timezone" name="timezone">
                            @foreach($timezones as $timezone)
                                <option value="{{ $timezone }}" @selected($selectedTimezone === $timezone)>{{ $timezone }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" for="locale">Locale</label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded" id="locale" name="locale" type="text" value="{{ old('locale', 'lt') }}">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="currency">Currency</label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded" id="currency" name="currency" type="text" value="{{ old('currency', 'EUR') }}">
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                    <label class="text-sm text-slate-700" for="is_active">Active</label>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <a href="{{ route('superadmin.organizations.index') }}" class="px-4 py-2 bg-slate-300 text-slate-700 rounded hover:bg-slate-400">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Create
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    if (!nameInput || !slugInput) {
        return;
    }

    let slugTouched = slugInput.value.trim() !== '';

    const slugify = (value) => value
        .toString()
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    nameInput.addEventListener('input', () => {
        if (!slugTouched) {
            slugInput.value = slugify(nameInput.value);
        }
    });

    slugInput.addEventListener('input', () => {
        slugTouched = slugInput.value.trim() !== '';
    });
});
</script>
@endpush
@break

@default
@section('content')
@php
    $timezones = \DateTimeZone::listIdentifiers(\DateTimeZone::EUROPE);
    $selectedTimezone = old('timezone', 'Europe/Vilnius');
    if (!in_array($selectedTimezone, $timezones, true)) {
        array_unshift($timezones, $selectedTimezone);
    }
@endphp
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-slate-900">Create Organization</h1>
        </div>

        <x-card>
            <form method="POST" action="{{ route('superadmin.organizations.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="name">Name</label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded" id="name" name="name" type="text" value="{{ old('name') }}" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="slug">Slug</label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded" id="slug" name="slug" type="text" value="{{ old('slug') }}" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="email">Email</label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded" id="email" name="email" type="email" value="{{ old('email') }}" required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" for="max_properties">Max Properties</label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded" id="max_properties" name="max_properties" type="number" min="1" value="{{ old('max_properties', 100) }}" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" for="max_users">Max Users</label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded" id="max_users" name="max_users" type="number" min="1" value="{{ old('max_users', 10) }}" required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="plan">Plan</label>
                    <select class="w-full px-3 py-2 border border-slate-300 rounded" id="plan" name="plan" required>
                        @foreach(\App\Enums\SubscriptionPlan::cases() as $plan)
                            <option value="{{ $plan->value }}" @selected(old('plan') === $plan->value)>{{ ucfirst($plan->value) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" for="timezone">Timezone</label>
                        <select class="w-full px-3 py-2 border border-slate-300 rounded" id="timezone" name="timezone">
                            @foreach($timezones as $timezone)
                                <option value="{{ $timezone }}" @selected($selectedTimezone === $timezone)>{{ $timezone }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1" for="locale">Locale</label>
                        <input class="w-full px-3 py-2 border border-slate-300 rounded" id="locale" name="locale" type="text" value="{{ old('locale', 'lt') }}">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1" for="currency">Currency</label>
                    <input class="w-full px-3 py-2 border border-slate-300 rounded" id="currency" name="currency" type="text" value="{{ old('currency', 'EUR') }}">
                </div>

                <div class="flex items-center gap-2">
                    <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true))>
                    <label class="text-sm text-slate-700" for="is_active">Active</label>
                </div>

                <div class="flex justify-end gap-2 pt-4">
                    <a href="{{ route('superadmin.organizations.index') }}" class="px-4 py-2 bg-slate-300 text-slate-700 rounded hover:bg-slate-400">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Create
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');

    if (!nameInput || !slugInput) {
        return;
    }

    let slugTouched = slugInput.value.trim() !== '';

    const slugify = (value) => value
        .toString()
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');

    nameInput.addEventListener('input', () => {
        if (!slugTouched) {
            slugInput.value = slugify(nameInput.value);
        }
    });

    slugInput.addEventListener('input', () => {
        slugTouched = slugInput.value.trim() !== '';
    });
});
</script>
@endpush
@endswitch
