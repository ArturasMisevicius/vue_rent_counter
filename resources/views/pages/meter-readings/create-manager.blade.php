{{--
    Meter Reading Creation View
    
    Displays the meter reading form component for managers to enter new readings.
    Uses the reusable x-meter-reading-form component with Alpine.js interactivity.
    
    Requirements:
    - 10.1: Dynamic meter selection with property filtering
    - 10.2: Real-time validation and charge preview
    - 10.3: Multi-zone support for electricity meters
    
    Component Features:
    - AJAX-powered provider/tariff cascading dropdowns
    - Previous reading display with consumption calculation
    - Client-side monotonicity validation
    - Charge preview based on selected tariff
    - Multi-zone support (day/night for electricity)
    
    Data Flow:
    1. Controller loads meters and providers
    2. Component renders with Alpine.js state management
    3. User selects meter → loads previous reading via API
    4. User selects provider → loads tariffs via API
    5. User enters reading → validates and calculates preview
    6. Form submits to API → redirects to index on success
    
    API Endpoints Used:
    - GET /api/meters/{id}/last-reading - Fetch previous reading
    - GET /api/providers/{id}/tariffs - Load tariffs for provider
    - POST /api/meter-readings - Submit new reading
    
    @see \App\Http\Controllers\Manager\MeterReadingController::create()
    @see \App\View\Components\MeterReadingForm
    @see \App\Http\Controllers\Api\MeterReadingApiController::store()
    
    @var \Illuminate\Database\Eloquent\Collection<\App\Models\Meter> $meters
    @var \Illuminate\Database\Eloquent\Collection<\App\Models\Provider> $providers
--}}
@extends('layouts.app')

@section('title', __('meter_readings.headings.create'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center mb-6">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('meter_readings.headings.create') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('meter_readings.manager.show.description') }}</p>
        </div>
    </div>

    <div class="mb-6">
        <a href="{{ route('manager.meter-readings.index') }}" class="text-blue-600 hover:text-blue-800">
            &larr; {{ __('meter_readings.actions.back') }}
        </a>
    </div>

    <div class="mt-8 max-w-4xl">
        <x-meter-reading-form 
            :meters="$meters" 
            :providers="$providers"
        />
    </div>
</div>
@endsection
