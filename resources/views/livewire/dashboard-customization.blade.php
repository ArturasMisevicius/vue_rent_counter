<div class="dashboard-customization">
    <!-- Customization Toggle Button -->
    <div class="mb-4 flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Dashboard Customization
        </h2>
        
        <div class="flex gap-2">
            @if($customizationMode)
                <div class="flex gap-2">
                    <x-filament::button
                        wire:click="exportLayout"
                        color="primary"
                        size="sm"
                        icon="heroicon-o-arrow-down-tray"
                    >
                        Export Layout
                    </x-filament::button>
                    
                    <x-filament::button
                        wire:click="resetToDefault"
                        color="danger"
                        size="sm"
                        icon="heroicon-o-arrow-path"
                    >
                        Reset to Default
                    </x-filament::button>
                </div>
            @endif
            
            <x-filament::button
                wire:click="toggleCustomizationMode"
                :color="$customizationMode ? 'success' : 'primary'"
                size="sm"
                :icon="$customizationMode ? 'heroicon-o-check' : 'heroicon-o-cog-6-tooth'"
            >
                {{ $customizationMode ? 'Done' : 'Customize' }}
            </x-filament::button>
        </div>
    </div>

    @if($customizationMode)
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Widget Library Panel -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Widget Library
                    </h3>
                    
                    @foreach($widgetLibrary as $category => $widgets)
                        <div class="mb-4">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 capitalize">
                                {{ $category }}
                            </h4>
                            
                            <div class="space-y-2">
                                @foreach($widgets as $widgetClass => $widget)
                                    <div class="widget-library-item p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer"
                                         data-widget-class="{{ $widgetClass }}">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ $widget['name'] }}
                                                </h5>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $widget['description'] }}
                                                </p>
                                            </div>
                                            
                                            @if($this->isWidgetEnabled($widgetClass))
                                                <x-filament::icon-button
                                                    wire:click="removeWidget('{{ $widgetClass }}')"
                                                    icon="heroicon-o-minus-circle"
                                                    color="danger"
                                                    size="sm"
                                                    tooltip="Remove from dashboard"
                                                />
                                            @else
                                                <x-filament::icon-button
                                                    wire:click="addWidget('{{ $widgetClass }}')"
                                                    icon="heroicon-o-plus-circle"
                                                    color="success"
                                                    size="sm"
                                                    tooltip="Add to dashboard"
                                                />
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Dashboard Preview -->
            <div class="lg:col-span-3">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        Dashboard Layout
                    </h3>
                    
                    <div id="dashboard-preview" class="sortable-container grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($this->getEnabledWidgets() as $widget)
                            <div class="widget-preview sortable-item border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 hover:border-primary-500 cursor-move"
                                 data-widget-class="{{ $widget['class'] }}"
                                 data-position="{{ $widget['position'] }}">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $availableWidgets[$widget['class']]['name'] ?? 'Unknown Widget' }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Size: {{ ucfirst($widget['size']) }} | 
                                            Refresh: {{ $widget['refresh_interval'] }}s
                                        </p>
                                    </div>
                                    
                                    <div class="flex gap-1">
                                        <x-filament::icon-button
                                            wire:click="openWidgetConfig('{{ $widget['class'] }}')"
                                            icon="heroicon-o-cog-6-tooth"
                                            color="gray"
                                            size="sm"
                                            tooltip="Configure widget"
                                        />
                                        
                                        <x-filament::icon-button
                                            wire:click="toggleWidget('{{ $widget['class'] }}')"
                                            icon="heroicon-o-eye-slash"
                                            color="warning"
                                            size="sm"
                                            tooltip="Hide widget"
                                        />
                                        
                                        <x-filament::icon-button
                                            wire:click="removeWidget('{{ $widget['class'] }}')"
                                            icon="heroicon-o-trash"
                                            color="danger"
                                            size="sm"
                                            tooltip="Remove widget"
                                        />
                                    </div>
                                </div>
                                
                                <div class="widget-preview-content bg-gray-100 dark:bg-gray-700 rounded p-2 text-center text-xs text-gray-600 dark:text-gray-400">
                                    Widget Preview
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    @if(count($this->getDisabledWidgets()) > 0)
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Hidden Widgets
                            </h4>
                            
                            <div class="flex flex-wrap gap-2">
                                @foreach($this->getDisabledWidgets() as $widget)
                                    <div class="inline-flex items-center gap-2 px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-full text-sm">
                                        <span class="text-gray-600 dark:text-gray-400">
                                            {{ $availableWidgets[$widget['class']]['name'] ?? 'Unknown Widget' }}
                                        </span>
                                        
                                        <x-filament::icon-button
                                            wire:click="toggleWidget('{{ $widget['class'] }}')"
                                            icon="heroicon-o-eye"
                                            color="success"
                                            size="xs"
                                            tooltip="Show widget"
                                        />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Widget Configuration Modal -->
    @if($selectedWidget)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeWidgetConfig"></div>
                
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                    Configure Widget: {{ $availableWidgets[$selectedWidget]['name'] ?? 'Unknown Widget' }}
                                </h3>
                                
                                <div class="mt-4 space-y-4">
                                    <!-- Widget Size -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Size
                                        </label>
                                        <select wire:model="widgetConfig.size" 
                                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                                            <option value="small">Small</option>
                                            <option value="medium">Medium</option>
                                            <option value="large">Large</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Refresh Interval -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Refresh Interval (seconds)
                                        </label>
                                        <select wire:model="widgetConfig.refresh_interval"
                                                class="block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white">
                                            <option value="10">10 seconds</option>
                                            <option value="30">30 seconds</option>
                                            <option value="60">1 minute</option>
                                            <option value="300">5 minutes</option>
                                            <option value="600">10 minutes</option>
                                            <option value="1800">30 minutes</option>
                                            <option value="3600">1 hour</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <x-filament::button
                            wire:click="saveWidgetConfig"
                            color="primary"
                            size="sm"
                        >
                            Save Changes
                        </x-filament::button>
                        
                        <x-filament::button
                            wire:click="closeWidgetConfig"
                            color="gray"
                            size="sm"
                            class="mr-2"
                        >
                            Cancel
                        </x-filament::button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    initializeSortable();
});

document.addEventListener('livewire:navigated', function() {
    initializeSortable();
});

function initializeSortable() {
    const container = document.getElementById('dashboard-preview');
    if (container && typeof Sortable !== 'undefined') {
        new Sortable(container, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function(evt) {
                const positions = {};
                const items = container.querySelectorAll('.sortable-item');
                
                items.forEach((item, index) => {
                    const widgetClass = item.dataset.widgetClass;
                    positions[widgetClass] = index + 1;
                });
                
                @this.call('updateWidgetPositions', positions);
            }
        });
    }
}

// Listen for dashboard updates to reinitialize sortable
document.addEventListener('livewire:init', () => {
    Livewire.on('dashboard-updated', () => {
        setTimeout(() => {
            initializeSortable();
        }, 100);
    });
    
    // Handle layout download
    Livewire.on('download-layout', (event) => {
        const data = event[0];
        const blob = new Blob([data.content], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = data.filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    });
});
</script>

<style>
.sortable-ghost {
    opacity: 0.4;
}

.sortable-chosen {
    transform: scale(1.02);
}

.sortable-drag {
    transform: rotate(5deg);
}

.widget-preview {
    transition: all 0.2s ease;
}

.widget-preview:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.widget-library-item {
    transition: all 0.2s ease;
}

.widget-library-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}
</style>
@endpush