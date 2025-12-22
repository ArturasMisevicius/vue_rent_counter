# Universal Utility Management System - Build-Ready Specification

## Executive Summary

### Project Overview
Transform the existing Lithuanian-specific heating system into a comprehensive Universal Utility Management System supporting multiple utility types (water, electricity, heating, gas, internet) with flexible pricing models, multi-tenant architecture, and role-based access control.

### Success Metrics
- **Performance**: <200ms response time for validation operations, <500ms for billing calculations
- **Scalability**: Support 10,000+ properties per tenant, 100,000+ meter readings per month
- **Accuracy**: 99.9% billing calculation accuracy with full audit trails
- **Availability**: 99.9% uptime with graceful degradation
- **User Experience**: WCAG 2.1 AA compliance, mobile-responsive interfaces
- **Migration**: Zero-downtime migration from existing heating system

### Constraints
- **Backward Compatibility**: Maintain 100% compatibility with existing heating calculations
- **Multi-Tenancy**: Strict data isolation with tenant-scoped queries
- **Security**: Role-based authorization, input sanitization, audit trails
- **Localization**: Support EN, LT, RU, ES, MG, PI languages
- **Performance**: Maintain existing system performance benchmarks

## User Stories with Acceptance Criteria

### Epic 1: Universal Service Framework

#### US-1.1: SuperAdmin Global Service Templates
**As a SuperAdmin, I want to create global utility service templates, so that all tenants can use standardized configurations while allowing customization.**

**Functional Acceptance Criteria:**
- GIVEN I am a SuperAdmin
- WHEN I create a new UtilityService with `is_global_template = true`
- THEN the system SHALL store the service with configurable attributes (name, unit_of_measurement, default_pricing_model, calculation_formula)
- AND the service SHALL be available to all tenant organizations for customization
- AND the system SHALL support JSON schema validation for configuration_schema and validation_rules

**A11y Acceptance Criteria:**
- Form fields MUST have proper labels and ARIA attributes
- Error messages MUST be announced to screen readers
- Keyboard navigation MUST work for all form controls
- Color contrast MUST meet WCAG 2.1 AA standards (4.5:1 minimum)

**Localization Acceptance Criteria:**
- All form labels and messages MUST be translatable
- Validation error messages MUST support all configured languages
- Service names MUST support Unicode characters for international utility names

**Performance Acceptance Criteria:**
- Service creation MUST complete within 200ms
- Global template listing MUST load within 100ms
- Cache invalidation MUST occur within 5 seconds across all nodes

#### US-1.2: Admin Service Configuration
**As an Admin, I want to configure utility services for my organization with flexible pricing models, so that I can handle different property types and billing scenarios.**

**Functional Acceptance Criteria:**
- GIVEN I am an Admin with appropriate permissions
- WHEN I configure a utility service
- THEN the system SHALL allow selection from FIXED_MONTHLY, CONSUMPTION_BASED, TIERED_RATES, TIME_OF_USE, HYBRID, or CUSTOM_FORMULA pricing models
- AND I SHALL be able to define rate schedules with peak/off-peak hours, seasonal rates, weekend rates
- AND the system SHALL validate configuration against the utility service schema

**A11y Acceptance Criteria:**
- Complex forms MUST use fieldsets and legends for grouping
- Dynamic form sections MUST announce changes to screen readers
- Form validation MUST not rely solely on color indicators

**Localization Acceptance Criteria:**
- Pricing model names and descriptions MUST be localized
- Currency formatting MUST respect tenant locale settings
- Date/time formats MUST follow locale conventions

**Performance Acceptance Criteria:**
- Configuration save MUST complete within 300ms
- Real-time validation MUST respond within 100ms
- Form auto-save MUST occur every 30 seconds without blocking UI

### Epic 2: Enhanced Reading Collection

#### US-2.1: Multi-Input Reading Collection
**As a Manager, I want to record meter readings with flexible input methods, so that I can efficiently collect consumption data for various utility types.**

**Functional Acceptance Criteria:**
- GIVEN I am a Manager with meter reading permissions
- WHEN I record a meter reading
- THEN the system SHALL support MANUAL, PHOTO_OCR, CSV_IMPORT, API_INTEGRATION, and ESTIMATED input methods
- AND the system SHALL validate readings against previous readings, reasonable consumption ranges, and meter specifications
- AND the system SHALL support multi-value readings using the reading_values JSON field

