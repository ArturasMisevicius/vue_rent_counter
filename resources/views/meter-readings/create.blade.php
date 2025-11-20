@extends('layouts.app')

@section('title', 'Enter Meter Reading')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6">
        <a href="{{ route('meter-readings.index') }}" class="text-blue-600 hover:text-blue-800">
            ← Back to Meter Readings
        </a>
    </div>
    
    <x-meter-reading-form 
        :meters="$meters" 
        :providers="$providers"
    />
</div>
@endsection
