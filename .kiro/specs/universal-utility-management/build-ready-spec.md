# Universal Utility Management System - Production Completion Spec

## Executive Summary

Complete the Universal Utility Management system for production deployment, focusing on enhanced user interfaces, advanced reporting, integration resilience, and comprehensive testing. The system is 80% complete with solid foundations - this spec addresses the final 20% needed for production readiness.

### Success Metrics
- **Tenant Isolation**: 100% data isolation verified by property tests (0 cross-tenant leaks)
- **Performance**: Dashboard loads <300ms, mobile interface <500ms on 3G
- **Accessibility**: WCAG 2.1 AA compliance across all interfaces
- **Reliability**: 99.9% uptime with graceful degradation for external service failures
- **Audit Compliance**: Complete audit trails for all utility service operations
- **Mobile Experience**: Responsive design supporting 320px+ viewports

### Constraints
- Maintain backward compatibility with existing heating calculator
- Preserve all existing tenant data and configurations
- Support Lithuanian regulatory requirements
- Multi-tenant security must be unbreachable
- All UI text must be translatable (EN/LT/RU)

## User Stories

### Epic 1: Enhanced Tenant Dashboard

#### Story 1.1: Multi-Utility Dashboard Widgets
**As a** tenant user  
**I want** to see all my utility consumption and costs in a unified dashboard  
**So that** I can monitor my usage patterns and costs across all services

**Acceptance Criteria:**
- [ ] Dashboard displays consumption data for all active utility services
- [ ] Real-time cost tracking with daily/monthly projections
- [ ] Interactive charts showing usage trends over time
- [ ] Comparison widgets showing period-over-period changes
- [ ] Mobile-responsive layout (320px+ viewports)
- [ ] Keyboard navigation support (Tab, Enter, Arrow keys)
- [ ] Screen reader compatibility with proper ARIA labels
- [ ] All text translatable in EN/LT/RU
- [ ] Loads within 300ms on cached requests
- [ ] Graceful degradation when external services unavailable

#### Story 1.2: Mobile Reading Interface
**As a** property manager  
**I want** to capture meter readings on mobile devices  
**So that** I can efficiently collect readings during property visits

**Acceptance Criteria:**
- [ ] Mobile-optimized forms for meter reading entry
- [ ] Camera integration for meter photo capture
- [ ] Offline data collection with sync capability
- [ ] GPS location verification for readings
- [ ] Touch-friendly interface with large tap targets (44px minimum)
- [ ] Works on iOS Safari and Android Chrome
- [ ] Offline storage using browser localStorage
- [ ] Sync queue with retry logic for failed uploads
- [ ] Photo compression and upload progress indicators
- [ ] Accessibility support for voice input

### Epic 2: Advanced Reporting System

#### Story 2.1: Universal Compliance Reports
**As a** property administrator  
**I want** to generate compliance reports for multiple utility types  
**So that** I can meet regulatory requirements and audit needs

**Acceptance Criteria:**
- [ ] Generate reports for electricity, water, heating, and gas services
- [ ] Export formats: PDF, Excel, CSV
- [ ] Scheduled report generation and email delivery
- [ ] Regulatory compliance templates for Lithuanian requirements
- [ ] Historical data analysis with trend identification
- [ ] Multi-tenant scoping (no cross-tenant data leakage)
- [ ] Report caching for performance (TTL: 1 hour)
- [ ] Audit trail for all report generation activities
- [ ] Accessible report layouts with proper heading structure
- [ ] Translatable report headers and labels

#### Story 2.2: Audit Data Visualization
**As a** system administrator  
**I want** to visualize audit data and system changes  
**So that** I can monitor system health and identify issues

**Acceptance Criteria:**
- [ ] Interactive dashboards showing system activity
- [ ] Change tracking visualization with rollback capabilities
- [ ] Performance metrics and health indicators
- [ ] Alert system for anomalies and failures
- [ ] Real-time monitoring of tenant operations
- [ ] Historical trend analysis with drill-down capabilities
- [ ] Export capabilities for audit reports
- [ ] Role-based access to different audit levels
- [ ] Mobile-responsive audit interfaces
- [ ] Integration with existing logging infrastructure

