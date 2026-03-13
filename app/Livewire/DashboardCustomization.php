<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\DashboardCustomizationService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Livewire\Component;

class DashboardCustomization extends Component implements HasForms
{
    use InteractsWithForms;

    public bool $customizationMode = false;

    public array $availableWidgets = [];

    public array $currentConfiguration = [];

    public array $widgetLibrary = [];

    public ?string $selectedWidget = null;

    public array $widgetConfig = [];

    protected DashboardCustomizationService $customizationService;

    public function boot(DashboardCustomizationService $customizationService): void
    {
        $this->customizationService = $customizationService;
    }

    public function mount(): void
    {
        $this->loadConfiguration();
        $this->loadWidgetLibrary();
    }

    public function loadConfiguration(): void
    {
        $user = auth()->user();
        $this->currentConfiguration = $this->customizationService->getUserConfiguration($user);
        $this->availableWidgets = $this->customizationService->getAvailableWidgets();
    }

    public function loadWidgetLibrary(): void
    {
        $this->widgetLibrary = $this->customizationService->getWidgetsByCategory();
    }

    public function toggleCustomizationMode(): void
    {
        $this->customizationMode = ! $this->customizationMode;

        if (! $this->customizationMode) {
            // Refresh configuration when exiting customization mode
            $this->loadConfiguration();
            $this->dispatch('dashboard-updated');
        }
    }

    public function addWidget(string $widgetClass): void
    {
        $user = auth()->user();

        if ($this->customizationService->addWidget($user, $widgetClass)) {
            $this->loadConfiguration();
            $this->dispatch('dashboard-updated');

            Notification::make()
                ->title('Widget Added')
                ->body('The widget has been added to your dashboard.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to Add Widget')
                ->body('The widget could not be added. It may already exist on your dashboard.')
                ->danger()
                ->send();
        }
    }

    public function removeWidget(string $widgetClass): void
    {
        $user = auth()->user();

        if ($this->customizationService->removeWidget($user, $widgetClass)) {
            $this->loadConfiguration();
            $this->dispatch('dashboard-updated');

            Notification::make()
                ->title('Widget Removed')
                ->body('The widget has been removed from your dashboard.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Failed to Remove Widget')
                ->body('The widget could not be removed.')
                ->danger()
                ->send();
        }
    }

    public function updateWidgetPositions(array $positions): void
    {
        $user = auth()->user();

        if ($this->customizationService->rearrangeWidgets($user, $positions)) {
            $this->loadConfiguration();
            $this->dispatch('dashboard-updated');

            Notification::make()
                ->title('Layout Updated')
                ->body('Widget positions have been saved.')
                ->success()
                ->send();
        }
    }

    public function updateWidgetSize(string $widgetClass, string $size): void
    {
        $user = auth()->user();

        if ($this->customizationService->updateWidgetSize($user, $widgetClass, $size)) {
            $this->loadConfiguration();
            $this->dispatch('dashboard-updated');

            Notification::make()
                ->title('Widget Size Updated')
                ->body('The widget size has been changed.')
                ->success()
                ->send();
        }
    }

    public function updateWidgetRefreshInterval(string $widgetClass, int $interval): void
    {
        $user = auth()->user();

        if ($this->customizationService->updateWidgetRefreshInterval($user, $widgetClass, $interval)) {
            $this->loadConfiguration();
            $this->dispatch('dashboard-updated');

            Notification::make()
                ->title('Refresh Interval Updated')
                ->body('The widget refresh interval has been changed.')
                ->success()
                ->send();
        }
    }

    public function toggleWidget(string $widgetClass): void
    {
        $user = auth()->user();

        if ($this->customizationService->toggleWidget($user, $widgetClass)) {
            $this->loadConfiguration();
            $this->dispatch('dashboard-updated');

            Notification::make()
                ->title('Widget Toggled')
                ->body('The widget visibility has been changed.')
                ->success()
                ->send();
        }
    }

    public function resetToDefault(): void
    {
        $user = auth()->user();

        if ($this->customizationService->resetToDefault($user)) {
            $this->loadConfiguration();
            $this->dispatch('dashboard-updated');

            Notification::make()
                ->title('Dashboard Reset')
                ->body('Your dashboard has been reset to the default layout.')
                ->success()
                ->send();
        }
    }

    public function openWidgetConfig(string $widgetClass): void
    {
        $this->selectedWidget = $widgetClass;
        $user = auth()->user();
        $this->widgetConfig = $this->customizationService->getWidgetConfiguration($user, $widgetClass) ?? [];
    }

    public function closeWidgetConfig(): void
    {
        $this->selectedWidget = null;
        $this->widgetConfig = [];
    }

    public function saveWidgetConfig(): void
    {
        if (! $this->selectedWidget) {
            return;
        }

        $user = auth()->user();

        // Update size if changed
        if (isset($this->widgetConfig['size'])) {
            $this->customizationService->updateWidgetSize($user, $this->selectedWidget, $this->widgetConfig['size']);
        }

        // Update refresh interval if changed
        if (isset($this->widgetConfig['refresh_interval'])) {
            $this->customizationService->updateWidgetRefreshInterval($user, $this->selectedWidget, (int) $this->widgetConfig['refresh_interval']);
        }

        $this->loadConfiguration();
        $this->dispatch('dashboard-updated');
        $this->closeWidgetConfig();

        Notification::make()
            ->title('Widget Configuration Saved')
            ->body('The widget settings have been updated.')
            ->success()
            ->send();
    }

    public function exportLayout(): void
    {
        $user = auth()->user();
        $configuration = $this->customizationService->exportConfiguration($user);

        $this->dispatch('download-layout', [
            'filename' => 'dashboard-layout-'.date('Y-m-d-H-i-s').'.json',
            'content' => json_encode($configuration, JSON_PRETTY_PRINT),
        ]);
    }

    public function shareLayoutUrl(): string
    {
        $user = auth()->user();
        $configuration = $this->customizationService->exportConfiguration($user);

        // Create a shareable URL with base64 encoded configuration
        $encodedConfig = base64_encode(json_encode($configuration));

        return route('dashboard').'?import='.$encodedConfig;
    }

    public function getEnabledWidgets(): array
    {
        return collect($this->currentConfiguration['widgets'] ?? [])
            ->filter(fn ($widget) => $widget['enabled'])
            ->sortBy('position')
            ->toArray();
    }

    public function getDisabledWidgets(): array
    {
        return collect($this->currentConfiguration['widgets'] ?? [])
            ->filter(fn ($widget) => ! $widget['enabled'])
            ->sortBy('position')
            ->toArray();
    }

    public function isWidgetEnabled(string $widgetClass): bool
    {
        $widgets = $this->currentConfiguration['widgets'] ?? [];

        foreach ($widgets as $widget) {
            if ($widget['class'] === $widgetClass) {
                return $widget['enabled'] ?? false;
            }
        }

        return false;
    }

    public function render()
    {
        return view('livewire.dashboard-customization');
    }
}
