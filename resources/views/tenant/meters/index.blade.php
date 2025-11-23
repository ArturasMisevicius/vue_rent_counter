@extends('layouts.app')

@section('title', 'My Meters')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">My Meters</h1>
            <p class="mt-2 text-sm text-gray-700">Utility meters for your assigned property</p>
        </div>
    </div>

    @if($meters->isEmpty())
        <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-6">
            <p class="text-sm text-gray-600 text-center">No meters have been installed for your property yet.</p>
        </div>
    @else
        <div class="mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($meters as $meter)
            <div class="bg-white shadow rounded-lg overflow-hidden hover:shadow-lg transition">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ enum_label($meter->type) }}
                        </h3>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            Active
                        </span>
                    </div>
                    
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Serial Number</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $meter->serial_number }}</dd>
                        </div>
                        
                        @if($meter->readings && $meter->readings->isNotEmpty())
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Latest Reading</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ number_format($meter->readings->first()->value, 2) }} 
                                {{ $meter->type->value === 'electricity' ? 'kWh' : 'm³' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Reading Date</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $meter->readings->first()->reading_date->format('Y-m-d') }}
                            </dd>
                        </div>
                        @else
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Latest Reading</dt>
                            <dd class="mt-1 text-sm text-gray-500 italic">No readings yet</dd>
                        </div>
                        @endif
                    </dl>
                    
                    <div class="mt-4">
                        <a href="{{ route('tenant.meters.show', $meter) }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            View History
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($meters instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-6">
            {{ $meters->links() }}
        </div>
        @endif
    @endif
</div>
@endsection