### Epic 3: Integration Resilience

#### Story 3.1: External System Error Handling
**As a** system user  
**I want** the system to handle external service failures gracefully  
**So that** I can continue working even when third-party services are unavailable

**Acceptance Criteria:**
- [ ] Circuit breaker pattern for external API calls
- [ ] Retry logic with exponential backoff
- [ ] Fallback mechanisms for critical operations
- [ ] User-friendly error messages with recovery suggestions
- [ ] Offline mode for essential functions
- [ ] Queue system for deferred operations
- [ ] Health checks for all external dependencies
- [ ] Monitoring and alerting for service degradation
- [ ] Graceful degradation without data loss
- [ ] Recovery procedures documented and tested

#### Story 3.2: Data Synchronization Resilience
**As a** system administrator  
**I want** robust data synchronization with external systems  
**So that** data remains consistent even during network issues

**Acceptance Criteria:**
- [ ] Conflict resolution for concurrent data changes
- [ ] Transaction rollback capabilities
- [ ] Data integrity verification
- [ ] Sync status monitoring and reporting
- [ ] Manual sync triggers for administrators
- [ ] Batch processing for large data sets
- [ ] Error logging with detailed context
- [ ] Recovery tools for failed synchronizations
- [ ] Performance monitoring for sync operations
- [ ] Automated testing of sync scenarios

## Technical Requirements

### Data Models & Migrations

#### Enhanced Audit Models
```sql
-- Enhanced audit logging for universal services
CREATE TABLE universal_service_audits (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    auditable_type VARCHAR(255) NOT NULL,
    auditable_id BIGINT UNSIGNED NOT NULL,
    event VARCHAR(50) NOT NULL,
    old_values JSON,
    new_values JSON,
    user_id BIGINT UNSIGNED,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_tenant_auditable (tenant_id, auditable_type, auditable_id),
    INDEX idx_created_at (created_at),
    INDEX idx_event (event),
    FOREIGN KEY (tenant_id) REFERENCES organizations(id) ON DELETE CASCADE
);

-- Report generation tracking
CREATE TABLE report_generations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tenant_id BIGINT UNSIGNED NOT NULL,
    report_type VARCHAR(100) NOT NULL,
    parameters JSON,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    file_path VARCHAR(500),
    generated_by BIGINT UNSIGNED,
    started_at TIMESTAMP,
    completed_at TIMESTAMP,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_tenant_status (tenant_id, status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (tenant_id) REFERENCES organizations(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Integration health monitoring
CREATE TABLE integration_health_checks (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    endpoint VARCHAR(500) NOT NULL,
    status ENUM('healthy', 'degraded', 'unhealthy') NOT NULL,
    response_time_ms INT UNSIGNED,
    error_message TEXT,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_service_status (service_name, status),
    INDEX idx_checked_at (checked_at)
);
```

### API Endpoints

#### Enhanced Reporting API
```php
// routes/api.php additions
Route::middleware(['auth', 'role:admin,manager'])->prefix('reports')->group(function () {
    Route::post('/compliance', [ComplianceReportController::class, 'generate']);
    Route::get('/compliance/{report}', [ComplianceReportController::class, 'download']);
    Route::get('/compliance', [ComplianceReportController::class, 'index']);
    Route::delete('/compliance/{report}', [ComplianceReportController::class, 'destroy']);
    
    Route::get('/audit/dashboard', [AuditReportController::class, 'dashboard']);
    Route::get('/audit/changes', [AuditReportController::class, 'changes']);
    Route::get('/audit/performance', [AuditReportController::class, 'performance']);
});

// Integration health endpoints
Route::middleware(['auth', 'role:admin'])->prefix('health')->group(function () {
    Route::get('/integrations', [IntegrationHealthController::class, 'index']);
    Route::post('/integrations/{service}/check', [IntegrationHealthController::class, 'check']);
    Route::get('/integrations/{service}/history', [IntegrationHealthController::class, 'history']);
});
```

### Controllers & Services

