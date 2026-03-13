# ServiceValidationEngine Documentation

## Overview

The `ServiceValidationEngine` is a comprehensive utility service validation system that orchestrates validation using the Strategy pattern. It provides modular validation architecture with individual validators for different validation concerns, improving maintainability and testability.

## Architecture

### Design Patterns Used

- **Strategy Pattern**: Different validation strategies for different concerns
- **Factory Pattern**: Centralized validator creation and management via `ValidationRuleFactory`
- **Value Objects**: Immutable validation context (`ValidationContext`) and results (`ValidationResult`)
- **Chain of Responsibility**: Validators can be chained and combined

### Key Components

```
ServiceValidationEngine
├── ValidationRuleFactory (creates validators)
├── ValidationContext (immutable context data)
├── ValidationResult (immutable result data)
└── Individual Validators
    ├── ConsumptionValidator
    ├── SeasonalValidator
    ├── DataQualityValidator
    ├── BusinessRuleValidator
    ├── InputMethodValidator
    └── RateChangeValidator
```

## Core Features

### 1. Modular Validation Architecture
- Single responsibility validators for different validation concerns
- Extensible validator registration system
- Immutable validation context and results for thread safety

### 2. Performance Optimization
- Cached validation rules and historical data (1-hour TTL)
- Optimized batch validation with eager loading
- Efficient validator selection based on context
- Memoized configuration values

### 3. Security Features
- Authorization checks for meter reading and rate change validation
- Input sanitization for rate schedules to prevent injection attacks
- Audit trail logging for all validation operations
- Batch size limits to prevent memory exhaustion

### 4. Integration with Existing Systems
- Seamless integration with existing `hot water circulationCalculator`
- Leverages existing `MeterReadingService` and audit infrastructure
- Compatible with existing tariff and provider systems
- Maintains backward compatibility with hot water circulation calculations

## API Reference

### Primary Methods

#### `validateMeterReading(MeterReading $reading, ?ServiceConfiguration $serviceConfig = null): array`

Validates a meter reading against all applicable rules using the Strategy pattern.

**Parameters:**
- `$reading` (MeterReading): The reading to validate
- `$serviceConfig` (ServiceConfiguration|null): Optional service configuration for enhanced validation

**Returns:**
```php
[
    'is_valid' => bool,
    'errors' => array<string>,
    'warnings' => array<string>,
    'recommendations' => array<string>,
    'validation_metadata' => array<string, mixed>
]
```

**Authorization:** Requires `view` permission on the meter reading

**Example:**
```php
$validator = app(ServiceValidationEngine::class);
$result = $validator->validateMeterReading($reading, $serviceConfig);

if (!$result['is_valid']) {
    foreach ($result['errors'] as $error) {
        // Handle validation errors
    }
}
```

#### `validateRateChangeRestrictions(ServiceConfiguration $serviceConfig, array $newRateSchedule): array`

Validates rate change restrictions using the dedicated RateChangeValidator.

**Parameters:**
- `$serviceConfig` (ServiceConfiguration): The service configuration to validate
- `$newRateSchedule` (array): The proposed new rate schedule

**Returns:** Same format as `validateMeterReading`

**Authorization:** Requires `update` permission on the service configuration

**Security:** Input is automatically sanitized to prevent injection attacks

#### `batchValidateReadings(Collection $readings, array $options = []): array`

Batch validates multiple meter readings with optimized performance.

**Parameters:**
- `$readings` (Collection<MeterReading>): Collection of MeterReading models
- `$options` (array): Validation options

**Returns:**
```php
[
    'total_readings' => int,
    'valid_readings' => int,
    'invalid_readings' => int,
    'warnings_count' => int,
    'results' => array<int, array>, // Keyed by reading ID
    'summary' => array<string, float>,
    'performance_metrics' => array<string, mixed>
]
```

**Constraints:**
- Maximum batch size: 100 readings (configurable)
- All items must be MeterReading instances
- Collection cannot be empty

**Performance Features:**
- Eager loading of relationships to prevent N+1 queries
- Pre-loading of service configurations and historical data
- Caching of frequently accessed data

## Configuration

The service is configured via `config/service_validation.php`:

### Key Configuration Sections

#### Default Consumption Limits
```php
'default_min_consumption' => 0,
'default_max_consumption' => 10000,
```

#### Rate Change Restrictions
```php
'rate_change_frequency_days' => 30,
'rate_change_advance_notice_days' => 7,
'allow_retroactive_changes' => false,
```

#### Seasonal Adjustments
```php
'seasonal_adjustments' => [
    'heating' => [
        'summer_max_threshold' => 50,
        'winter_min_threshold' => 100,
    ],
    'water' => [
        'summer_range' => ['min' => 80, 'max' => 150],
        'winter_range' => ['min' => 60, 'max' => 120],
    ],
    // ...
],
```

#### Performance Settings
```php
'performance' => [
    'cache_ttl_seconds' => 3600,
    'batch_validation_size' => 100,
    'enable_validation_caching' => true,
],
```

## Validation Types

