@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('tenants.pages.index.title') ?? 'Tenants' }}</h1>
            <p class="text-slate-600">{{ __('tenants.pages.index.subtitle') ?? 'All tenants across all organizations' }}</p>
        </div>
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.name') ?? 'Name' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.email') ?? 'Email' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.property') ?? 'Property' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.invoices') ?? 'Invoices' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('tenants.fields.readings') ?? 'Readings' }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('app.nav.actions') ?? 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($tenants as $tenant)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $tenant->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                            @if($tenant->property)
                                <a href="{{ route('filament.admin.resources.properties.edit', $tenant->property) }}" class="text-indigo-600 hover:text-indigo-800">
                                    {{ $tenant->property->address }}
                                </a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->invoices_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $tenant->meter_readings_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="px-2 py-1 text-xs font-semibold text-white bg-slate-600 rounded hover:bg-slate-700">
                                    {{ __('common.view') }}
                                </a>
                                <a href="{{ route('filament.admin.resources.tenants.edit', $tenant) }}" class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">
                                    {{ __('common.edit') }}
                                </a>
                                <form action="{{ route('filament.admin.resources.tenants.destroy', $tenant) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2 py-1 text-xs font-semibold text-white bg-red-600 rounded hover:bg-red-700">
                                        {{ __('common.delete') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-slate-500">
                            {{ __('tenants.empty') ?? 'No tenants found' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($tenants->hasPages())
        <div class="mt-4">
            {{ $tenants->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