#### ComplianceReportController
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GenerateComplianceReportRequest;
use App\Services\Reporting\ComplianceReportService;
use Illuminate\Http\JsonResponse;

final class ComplianceReportController extends Controller
{
    public function __construct(
        private readonly ComplianceReportService $reportService,
    ) {}

    public function generate(GenerateComplianceReportRequest $request): JsonResponse
    {
        $report = $this->reportService->generateReport(
            tenantId: auth()->user()->tenant_id,
            reportType: $request->validated('report_type'),
            parameters: $request->validated('parameters', []),
            userId: auth()->id(),
        );

        return response()->json([
            'data' => [
                'id' => $report->id,
                'status' => $report->status,
                'estimated_completion' => $report->estimated_completion,
            ],
        ], 202);
    }

    public function download(ReportGeneration $report): Response
    {
        $this->authorize('view', $report);

        if ($report->status !== 'completed') {
            abort(404, 'Report not ready for download');
        }

        return response()->download(
            storage_path('app/' . $report->file_path),
            $report->getFileName(),
            ['Content-Type' => $report->getMimeType()]
        );
    }
}
```

### Filament Enhancements

#### Enhanced Dashboard Widgets
```php
<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use App\Services\Dashboard\UtilityAnalyticsService;
use Filament\Widgets\ChartWidget;

final class MultiUtilityConsumptionChart extends ChartWidget
{
    protected static ?string $heading = 'Utility Consumption Trends';
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    public function __construct(
        private readonly UtilityAnalyticsService $analyticsService,
    ) {
        parent::__construct();
    }

    protected function getData(): array
    {
        $tenantId = auth()->user()->tenant_id;
        
        return $this->analyticsService->getConsumptionTrends(
            tenantId: $tenantId,
            period: $this->filter ?? '30d'
        );
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            '7d' => __('dashboard.filters.last_7_days'),
            '30d' => __('dashboard.filters.last_30_days'),
            '90d' => __('dashboard.filters.last_90_days'),
            '1y' => __('dashboard.filters.last_year'),
        ];
    }
}
```

### Validation Rules

#### GenerateComplianceReportRequest
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

final class GenerateComplianceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->can('generate_reports');
    }

    public function rules(): array
    {
        return [
            'report_type' => [
                'required',
                'string',
                'in:consumption_summary,compliance_audit,cost_analysis,usage_trends',
            ],
            'parameters' => ['array'],
            'parameters.start_date' => ['required_with:parameters', 'date'],
            'parameters.end_date' => ['required_with:parameters', 'date', 'after:parameters.start_date'],
            'parameters.service_types' => ['array'],
            'parameters.service_types.*' => ['string', 'in:electricity,water,heating,gas'],
            'parameters.format' => ['string', 'in:pdf,excel,csv'],
            'parameters.include_charts' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'report_type.required' => __('validation.reports.type_required'),
            'report_type.in' => __('validation.reports.invalid_type'),
            'parameters.start_date.required_with' => __('validation.reports.start_date_required'),
            'parameters.end_date.after' => __('validation.reports.end_date_after_start'),
        ];
    }
}
```

## UX Requirements

### Loading States
- **Dashboard Loading**: Skeleton screens for widgets during data fetch
- **Report Generation**: Progress indicators with estimated completion time
- **Mobile Sync**: Upload progress with retry options
- **Chart Loading**: Shimmer effects for chart containers

### Empty States
- **No Utility Data**: Onboarding flow to set up first utility service
- **No Reports**: Call-to-action to generate first report
- **No Meter Readings**: Guide to add first reading with mobile interface
- **Offline Mode**: Clear indication of offline status with sync pending count

### Error States
- **Network Errors**: Retry buttons with exponential backoff
- **Validation Errors**: Inline field validation with clear messaging
- **Permission Errors**: Helpful messages with contact information
- **Service Unavailable**: Graceful degradation with offline capabilities

### Keyboard Navigation
- **Tab Order**: Logical tab sequence through all interactive elements
- **Focus Indicators**: Clear visual focus indicators (2px outline)
- **Keyboard Shortcuts**: Alt+D for dashboard, Alt+R for reports
- **Skip Links**: Skip to main content, skip to navigation

