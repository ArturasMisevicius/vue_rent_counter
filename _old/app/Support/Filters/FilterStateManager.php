<?php

declare(strict_types=1);

namespace App\Support\Filters;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

/**
 * Filter State Management for Filament Resources
 *
 * Handles URL-based filter persistence and validation for actionable widgets
 * that navigate to filtered resource views. Ensures filter state is maintained
 * across page loads and provides fallback handling for invalid parameters.
 *
 * ## Key Features
 * - URL parameter parsing and validation
 * - Filter state persistence across navigation
 * - Multi-value filter support
 * - Date range filter handling
 * - Tenant scoping integration
 * - Error handling with graceful fallbacks
 *
 * ## Usage Example
 * ```php
 * // In ListInvoices page
 * protected function getTableFilters(): array
 * {
 *     $filterManager = new FilterStateManager();
 *     
 *     return [
 *         SelectFilter::make('status')
 *             ->options(['draft' => 'Draft', 'paid' => 'Paid'])
 *             ->default($filterManager->getFilterValue('status')),
 *     ];
 * }
 * ```
 *
 * @package App\Support\Filters
 */
class FilterStateManager
{
    private Request $request;
    private array $filters;

    public function __construct(?Request $request = null)
    {
        $this->request = $request ?? request();
        $this->filters = $this->parseFiltersFromUrl();
    }