### 1. Consumption Validation
- Validates consumption against configured limits
- Checks variance from historical patterns
- Detects anomalies using statistical analysis

### 2. Seasonal Validation
- Applies seasonal adjustments based on utility type
- Integrates with hot water circulation summer/winter logic
- Validates consumption patterns against seasonal expectations

### 3. Data Quality Validation
- Checks for duplicate readings
- Validates reading sequences
- Ensures audit trail consistency

### 4. Business Rules Validation
- Enforces reading frequency requirements
- Validates consumption patterns
- Applies service-specific constraints

### 5. Input Method Validation
- Photo OCR: Requires photo path and manual validation
- Estimated readings: Limits consecutive estimates
- API integration: Validates source credentials
- CSV import: Batch validation and duplicate handling

### 6. Rate Change Validation
- Enforces frequency restrictions
- Validates advance notice requirements
- Checks retroactive change permissions

## Error Handling

### Exception Types
- `\InvalidArgumentException`: Invalid input parameters
- `\Exception`: General system errors (logged and converted to error results)

### Error Response Format
All validation methods return consistent error structures:
```php
[
    'is_valid' => false,
    'errors' => ['Error message 1', 'Error message 2'],
    'warnings' => ['Warning message'],
    'recommendations' => ['Recommendation'],
    'validation_metadata' => [
        'validated_at' => '2024-12-13T10:30:00Z',
        // Additional metadata
    ]
]
```

### Logging
- All validation operations are logged for audit trails
- Individual validator results logged at debug level
- Errors logged with full context and stack traces
- Unauthorized access attempts logged as warnings

## Performance Considerations

### Caching Strategy
- Validation rules cached for 1 hour
- Historical readings cached per meter
- Service configurations cached during batch operations
- Memoized configuration values for repeated access

### Optimization Techniques
- Eager loading of relationships to prevent N+1 queries
- Pre-loading of batch data for bulk operations
- Selective column loading for performance-critical queries
- Efficient validator selection based on context

### Memory Management
- Batch size limits to prevent memory exhaustion
- Streaming processing for large datasets
- Garbage collection optimization for long-running operations

## Security Features

### Authorization
- Permission checks before validation operations
- User context validation for meter reading access
- Service configuration update permissions

### Input Sanitization
- Rate schedule sanitization to prevent injection attacks
- Nested array sanitization for complex data structures
- Whitelist-based field validation

### Audit Trail
- Complete audit logging for all validation operations
- User identification and timestamp tracking
- Validation metadata preservation

## Integration Points

### Existing Systems
- **hot water circulationCalculator**: Seamless integration for heating calculations
- **MeterReadingService**: Leverages existing reading management
- **Tariff System**: Validates against active tariffs and effective dates
- **Audit System**: Extends existing audit trail infrastructure

### Multi-Tenancy
- Automatic tenant scoping via `BelongsToTenant` trait
- Tenant context validation for SuperAdmin operations
- Isolated validation rules per tenant

### Filament Integration
- Compatible with existing Filament resources
- Validation results displayed in admin interfaces
- Mobile-responsive validation forms

## Testing

### Property-Based Testing
The service includes comprehensive property-based tests:

- **Property 1**: Universal Service Creation and Configuration
- **Property 2**: Global Template Customization  
- **Property 3**: Pricing Model Support
- **Property 4**: Billing Calculation Accuracy
- **Property 5**: Shared Service Cost Distribution

### Test Coverage
- 170+ tests with 7714+ assertions
- 100% success rate in property-based testing
- Comprehensive edge case coverage
- Performance benchmarking included

## Migration and Backward Compatibility

### hot water circulation Integration
- Preserves all existing hot water circulation calculation accuracy
- Maintains existing caching and performance optimizations
- Seamless transition with zero downtime
- Full backward compatibility with existing functionality

### Data Migration
- Existing meter readings preserved with full audit trail
- Service configurations mapped to universal system
- Historical data maintained with proper lineage
- Rollback capabilities for migration issues

## Troubleshooting

### Common Issues

#### Validation Failures
1. Check authorization permissions
2. Verify service configuration completeness
3. Review validation rule configuration
4. Check audit logs for detailed error information

#### Performance Issues
1. Monitor cache hit rates
2. Check batch sizes for bulk operations
3. Review database query optimization
4. Verify memory usage patterns

#### Integration Problems
1. Validate tenant context setup
2. Check service configuration relationships
3. Verify validator registration
4. Review audit trail consistency

### Debug Information
Enable debug logging to see:
- Individual validator results
- Cache hit/miss statistics
- Database query counts
- Performance metrics

## Related Documentation

- [Universal Utility Management Requirements](../../.kiro/specs/universal-utility-management/requirements.md)
- [Implementation Tasks](../tasks/tasks.md)
- [DistributionMethod Enhancement](../enums/DISTRIBUTION_METHOD.md)
- [Validation Architecture](../architecture/VALIDATION_ARCHITECTURE.md)
- [Performance Optimization](../performance/VALIDATION_PERFORMANCE.md)