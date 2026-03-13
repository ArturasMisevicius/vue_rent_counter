<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use Filament\Resources\Resource;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Log;

/**
 * Base class for creating actionable dashboard widgets.
 *
 * Provides helper methods to make widget statistics clickable, navigating
 * users to filtered resource views. Includes error handling, authorization
 * checks, and visual feedback for interactive elements.
 *
 * ## Key Features
 * - Makes Stat objects clickable with filtered navigation
 * - Validates user permissions before creating actionable links
 * - Provides consistent hover states and visual feedback
 * - Handles errors gracefully with fallback to non-actionable stats
 * - Supports tenant-scoped filtering
 *
 * ## Usage Example
 * ```php
 * class DebtOverviewWidget extends ActionableWidget
 * {
 *     protected function getStats(): array
 *     {
 *         $debtAmount = $this->calculateDebtAmount();
 *         
 *         $stat = Stat::make('Outstanding Debt', $debtAmount)
 *             ->description('Total unpaid invoices')
 *             ->color('danger');
 *             
 *         return [
 *             $this->makeStatActionable($stat, InvoiceResource::class, [
 *                 'status' => ['value' => 'unpaid']
 *             ])
 *         ];
 *     }
 * }
 * ```
 *
 * @package App\Filament\Widgets
 */
abstract class ActionableWidget extends Widget
{
    /**
     * Make a Stat object clickable with filtered navigation.
     *
     * Converts a regular Stat into an actionable widget that navigates to
     * a filtered resource view when clicked. Includes authorization checks,
     * error handling, and visual feedback.
     *
     * @param Stat $stat The stat object to make actionable
     * @param string $resourceClass The Filament resource class to navigate to
     * @param array $filters Filter parameters to apply to the resource view
     * @return Stat The actionable stat with URL and styling
     */
    protected function makeStatActionable(Stat $stat, string $resourceClass, array $filters = []): Stat
    {
        try {
            // Check if user can access the resource
            if (!$this->canViewResource($resourceClass)) {
                return $stat; // Return non-actionable stat
            }
            
            // Apply tenant scoping to filters
            $filters = $this->applyTenantScope($filters);
            
            // Generate the filtered URL
            $url = $resourceClass::getUrl('index', [
                'tableFilters' => $filters
            ]);
            
            return $stat
                ->url($url)
                ->extraAttributes([
                    'class' => 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200',
                    'title' => 'Click to view filtered results',
                    'style' => 'text-decoration: none;'
                ]);
                
        } catch (\Exception $e) {
            Log::warning('Failed to make stat actionable', [
                'resource' => $resourceClass,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            
            return $stat; // Return non-actionable stat on error
        }
    }
    
    /**
     * Check if the current user can view the specified resource.
     *
     * @param string $resourceClass The Filament resource class
     * @return bool True if user can view the resource, false otherwise
     */
    protected function canViewResource(string $resourceClass): bool
    {
        try {
            $modelClass = $resourceClass::getModel();
            return auth()->user()?->can('viewAny', $modelClass) ?? false;
        } catch (\Exception $e) {
            Log::warning('Failed to check resource permissions', [
                'resource' => $resourceClass,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Apply tenant scoping to filter parameters.
     *
     * Ensures that filters maintain tenant isolation by adding tenant_id
     * filter when appropriate. Respects existing tenant context.
     *
     * @param array $filters The filter parameters
     * @return array The filters with tenant scoping applied
     */
    protected function applyTenantScope(array $filters): array
    {
        $user = auth()->user();
        
        if ($user && property_exists($user, 'tenant_id') && $user->tenant_id) {
            // Only add tenant filter if not already present and user is not superadmin
            if (!isset($filters['tenant_id']) && !$user->hasRole('superadmin')) {
                $filters['tenant_id'] = ['value' => $user->tenant_id];
            }
        }
        
        return $filters;
    }
    
    /**
     * Get the current tenant ID for caching and scoping.
     *
     * @return int|null The tenant ID or null if not available
     */
    protected function getTenantId(): ?int
    {
        $user = auth()->user();
        return $user && property_exists($user, 'tenant_id') ? $user->tenant_id : null;
    }
}