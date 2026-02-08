<?php

declare(strict_types=1);

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Support\Filters\FilterStateManager;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * List Invoices Page with Filter State Management
 *
 * Extends the base ListRecords page to provide URL-based filter persistence
 * for actionable widget navigation. Maintains filter state when users navigate
 * from dashboard widgets to filtered invoice views.
 *
 * ## Key Features
 * - URL-based filter persistence
 * - Actionable widget integration
 * - Filter state validation
 * - Tenant-scoped filtering
 *
 * @package App\Filament\Resources\InvoiceResource\Pages
 */
class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    private ?FilterStateManager $filterManager = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    /**
     * Get the filter state manager instance.
     *
     * Lazy-loads the FilterStateManager to handle URL-based filter parameters
     * from actionable widget navigation.
     *
     * @return FilterStateManager The filter manager instance
     */
    protected function getFilterManager(): FilterStateManager
    {
        return $this->filterManager ??= new FilterStateManager();
    }

    /**
     * Get filter value from URL parameters.
     *
     * Helper method to retrieve filter values for use in table filter defaults.
     * Provides fallback handling for missing or invalid filter parameters.
     *
     * @param string $filterName The filter name to retrieve
     * @param mixed $default Default value if filter not found
     * @return mixed The filter value or default
     */
    protected function getFilterFromUrl(string $filterName, mixed $default = null): mixed
    {
        return $this->getFilterManager()->getFilterValue($filterName, $default);
    }

    /**
     * Check if filters are active from URL.
     *
     * Determines if the page was loaded with filter parameters from
     * actionable widget navigation.
     *
     * @return bool True if filters are active, false otherwise
     */
    protected function hasActiveFilters(): bool
    {
        return $this->getFilterManager()->getFilterCount() > 0;
    }

    /**
     * Get page subtitle when filters are active.
     *
     * Provides contextual information when users navigate from actionable
     * widgets to filtered views.
     *
     * @return string|null Page subtitle or null
     */
    public function getSubheading(): ?string
    {
        if (!$this->hasActiveFilters()) {
            return null;
        }

        $filterCount = $this->getFilterManager()->getFilterCount();
        
        return trans_choice(
            'app.pages.filtered_view_subtitle',
            $filterCount,
            ['count' => $filterCount]
        );
    }
}
