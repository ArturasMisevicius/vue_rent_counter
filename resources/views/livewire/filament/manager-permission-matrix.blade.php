@php
    use Illuminate\Support\Js;
@endphp

<div
    x-data="{
        matrix: $wire.entangle('matrix').live,
        selectedPreset: $wire.entangle('selectedPreset').live,
        presets: {{ Js::from($presetMatrices) }},
        detectPreset() {
            for (const [key, preset] of Object.entries(this.presets)) {
                if (JSON.stringify(preset) === JSON.stringify(this.matrix)) {
                    this.selectedPreset = key
                    return
                }
            }

            this.selectedPreset = 'custom'
        },
        applyPreset(key) {
            this.matrix = JSON.parse(JSON.stringify(this.presets[key] ?? {}))
            this.selectedPreset = key
        },
    }"
    x-init="detectPreset(); $watch('matrix', () => detectPreset())"
    class="fi-section rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-gray-900"
>
    @if (! $isManager)
        <div class="space-y-2">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                {{ __('admin.manager_permissions.section') }}
            </h3>

            <p class="text-sm text-gray-600 dark:text-gray-300">
                {{ __('admin.manager_permissions.non_manager_placeholder') }}
            </p>
        </div>
    @else
        <div class="space-y-6">
            <div class="space-y-3">
                @if ($showsSuperadminBanner)
                    <div class="rounded-xl border border-info-200 bg-info-50 p-4 text-sm text-info-900 dark:border-info-500/30 dark:bg-info-500/10 dark:text-info-100">
                        {{ __('admin.manager_permissions.superadmin_banner', [
                            'manager' => $manager?->name ?? __('admin.manager_permissions.labels.unknown_manager'),
                            'organization' => $organization?->name ?? __('admin.manager_permissions.labels.unknown_organization'),
                        ]) }}
                    </div>
                @endif

                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-1">
                        <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                            {{ __('admin.manager_permissions.section') }}
                        </h3>

                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            {{ __('admin.manager_permissions.description') }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($presetLabels as $presetKey => $presetLabel)
                            <button
                                type="button"
                                x-on:click="applyPreset(@js($presetKey))"
                                x-bind:class="selectedPreset === @js($presetKey)
                                    ? 'border-primary-600 bg-primary-600 text-white dark:border-primary-500 dark:bg-primary-500'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-primary-300 hover:text-primary-700 dark:border-white/15 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-primary-400 dark:hover:text-primary-200'"
                                class="rounded-lg border px-3 py-2 text-sm font-medium transition"
                            >
                                {{ $presetLabel }}
                            </button>
                        @endforeach

                        <span
                            x-show="selectedPreset === 'custom'"
                            class="rounded-lg border border-warning-300 bg-warning-50 px-3 py-2 text-sm font-medium text-warning-800 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-100"
                        >
                            {{ __('admin.manager_permissions.presets.custom') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-white/10">
                <div class="grid grid-cols-[minmax(0,2fr)_repeat(4,minmax(88px,1fr))] gap-px bg-gray-200 text-sm dark:bg-white/10">
                    <div class="bg-gray-50 px-4 py-3 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        {{ __('admin.manager_permissions.headers.resource') }}
                    </div>

                    @foreach (['create', 'edit', 'delete', 'view'] as $header)
                        <div class="bg-gray-50 px-4 py-3 text-center font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            {{ __("admin.manager_permissions.headers.{$header}") }}
                        </div>
                    @endforeach

                    @foreach ($labels as $resource => $label)
                        @php
                            $resourceAvailability = $availability[$resource] ?? ['available' => true, 'reason' => null];
                        @endphp

                        <div class="bg-white px-4 py-3 text-sm font-medium text-gray-900 dark:bg-gray-900 dark:text-gray-100 {{ $resourceAvailability['available'] ? '' : 'opacity-60' }}"
                             title="{{ $resourceAvailability['reason'] ?? '' }}">
                            {{ $label }}
                        </div>

                        @foreach (['can_create', 'can_edit', 'can_delete'] as $flag)
                            <label
                                class="flex items-center justify-center bg-white px-4 py-3 dark:bg-gray-900 {{ $resourceAvailability['available'] ? '' : 'opacity-60' }}"
                                title="{{ $resourceAvailability['reason'] ?? '' }}"
                            >
                                <input
                                    type="checkbox"
                                    x-model="matrix.{{ $resource }}.{{ $flag }}"
                                    @disabled(! $resourceAvailability['available'])
                                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:border-white/15 dark:bg-gray-950"
                                >
                            </label>
                        @endforeach

                        <div
                            class="flex items-center justify-center bg-white px-4 py-3 text-sm text-gray-500 dark:bg-gray-900 dark:text-gray-300"
                            title="{{ __('admin.manager_permissions.view_locked_tooltip') }}"
                        >
                            <span aria-hidden="true">✓</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    @if ($copyableManagers !== [])
                        <button
                            type="button"
                            wire:click="openCopyModal"
                            class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-300 dark:hover:text-primary-200"
                        >
                            {{ __('admin.manager_permissions.copy_from_manager') }}
                        </button>
                    @endif
                </div>

                <div class="flex justify-end">
                    <button
                        type="button"
                        wire:click="save"
                        class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400"
                    >
                        {{ __('admin.manager_permissions.save') }}
                    </button>
                </div>
            </div>
        </div>

        <x-filament::modal
            :id="$copyModalId"
            :heading="__('admin.manager_permissions.copy_modal.heading')"
            :description="__('admin.manager_permissions.copy_modal.description')"
            width="md"
        >
            <div class="space-y-4">
                <label class="block space-y-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                        {{ __('admin.manager_permissions.copy_modal.select_label') }}
                    </span>

                    <select
                        wire:model.live="copyFromManagerId"
                        class="block w-full rounded-lg border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/15 dark:bg-gray-900 dark:text-gray-100"
                    >
                        <option value="">
                            {{ __('admin.manager_permissions.copy_modal.placeholder') }}
                        </option>

                        @foreach ($copyableManagers as $managerOption)
                            <option value="{{ $managerOption['id'] }}">
                                {{ $managerOption['name'] }} ({{ $managerOption['email'] }})
                            </option>
                        @endforeach
                    </select>
                </label>

                <div class="flex justify-end">
                    <button
                        type="button"
                        wire:click="copyFromSelectedManager"
                        @disabled(! $copyFromManagerId)
                        class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-primary-500 dark:hover:bg-primary-400"
                    >
                        {{ __('admin.manager_permissions.copy_modal.confirm') }}
                    </button>
                </div>
            </div>
        </x-filament::modal>
    @endif
</div>
