@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900">{{ $organization->name }}</h1>
            <p class="text-slate-600 mt-2">{{ $organization->email }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('superadmin.organizations.edit', $organization) }}" class="px-4 py-2 bg-slate-600 text-white rounded hover:bg-slate-700">
                Edit
            </a>
            <a href="{{ route('superadmin.organizations.index') }}" class="px-4 py-2 bg-slate-300 text-slate-700 rounded hover:bg-slate-400">
                Back
            </a>
        </div>
    </div>

    <x-card>
        <h2 class="text-xl font-semibold mb-4">Organization Details</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <dt class="text-sm font-medium text-slate-500">Name</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $organization->name }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Email</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $organization->email }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Plan</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ ucfirst($organization->plan->value) }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Status</dt>
                <dd class="mt-1 text-sm text-slate-900">{{ $organization->is_active ? 'Active' : 'Inactive' }}</dd>
            </div>
        </dl>
    </x-card>
</div>
@endsection

