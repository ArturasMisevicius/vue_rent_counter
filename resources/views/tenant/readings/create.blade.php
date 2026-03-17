@extends('layouts.app')

@section('title', __('tenant.readings.title') . ' · ' . config('app.name', 'Tenanto'))

@section('content')
    <main class="mx-auto max-w-6xl py-8">
        @livewire(\App\Livewire\Tenant\SubmitReadingPage::class)
    </main>
@endsection
