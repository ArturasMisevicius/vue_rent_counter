@extends('layouts.app')

@section('title', __('tariffs.pages.admin_form.create_title'))

@section('content')
<div class="px-4 sm:px-6 lg:px-8">
<div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
            <h1 class="text-2xl font-semibold text-slate-900">{{ __('tariffs.pages.admin_form.create_title') }}</h1>
            <p class="mt-2 text-sm text-slate-700">{{ __('tariffs.pages.admin_form.create_subtitle') }}</p>
        </div>
    </div>

    <div class="mt-8 max-w-2xl">
        <x-card>
            <form action="{{ route('admin.tariffs.store') }}" method="POST">
                @csrf

                <div class="space-y-6">
                    <x-form-input 
                        name="name" 
                        label="{{ __('tariffs.pages.admin_form.labels.name') }}" 
                        :value="old('name')" 
                        required 
                    />

                    <x-form-select 
                        name="provider_id" 
                        label="{{ __('tariffs.pages.admin_form.labels.provider') }}" 
                        :options="$providers->pluck('name', 'id')" 
                        :selected="old('provider_id', request('provider_id'))" 
                        required 
                    />

                    <div x-data="tariffConfigEditor()" x-init="init()">
                        <label for="configuration" class="block text-sm font-medium text-slate-700">{{ __('tariffs.pages.admin_form.labels.configuration') }}</label>
                        
                        <!-- Tab Navigation -->
                        <div class="mt-2 border-b border-slate-200">
                            <nav class="-mb-px flex space-x-4" aria-label="Tabs">
                                <button 
                                    type="button"
                                    @click="activeTab = 'visual'" 
                                    :class="activeTab === 'visual' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                                    class="whitespace-nowrap border-b-2 px-1 py-2 text-sm font-medium transition">
                                    Visual Editor
                                </button>
                                <button 
                                    type="button"
                                    @click="activeTab = 'json'" 
                                    :class="activeTab === 'json' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                                    class="whitespace-nowrap border-b-2 px-1 py-2 text-sm font-medium transition">
                                    JSON Editor
                                </button>
                            </nav>
                        </div>

                        <!-- Visual Editor -->
                        <div x-show="activeTab === 'visual'" class="mt-4 space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Tariff Type</label>
                                <select x-model="config.type" @change="updateJson()" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="flat">Flat Rate</option>
                                    <option value="time_of_use">Time of Use</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Currency</label>
                                <input type="text" x-model="config.currency" @input="updateJson()" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                            </div>

                            <!-- Flat Rate Fields -->
                            <template x-if="config.type === 'flat'">
                                <div class="space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <h4 class="text-sm font-semibold text-slate-900">Flat Rate Configuration</h4>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Rate (per unit)</label>
                                        <input type="number" step="0.0001" x-model.number="config.rate" @input="updateJson()" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Fixed Fee (optional)</label>
                                        <input type="number" step="0.01" x-model.number="config.fixed_fee" @input="updateJson()" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                    </div>
                                </div>
                            </template>

                            <!-- Time of Use Fields -->
                            <template x-if="config.type === 'time_of_use'">
                                <div class="space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-semibold text-slate-900">Time of Use Zones</h4>
                                        <button type="button" @click="addZone()" class="inline-flex items-center rounded-md bg-indigo-600 px-2.5 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                            Add Zone
                                        </button>
                                    </div>
                                    <template x-for="(zone, index) in config.zones" :key="index">
                                        <div class="rounded-md border border-slate-300 bg-white p-3 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-sm font-medium text-slate-700">Zone <span x-text="index + 1"></span></span>
                                                <button type="button" @click="removeZone(index)" class="text-red-600 hover:text-red-800 text-sm">Remove</button>
                                            </div>
                                            <div class="grid grid-cols-2 gap-2">
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-600">ID</label>
                                                    <input type="text" x-model="zone.id" @input="updateJson()" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-600">Rate</label>
                                                    <input type="number" step="0.0001" x-model.number="zone.rate" @input="updateJson()" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-600">Start Time</label>
                                                    <input type="time" x-model="zone.start" @input="updateJson()" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-600">End Time</label>
                                                    <input type="time" x-model="zone.end" @input="updateJson()" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm" />
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Weekend Logic</label>
                                        <select x-model="config.weekend_logic" @change="updateJson()" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="apply_night_rate">Apply Night Rate</option>
                                            <option value="apply_day_rate">Apply Day Rate</option>
                                            <option value="separate_rate">Separate Rate</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-slate-700">Fixed Fee (optional)</label>
                                        <input type="number" step="0.01" x-model.number="config.fixed_fee" @input="updateJson()" class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                                    </div>
                                </div>
                            </template>

                            <!-- Validation Messages -->
                            <div x-show="validationError" class="rounded-md bg-red-50 p-3">
                                <p class="text-sm text-red-800" x-text="validationError"></p>
                            </div>
                        </div>

                        <!-- JSON Editor -->
                        <div x-show="activeTab === 'json'" class="mt-4">
                            <div class="rounded-md bg-slate-50 p-4 text-xs text-slate-700 space-y-2 mb-2">
                                <p class="font-semibold">{{ __('tariffs.pages.admin_form.examples.flat_heading') }}</p>
                                <pre class="bg-white p-2 rounded border border-slate-200">{{ json_encode(['type' => 'flat', 'currency' => 'EUR', 'rate' => 0.15], JSON_PRETTY_PRINT) }}</pre>
                                
                                <p class="font-semibold mt-3">{{ __('tariffs.pages.admin_form.examples.tou_heading') }}</p>
                                <pre class="bg-white p-2 rounded border border-slate-200">{{ json_encode([
    'type' => 'time_of_use',
    'currency' => 'EUR',
    'zones' => [
        ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
        ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.09]
    ],
    'weekend_logic' => 'apply_night_rate'
], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            
                            <textarea 
                                x-model="jsonText"
                                @input="parseJson()"
                                rows="12" 
                                @class([
                                    'block w-full rounded-md shadow-sm focus:ring-indigo-500 sm:text-sm font-mono',
                                    'border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500' => $errors->has('configuration'),
                                    'border-slate-300 focus:border-indigo-500' => !$errors->has('configuration'),
                                ])
                                placeholder="{{ __('tariffs.pages.admin_form.placeholders.configuration') }}"
                            ></textarea>
                            
                            <div x-show="jsonError" class="mt-2 rounded-md bg-red-50 p-3">
                                <p class="text-sm text-red-800" x-text="jsonError"></p>
                            </div>
                        </div>

                        <!-- Hidden input for form submission -->
                        <input type="hidden" name="configuration" x-model="jsonText" />
                        
                        @error('configuration')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('configuration.*')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    @push('scripts')
                    <script>
                        function tariffConfigEditor() {
                            return {
                                activeTab: 'visual',
                                config: {},
                                jsonText: '',
                                jsonError: '',
                                validationError: '',
                                
                                init() {
                                    try {
                                        const initialConfig = @json(old('configuration', ['type' => 'flat', 'currency' => 'EUR', 'rate' => 0.15]));
                                        this.config = typeof initialConfig === 'string' ? JSON.parse(initialConfig) : initialConfig;
                                        this.jsonText = JSON.stringify(this.config, null, 2);
                                    } catch (e) {
                                        this.jsonError = 'Failed to parse initial configuration';
                                        this.config = { type: 'flat', currency: 'EUR', rate: 0.15 };
                                        this.jsonText = JSON.stringify(this.config, null, 2);
                                    }
                                },
                                
                                updateJson() {
                                    this.validationError = '';
                                    try {
                                        this.jsonText = JSON.stringify(this.config, null, 2);
                                        this.jsonError = '';
                                    } catch (e) {
                                        this.jsonError = 'Failed to generate JSON: ' + e.message;
                                    }
                                },
                                
                                parseJson() {
                                    this.jsonError = '';
                                    try {
                                        this.config = JSON.parse(this.jsonText);
                                    } catch (e) {
                                        this.jsonError = 'Invalid JSON: ' + e.message;
                                    }
                                },
                                
                                addZone() {
                                    if (!this.config.zones) {
                                        this.config.zones = [];
                                    }
                                    this.config.zones.push({
                                        id: 'zone_' + (this.config.zones.length + 1),
                                        start: '00:00',
                                        end: '23:59',
                                        rate: 0
                                    });
                                    this.updateJson();
                                },
                                
                                removeZone(index) {
                                    this.config.zones.splice(index, 1);
                                    this.updateJson();
                                }
                            };
                        }
                    </script>
                    @endpush

                    <div class="grid grid-cols-2 gap-4">
                        <x-form-input 
                            name="active_from" 
                            label="{{ __('tariffs.pages.admin_form.labels.active_from') }}" 
                            type="date" 
                            :value="old('active_from', now()->format('Y-m-d'))" 
                            required 
                        />

                        <x-form-input 
                            name="active_until" 
                            label="{{ __('tariffs.pages.admin_form.labels.active_until') }}" 
                            type="date" 
                            :value="old('active_until')" 
                        />
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-x-3">
                    <a href="{{ route('admin.tariffs.index') }}" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                        {{ __('tariffs.pages.admin_form.actions.cancel') }}
                    </a>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                        {{ __('tariffs.pages.admin_form.actions.save_create') }}
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</div>
@endsection