**A11y Acceptance Criteria:**
- File upload controls MUST have proper labels and instructions
- Camera interface MUST be keyboard accessible
- Progress indicators MUST be announced to screen readers
- Error states MUST be clearly communicated

**Localization Acceptance Criteria:**
- Input method labels MUST be localized
- File format instructions MUST be translated
- OCR confidence messages MUST support all languages

**Performance Acceptance Criteria:**
- Manual entry MUST save within 100ms
- Photo OCR processing MUST complete within 5 seconds
- CSV import MUST process 1000 readings within 30 seconds
- Batch validation MUST handle 100 readings within 10 seconds

### Epic 3: Universal Billing Engine

#### US-3.1: Automated Billing Calculations
**As a Manager, I want the system to automatically calculate utility bills using configured pricing models, so that invoices are accurate and consistent.**

**Functional Acceptance Criteria:**
- GIVEN I have configured service pricing models
- WHEN generating an invoice
- THEN the system SHALL apply the correct pricing model based on property type, service configuration, and billing period
- AND the system SHALL handle tiered rates, time-of-use pricing, and seasonal adjustments automatically
- AND the system SHALL create immutable snapshots of all pricing data for invoice integrity

**A11y Acceptance Criteria:**
- Invoice generation progress MUST be accessible to screen readers
- Calculation results MUST be presented in accessible tables
- Error notifications MUST be properly announced

**Localization Acceptance Criteria:**
- Currency amounts MUST be formatted according to tenant locale
- Invoice line items MUST be translated
- Tax calculations MUST support locale-specific rules

**Performance Acceptance Criteria:**
- Single invoice calculation MUST complete within 500ms
- Batch invoice generation MUST process 100 invoices within 60 seconds
- Pricing model application MUST execute within 50ms per service

## Data Models and Migrations

### Enhanced Models

#### UtilityService Model Extensions
```php
// Additional fields for universal capabilities
$table->json('configuration_schema')->nullable();
$table->json('validation_rules')->nullable();
$table->json('business_logic_config')->nullable();
$table->string('service_type_bridge')->nullable(); // Bridge to existing ServiceType enum
$table->boolean('is_global_template')->default(false);
$table->unsignedBigInteger('created_by_tenant_id')->nullable();

// Indexes for performance
$table->index(['tenant_id', 'is_active']);
$table->index(['is_global_template', 'is_active']);
$table->index('service_type_bridge');
```

#### ServiceConfiguration Model Extensions
```php
// Enhanced pricing and distribution
$table->string('area_type')->nullable(); // total_area, heated_area, commercial_area
$table->text('custom_formula')->nullable();
$table->json('configuration_overrides')->nullable();

// Indexes for performance
$table->index(['tenant_id', 'is_active']);
$table->index(['effective_from', 'effective_until']);
$table->index(['property_id', 'utility_service_id']);
```

#### MeterReading Model Extensions
```php
// Universal reading capabilities
$table->json('reading_values')->nullable(); // Multi-value readings
$table->string('input_method')->default('manual');
$table->string('validation_status')->default('pending');
$table->string('photo_path')->nullable();
$table->unsignedBigInteger('validated_by')->nullable();

// Indexes for performance
$table->index(['tenant_id', 'validation_status']);
$table->index(['meter_id', 'reading_date']);
$table->index(['input_method', 'validation_status']);
```

### Migration Strategy
1. **Phase 1**: Add new fields to existing models with nullable constraints
2. **Phase 2**: Migrate existing heating data to universal format
3. **Phase 3**: Add non-nullable constraints and optimize indexes
4. **Phase 4**: Remove deprecated fields after validation

### Rollback Plan
- Maintain shadow tables with original data during migration
- Implement feature flags for gradual rollout
- Provide rollback scripts for each migration phase

## APIs and Controllers

