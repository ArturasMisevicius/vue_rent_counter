@extends('layouts.app')

@section('title', 'My Property')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">My Property</h1>
            <p class="mt-2 text-sm text-gray-700">Details about your assigned property</p>
        </div>
    </div>

    @if(!$property)
        <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">No Property Assigned</h3>
                    <p class="mt-2 text-sm text-yellow-700">You do not have a property assigned yet. Please contact your administrator.</p>
                </div>
            </div>
        </div>
    @else
        <!-- Property Details -->
        <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-6">Property Information</h2>
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Address</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $property->address }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Property Type</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ ucfirst($property->type->value) }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Area</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $property->area_sqm }} m²</dd>
                    </div>
                    @if($property->building)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Building</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $property->building->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Building Address</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $property->building->address }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Meters for this Property -->
        @if($property->meters && $property->meters->count() > 0)
        <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">Utility Meters</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Serial Number</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($property->meters as $meter)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ ucfirst(str_replace('_', ' ', $meter->type->value)) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $meter->serial_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <a href="{{ route('tenant.meters.show', $meter) }}" class="text-indigo-600 hover:text-indigo-900">View Details</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @else
        <div class="mt-8 bg-gray-50 border border-gray-200 rounded-lg p-6">
            <p class="text-sm text-gray-600 text-center">No meters have been installed for this property yet.</p>
        </div>
        @endif
    @endif
</div>
@endsection
