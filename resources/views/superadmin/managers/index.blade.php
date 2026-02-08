@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ __('manager.pages.index.title') ?? 'Managers' }}</h1>
            <p class="text-slate-600">{{ __('manager.pages.index.subtitle') ?? 'All managers across all organizations' }}</p>
        </div>
    </div>

    <x-card>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('manager.fields.name') ?? 'Name' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('manager.fields.email') ?? 'Email' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('manager.fields.properties') ?? 'Properties' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('manager.fields.buildings') ?? 'Buildings' }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('manager.fields.invoices') ?? 'Invoices' }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">{{ __('app.nav.actions') ?? 'Actions' }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @forelse($managers as $manager)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $manager->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                            <a href="{{ route('superadmin.managers.show', $manager) }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ $manager->name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $manager->email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $manager->properties_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $manager->buildings_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $manager->invoices_count }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <a href="{{ route('superadmin.managers.show', $manager) }}" class="px-2 py-1 text-xs font-semibold text-white bg-slate-600 rounded hover:bg-slate-700">
                                    {{ __('common.view') }}
                                </a>
                                <a href="{{ route('filament.admin.resources.users.edit', $manager) }}" class="px-2 py-1 text-xs font-semibold text-white bg-indigo-600 rounded hover:bg-indigo-700">
                                    {{ __('common.edit') }}
                                </a>
                                <form action="{{ route('filament.admin.resources.users.destroy', $manager) }}" method="POST" onsubmit="return confirm('{{ __('common.confirm_delete') }}');">
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
                            {{ __('manager.empty') ?? 'No managers found' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($managers->hasPages())
        <div class="mt-4">
            {{ $managers->links() }}
        </div>
        @endif
    </x-card>
</div>
@endsection