### ServiceValidationController
```php
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateMeterReadingRequest;
use App\Http\Requests\ValidateRateChangeRequest;
use App\Services\ServiceValidationEngine;
use Illuminate\Http\JsonResponse;

class ServiceValidationController extends Controller
{
    public function __construct(
        private readonly ServiceValidationEngine $validationEngine
    ) {}

    /**
     * Validate a single meter reading.
     */
    public function validateMeterReading(
        ValidateMeterReadingRequest $request,
        MeterReading $reading
    ): JsonResponse {
        $this->authorize('view', $reading);
        
        $result = $this->validationEngine->validateMeterReading(
            $reading,
            $request->validated()['service_configuration_id'] ?? null
        );
        
        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Batch validate multiple readings.
     */
    public function batchValidateReadings(
        BatchValidateReadingsRequest $request
    ): JsonResponse {
        $readings = MeterReading::whereIn('id', $request->validated()['reading_ids'])
            ->get();
            
        // Authorization check for each reading
        $readings->each(fn($reading) => $this->authorize('view', $reading));
        
        $result = $this->validationEngine->batchValidateReadings(
            $readings,
            $request->validated()['validation_options'] ?? []
        );
        
        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
```

### Validation Rules

#### ValidateMeterReadingRequest
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateMeterReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('reading'));
    }

    public function rules(): array
    {
        return [
            'service_configuration_id' => 'sometimes|exists:service_configurations,id',
            'validation_options' => 'sometimes|array',
            'validation_options.skip_seasonal_validation' => 'boolean',
            'validation_options.strict_mode' => 'boolean',
            'validation_options.include_recommendations' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'service_configuration_id.exists' => __('validation.service_configuration_not_found'),
        ];
    }
}
```

### Authorization Matrix

| Role | Create Services | Configure Services | Validate Readings | View Reports |
|------|----------------|-------------------|------------------|--------------|
| SuperAdmin | Global Templates | All Tenants | All Tenants | All Tenants |
| Admin | Tenant Services | Own Tenant | Own Tenant | Own Tenant |
| Manager | No | Assigned Properties | Assigned Properties | Assigned Properties |
| Tenant | No | No | Own Properties | Own Properties |

## UX Requirements

### State Management

#### Loading States
```php
// Filament component loading states
public function getLoadingIndicatorTarget(): string
{
    return 'validate-readings';
}

public function getLoadingMessage(): string
{
    return __('validation.processing_readings');
}
```

#### Empty States
- **No Readings**: Display helpful message with action to add first reading
- **No Services**: Guide user through service configuration setup
- **No Validation Rules**: Provide default rule templates

#### Error States
- **Validation Failures**: Clear error messages with suggested actions
- **Network Errors**: Retry mechanisms with exponential backoff
- **Permission Errors**: Helpful messages directing to appropriate contact

#### Success States
- **Validation Complete**: Summary of results with next steps
- **Batch Processing**: Progress indicators with estimated completion time
- **Configuration Saved**: Confirmation with option to test configuration

### Keyboard Navigation
- Tab order follows logical flow
- All interactive elements accessible via keyboard
- Escape key closes modals and cancels operations
- Enter key submits forms and confirms actions

### Focus Management
- Focus returns to trigger element after modal close
- Focus moves to first error field on validation failure
- Focus indicators clearly visible (2px outline minimum)

### Optimistic UI
- Immediate feedback for user actions
- Rollback on server errors
- Loading states for operations >100ms

### URL State Persistence
- Filter states preserved in URL parameters
- Deep linking to specific validation results
- Browser back/forward navigation support

## Non-Functional Requirements

### Performance Budgets
- **Page Load**: <2 seconds for initial load
- **API Response**: <200ms for validation operations
- **Batch Operations**: <10 seconds for 100 readings
- **Memory Usage**: <100MB per validation batch
- **Database Queries**: <10 queries per validation operation

### Accessibility Standards
- **WCAG 2.1 AA Compliance**: All interfaces must meet AA standards
- **Screen Reader Support**: Full compatibility with NVDA, JAWS, VoiceOver
- **Keyboard Navigation**: All functionality accessible without mouse
- **Color Contrast**: Minimum 4.5:1 for normal text, 3:1 for large text
- **Focus Indicators**: Clearly visible focus states for all interactive elements

### Security Headers and CSP
```php
// config/security.php additions
'headers' => [
    'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'",
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'Referrer-Policy' => 'strict-origin-when-cross-origin',
    'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
],
```

### Privacy Compliance
- **Data Minimization**: Collect only necessary validation data
- **Retention Policies**: Automatic cleanup of old validation logs
- **Anonymization**: Remove PII from performance metrics
- **Consent Management**: Clear opt-in for optional data collection

### Observability
```php
// Monitoring configuration
'monitoring' => [
    'validation_metrics' => [
        'success_rate' => 'counter',
        'response_time' => 'histogram',
        'error_rate' => 'counter',
        'batch_size' => 'histogram',
    ],
    'alerts' => [
        'high_error_rate' => ['threshold' => 0.05, 'window' => '5m'],
        'slow_response' => ['threshold' => 500, 'window' => '1m'],
        'memory_usage' => ['threshold' => 100, 'window' => '1m'],
    ],
],
```

## Testing Plan

### Pest Unit Tests
```php
<?php

