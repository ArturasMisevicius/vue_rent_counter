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
            <h1 class="text-2xl font-semibold text-slate-900">Audit Trail</h1>
            <p class="mt-2 text-sm text-slate-700">View system activity and meter reading changes</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mt-8">
        <x-card>
            <form method="GET" action="{{ route('admin.audit.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <label for="from_date" class="block text-sm font-medium text-slate-700">From Date</label>
                    <input 
                        type="date" 
                        name="from_date" 
                        id="from_date" 
                        value="{{ request('from_date') }}"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                </div>

                <div>
                    <label for="to_date" class="block text-sm font-medium text-slate-700">To Date</label>
                    <input 
                        type="date" 
                        name="to_date" 
                        id="to_date" 
                        value="{{ request('to_date') }}"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                </div>

                <div>
                    <label for="meter_serial" class="block text-sm font-medium text-slate-700">Meter Serial</label>
                    <input 
                        type="text" 
                        name="meter_serial" 
                        id="meter_serial" 
                        value="{{ request('meter_serial') }}"
                        placeholder="Search by serial..."
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        Apply Filters
                    </button>
                    @if(request()->hasAny(['from_date', 'to_date', 'meter_serial']))
                    <a href="{{ route('admin.audit.index') }}" class="rounded-md bg-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-300">
                        Clear
                    </a>
                    @endif
                </div>
            </form>
        </x-card>
    </div>

    <!-- Audit Log Table -->
    <div class="mt-8">
        <div class="hidden sm:block">
            <x-data-table caption="Audit trail">
                <x-slot name="header">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Timestamp</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Meter</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Reading Date</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Old Value</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">New Value</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Changed By</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Reason</th>
                    </tr>
                </x-slot>

                @forelse($audits as $audit)
                <tr>
                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-slate-900 sm:pl-6">
                        {{ $audit->created_at->format('Y-m-d H:i:s') }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        @if($audit->meterReading && $audit->meterReading->meter)
                            <div class="font-medium text-slate-900">{{ $audit->meterReading->meter->serial_number }}</div>
                            <div class="text-xs text-slate-500">{{ enum_label($audit->meterReading->meter->meter_type ?? null, \App\Enums\MeterType::class) }}</div>
                        @else
                            <span class="text-slate-400">N/A</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        @if($audit->meterReading)
                            {{ $audit->meterReading->reading_date->format('Y-m-d') }}
                        @else
                            <span class="text-slate-400">N/A</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <span class="font-mono">{{ number_format($audit->old_value, 2) }}</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <span class="font-mono">{{ number_format($audit->new_value, 2) }}</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $audit->changedByUser->name ?? 'System' }}
                    </td>
                    <td class="px-3 py-4 text-sm text-slate-500">
                        <div class="max-w-xs truncate" title="{{ $audit->change_reason }}">
                            {{ $audit->change_reason }}
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-3 py-8 text-center text-sm text-slate-500">
                        No audit records found.
                        @if(request()->hasAny(['from_date', 'to_date', 'meter_serial']))
                            <a href="{{ route('admin.audit.index') }}" class="text-indigo-600 hover:text-indigo-500">Clear filters</a> to see all records.
                        @endif
                    </td>
                </tr>
                @endforelse
            </x-data-table>
        </div>

        <div class="sm:hidden space-y-3">
            @forelse($audits as $audit)
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-900">{{ $audit->created_at->format('Y-m-d H:i:s') }}</p>
                        <p class="text-xs text-slate-600">{{ $audit->meterReading?->meter?->serial_number ?? 'N/A' }}</p>
                        <p class="text-xs text-slate-600">
                            Reading: {{ $audit->meterReading?->reading_date?->format('Y-m-d') ?? 'N/A' }}
                        </p>
                    </div>
                    <div class="text-right text-xs text-slate-600">
                        <p>Old: <span class="font-mono">{{ number_format($audit->old_value, 2) }}</span></p>
                        <p>New: <span class="font-mono">{{ number_format($audit->new_value, 2) }}</span></p>
                    </div>
                </div>
                <p class="mt-1 text-xs text-slate-600">By: {{ $audit->changedByUser->name ?? 'System' }}</p>
                <p class="mt-1 text-xs text-slate-600 truncate" title="{{ $audit->change_reason }}">{{ $audit->change_reason }}</p>
            </div>
            @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                No audit records found.
                @if(request()->hasAny(['from_date', 'to_date', 'meter_serial']))
                    <a href="{{ route('admin.audit.index') }}" class="text-indigo-700 font-semibold">Clear filters</a> to see all records.
                @endif
            </div>
            @endforelse
        </div>
    </div>

    <div class="mt-4">
        {{ $audits->links() }}
    </div>
</div>
@endsection
