<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DashboardCustomization;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DashboardCustomizationService
{
    /**
     * Get the dashboard configuration for a user
     */
    public function getUserConfiguration(User $user): array
    {
        $cacheKey = "dashboard_config_{$user->id}";
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            $customization = DashboardCustomization::where('user_id', $user->id)->first();
            
            if (!$customization) {
                return DashboardCustomization::getDefaultConfiguration();
            }
            
            return [
                'widgets' => $customization->widget_configuration ?? DashboardCustomization::getDefaultConfiguration()['widgets'],
                'layout' => $customization->layout_configuration ?? DashboardCustomization::getDefaultConfiguration()['layout'],
            ];
        });
    }

    /**
     * Save dashboard configuration for a user
     */
    public function saveUserConfiguration(User $user, array $configuration): bool
    {
        try {
            DashboardCustomization::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'widget_configuration' => $configuration['widgets'] ?? null,
                    'layout_configuration' => $configuration['layout'] ?? null,
                    'refresh_intervals' => $this->extractRefreshIntervals($configuration['widgets'] ?? []),
                ]
            );

            // Clear cache
            Cache::forget("dashboard_config_{$user->id}");
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to save dashboard configuration', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Add a widget to user's dashboard
     */
    public function addWidget(User $user, string $widgetClass, array $options = []): bool
    {
        $configuration = $this->getUserConfiguration($user);
        $availableWidgets = DashboardCustomization::getAvailableWidgets();
        
        if (!isset($availableWidgets[$widgetClass])) {
            return false;
        }
        
        $widget = $availableWidgets[$widgetClass];
        $nextPosition = $this->getNextPosition($configuration['widgets']);
        
        $newWidget = [
            'class' => $widgetClass,
            'position' => $options['position'] ?? $nextPosition,
            'size' => $options['size'] ?? $widget['default_size'],
            'refresh_interval' => $options['refresh_interval'] ?? $widget['default_refresh'],
            'enabled' => $options['enabled'] ?? true,
        ];
        
        // Check if widget already exists
        $existingIndex = $this->findWidgetIndex($configuration['widgets'], $widgetClass);
        if ($existingIndex !== false) {
            return false; // Widget already exists
        }
        
        $configuration['widgets'][] = $newWidget;
        
        // Re-sort by position
        usort($configuration['widgets'], fn($a, $b) => $a['position'] <=> $b['position']);
        
        return $this->saveUserConfiguration($user, $configuration);
    }

    /**
     * Remove a widget from user's dashboard
     */
    public function removeWidget(User $user, string $widgetClass): bool
    {
        $configuration = $this->getUserConfiguration($user);
        
        $widgetIndex = $this->findWidgetIndex($configuration['widgets'], $widgetClass);
        if ($widgetIndex === false) {
            return false;
        }
        
        unset($configuration['widgets'][$widgetIndex]);
        $configuration['widgets'] = array_values($configuration['widgets']); // Re-index
        
        return $this->saveUserConfiguration($user, $configuration);
    }

    /**
     * Rearrange widgets by updating their positions
     */
    public function rearrangeWidgets(User $user, array $widgetPositions): bool
    {
        $configuration = $this->getUserConfiguration($user);
        
        foreach ($configuration['widgets'] as &$widget) {
            if (isset($widgetPositions[$widget['class']])) {
                $widget['position'] = $widgetPositions[$widget['class']];
            }
        }
        
        // Sort by position
        usort($configuration['widgets'], fn($a, $b) => $a['position'] <=> $b['position']);
        
        return $this->saveUserConfiguration($user, $configuration);
    }

    /**
     * Update widget size
     */
    public function updateWidgetSize(User $user, string $widgetClass, string $size): bool
    {
        if (!in_array($size, ['small', 'medium', 'large'])) {
            return false;
        }
        
        $configuration = $this->getUserConfiguration($user);
        $widgetIndex = $this->findWidgetIndex($configuration['widgets'], $widgetClass);
        
        if ($widgetIndex === false) {
            return false;
        }
        
        $configuration['widgets'][$widgetIndex]['size'] = $size;
        
        return $this->saveUserConfiguration($user, $configuration);
    }

    /**
     * Update widget refresh interval
     */
    public function updateWidgetRefreshInterval(User $user, string $widgetClass, int $interval): bool
    {
        if ($interval < 10 || $interval > 3600) { // Between 10 seconds and 1 hour
            return false;
        }
        
        $configuration = $this->getUserConfiguration($user);
        $widgetIndex = $this->findWidgetIndex($configuration['widgets'], $widgetClass);
        
        if ($widgetIndex === false) {
            return false;
        }
        
        $configuration['widgets'][$widgetIndex]['refresh_interval'] = $interval;
        
        return $this->saveUserConfiguration($user, $configuration);
    }

    /**
     * Toggle widget enabled/disabled state
     */
    public function toggleWidget(User $user, string $widgetClass): bool
    {
        $configuration = $this->getUserConfiguration($user);
        $widgetIndex = $this->findWidgetIndex($configuration['widgets'], $widgetClass);
        
        if ($widgetIndex === false) {
            return false;
        }
        
        $configuration['widgets'][$widgetIndex]['enabled'] = !$configuration['widgets'][$widgetIndex]['enabled'];
        
        return $this->saveUserConfiguration($user, $configuration);
    }

    /**
     * Reset dashboard to default configuration
     */
    public function resetToDefault(User $user): bool
    {
        try {
            DashboardCustomization::where('user_id', $user->id)->delete();
            Cache::forget("dashboard_config_{$user->id}");
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to reset dashboard configuration', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Export dashboard configuration
     */
    public function exportConfiguration(User $user): array
    {
        return $this->getUserConfiguration($user);
    }

    /**
     * Import dashboard configuration
     */
    public function importConfiguration(User $user, array $configuration): bool
    {
        // Validate configuration structure
        if (!$this->validateConfiguration($configuration)) {
            return false;
        }
        
        return $this->saveUserConfiguration($user, $configuration);
    }

    /**
     * Get available widgets for the widget library
     */
    public function getAvailableWidgets(): array
    {
        return DashboardCustomization::getAvailableWidgets();
    }

    /**
     * Get widgets grouped by category
     */
    public function getWidgetsByCategory(): array
    {
        $widgets = $this->getAvailableWidgets();
        $grouped = [];
        
        foreach ($widgets as $class => $widget) {
            $category = $widget['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][$class] = $widget;
        }
        
        return $grouped;
    }

    /**
     * Get enabled widgets for a user in the correct order
     */
    public function getEnabledWidgets(User $user): array
    {
        $configuration = $this->getUserConfiguration($user);
        
        return collect($configuration['widgets'])
            ->filter(fn($widget) => $widget['enabled'])
            ->sortBy('position')
            ->pluck('class')
            ->toArray();
    }

    /**
     * Get widget configuration for a specific widget
     */
    public function getWidgetConfiguration(User $user, string $widgetClass): ?array
    {
        $configuration = $this->getUserConfiguration($user);
        $widgetIndex = $this->findWidgetIndex($configuration['widgets'], $widgetClass);
        
        return $widgetIndex !== false ? $configuration['widgets'][$widgetIndex] : null;
    }

    /**
     * Private helper methods
     */
    private function findWidgetIndex(array $widgets, string $widgetClass): int|false
    {
        foreach ($widgets as $index => $widget) {
            if ($widget['class'] === $widgetClass) {
                return $index;
            }
        }
        
        return false;
    }

    private function getNextPosition(array $widgets): int
    {
        if (empty($widgets)) {
            return 1;
        }
        
        $maxPosition = max(array_column($widgets, 'position'));
        return $maxPosition + 1;
    }

    private function extractRefreshIntervals(array $widgets): array
    {
        $intervals = [];
        foreach ($widgets as $widget) {
            $intervals[$widget['class']] = $widget['refresh_interval'] ?? 60;
        }
        
        return $intervals;
    }

    private function validateConfiguration(array $configuration): bool
    {
        // Check required keys
        if (!isset($configuration['widgets']) || !isset($configuration['layout'])) {
            return false;
        }
        
        // Validate widgets structure
        if (!is_array($configuration['widgets'])) {
            return false;
        }
        
        $availableWidgets = array_keys($this->getAvailableWidgets());
        
        foreach ($configuration['widgets'] as $widget) {
            if (!is_array($widget) || 
                !isset($widget['class']) || 
                !in_array($widget['class'], $availableWidgets)) {
                return false;
            }
        }
        
        return true;
    }
}