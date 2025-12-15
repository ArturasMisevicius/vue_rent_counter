@extends('layouts.app')

@section('title', __('tenants.headings.show'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center sm:justify-between">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ $tenant->name }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('tenants.headings.show') }}</p>
        </div>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex gap-3">
            <form action="{{ route('admin.tenants.toggle-active', $tenant) }}" method="POST">
                @csrf
                @method('PATCH')
                @if($tenant->is_active)
                    <button type="submit" class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                        {{ __('tenants.actions.deactivate') }}
                    </button>
                @else
                    <button type="submit" class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                        {{ __('tenants.actions.reactivate') }}
                    </button>
                @endif
            </form>
            <a href="{{ route('admin.tenants.reassign-form', $tenant) }}" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                {{ __('tenants.actions.reassign') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mt-4 rounded-md bg-green-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Account Information -->
        <x-card title="{{ __('tenants.headings.account') }}">
            <dl class="divide-y divide-slate-200">
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.status') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                        @if($tenant->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-700">{{ __('tenants.statuses.active') }}</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">{{ __('tenants.statuses.inactive') }}</span>
                        @endif
                    </dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.email') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $tenant->email }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.created') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $tenant->created_at->format('M d, Y') }}</dd>
                </div>
                <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                    <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.created_by') }}</dt>
                    <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">
                        {{ $tenant->parentUser->name ?? __('providers.statuses.not_available') }}
                    </dd>
                </div>
            </dl>
        </x-card>

        <!-- Current Property Assignment -->
        <x-card title="{{ __('tenants.headings.current_property') }}">
            @if($tenant->property)
                <dl class="divide-y divide-slate-200">
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.address') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $tenant->property->address }}</dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.type') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ enum_label($tenant->property->type) }}</dd>
                    </div>
                    <div class="py-3 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-slate-500">{{ __('tenants.labels.area') }}</dt>
                        <dd class="mt-1 text-sm text-slate-900 sm:col-span-2 sm:mt-0">{{ $tenant->property->area }} m²</dd>
                    </div>
                </dl>
            @else
                <p class="text-sm text-slate-500">{{ __('tenants.empty.property') }}</p>
            @endif
        </x-card>
    </div>

    <!-- Assignment History -->
    <div class="mt-8">
        <x-card title="{{ __('tenants.headings.assignment_history') }}">
            <div class="flow-root">
                <ul role="list" class="-mb-8">
                    @forelse($assignmentHistory as $index => $history)
                    <li>
                        <div class="relative pb-8">
                            @if(!$loop->last)
                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                            @endif
                            <div class="relative flex space-x-3">
                                <div>
                                    <span class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center ring-8 ring-white">
                                        <svg class="h-5 w-5 text-white" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                    <div>
                                        <p class="text-sm text-slate-500">
                                            <span class="font-medium text-slate-900">{{ enum_label($history->action, \App\Enums\UserAssignmentAction::class) }}</span>
                                            @if($history->action === \App\Enums\UserAssignmentAction::REASSIGNED->value)
                                                - {{ __('tenants.actions.reassign') }}
                                            @elseif($history->action === \App\Enums\UserAssignmentAction::ASSIGNED->value)
                                                - {{ __('tenants.actions.reassign') }}
                                            @elseif($history->action === \App\Enums\UserAssignmentAction::CREATED->value)
                                                - {{ __('tenants.headings.show') }}
                                            @endif
                                        </p>
                                        @if($history->reason)
                                            <p class="mt-1 text-sm text-slate-500">{{ __('tenants.labels.reason') ?? 'Reason' }}: {{ $history->reason }}</p>
                                        @endif
                                    </div>
                                    <div class="whitespace-nowrap text-right text-sm text-slate-500">
                                        {{ \Carbon\Carbon::parse($history->created_at)->format('M d, Y') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                    @empty
                    <li class="text-sm text-slate-500">{{ __('tenants.empty.assignment_history') }}</li>
                    @endforelse
                </ul>
            </div>
        </x-card>
    </div>

    <!-- Recent Meter Readings -->
    @if($tenant->meterReadings->isNotEmpty())
    <div class="mt-8">
        <x-card title="{{ __('tenants.headings.recent_readings') }}">
            <div class="flow-root">
                <ul role="list" class="-my-5 divide-y divide-slate-200">
                    @foreach($tenant->meterReadings as $reading)
                    <li class="py-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 truncate">
                                    {{ $reading->meter->getServiceDisplayName() }}
                                    <span class="text-xs text-slate-400">({{ $reading->meter->getUnitOfMeasurement() }})</span>
                                </p>
                                <p class="text-sm text-slate-500 truncate">
                                    {{ __('tenants.labels.reading') }}: {{ number_format($reading->value, 2) }}
                                </p>
                            </div>
                            <div class="text-sm text-slate-500">
                                {{ $reading->reading_date->format('M d, Y') }}
                            </div>
                        </div>
                    </li>
                    @endforeach
                    @if($tenant->meterReadings->isEmpty())
                    <li class="py-4 text-sm text-slate-500">{{ __('tenants.empty.recent_readings') }}</li>
                    @endif
                </ul>
            </div>
        </x-card>
    </div>
    @endif

    <!-- Recent Invoices -->
    @if($recentInvoices->isNotEmpty())
    <div class="mt-8">
        <x-card title="{{ __('tenants.headings.recent_invoices') }}">
            <div class="flow-root">
                <ul role="list" class="-my-5 divide-y divide-slate-200">
                    @foreach($recentInvoices as $invoice)
                    <li class="py-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-900 truncate">
                                    {{ __('tenants.labels.invoice', ['id' => $invoice->id]) }}
                                </p>
                                <p class="text-sm text-slate-500 truncate">
                                    €{{ number_format($invoice->total_amount, 2) }}
                                </p>
                            </div>
                            <div>
                                <x-status-badge :status="$invoice->status">
                                    {{ ucfirst($invoice->status) }}
                                </x-status-badge>
                            </div>
                        </div>
                    </li>
                    @endforeach
                    @if($recentInvoices->isEmpty())
                    <li class="py-4 text-sm text-slate-500">{{ __('tenants.empty.recent_invoices') }}</li>
                    @endif
                </ul>
            </div>
        </x-card>
    </div>
    @endif
</div>
@endsection
