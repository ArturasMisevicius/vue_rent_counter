@extends('layouts.app')

@section('title', __('meter_readings.headings.create'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('meter_readings.headings.create') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('meter_readings.shared.show.description') }}</p>
        </div>
    </div>

    <div class="mb-6">
        <a href="{{ route('manager.meter-readings.index') }}" class="text-blue-600 hover:text-blue-800">
            &larr; {{ __('meter_readings.actions.back') }}
        </a>
    </div>

    <div class="mt-8 max-w-4xl">
        <livewire:manager.meter-reading-form />
    </div>
</div>
@endsection
