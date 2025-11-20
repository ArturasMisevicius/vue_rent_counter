@extends('layouts.app')

@section('title', 'Audit Trail')

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
    <x-breadcrumbs>
        <x-breadcrumb-item :href="route('admin.dashboard')">Dashboard</x-breadcrumb-item>
        <x-breadcrumb-item :active="true">Audit Trail</x-breadcrumb-item>
    </x-breadcrumbs>

    <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-gray-900">Audit Trail</h1>
            <p class="mt-2 text-sm text-gray-700">View system activity and meter reading changes</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mt-8">
        <x-card>
            <form method="GET" action="{{ route('admin.audit.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <label for="from_date" class="block text-sm font-medium text-gray-700">From Date</label>
                    <input 
                        type="date" 
                        name="from_date" 
                        id="from_date" 
                        value="{{ request('from_date') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                </div>

                <div>
                    <label for="to_date" class="block text-sm font-medium text-gray-700">To Date</label>
                    <input 
                        type="date" 
                        name="to_date" 
                        id="to_date" 
                        value="{{ request('to_date') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                </div>

                <div>
                    <label for="meter_serial" class="block text-sm font-medium text-gray-700">Meter Serial</label>
                    <input 
                        type="text" 
                        name="meter_serial" 
                        id="meter_serial" 
                        value="{{ request('meter_serial') }}"
                        placeholder="Search by serial..."
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Apply Filters
                    </button>
                    @if(request()->hasAny(['from_date', 'to_date', 'meter_serial']))
                    <a href="{{ route('admin.audit.index') }}" class="rounded-md bg-gray-200 px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-300">
                        Clear
                    </a>
                    @endif
                </div>
            </form>
        </x-card>
    </div>

    <!-- Audit Log Table -->
    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-300">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">Timestamp</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Meter</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Reading Date</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Old Value</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">New Value</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Changed By</th>
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse($audits as $audit)
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-900 sm:pl-6">
                                    {{ $audit->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    @if($audit->meterReading && $audit->meterReading->meter)
                                        <div class="font-medium text-gray-900">{{ $audit->meterReading->meter->serial_number }}</div>
                                        <div class="text-xs text-gray-500">{{ ucfirst(str_replace('_', ' ', $audit->meterReading->meter->meter_type->value)) }}</div>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    @if($audit->meterReading)
                                        {{ $audit->meterReading->reading_date->format('Y-m-d') }}
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <span class="font-mono">{{ number_format($audit->old_value, 2) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    <span class="font-mono">{{ number_format($audit->new_value, 2) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                                    {{ $audit->changedByUser->name ?? 'System' }}
                                </td>
                                <td class="px-3 py-4 text-sm text-gray-500">
                                    <div class="max-w-xs truncate" title="{{ $audit->change_reason }}">
                                        {{ $audit->change_reason }}
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">
                                    No audit records found.
                                    @if(request()->hasAny(['from_date', 'to_date', 'meter_serial']))
                                        <a href="{{ route('admin.audit.index') }}" class="text-indigo-600 hover:text-indigo-500">Clear filters</a> to see all records.
                                    @endif
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {{ $audits->links() }}
    </div>
</div>
@endsection
