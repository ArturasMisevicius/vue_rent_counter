<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-semibold text-slate-950">{{ __('superadmin.system_configuration.title') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('superadmin.system_configuration.description') }}</p>
        </section>

        @if (filled($savedMessage))
            <section class="rounded-3xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium text-emerald-700">
                {{ $savedMessage }}
            </section>
        @endif

        @forelse ($groups as $group)
            <details class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" open>
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-6 py-5">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-950">{{ $group['label'] }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ __('superadmin.system_configuration.groups.count', ['count' => $group['rows']->count()]) }}</p>
                    </div>

                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                        {{ __('superadmin.system_configuration.groups.collapse') }}
                    </span>
                </summary>

                <div class="border-t border-slate-200">
                    @if ($group['rows']->isNotEmpty())
                        <div class="hidden grid-cols-12 gap-4 bg-slate-50 px-6 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 lg:grid">
                            <div class="col-span-4">{{ __('superadmin.system_configuration.columns.key') }}</div>
                            <div class="col-span-4">{{ __('superadmin.system_configuration.columns.description') }}</div>
                            <div class="col-span-3">{{ __('superadmin.system_configuration.columns.current_value') }}</div>
                            <div class="col-span-1 text-right">{{ __('superadmin.system_configuration.columns.action') }}</div>
                        </div>

                        <div class="divide-y divide-slate-100">
                            @foreach ($group['rows'] as $row)
                                @php
                                    $isEditing = (bool) ($editing[$row['id']] ?? false);
                                    $draftValue = (string) ($draftValues[$row['id']] ?? $row['display_value']);
                                @endphp

                                <div wire:key="system-setting-row-{{ $row['id'] }}" class="grid gap-4 px-6 py-5 lg:grid-cols-12 lg:items-start">
                                    <div class="space-y-1 lg:col-span-4">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">{{ $row['label'] }}</p>
                                        <p class="font-medium text-slate-950">{{ $row['key'] }}</p>
                                    </div>

                                    <div class="lg:col-span-4">
                                        <p class="text-sm leading-6 text-slate-600">{{ $row['description'] }}</p>
                                    </div>

                                    <div class="space-y-3 lg:col-span-3">
                                        @if ($isEditing)
                                            @if ($row['editor'] === 'list')
                                                <textarea
                                                    wire:model.defer="draftValues.{{ $row['id'] }}"
                                                    rows="3"
                                                    class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                                >{{ $draftValue }}</textarea>
                                            @elseif ($row['editor'] === 'boolean')
                                                <select
                                                    wire:model.defer="draftValues.{{ $row['id'] }}"
                                                    class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                                >
                                                    <option value="true">{{ __('superadmin.system_configuration.values.true') }}</option>
                                                    <option value="false">{{ __('superadmin.system_configuration.values.false') }}</option>
                                                </select>
                                            @else
                                                <input
                                                    type="{{ $row['editor'] === 'email' ? 'email' : ($row['editor'] === 'number' ? 'number' : 'text') }}"
                                                    wire:model.defer="draftValues.{{ $row['id'] }}"
                                                    class="w-full rounded-2xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200"
                                                />
                                            @endif

                                            @error("draftValues.{$row['id']}")
                                                <p class="text-sm font-medium text-rose-600">{{ $message }}</p>
                                            @enderror
                                        @else
                                            <p class="rounded-2xl bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900">
                                                {{ filled($row['display_value']) ? $row['display_value'] : __('superadmin.system_configuration.values.empty') }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="flex items-center justify-start gap-3 lg:col-span-1 lg:justify-end">
                                        @if ($isEditing)
                                            <button
                                                type="button"
                                                wire:click="saveSetting({{ $row['id'] }})"
                                                class="text-sm font-semibold text-emerald-700 transition hover:text-emerald-800"
                                            >
                                                {{ __('superadmin.system_configuration.actions.save') }}
                                            </button>

                                            <button
                                                type="button"
                                                wire:click="cancelEditing({{ $row['id'] }})"
                                                class="text-sm font-semibold text-slate-500 transition hover:text-slate-700"
                                            >
                                                {{ __('superadmin.system_configuration.actions.cancel') }}
                                            </button>
                                        @else
                                            <button
                                                type="button"
                                                wire:click="startEditing({{ $row['id'] }})"
                                                class="text-sm font-semibold text-amber-700 transition hover:text-amber-800"
                                            >
                                                {{ __('superadmin.system_configuration.actions.edit') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="px-6 py-5 text-sm text-slate-500">
                            {{ __('superadmin.system_configuration.groups.empty') }}
                        </div>
                    @endif
                </div>
            </details>
        @empty
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">{{ __('superadmin.system_configuration.empty.settings') }}</p>
            </section>
        @endforelse
    </div>
</x-filament-panels::page>
