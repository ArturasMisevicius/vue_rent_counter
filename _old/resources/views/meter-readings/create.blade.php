@extends('layouts.app')

@section('title', __('meter_readings.headings.create'))

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('manager.meter-readings.index') }}" class="text-blue-600 hover:text-blue-800">
            &larr; {{ __('meter_readings.actions.back') }}
        </a>
    </div>

    <livewire:manager.meter-reading-form />
</div>
@endsection