### URL State Persistence
- **Dashboard Filters**: Persist selected time periods and service types
- **Report Parameters**: Maintain report configuration in URL
- **Mobile Forms**: Preserve form state during navigation
- **Search States**: Maintain search queries and filters

## Non-Functional Requirements

### Performance Budgets
- **Dashboard Load**: <300ms for cached data, <1s for fresh data
- **Mobile Interface**: <500ms on 3G connection
- **Report Generation**: <30s for standard reports, <5min for complex reports
- **Chart Rendering**: <200ms for up to 1000 data points
- **API Response**: <100ms for simple queries, <500ms for complex aggregations

### Accessibility (WCAG 2.1 AA)
- **Color Contrast**: Minimum 4.5:1 for normal text, 3:1 for large text
- **Focus Management**: Proper focus handling in modals and dynamic content
- **Screen Reader**: All content accessible via screen reader
- **Keyboard Only**: Full functionality available via keyboard
- **Motion**: Respect prefers-reduced-motion settings

### Security
- **CSP Headers**: Strict Content Security Policy
- **CSRF Protection**: All forms protected with CSRF tokens
- **Input Validation**: Server-side validation for all inputs
- **SQL Injection**: Parameterized queries only
- **XSS Prevention**: Output encoding for all user content

### Privacy
- **Data Minimization**: Collect only necessary data
- **Audit Logging**: Log access to sensitive data
- **Data Retention**: Automatic cleanup of old audit logs (2 years)
- **Anonymization**: Remove PII from debug logs

### Observability
- **Application Metrics**: Response times, error rates, throughput
- **Business Metrics**: Report generation success rates, user engagement
- **Infrastructure Metrics**: Database performance, queue depths
- **Alerting**: Automated alerts for critical failures

## Testing Plan

### Unit Tests (Pest)
```php
// Test tenant initialization service
it('creates default utility services for new tenant', function () {
    $tenant = Organization::factory()->create();
    $service = app(TenantInitializationService::class);
    
    $result = $service->initializeUniversalServices($tenant);
    
    expect($result->getServiceCount())->toBe(4);
    expect($result->getUtilityService('electricity'))->toBeInstanceOf(UtilityService::class);
    expect($result->getUtilityService('heating'))->toBeInstanceOf(UtilityService::class);
});

// Test compliance report generation
it('generates compliance report with proper tenant scoping', function () {
    $tenant = Organization::factory()->create();
    $user = User::factory()->for($tenant)->create();
    
    $service = app(ComplianceReportService::class);
    $report = $service->generateReport(
        tenantId: $tenant->id,
        reportType: 'consumption_summary',
        parameters: ['start_date' => '2024-01-01', 'end_date' => '2024-12-31'],
        userId: $user->id
    );
    
    expect($report->tenant_id)->toBe($tenant->id);
    expect($report->status)->toBe('pending');
});
```

### Feature Tests (Pest)
```php
// Test dashboard widget loading
it('loads dashboard widgets for authenticated tenant user', function () {
    $user = User::factory()->create();
    
    $this->actingAs($user)
        ->get('/tenant/dashboard')
        ->assertOk()
        ->assertSee('Utility Consumption Trends')
        ->assertSee('Cost Tracking');
});

// Test mobile reading interface
it('allows mobile meter reading submission', function () {
    $user = User::factory()->create();
    $meter = Meter::factory()->for($user->tenant)->create();
    
    $this->actingAs($user)
        ->postJson('/api/meter-readings', [
            'meter_id' => $meter->id,
            'value' => 1500,
            'reading_date' => now()->toDateString(),
            'input_method' => 'mobile',
        ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'value', 'meter_id']]);
});
```

