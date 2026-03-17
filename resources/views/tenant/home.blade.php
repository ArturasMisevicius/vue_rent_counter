@extends('layouts.app')

@section('title', __('dashboard.tenant_title') . ' · ' . config('app.name', 'Tenanto'))

@section('content')
    <main class="mx-auto max-w-6xl py-8">
        @livewire(\App\Livewire\Tenant\HomeSummary::class)
    </main>
@endsection
