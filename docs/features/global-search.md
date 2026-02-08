# Global Search Feature

## Overview

The Global Search feature provides superadmin users with the ability to search across all resources in the platform from a single interface. This feature implements Requirements 14.1, 14.2, 14.4, and 14.5 from the superadmin dashboard enhancement specification.

## Features

### 1. Platform-Wide Search (Requirement 14.1)

The global search searches across the following resource types:
- **Organizations**: Search by name, email, slug, domain, phone
- **Users**: Search by name, email, organization name
- **Properties**: Search by address, unit number
- **Buildings**: Search by name, address
- **Meters**: Search by serial number
- **Invoices**: Search by invoice number, payment reference

### 2. Enhanced Search UI (Requirement 14.2)

The search interface includes:
- **Real-time search**: Results update as you type (300ms debounce)
- **Autocomplete suggestions**: Contextual suggestions based on query
- **Grouped results**: Results organized by resource type with counts
- **Quick navigation**: Click any result to navigate to detailed view
- **Keyboard shortcuts**: Accessible via keyboard navigation

### 3. Search Suggestions (Requirement 14.5)

The system provides intelligent search suggestions:
- Resource type hints (e.g., "Organization: query")
- ID-based suggestions for numeric queries
- Email suggestions for queries containing "@"
- Context-aware recommendations

### 4. Result Grouping (Requirement 14.4)

Search results are grouped by resource type:
- Each group shows the resource type name and count
- Results within groups are sorted by relevance
- Groups are ordered by total relevance score
- Maximum 5 results per resource type (configurable)

## Access Control

### Superadmin Only
- Only users with `UserRole::SUPERADMIN` can access global search
- The search component is hidden from non-superadmin users
- Search requests from non-superadmin users return empty results

### Authorization Check
```php
public function canSearch(): bool
{
    $user = auth()->user();
    return $user && $user->role === \App\Enums\UserRole::SUPERADMIN;
}
```

## Technical Implementation

### Components

1. **GlobalSearchComponent** (`app/Livewire/GlobalSearchComponent.php`)
   - Livewire component handling search logic
   - Real-time search with debouncing
   - Result grouping and formatting

2. **Search UI** (`resources/views/livewire/global-search-component.blade.php`)
   - Alpine.js powered interface
   - Responsive dropdown with results
   - Loading states and error handling

3. **Header Integration** (`resources/views/filament/components/global-search-header.blade.php`)
   - Integrated into Filament admin panel header
   - Positioned prominently for easy access

### Filament Resource Configuration

Each searchable resource includes:

```php
protected static ?string $recordTitleAttribute = 'name';
protected static int $globalSearchResultsLimit = 5;

public static function getGloballySearchableAttributes(): array
{
    return ['name', 'email', 'address']; // Searchable fields
}

public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
{
    return [
        'Email' => $record->email,
        'Status' => $record->status,
    ];
}
```

## Usage

### Basic Search
1. Navigate to any page in the admin panel as a superadmin
2. Use the search box in the header
3. Type at least 2 characters to trigger search
4. View grouped results in the dropdown
5. Click any result to navigate to its detail page

### Search Tips
- Use specific terms for better results
- Try searching by ID numbers for exact matches
- Use email addresses to find users or organizations
- Search partial addresses to find properties

### Keyboard Navigation
- **Tab**: Navigate between search results
- **Enter**: Select highlighted result
- **Escape**: Close search dropdown
- **Arrow keys**: Navigate within results

## Performance Considerations

### Optimization Features
- **Debounced search**: 300ms delay prevents excessive queries
- **Minimum query length**: 2 characters required
- **Result limits**: Maximum 5 results per resource type
- **Caching**: Leverages Filament's built-in caching mechanisms

### Database Indexes
Ensure proper indexes exist on searchable fields:
```sql
-- Organizations
CREATE INDEX idx_organizations_search ON organizations(name, email, slug);

-- Users  
CREATE INDEX idx_users_search ON users(name, email, organization_name);

-- Properties
CREATE INDEX idx_properties_search ON properties(address, unit_number);
```

## Testing

The global search feature includes comprehensive tests:

```bash
# Run global search tests
php artisan test tests/Feature/GlobalSearchTest.php
```

Test coverage includes:
- Superadmin access control
- Non-superadmin restrictions
- Search functionality across resource types
- Result grouping and formatting
- Minimum query length validation

## Configuration

### Customizing Search Limits
```php
// In resource files
protected static int $globalSearchResultsLimit = 10; // Default: 5
```

### Adding New Searchable Resources
1. Add `recordTitleAttribute` to the resource
2. Implement `getGloballySearchableAttributes()`
3. Implement `getGlobalSearchResultDetails()`
4. Update the search component's type mapping

### Customizing Search Fields
```php
public static function getGloballySearchableAttributes(): array
{
    return [
        'primary_field',    // High priority
        'secondary_field',  // Medium priority
        'tertiary_field',   // Low priority
    ];
}
```

## Security Considerations

### Data Access
- Search respects existing authorization policies
- Results filtered by user permissions
- No cross-tenant data leakage
- Audit logging for search activities

### Input Validation
- Query length limits (2-255 characters)
- SQL injection prevention via Eloquent ORM
- XSS protection via Blade escaping
- Rate limiting on search requests

## Troubleshooting

### Common Issues

**Search not working**
- Verify user has superadmin role
- Check if global search is enabled in panel configuration
- Ensure resources have proper search configuration

**No results found**
- Verify searchable attributes are configured
- Check database indexes on search fields
- Confirm data exists in searchable fields

**Performance issues**
- Add database indexes on searchable columns
- Reduce `globalSearchResultsLimit` values
- Increase debounce delay if needed

### Debug Mode
Enable debug logging for search operations:
```php
// In GlobalSearchComponent
logger()->debug('Global search executed', [
    'query' => $this->query,
    'results_count' => count($this->results),
    'user_id' => auth()->id(),
]);
```

## Future Enhancements

### Planned Features
- Advanced search filters
- Search history and saved searches
- Export search results
- Search analytics and reporting
- Full-text search integration

### API Integration
- REST API endpoints for search
- GraphQL search interface
- Webhook notifications for search events
- Third-party search service integration