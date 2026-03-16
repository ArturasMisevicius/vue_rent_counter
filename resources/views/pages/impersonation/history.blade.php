@extends('layouts.app')

@section('title', __('app.impersonation.history'))

@section('content')
<x-ui.page
    class="px-4 sm:px-6 lg:px-8"
    :title="__('app.impersonation.history')"
    :description="__('app.impersonation.history_description')"
>
    <x-ui.section-card>
        <form method="GET" action="{{ route('superadmin.impersonation.history') }}" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div>
                <label for="superadmin_id" class="mb-2 block text-sm font-medium text-slate-700">{{ __('app.impersonation.shared') }}</label>
                <select name="superadmin_id" id="superadmin_id" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">{{ __('app.common.all') }}</option>
                    @foreach($superadmins as $superadmin)
                        <option value="{{ $superadmin->id }}" @selected(request('superadmin_id') == $superadmin->id)>{{ $superadmin->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="target_user_id" class="mb-2 block text-sm font-medium text-slate-700">{{ __('app.impersonation.target_user') }}</label>
                <select name="target_user_id" id="target_user_id" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">{{ __('app.common.all') }}</option>
                    @foreach($targetUsers as $targetUser)
                        <option value="{{ $targetUser->id }}" @selected(request('target_user_id') == $targetUser->id)>{{ $targetUser->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="date_from" class="mb-2 block text-sm font-medium text-slate-700">{{ __('app.common.date_from') }}</label>
                <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div>
                <label for="date_to" class="mb-2 block text-sm font-medium text-slate-700">{{ __('app.common.date_to') }}</label>
                <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="block w-full rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div class="flex flex-col gap-3 md:flex-row md:items-end xl:col-span-4 xl:justify-end">
                <button type="submit" class="inline-flex items-center justify-center rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2">
                    {{ __('app.common.filter') }}
                </button>
                <a href="{{ route('superadmin.impersonation.history') }}" class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-300 focus:ring-offset-2">
                    {{ __('app.common.clear') }}
                </a>
            </div>
        </form>
    </x-ui.section-card>

    <x-card>
        <div class="mb-5 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ __('app.impersonation.sessions') }}</h2>
                <p class="text-sm text-slate-500">{{ $logs->total() }}</p>
            </div>
        </div>

        @if($logs->count() > 0)
            <div class="hidden md:block">
                <x-data-table :caption="__('app.impersonation.history')">
                    <x-slot name="header">
                        <tr>
                            <th scope="col">{{ __('app.common.date_time') }}</th>
                            <th scope="col">{{ __('app.impersonation.shared') }}</th>
                            <th scope="col">{{ __('app.impersonation.target_user') }}</th>
                            <th scope="col">{{ __('app.common.organization') }}</th>
                            <th scope="col">{{ __('app.common.action') }}</th>
                            <th scope="col">{{ __('app.impersonation.duration') }}</th>
                            <th scope="col">{{ __('app.impersonation.reason') }}</th>
                        </tr>
                    </x-slot>

                    @foreach($logs as $log)
                        <tr>
                            <td class="font-medium text-slate-900">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $log->user->name ?? __('app.common.unknown') }}</td>
                            <td>
                                <div class="font-medium text-slate-900">{{ $log->metadata['target_user_name'] ?? __('app.common.unknown') }}</div>
                                <div class="text-xs text-slate-500">{{ $log->metadata['target_user_email'] ?? '' }}</div>
                            </td>
                            <td>{{ $log->organization->name ?? __('app.common.unknown') }}</td>
                            <td>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $log->action === 'impersonation_started' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $log->action === 'impersonation_started' ? __('app.impersonation.started') : __('app.impersonation.ended') }}
                                </span>
                            </td>
                            <td>
                                @if($log->action === 'impersonation_ended' && isset($log->metadata['duration_seconds']))
                                    {{ gmdate('H:i:s', $log->metadata['duration_seconds']) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $log->metadata['reason'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </x-data-table>
            </div>

            <div class="space-y-3 md:hidden">
                @foreach($logs as $log)
                    <x-ui.list-record
                        :title="$log->metadata['target_user_name'] ?? __('app.common.unknown')"
                        :subtitle="$log->metadata['target_user_email'] ?? __('app.common.unknown')"
                    >
                        <x-slot name="aside">
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $log->action === 'impersonation_started' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                {{ $log->action === 'impersonation_started' ? __('app.impersonation.started') : __('app.impersonation.ended') }}
                            </span>
                        </x-slot>

                        <x-slot name="meta">
                            <x-ui.list-meta :label="__('app.common.date_time')">{{ $log->created_at->format('Y-m-d H:i:s') }}</x-ui.list-meta>
                            <x-ui.list-meta :label="__('app.impersonation.shared')">{{ $log->user->name ?? __('app.common.unknown') }}</x-ui.list-meta>
                            <x-ui.list-meta :label="__('app.common.organization')">{{ $log->organization->name ?? __('app.common.unknown') }}</x-ui.list-meta>
                            <x-ui.list-meta :label="__('app.impersonation.duration')">
                                @if($log->action === 'impersonation_ended' && isset($log->metadata['duration_seconds']))
                                    {{ gmdate('H:i:s', $log->metadata['duration_seconds']) }}
                                @else
                                    -
                                @endif
                            </x-ui.list-meta>
                            <x-ui.list-meta :label="__('app.impersonation.reason')" class="sm:col-span-2 xl:col-span-3">
                                {{ $log->metadata['reason'] ?? '-' }}
                            </x-ui.list-meta>
                        </x-slot>
                    </x-ui.list-record>
                @endforeach
            </div>

            <div class="mt-6">
                {{ $logs->withQueryString()->links() }}
            </div>
        @else
            <div class="px-4 py-8 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="mt-4 text-base font-semibold text-slate-900">{{ __('app.impersonation.no_sessions') }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ __('app.impersonation.no_sessions_description') }}</p>
            </div>
        @endif
    </x-card>
</x-ui.page>
@endsection
