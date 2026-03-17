<x-filament-panels::page>
    <div class="space-y-6">
        @foreach ($this->groupedSettings as $group)
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $group['label'] }}</h2>
                </div>

                <div class="space-y-4">
                    @foreach ($group['settings'] as $setting)
                        <form wire:submit.prevent="updateSetting({{ $setting->id }})" class="grid gap-4 rounded-xl border border-slate-200 p-4 lg:grid-cols-[minmax(0,1fr)_20rem_auto] lg:items-start">
                            <div class="space-y-1">
                                <h3 class="font-medium text-slate-900">{{ $setting->label }}</h3>
                                <p class="text-sm text-slate-600">{{ $setting->description }}</p>
                            </div>

                            <div class="space-y-2">
                                @if ($setting->type === 'boolean')
                                    <select wire:model="settingValues.{{ $setting->id }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500">
                                        <option value="0">False</option>
                                        <option value="1">True</option>
                                    </select>
                                @else
                                    <input
                                        type="{{ $setting->type === 'email' ? 'email' : 'text' }}"
                                        wire:model="settingValues.{{ $setting->id }}"
                                        class="w-full rounded-lg border-slate-300 text-sm shadow-sm focus:border-sky-500 focus:ring-sky-500"
                                    />
                                @endif

                                @error("settingValues.{$setting->id}")
                                    <p class="text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex justify-end lg:justify-start">
                                <x-filament::button type="submit">
                                    Save
                                </x-filament::button>
                            </div>
                        </form>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</x-filament-panels::page>
