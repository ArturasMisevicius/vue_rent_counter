<x-filament-panels::page>
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-4 lg:grid-cols-[minmax(0,16rem)_minmax(0,16rem)_1fr] lg:items-end">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Language</label>
                    <select wire:model.live="selectedLocale" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        @foreach ($this->locales as $locale)
                            <option value="{{ $locale['code'] }}">
                                {{ $locale['label'] }}{{ $locale['is_default'] ? ' (default)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">Group</label>
                    <select wire:model.live="selectedGroup" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                        @foreach ($this->groups as $group)
                            <option value="{{ $group }}">{{ $group }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                    <x-filament::button type="button" wire:click="exportMissingTranslations">
                        Export missing
                    </x-filament::button>

                    <x-filament::button type="button" color="gray" wire:click="importTranslations">
                        Import exported
                    </x-filament::button>
                </div>
            </div>

            @if (filled($exportedMissingTranslationsPath))
                <p class="mt-4 text-sm text-slate-600">
                    Latest export: {{ $exportedMissingTranslationsPath }}
                </p>
            @endif
        </section>

        <section class="space-y-4">
            @forelse ($this->rows as $row)
                <form wire:submit.prevent="saveTranslationByStateKey('{{ $row->stateKey }}')" class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-[minmax(0,20rem)_minmax(0,1fr)_auto] lg:items-start">
                    <div class="space-y-2">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $row->key }}</p>
                            @if ($row->missing)
                                <p class="text-xs font-medium uppercase tracking-wide text-amber-600">Missing</p>
                            @endif
                        </div>

                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Default</p>
                            <p class="mt-1 text-sm text-slate-700">{{ $row->sourceValue }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-xs font-medium uppercase tracking-wide text-slate-500">Translation</label>
                        <input
                            type="text"
                            wire:model="translationValues.{{ $row->stateKey }}"
                            class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                        />
                    </div>

                    <div class="flex justify-end lg:justify-start">
                        <x-filament::button type="submit">
                            Save
                        </x-filament::button>
                    </div>
                </form>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-500 shadow-sm">
                    No translation groups are available yet.
                </div>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
