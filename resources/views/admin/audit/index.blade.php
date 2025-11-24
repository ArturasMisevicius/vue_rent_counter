@extends('layouts.app')

@section('title', __('audit.pages.index.title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('audit.pages.index.title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('audit.pages.index.description') }}</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="mt-8">
        <x-card>
            <form method="GET" action="{{ route('admin.audit.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <label for="from_date" class="block text-sm font-medium text-slate-700">{{ __('audit.pages.index.filters.from_date') }}</label>
                    <input 
                        type="date" 
                        name="from_date" 
                        id="from_date" 
                        value="{{ request('from_date') }}"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                </div>

                <div>
                    <label for="to_date" class="block text-sm font-medium text-slate-700">{{ __('audit.pages.index.filters.to_date') }}</label>
                    <input 
                        type="date" 
                        name="to_date" 
                        id="to_date" 
                        value="{{ request('to_date') }}"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                </div>

                <div>
                    <label for="meter_serial" class="block text-sm font-medium text-slate-700">{{ __('audit.pages.index.filters.meter_serial') }}</label>
                    <input 
                        type="text" 
                        name="meter_serial" 
                        id="meter_serial" 
                        value="{{ request('meter_serial') }}"
                        placeholder="{{ __('audit.pages.index.filters.meter_placeholder') }}"
                        class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        {{ __('audit.pages.index.filters.apply') }}
                    </button>
                    @if(request()->hasAny(['from_date', 'to_date', 'meter_serial']))
                    <a href="{{ route('admin.audit.index') }}" class="rounded-md bg-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-300">
                        {{ __('audit.pages.index.filters.clear') }}
                    </a>
                    @endif
                </div>
            </form>
        </x-card>
    </div>

    <!-- Audit Log Table -->
    <div class="mt-8">
        <div class="hidden sm:block">
            <x-data-table caption="{{ __('audit.pages.index.table.caption') }}">
                <x-slot name="header">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">{{ __('audit.pages.index.table.timestamp') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('audit.pages.index.table.meter') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('audit.pages.index.table.reading_date') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('audit.pages.index.table.old_value') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('audit.pages.index.table.new_value') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('audit.pages.index.table.changed_by') }}</th>
                        <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">{{ __('audit.pages.index.table.reason') }}</th>
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
                            <span class="text-slate-400">{{ __('audit.pages.index.states.not_available') }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        @if($audit->meterReading)
                            {{ $audit->meterReading->reading_date->format('Y-m-d') }}
                        @else
                            <span class="text-slate-400">{{ __('audit.pages.index.states.not_available') }}</span>
                        @endif
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <span class="font-mono">{{ number_format($audit->old_value, 2) }}</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <span class="font-mono">{{ number_format($audit->new_value, 2) }}</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        {{ $audit->changedByUser->name ?? __('audit.pages.index.states.system') }}
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
                        {{ __('audit.pages.index.states.empty') }}
                        @if(request()->hasAny(['from_date', 'to_date', 'meter_serial']))
                            <a href="{{ route('admin.audit.index') }}" class="text-indigo-600 hover:text-indigo-500">{{ __('audit.pages.index.states.clear_filters') }}</a> {{ __('audit.pages.index.states.see_all') }}
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
                        <p class="text-xs text-slate-600">{{ $audit->meterReading?->meter?->serial_number ?? __('audit.pages.index.states.not_available') }}</p>
                        <p class="text-xs text-slate-600">
                            {{ __('audit.pages.index.table.reading') }} {{ $audit->meterReading?->reading_date?->format('Y-m-d') ?? __('audit.pages.index.states.not_available') }}
                        </p>
                    </div>
                    <div class="text-right text-xs text-slate-600">
                        <p>{{ __('audit.pages.index.states.old_short') }} <span class="font-mono">{{ number_format($audit->old_value, 2) }}</span></p>
                        <p>{{ __('audit.pages.index.states.new_short') }} <span class="font-mono">{{ number_format($audit->new_value, 2) }}</span></p>
                    </div>
                </div>
                <p class="mt-1 text-xs text-slate-600">{{ __('audit.pages.index.states.by') }} {{ $audit->changedByUser->name ?? __('audit.pages.index.states.system') }}</p>
                <p class="mt-1 text-xs text-slate-600 truncate" title="{{ $audit->change_reason }}">{{ $audit->change_reason }}</p>
            </div>
            @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white px-4 py-6 text-center text-sm text-slate-600 shadow-sm">
                {{ __('audit.pages.index.states.empty') }}
                @if(request()->hasAny(['from_date', 'to_date', 'meter_serial']))
                    <a href="{{ route('admin.audit.index') }}" class="text-indigo-700 font-semibold">{{ __('audit.pages.index.states.clear_filters') }}</a> {{ __('audit.pages.index.states.see_all') }}
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
