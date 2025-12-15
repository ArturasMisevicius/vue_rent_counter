@extends('layouts.app')

@section('title', 'Property')

@section('content')
    <div class="px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-slate-900">{{ $property->name }}</h1>
        <p class="mt-2 text-sm text-slate-700">{{ $property->address }}</p>
    </div>
@endsection