use App\Services\ServiceValidationEngine;
use App\Models\MeterReading;
use App\Models\ServiceConfiguration;

describe('ServiceValidationEngine', function () {
    it('validates meter reading with consumption limits', function () {
        $reading = MeterReading::factory()->create(['value' => 150]);
        $config = ServiceConfiguration::factory()->create();
        
        $result = app(ServiceValidationEngine::class)
            ->validateMeterReading($reading, $config);
            
        expect($result['is_valid'])->toBeTrue();
        expect($result['errors'])->toBeEmpty();
    });
    
    it('rejects readings exceeding consumption limits', function () {
        $reading = MeterReading::factory()->create(['value' => 10000]);
        $config = ServiceConfiguration::factory()->create();
        
        $result = app(ServiceValidationEngine::class)
            ->validateMeterReading($reading, $config);
            
        expect($result['is_valid'])->toBeFalse();
        expect($result['errors'])->toContain('Consumption exceeds maximum allowed limit');
    });
});
```

### Playwright E2E Tests
```javascript
// tests/e2e/validation.spec.js
import { test, expect } from '@playwright/test';

test.describe('Meter Reading Validation', () => {
  test('validates reading through UI', async ({ page }) => {
    await page.goto('/admin/meter-readings');
    
    // Create new reading
    await page.click('[data-testid="create-reading"]');
    await page.fill('[data-testid="reading-value"]', '150');
    await page.click('[data-testid="save-reading"]');
    
    // Verify validation success
    await expect(page.locator('[data-testid="validation-status"]'))
      .toContainText('Validated');
  });
  
  test('handles validation errors gracefully', async ({ page }) => {
    await page.goto('/admin/meter-readings');
    
    // Create invalid reading
    await page.click('[data-testid="create-reading"]');
    await page.fill('[data-testid="reading-value"]', '99999');
    await page.click('[data-testid="save-reading"]');
    
    // Verify error display
    await expect(page.locator('[data-testid="validation-errors"]'))
      .toBeVisible();
    await expect(page.locator('[data-testid="error-message"]'))
      .toContainText('exceeds maximum allowed limit');
  });
});
```

### Property-Based Tests
```php
<?php

use App\Services\ServiceValidationEngine;
use App\Models\MeterReading;

it('validates any reasonable consumption value', function () {
    $engine = app(ServiceValidationEngine::class);
    
    // Property: Any consumption between 0 and 1000 should be valid
    forAll(
        Generator\choose(0, 1000)
    )->then(function (int $consumption) use ($engine) {
        $reading = MeterReading::factory()->make(['value' => $consumption]);
        $result = $engine->validateMeterReading($reading);
        
        expect($result['is_valid'])->toBeTrue();
    });
});
```

### Performance Tests
```php
<?php

it('validates 100 readings within performance budget', function () {
    $readings = MeterReading::factory()->count(100)->create();
    $engine = app(ServiceValidationEngine::class);
    
    $startTime = microtime(true);
    $result = $engine->batchValidateReadings($readings);
    $duration = microtime(true) - $startTime;
    
    expect($duration)->toBeLessThan(10.0); // 10 second budget
    expect($result['total_readings'])->toBe(100);
});
```

## Migration and Deployment

### Migration Strategy
1. **Pre-Migration**: Create backups and validate data integrity
2. **Schema Migration**: Add new fields with nullable constraints
3. **Data Migration**: Transform existing heating data to universal format
4. **Validation**: Verify migrated data accuracy
5. **Cutover**: Switch to universal system with feature flags
6. **Cleanup**: Remove deprecated fields and optimize indexes

### Deployment Considerations
- **Zero Downtime**: Use blue-green deployment strategy
- **Feature Flags**: Gradual rollout of universal features
- **Monitoring**: Enhanced monitoring during migration period
- **Rollback Plan**: Immediate rollback capability for 48 hours

### Database Seeding
```php
<?php

