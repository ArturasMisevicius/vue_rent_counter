@php
    $role = auth()->user()?->role?->value;
@endphp

@switch($role)
@case('superadmin')
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">{{ $invitation->organization_name ?? 'Invitation' }}</h1>
                <p class="text-slate-600 mt-2">{{ $invitation->email }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('superadmin.dashboard') }}" class="px-4 py-2 bg-slate-300 text-slate-700 rounded hover:bg-slate-400">Back</a>
            </div>
        </div>

        <x-card>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-slate-500">Status</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ ucfirst($invitation->status ?? ($invitation->isPending() ? 'pending' : 'inactive')) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Plan</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $invitation->plan_type }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Expires</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $invitation->expires_at?->format('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Token</dt>
                    <dd class="mt-1 text-sm text-slate-900 font-mono break-all">{{ $invitation->token }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</div>
@endsection
@break

@default
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">{{ $invitation->organization_name ?? 'Invitation' }}</h1>
                <p class="text-slate-600 mt-2">{{ $invitation->email }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('superadmin.dashboard') }}" class="px-4 py-2 bg-slate-300 text-slate-700 rounded hover:bg-slate-400">Back</a>
            </div>
        </div>

        <x-card>
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-slate-500">Status</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ ucfirst($invitation->status ?? ($invitation->isPending() ? 'pending' : 'inactive')) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Plan</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $invitation->plan_type }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Expires</dt>
                    <dd class="mt-1 text-sm text-slate-900">{{ $invitation->expires_at?->format('Y-m-d H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-slate-500">Token</dt>
                    <dd class="mt-1 text-sm text-slate-900 font-mono break-all">{{ $invitation->token }}</dd>
                </div>
            </dl>
        </x-card>
    </div>
</div>
@endsection
@endswitch