    /**
     * Parse filter parameters from URL.
     *
     * Extracts tableFilters parameter from the request and validates
     * the structure. Handles both single values and complex filter objects.
     *
     * @return array Parsed and validated filter parameters
     */
    private function parseFiltersFromUrl(): array
    {
        try {
            $tableFilters = $this->request->query('tableFilters', []);
            
            if (!is_array($tableFilters)) {
                return [];
            }
            
            return $this->validateFilterStructure($tableFilters);
        } catch (\Exception $e) {
            Log::warning('Failed to parse filters from URL', [
                'url' => $this->request->fullUrl(),
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Validate filter parameter structure.
     *
     * Ensures filter parameters follow the expected structure:
     * - Simple values: ['status' => 'active']
     * - Complex filters: ['status' => ['value' => 'active', 'operator' => '=']]
     * - Multi-value filters: ['tags' => ['values' => ['tag1', 'tag2']]]
     *
     * @param array $filters Raw filter parameters
     * @return array Validated filter parameters
     */
    private function validateFilterStructure(array $filters): array
    {
        $validated = [];
        
        foreach ($filters as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            
            // Handle simple string/numeric values
            if (is_string($value) || is_numeric($value)) {
                $validated[$key] = ['value' => $value];
                continue;
            }
            
            // Handle array values (complex filters)
            if (is_array($value)) {
                $validated[$key] = $this->validateComplexFilter($value);
                continue;
            }
        }
        
        return $validated;
    }

    /**
     * Validate complex filter structure.
     *
     * Handles complex filter objects with operators, multiple values,
     * date ranges, and other advanced filter types.
     *
     * @param array $filter Complex filter data
     * @return array Validated complex filter
     */
    private function validateComplexFilter(array $filter): array
    {
        $validated = [];
        
        // Handle single value with operator
        if (isset($filter['value'])) {
            $validated['value'] = $filter['value'];
            
            if (isset($filter['operator']) && is_string($filter['operator'])) {
                $validated['operator'] = $filter['operator'];
            }
        }
        
        // Handle multiple values
        if (isset($filter['values']) && is_array($filter['values'])) {
            $validated['values'] = array_filter($filter['values'], function ($value) {
                return is_string($value) || is_numeric($value);
            });
        }
        
        // Handle date ranges
        if (isset($filter['from']) && is_string($filter['from'])) {
            $validated['from'] = $filter['from'];
        }
        
        if (isset($filter['until']) && is_string($filter['until'])) {
            $validated['until'] = $filter['until'];
        }
        
        // Handle null checks
        if (isset($filter['operator'])) {
            if ($filter['operator'] === 'isNull' || $filter['operator'] === 'isNotNull') {
                $validated['operator'] = $filter['operator'];
            }
        }
        
        return $validated;
    }

    /**
     * Get filter value for a specific filter name.
     *
     * Retrieves the filter value from URL parameters with fallback handling.
     * Supports both simple values and complex filter structures.
     *
     * @param string $filterName The filter name to retrieve
     * @param mixed $default Default value if filter not found
     * @return mixed The filter value or default
     */
    public function getFilterValue(string $filterName, mixed $default = null): mixed
    {
        if (!isset($this->filters[$filterName])) {
            return $default;
        }
        
        $filter = $this->filters[$filterName];
        
        // Return simple value
        if (isset($filter['value'])) {
            return $filter['value'];
        }
        
        // Return multiple values
        if (isset($filter['values'])) {
            return $filter['values'];
        }
        
        // Return the entire filter structure for complex filters
        return $filter;
    }

    /**
     * Get all filters as an array.
     *
     * Returns all parsed filters in a format suitable for Filament
     * table filter default values.
     *
     * @return array All filter parameters
     */
    public function getAllFilters(): array
    {
        return $this->filters;
    }

    /**
     * Check if a specific filter is active.
     *
     * Determines if a filter has been applied based on URL parameters.
     *
     * @param string $filterName The filter name to check
     * @return bool True if filter is active, false otherwise
     */
    public function hasFilter(string $filterName): bool
    {
        return isset($this->filters[$filterName]);
    }

    /**
     * Get filter count.
     *
     * Returns the number of active filters for display purposes.
     *
     * @return int Number of active filters
     */
    public function getFilterCount(): int
    {
        return count($this->filters);
    }

    /**
     * Generate filter URL parameters.
     *
     * Creates URL parameters for navigation to filtered views.
     * Used by ActionableWidget to generate filtered navigation URLs.
     *
     * @param array $filters Filter parameters to encode
     * @return array URL parameters for tableFilters
     */
    public static function generateFilterParams(array $filters): array
    {
        if (empty($filters)) {
            return [];
        }
        
        return ['tableFilters' => $filters];
    }

    /**
     * Merge filters with existing URL parameters.
     *
     * Combines new filter parameters with existing ones, allowing
     * for additive filtering from multiple sources.
     *
     * @param array $newFilters New filters to merge
     * @return array Combined filter parameters
     */
    public function mergeFilters(array $newFilters): array
    {
        return array_merge($this->filters, $newFilters);
    }

    /**
     * Clear specific filters.
     *
     * Removes specified filters from the current filter set.
     *
     * @param array $filterNames Filter names to remove
     * @return array Remaining filters
     */
    public function clearFilters(array $filterNames): array
    {
        return Arr::except($this->filters, $filterNames);
    }

    /**
     * Validate date range filter.
     *
     * Ensures date range filters have valid date formats and logical ranges.
     *
     * @param array $dateFilter Date range filter data
     * @return array Validated date range filter
     */
    public function validateDateRange(array $dateFilter): array
    {
        $validated = [];
        
        if (isset($dateFilter['from'])) {
            try {
                $from = \Carbon\Carbon::parse($dateFilter['from']);
                $validated['from'] = $from->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning('Invalid date range from value', [
                    'value' => $dateFilter['from'],
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        if (isset($dateFilter['until'])) {
            try {
                $until = \Carbon\Carbon::parse($dateFilter['until']);
                $validated['until'] = $until->format('Y-m-d');
            } catch (\Exception $e) {
                Log::warning('Invalid date range until value', [
                    'value' => $dateFilter['until'],
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Ensure from is not after until
        if (isset($validated['from']) && isset($validated['until'])) {
            $from = \Carbon\Carbon::parse($validated['from']);
            $until = \Carbon\Carbon::parse($validated['until']);
            
            if ($from->isAfter($until)) {
                Log::warning('Date range from is after until', [
                    'from' => $validated['from'],
                    'until' => $validated['until']
                ]);
                
                // Swap the dates
                $validated['from'] = $until->format('Y-m-d');
                $validated['until'] = $from->format('Y-m-d');
            }
        }
        
        return $validated;
    }

    /**
     * Apply tenant scoping to filters.
     *
     * Ensures filters respect tenant isolation by adding tenant_id
     * filter when appropriate.
     *
     * @param array $filters Filters to scope
     * @return array Tenant-scoped filters
     */
    public function applyTenantScoping(array $filters): array
    {
        $user = auth()->user();
        
        if (!$user || !property_exists($user, 'tenant_id') || !$user->tenant_id) {
            return $filters;
        }
        
        // Don't add tenant filter for superadmin
        if (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) {
            return $filters;
        }
        
        // Add tenant filter if not already present
        if (!isset($filters['tenant_id'])) {
            $filters['tenant_id'] = ['value' => $user->tenant_id];
        }
        
        return $filters;
    }
}