### Property Tests
```php
// Test tenant data isolation
it('ensures tenant data isolation across all operations', function () {
    $tenant1 = Organization::factory()->create();
    $tenant2 = Organization::factory()->create();
    
    $user1 = User::factory()->for($tenant1)->create();
    $user2 = User::factory()->for($tenant2)->create();
    
    // Create services for both tenants
    $service = app(TenantInitializationService::class);
    $result1 = $service->initializeUniversalServices($tenant1);
    $result2 = $service->initializeUniversalServices($tenant2);
    
    // Verify isolation
    $this->actingAs($user1);
    $tenant1Services = UtilityService::all();
    
    $this->actingAs($user2);
    $tenant2Services = UtilityService::all();
    
    expect($tenant1Services->pluck('id')->intersect($tenant2Services->pluck('id')))->toBeEmpty();
})->repeat(100);
```

### Browser Tests (Playwright)
```javascript
// Test mobile reading interface
test('mobile meter reading workflow', async ({ page }) => {
  await page.goto('/tenant/meters');
  await page.click('[data-testid="add-reading-mobile"]');
  
  // Test camera integration
  await page.click('[data-testid="camera-capture"]');
  await page.waitForSelector('[data-testid="photo-preview"]');
  
  // Test form submission
  await page.fill('[data-testid="reading-value"]', '1500');
  await page.click('[data-testid="submit-reading"]');
  
  await expect(page.locator('[data-testid="success-message"]')).toBeVisible();
});

// Test dashboard responsiveness
test('dashboard responsive behavior', async ({ page }) => {
  await page.setViewportSize({ width: 320, height: 568 }); // iPhone SE
  await page.goto('/tenant/dashboard');
  
  // Verify mobile layout
  await expect(page.locator('[data-testid="mobile-nav"]')).toBeVisible();
  await expect(page.locator('[data-testid="desktop-sidebar"]')).toBeHidden();
  
  // Test widget interactions
  await page.click('[data-testid="consumption-widget"]');
  await expect(page.locator('[data-testid="consumption-details"]')).toBeVisible();
});
```

## Migration & Deployment

### Database Migrations
```bash
# Create new migration files
php artisan make:migration create_universal_service_audits_table
php artisan make:migration create_report_generations_table
php artisan make:migration create_integration_health_checks_table

# Add indexes for performance
php artisan make:migration add_performance_indexes_to_existing_tables
```

### Deployment Steps
1. **Pre-deployment**
   - Run database migrations in staging
   - Verify all existing data integrity
   - Test rollback procedures

2. **Deployment**
   - Deploy application code
   - Run migrations with zero downtime
   - Update configuration files
   - Clear application caches

3. **Post-deployment**
   - Verify all services are healthy
   - Run smoke tests
   - Monitor error rates and performance
   - Validate tenant data isolation

### Rollback Plan
- Database migration rollbacks tested and documented
- Application code rollback via deployment pipeline
- Configuration rollback procedures
- Data integrity verification after rollback

## Documentation Updates

### README Updates
- Add mobile interface usage instructions
- Document new reporting capabilities
- Update API documentation with new endpoints
- Add troubleshooting guide for integration issues

### .kiro Documentation
- Update implementation status in tasks.md
- Add new API endpoints to api documentation
- Document mobile interface patterns
- Update testing procedures and coverage reports

### User Documentation
- Mobile reading interface user guide
- Dashboard widget configuration guide
- Report generation and scheduling guide
- Troubleshooting common issues

## Monitoring & Alerting

### Application Monitoring
- **Error Rate**: Alert if >1% error rate for 5 minutes
- **Response Time**: Alert if P95 >1s for 10 minutes
- **Queue Depth**: Alert if >1000 jobs pending for 15 minutes
- **Database Performance**: Alert if slow queries >100ms average

### Business Monitoring
- **Report Generation**: Alert if >10% failure rate
- **Tenant Isolation**: Alert on any cross-tenant data access
- **Mobile Sync**: Alert if >5% sync failures
- **Integration Health**: Alert if external service unhealthy >5 minutes

### Infrastructure Monitoring
- **Database Connections**: Alert if >80% pool utilization
- **Memory Usage**: Alert if >85% memory usage
- **Disk Space**: Alert if <20% free space
- **Network Latency**: Alert if >200ms to external services

This specification provides a comprehensive roadmap for completing the Universal Utility Management system and making it production-ready while maintaining the high quality and security standards established in the existing implementation.