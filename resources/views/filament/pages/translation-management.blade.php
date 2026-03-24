<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-semibold text-slate-950">{{ __('superadmin.translation_management.title') }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ __('superadmin.translation_management.description') }}</p>
        </section>

        @if (count($locales) > 0)
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">{{ __('superadmin.translation_management.columns.key') }}</th>
                                @foreach ($locales as $locale)
                                    <th class="px-4 py-3 text-left font-semibold uppercase tracking-[0.2em] text-slate-500">{{ $locale }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($rows as $row)
                                <tr wire:key="translation-row-{{ $row->group }}-{{ str_replace('.', '-', $row->key) }}">
                                    <td class="px-4 py-4 align-top">
                                        <p class="font-medium text-slate-950">{{ $row->group }}.{{ $row->key }}</p>
                                    </td>
                                    @foreach ($locales as $locale)
                                        @php
                                            $value = $row->values[$locale] ?? null;
                                        @endphp

                                        <td class="px-4 py-4 align-top">
                                            <input
                                                type="text"
                                                wire:key="translation-cell-{{ $row->group }}-{{ str_replace('.', '-', $row->key) }}-{{ $locale }}"
                                                wire:model.blur="draftValues.{{ $row->group }}.{{ $row->key }}.{{ $locale }}"
                                                wire:change="saveValue('{{ $row->group }}', '{{ $row->key }}', '{{ $locale }}')"
                                                class="w-full rounded-2xl border px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 {{ blank($value) ? 'border-amber-300 bg-amber-50' : 'border-slate-200 bg-white' }}"
                                                placeholder="{{ __('superadmin.translation_management.placeholders.missing_translation') }}"
                                            />
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($locales) + 1 }}" class="px-4 py-6 text-sm text-slate-500">{{ __('superadmin.translation_management.empty.translations') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @else
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm text-slate-500">{{ __('superadmin.translation_management.empty.languages') }}</p>
            </section>
        @endif
    </div>
</x-filament-panels::page>
