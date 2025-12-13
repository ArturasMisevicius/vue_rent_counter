<?php

namespace App\Livewire;

use Filament\Facades\Filament;
use Livewire\Component;

/**
 * Global Search UI Component
 * 
 * Provides enhanced search interface with autocomplete, grouped results,
 * and navigation to detailed views for superadmin users.
 * 
 * Requirements: 14.2, 14.3, 14.5
 */
class GlobalSearchComponent extends Component
{
    /**
     * Current search query
     */
    public string $query = '';

    /**
     * Search results grouped by resource type
     */
    public array $results = [];

    /**
     * Search suggestions for autocomplete
     */
    public array $suggestions = [];

    /**
     * Whether search is currently active/focused
     */
    public bool $isActive = false;

    /**
     * Whether to show results dropdown
     */
    public bool $showResults = false;

    /**
     * Loading state for search
     */
    public bool $isLoading = false;

    /**
     * Component listeners
     */
    protected $listeners = [
        'focusSearch' => 'focusSearch',
        'clearSearch' => 'clearSearch',
    ];

    /**
     * Component rules for validation
     */
    protected $rules = [
        'query' => 'string|max:255',
    ];

    /**
     * Real-time search when query changes
     */
    public function updatedQuery(): void
    {
        $this->isLoading = true;
        
        if (strlen($this->query) < 2) {
            $this->results = [];
            $this->suggestions = [];
            $this->showResults = false;
            $this->isLoading = false;
            return;
        }

        $this->performSearch();
        $this->isLoading = false;
    }

    /**
     * Perform the actual search
     */
    protected function performSearch(): void
    {
        try {
            // Use Filament's built-in global search
            $panel = Filament::getCurrentPanel();
            $globalSearch = $panel->getGlobalSearch();
            
            if (!$globalSearch) {
                $this->results = [];
                $this->suggestions = [];
                $this->showResults = false;
                return;
            }
            
            // Get search results from Filament
            $searchResults = $globalSearch->getResults($this->query);
            
            // Group results by resource type
            $this->results = $this->groupResultsByType($searchResults);
            
            // Generate simple suggestions
            $this->suggestions = $this->generateSuggestions($this->query);
            
            $this->showResults = true;
            
        } catch (\Exception $e) {
            $this->results = [];
            $this->suggestions = [];
            $this->showResults = false;
            
            // Log error for debugging
            logger()->error('Global search error', [
                'query' => $this->query,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * Group search results by resource type
     * 
     * @param \Illuminate\Support\Collection $results Raw search results from Filament
     * @return array Grouped results with counts
     */
    protected function groupResultsByType($results): array
    {
        $grouped = [];
        $typeMapping = [
            'organization-resource' => 'Organizations',
            'platform-user-resource' => 'Users', 
            'property-resource' => 'Properties',
            'building-resource' => 'Buildings',
            'meter-resource' => 'Meters',
            'invoice-resource' => 'Invoices',
        ];

        foreach ($results as $result) {
            // Determine resource type from URL or resource class
            $resourceType = $this->extractResourceTypeFromUrl($result->url);
            $displayType = $typeMapping[$resourceType] ?? ucfirst(str_replace('-resource', '', $resourceType));
            
            if (!isset($grouped[$displayType])) {
                $grouped[$displayType] = [
                    'type' => $resourceType,
                    'display_name' => $displayType,
                    'results' => [],
                    'count' => 0,
                ];
            }
            
            $grouped[$displayType]['results'][] = [
                'title' => $result->title,
                'url' => $result->url,
                'details' => $result->details ?? [],
                'relevance_score' => 1, // Filament doesn't provide relevance scores
            ];
            
            $grouped[$displayType]['count']++;
        }

        return $grouped;
    }

    /**
     * Extract resource type from Filament resource URL
     * 
     * @param string $url Resource URL
     * @return string Resource type
     */
    protected function extractResourceTypeFromUrl(string $url): string
    {
        $urlMapping = [
            'organization-resource' => 'organizations',
            'platform-user-resource' => 'users',
            'property-resource' => 'properties',
            'building-resource' => 'buildings',
            'meter-resource' => 'meters',
            'invoice-resource' => 'invoices',
        ];

        foreach ($urlMapping as $urlPattern => $resourceType) {
            if (str_contains($url, $urlPattern)) {
                return $resourceType;
            }
        }

        return 'unknown';
    }

    /**
     * Handle search focus
     */
    public function focusSearch(): void
    {
        $this->isActive = true;
        
        if (!empty($this->query) && strlen($this->query) >= 2) {
            $this->showResults = true;
        }
    }

    /**
     * Handle search blur
     */
    public function blurSearch(): void
    {
        // Delay hiding results to allow for clicks
        $this->dispatch('hideResultsDelayed');
    }

    /**
     * Clear search and results
     */
    public function clearSearch(): void
    {
        $this->query = '';
        $this->results = [];
        $this->suggestions = [];
        $this->showResults = false;
        $this->isActive = false;
    }

    /**
     * Navigate to a search result
     * 
     * @param string $url Result URL
     */
    public function navigateToResult(string $url): void
    {
        $this->clearSearch();
        $this->redirect($url);
    }

    /**
     * Use a search suggestion
     * 
     * @param string $suggestion Suggestion text
     */
    public function useSuggestion(string $suggestion): void
    {
        // Extract the actual query from suggestion (remove prefix)
        $parts = explode(': ', $suggestion, 2);
        $this->query = $parts[1] ?? $suggestion;
        
        $this->performSearch();
    }

    /**
     * Generate search suggestions
     * 
     * @param string $query Search query
     * @return array Array of suggestions
     */
    protected function generateSuggestions(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $suggestions = [];

        // Add common search patterns
        $suggestions[] = "Organization: {$query}";
        $suggestions[] = "User: {$query}";
        $suggestions[] = "Property: {$query}";
        
        // Add ID-based suggestions if query is numeric
        if (is_numeric($query)) {
            $suggestions[] = "ID: {$query}";
            $suggestions[] = "Invoice: {$query}";
            $suggestions[] = "Meter: {$query}";
        }

        // Add email suggestions if query contains @
        if (str_contains($query, '@')) {
            $suggestions[] = "Email: {$query}";
        }

        return array_slice($suggestions, 0, 5);
    }

    /**
     * Get total results count
     * 
     * @return int Total number of results
     */
    public function getTotalResultsCount(): int
    {
        return array_sum(array_column($this->results, 'count'));
    }

    /**
     * Check if user can access global search
     * 
     * @return bool True if user can search
     */
    public function canSearch(): bool
    {
        $user = auth()->user();
        return $user && $user->role === \App\Enums\UserRole::SUPERADMIN;
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.global-search-component');
    }
}