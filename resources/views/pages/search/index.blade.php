@php
    $role = auth()->user()?->role?->value;
@endphp

@extends('layouts.app')

@switch($role)
@case('superadmin')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">Search</h1>
        <p class="text-slate-600 mt-2">Search organizations and users across the platform.</p>
    </div>

    <form method="GET" action="{{ route('superadmin.search') }}" class="mb-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <input
                type="text"
                name="query"
                value="{{ $query }}"
                placeholder="Type to search…"
                class="w-full sm:flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
            />
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2 font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
            >
                Search
            </button>
        </div>
    </form>

    @if(blank($query))
        <div class="rounded-xl border border-slate-200 bg-white p-6 text-slate-600">
            Enter a query to begin searching.
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Organizations</h2>
                @if($organizations->isEmpty())
                    <p class="text-slate-600">No organizations found.</p>
                @else
                    <ul class="space-y-2">
                        @foreach($organizations as $organization)
                            <li class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ $organization->name }}</p>
                                <p class="text-sm text-slate-600">{{ $organization->email }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Users</h2>
                @if($users->isEmpty())
                    <p class="text-slate-600">No users found.</p>
                @else
                    <ul class="space-y-2">
                        @foreach($users as $user)
                            <li class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                <p class="text-sm text-slate-600">{{ $user->email }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
@break

@default
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">Search</h1>
        <p class="text-slate-600 mt-2">Search organizations and users across the platform.</p>
    </div>

    <form method="GET" action="{{ route('superadmin.search') }}" class="mb-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <input
                type="text"
                name="query"
                value="{{ $query }}"
                placeholder="Type to search…"
                class="w-full sm:flex-1 rounded-lg border border-slate-300 bg-white px-4 py-2 text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
            />
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2 font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
            >
                Search
            </button>
        </div>
    </form>

    @if(blank($query))
        <div class="rounded-xl border border-slate-200 bg-white p-6 text-slate-600">
            Enter a query to begin searching.
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Organizations</h2>
                @if($organizations->isEmpty())
                    <p class="text-slate-600">No organizations found.</p>
                @else
                    <ul class="space-y-2">
                        @foreach($organizations as $organization)
                            <li class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ $organization->name }}</p>
                                <p class="text-sm text-slate-600">{{ $organization->email }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-900 mb-4">Users</h2>
                @if($users->isEmpty())
                    <p class="text-slate-600">No users found.</p>
                @else
                    <ul class="space-y-2">
                        @foreach($users as $user)
                            <li class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                <p class="text-sm text-slate-600">{{ $user->email }}</p>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
@endswitch