use App\Models\UtilityService;
use App\Enums\PricingModel;
use App\Enums\ServiceType;

class UniversalUtilitySeeder extends Seeder
{
    public function run(): void
    {
        // Create global utility service templates
        UtilityService::create([
            'name' => 'Electricity',
            'slug' => 'electricity',
            'unit_of_measurement' => 'kWh',
            'default_pricing_model' => PricingModel::TIME_OF_USE,
            'service_type_bridge' => ServiceType::ELECTRICITY,
            'is_global_template' => true,
            'is_active' => true,
        ]);
        
        UtilityService::create([
            'name' => 'Water',
            'slug' => 'water',
            'unit_of_measurement' => 'm³',
            'default_pricing_model' => PricingModel::CONSUMPTION_BASED,
            'service_type_bridge' => ServiceType::WATER,
            'is_global_template' => true,
            'is_active' => true,
        ]);
        
        UtilityService::create([
            'name' => 'Heating',
            'slug' => 'heating',
            'unit_of_measurement' => 'kWh',
            'default_pricing_model' => PricingModel::HYBRID,
            'service_type_bridge' => ServiceType::HEATING,
            'is_global_template' => true,
            'is_active' => true,
        ]);
    }
}
```

## Documentation Updates

### README Updates
```markdown
## Universal Utility Management System

### New Features
- **Multi-Utility Support**: Water, electricity, heating, gas, internet services
- **Flexible Pricing**: Fixed, consumption-based, tiered, hybrid, and custom pricing models
- **Enhanced Validation**: Multi-input reading collection with OCR and API integration
- **Universal Billing**: Automated calculations with seasonal adjustments

### Migration Guide
See [MIGRATION.md](docs/MIGRATION.md) for detailed migration instructions from heating system.

### API Documentation
- [Service Validation API](docs/api/SERVICE_VALIDATION_API.md)
- [Universal Billing API](docs/api/UNIVERSAL_BILLING_API.md)
- [Reading Collection API](docs/api/READING_COLLECTION_API.md)
```

### API Documentation
- Update existing SERVICE_VALIDATION_API.md with new endpoints
- Create UNIVERSAL_BILLING_API.md for billing engine documentation
- Add READING_COLLECTION_API.md for enhanced reading capabilities

### .kiro Documentation
- Update steering files with universal system guidelines
- Add spec documentation for future enhancements
- Create troubleshooting guides for common issues

## Monitoring and Alerting

### Key Metrics
```php
// config/monitoring.php
'metrics' => [
    'validation_success_rate' => [
        'type' => 'gauge',
        'description' => 'Percentage of successful validations',
        'alert_threshold' => 0.95,
    ],
    'batch_processing_time' => [
        'type' => 'histogram',
        'description' => 'Time to process validation batches',
        'alert_threshold' => 10000, // 10 seconds
    ],
    'memory_usage' => [
        'type' => 'gauge',
        'description' => 'Memory usage during validation',
        'alert_threshold' => 104857600, // 100MB
    ],
],
```

### Alert Configuration
```yaml
# monitoring/alerts.yml
alerts:
  - name: high_validation_error_rate
    condition: validation_error_rate > 0.05
    duration: 5m
    severity: warning
    
  - name: slow_validation_response
    condition: avg_validation_time > 500ms
    duration: 2m
    severity: critical
    
  - name: memory_exhaustion
    condition: validation_memory_usage > 100MB
    duration: 1m
    severity: critical
```

### Health Checks
```php
<?php

namespace App\Http\Controllers;

class HealthController extends Controller
{
    public function validation(): JsonResponse
    {
        $engine = app(ServiceValidationEngine::class);
        
        // Test basic validation functionality
        $testReading = MeterReading::factory()->make();
        $result = $engine->validateMeterReading($testReading);
        
        return response()->json([
            'status' => $result ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toISOString(),
            'checks' => [
                'validation_engine' => $result ? 'pass' : 'fail',
                'database' => DB::connection()->getPdo() ? 'pass' : 'fail',
                'cache' => Cache::store()->get('health_check') !== null ? 'pass' : 'fail',
            ],
        ]);
    }
}
```

This comprehensive specification provides a complete roadmap for implementing the Universal Utility Management System while maintaining backward compatibility with the existing heating system and ensuring high performance, security, and accessibility standards.