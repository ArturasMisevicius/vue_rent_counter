# Multi-Tenant Data Management Design

## Overview

This design implements robust multi-tenant data management for the Vilnius utilities billing platform, ensuring complete data isolation between tenants while providing efficient access patterns and maintaining performance. The system has evolved from a strict security-first approach to a permissive workflow that balances security with operational efficiency based on business feedback.

## Architecture

### Core Components

#### 1. Tenant Context Management
- **TenantContext Service**: Centralized service for managing current tenant context
- **Session-based Persistence**: Tenant context stored in user session for consistency
- **Context Switching**: Superadmin capability to switch between tenant contexts with audit logging
- **Fallback Mechanism**: Non-superadmin users automatically locked to their organization

#### 2. Data Isolation Layer
- **BelongsToTenant Trait**: Applied to all tenant-aware models for automatic tenant association
- **TenantScope Global Scope**: Automatically filters all queries to current tenant
- **Model-Level Protection**: Every model with tenant data implements tenant scoping
- **Query-Level Security**: All queries include tenant filtering by default

#### 3. Authorization Framework
- **Permissive Workflow Strategy**: Business-friendly access patterns that enable operational efficiency
- **Policy-Based Authorization**: Laravel policies enforce tenant-level permissions
- **Role Hierarchy Protection**: Prevents privilege escalation while allowing reasonable self-service
- **Audit Logging**: All tenant operations logged for compliance

## Components and Interfaces

### Core Services

#### TenantContext Service
```php
interface TenantContextInterface
{
    public function set(int $tenantId): void;
    public function get(): ?int;
    public function switch(int $tenantId, User $user): void;
    public function validate(User $user): bool;
    public function clear(): void;
}
```

**Responsibilities:**
- Manage current tenant context in session
- Validate user access to tenant contexts
- Log all context switches for audit compliance
- Provide fallback to user's default tenant

#### Workflow Strategy Interface
```php
interface WorkflowStrategyInterface
{
    public function canEditMeterReading(User $user, MeterReading $reading): PolicyResult;
    public function canDeleteMeterReading(User $user, MeterReading $reading): PolicyResult;
    public function canManageUser(User $user, User $targetUser): PolicyResult;
    public function canDeleteInvoice(User $user, Invoice $invoice): PolicyResult;
}
```

**Implementations:**
- **PermissiveWorkflowStrategy**: Business-friendly rules for operational efficiency
- **TruthButVerifyWorkflowStrategy**: Enhanced security with verification requirements

### Data Access Layer

#### Repository Pattern with Tenant Scoping
```php
interface TenantAwareRepositoryInterface
{
    public function findForTenant(int $id, int $tenantId): ?Model;
    public function createForTenant(array $data, int $tenantId): Model;
    public function updateForTenant(Model $model, array $data): Model;
    public function deleteForTenant(Model $model): bool;
}
```

#### Global Scopes
- **TenantScope**: Automatically filters queries by tenant_id
- **HierarchicalScope**: Manages hierarchical access patterns for managers/admins

### Authorization Components

#### Policy Result Value Object
```php
class PolicyResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly string $reason,
        public readonly array $context = []
    ) {}
}
```

#### Authorization Context
```php
class AuthorizationContext
{
    public function __construct(
        public readonly User $user,
        public readonly ?Model $resource,
        public readonly string $action,
        public readonly array $metadata = []
    ) {}
}
```

## Data Models

### Core Tenant-Aware Models

#### Tenant Model
```php
class Tenant extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'settings',
        'subscription_id',
        'status'
    ];

    protected $casts = [
        'settings' => 'array',
        'status' => TenantStatus::class
    ];
}
```

#### BelongsToTenant Trait
```php
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);
        
        static::creating(function ($model) {
            if (!$model->tenant_id) {
                $model->tenant_id = app(TenantContext::class)->get();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

### Tenant-Scoped Models
All models implementing tenant isolation:
- Organization
- User  
- Property
- Building
- Meter
- MeterReading
- Invoice
- Tariff
- Provider

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Data Isolation Properties

#### P1: Tenant Boundary Invariant
**Property**: All data access is automatically scoped to the current tenant context
**Formal**: `∀ queries Q, user U, tenant T → Q.results ⊆ data(T) where T = context(U)`
**Verification**: Property tests ensure no query returns data outside current tenant scope
**Implementation**: Global `TenantScope` applied to all tenant-aware models

#### P2: Cross-Tenant Prevention
**Property**: Users cannot access data belonging to other tenants
**Formal**: `∀ operations O, users U1,U2 → tenant(U1) ≠ tenant(U2) → ¬canAccess(U1, data(U2))`
**Verification**: Attempt cross-tenant access in all scenarios, verify all fail
**Implementation**: Policy-based authorization with tenant ownership validation

### Permissive Workflow Properties

#### P3: Tenant Self-Service
**Property**: Tenants can edit/delete their own meter readings when status is pending
**Formal**: `∀ tenant T, reading R → (owner(R) = T ∧ status(R) = PENDING) → canEdit(T, R)`
**Verification**: Test tenant can modify own pending readings, cannot modify others or finalized readings
**Implementation**: `MeterReadingPolicy` with ownership and status validation

#### P4: Manager Authority
**Property**: Managers have full CRUD rights over users within their tenant (except Superadmins/Admins)
**Formal**: `∀ manager M, user U → (tenant(M) = tenant(U) ∧ role(U) ∉ {SUPERADMIN, ADMIN}) → canManage(M, U)`
**Verification**: Test manager can manage tenant users, cannot manage higher-role users
**Implementation**: `UserPolicy` with role hierarchy protection

#### P5: Draft Invoice Control
**Property**: Managers can delete draft invoices within their tenant
**Formal**: `∀ manager M, invoice I → (tenant(M) = tenant(I) ∧ status(I) = DRAFT) → canDelete(M, I)`
**Verification**: Test manager can delete draft invoices, cannot delete finalized invoices
**Implementation**: `InvoicePolicy` with status-based validation

### Security Properties

#### P6: Role Hierarchy Integrity
**Property**: Users cannot modify other users with equal or higher roles
**Formal**: `∀ users U1,U2 → role(U1) ≤ role(U2) → ¬canModify(U1, U2)`
**Verification**: Test all role combinations, verify hierarchy is respected
**Implementation**: Role comparison logic in all user management policies

#### P7: Status Integrity
**Property**: Operations respect valid status transitions for resources
**Formal**: `∀ operations O, resources R → status(R) ∈ validTransitions(O, R)`
**Verification**: Test invalid status transitions are rejected
**Implementation**: Status validation in model observers and policies

### Audit Properties

#### P8: Operation Logging
**Property**: All tenant operations are logged with complete context
**Formal**: `∀ operations O → logged(O) ∧ timestamp(O) ∧ actor(O) ∧ target(O) ∧ tenant(O)`
**Verification**: Verify all operations generate audit logs with required fields
**Implementation**: Model observers and audit logging service

### Performance Properties

#### P9: Query Performance Invariant
**Property**: Tenant scoping does not significantly degrade query performance
**Formal**: `∀ queries Q → performance(Q.withTenantScope) ≤ 1.1 × performance(Q.withoutTenantScope)`
**Verification**: Performance benchmarks comparing scoped vs unscoped queries
**Implementation**: Optimized database indexes and query patterns

#### P10: Cache Isolation
**Property**: Cache keys are tenant-scoped to prevent data leakage
**Formal**: `∀ cache entries C, tenants T1,T2 → tenant(C) = T1 → ¬accessible(T2, C)`
**Verification**: Test cache isolation between tenants
**Implementation**: Tenant-prefixed cache keys and cache invalidation strategies

<function_calls>
<invoke name="prework">
<parameter name="featureName">multi-tenant-data-management

## Error Handling

### Tenant Context Errors

#### Missing Tenant Context
**Scenario**: Request processed without valid tenant context
**Response**: Redirect to tenant selection or user's default tenant
**Logging**: Log context resolution attempts and failures
**Recovery**: Graceful fallback to user's organization tenant

#### Invalid Tenant Access
**Scenario**: User attempts to access unauthorized tenant
**Response**: HTTP 403 Forbidden with clear error message
**Logging**: Log unauthorized access attempts for security monitoring
**Recovery**: Redirect to user's authorized tenant context

#### Tenant Context Corruption
**Scenario**: Session contains invalid or corrupted tenant data
**Response**: Clear corrupted context and reinitialize
**Logging**: Log context corruption events for debugging
**Recovery**: Force re-authentication if corruption persists

### Authorization Errors

#### Insufficient Permissions
**Scenario**: User lacks required permissions for operation
**Response**: HTTP 403 with specific permission requirements
**Logging**: Log permission denials with full context
**Recovery**: Provide clear guidance on required permissions

#### Cross-Tenant Access Attempt
**Scenario**: Attempt to access data from different tenant
**Response**: HTTP 404 (resource not found) to avoid information disclosure
**Logging**: Log as security event with full request context
**Recovery**: No recovery - maintain security boundary

#### Role Hierarchy Violation
**Scenario**: Lower-role user attempts to modify higher-role user
**Response**: HTTP 403 with role hierarchy explanation
**Logging**: Log hierarchy violations for audit compliance
**Recovery**: Provide role-appropriate alternative actions

### Data Integrity Errors

#### Status Transition Violation
**Scenario**: Invalid status change attempted (e.g., edit finalized reading)
**Response**: HTTP 422 with valid transition options
**Logging**: Log invalid transitions for business rule monitoring
**Recovery**: Provide valid status transition paths

#### Ownership Violation
**Scenario**: User attempts to modify resource they don't own
**Response**: HTTP 403 with ownership requirements
**Logging**: Log ownership violations for security monitoring
**Recovery**: Redirect to user's owned resources

### Performance Degradation

#### Query Timeout
**Scenario**: Tenant-scoped query exceeds timeout threshold
**Response**: HTTP 504 with retry guidance
**Logging**: Log slow queries for performance optimization
**Recovery**: Implement query optimization or pagination

#### Cache Miss Cascade
**Scenario**: Tenant cache invalidation causes performance impact
**Response**: Graceful degradation with reduced functionality
**Logging**: Log cache performance metrics
**Recovery**: Implement cache warming strategies

## Testing Strategy

### Property-Based Testing

#### Data Isolation Tests
```php
/**
 * Property: No cross-tenant data leakage
 * Generates random tenant pairs and verifies complete isolation
 */
class TenantIsolationPropertyTest extends TestCase
{
    public function test_tenant_boundary_invariant(): void
    {
        // Generate random tenant scenarios
        $scenarios = $this->generateTenantScenarios(100);
        
        foreach ($scenarios as $scenario) {
            $this->assertTenantBoundaryRespected($scenario);
        }
    }
    
    public function test_cross_tenant_prevention(): void
    {
        // Test all possible cross-tenant access attempts
        $this->forAll(
            Generator::tenantPair(),
            Generator::userPair(),
            Generator::resourceType()
        )->then(function ($tenant1, $tenant2, $user1, $user2, $resource) {
            $this->assertCrossTenantAccessDenied($user1, $user2, $resource);
        });
    }
}
```

#### Permissive Workflow Tests
```php
/**
 * Property: Permissive rules work correctly within security boundaries
 */
class PermissiveWorkflowPropertyTest extends TestCase
{
    public function test_tenant_self_service_property(): void
    {
        $this->forAll(
            Generator::tenant(),
            Generator::meterReading(),
            Generator::validationStatus()
        )->then(function ($tenant, $reading, $status) {
            $canEdit = $this->canTenantEditReading($tenant, $reading, $status);
            $expected = ($reading->entered_by === $tenant->id && $status === ValidationStatus::PENDING);
            
            $this->assertEquals($expected, $canEdit);
        });
    }
    
    public function test_manager_authority_property(): void
    {
        $this->forAll(
            Generator::manager(),
            Generator::user(),
            Generator::userRole()
        )->then(function ($manager, $user, $role) {
            $canManage = $this->canManagerManageUser($manager, $user, $role);
            $expected = ($manager->tenant_id === $user->tenant_id && 
                        !in_array($role, [UserRole::SUPERADMIN, UserRole::ADMIN]));
            
            $this->assertEquals($expected, $canManage);
        });
    }
}
```

### Integration Testing

#### End-to-End Tenant Workflows
```php
class TenantWorkflowIntegrationTest extends TestCase
{
    public function test_complete_tenant_lifecycle(): void
    {
        // Create tenant with full data set
        $tenant = $this->createTenantWithData();
        
        // Test all major workflows
        $this->assertMeterReadingWorkflow($tenant);
        $this->assertInvoiceGenerationWorkflow($tenant);
        $this->assertUserManagementWorkflow($tenant);
        $this->assertReportingWorkflow($tenant);
        
        // Verify no cross-tenant contamination
        $this->assertTenantIsolationMaintained($tenant);
    }
    
    public function test_tenant_context_switching(): void
    {
        $superadmin = User::factory()->superadmin()->create();
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        // Test context switching
        $this->actingAs($superadmin)
            ->post(route('tenant.switch', $tenant1))
            ->assertRedirect();
            
        $this->assertEquals($tenant1->id, app(TenantContext::class)->get());
        
        // Verify data access is properly scoped
        $this->assertTenantDataAccess($tenant1);
        
        // Switch to different tenant
        $this->post(route('tenant.switch', $tenant2))
            ->assertRedirect();
            
        $this->assertEquals($tenant2->id, app(TenantContext::class)->get());
        $this->assertTenantDataAccess($tenant2);
    }
}
```

### Performance Testing

#### Tenant Scoping Performance
```php
class TenantPerformanceTest extends TestCase
{
    public function test_query_performance_with_scoping(): void
    {
        // Create large dataset across multiple tenants
        $this->createLargeMultiTenantDataset();
        
        // Measure query performance
        $startTime = microtime(true);
        $results = Property::all(); // Automatically scoped
        $scopedTime = microtime(true) - $startTime;
        
        // Compare with unscoped query
        $startTime = microtime(true);
        $unscopedResults = Property::withoutGlobalScopes()->get();
        $unscopedTime = microtime(true) - $startTime;
        
        // Verify performance is acceptable (within 10% overhead)
        $this->assertLessThan($unscopedTime * 1.1, $scopedTime);
        
        // Verify correct scoping
        $this->assertLessThan($unscopedResults->count(), $results->count());
    }
    
    public function test_cache_performance_isolation(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        // Test cache isolation doesn't impact performance
        $this->withTenantContext($tenant1, function () {
            $this->measureCachePerformance('tenant1_operations');
        });
        
        $this->withTenantContext($tenant2, function () {
            $this->measureCachePerformance('tenant2_operations');
        });
        
        // Verify no cache contamination
        $this->assertCacheIsolation($tenant1, $tenant2);
    }
}
```

### Security Testing

#### Authorization Boundary Tests
```php
class TenantSecurityTest extends TestCase
{
    public function test_authorization_boundaries(): void
    {
        $tenant1 = Tenant::factory()->create();
        $tenant2 = Tenant::factory()->create();
        
        $manager1 = User::factory()->manager()->for($tenant1)->create();
        $user2 = User::factory()->tenant()->for($tenant2)->create();
        
        // Attempt cross-tenant user management
        $this->actingAs($manager1)
            ->put(route('users.update', $user2), ['name' => 'Hacked'])
            ->assertForbidden();
            
        // Verify no data was modified
        $this->assertEquals($user2->fresh()->name, $user2->name);
    }
    
    public function test_permissive_workflow_security(): void
    {
        $tenant = Tenant::factory()->create();
        $tenantUser = User::factory()->tenant()->for($tenant)->create();
        
        // Create meter reading in different states
        $pendingReading = MeterReading::factory()
            ->for($tenant)
            ->pending()
            ->create(['entered_by' => $tenantUser->id]);
            
        $finalizedReading = MeterReading::factory()
            ->for($tenant)
            ->finalized()
            ->create(['entered_by' => $tenantUser->id]);
        
        // Test tenant can edit pending reading
        $this->actingAs($tenantUser)
            ->put(route('meter-readings.update', $pendingReading), ['value' => 1000])
            ->assertSuccessful();
            
        // Test tenant cannot edit finalized reading
        $this->actingAs($tenantUser)
            ->put(route('meter-readings.update', $finalizedReading), ['value' => 1000])
            ->assertForbidden();
    }
}
```

### Audit Testing

#### Audit Trail Verification
```php
class AuditTrailTest extends TestCase
{
    public function test_all_operations_logged(): void
    {
        $tenant = Tenant::factory()->create();
        $manager = User::factory()->manager()->for($tenant)->create();
        
        // Perform various operations
        $this->actingAs($manager);
        
        $user = User::factory()->tenant()->for($tenant)->create();
        $reading = MeterReading::factory()->for($tenant)->create();
        $invoice = Invoice::factory()->draft()->for($tenant)->create();
        
        // Modify resources
        $user->update(['name' => 'Updated Name']);
        $reading->update(['value' => 1000]);
        $invoice->delete();
        
        // Verify all operations are logged
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $manager->id,
            'tenant_id' => $tenant->id,
            'action' => 'updated',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
        
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $manager->id,
            'tenant_id' => $tenant->id,
            'action' => 'updated',
            'auditable_type' => MeterReading::class,
            'auditable_id' => $reading->id,
        ]);
        
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $manager->id,
            'tenant_id' => $tenant->id,
            'action' => 'deleted',
            'auditable_type' => Invoice::class,
            'auditable_id' => $invoice->id,
        ]);
    }
}
```

## Implementation Plan

### Phase 1: Core Infrastructure (Week 1-2)
1. **TenantContext Service Implementation**
   - Session-based context management
   - Context switching for superadmins
   - Validation and fallback mechanisms

2. **Global Scoping System**
   - TenantScope implementation
   - BelongsToTenant trait
   - Model integration

3. **Basic Authorization Framework**
   - Policy structure setup
   - Role hierarchy validation
   - Initial permissive workflow rules

### Phase 2: Permissive Workflow (Week 3)
1. **Meter Reading Self-Service**
   - Tenant edit/delete permissions for pending readings
   - Status validation logic
   - Audit logging integration

2. **Manager User Management**
   - Full CRUD permissions within tenant
   - Role hierarchy protection
   - Bulk operations support

3. **Invoice Management**
   - Draft invoice deletion permissions
   - Status-based validation
   - Financial audit requirements

### Phase 3: Security & Audit (Week 4)
1. **Comprehensive Audit Logging**
   - Model observers for all tenant operations
   - Audit trail reporting
   - Compliance reporting features

2. **Security Hardening**
   - Cross-tenant access prevention
   - Session security enhancements
   - Rate limiting per tenant

3. **Error Handling**
   - Graceful error recovery
   - Security event logging
   - User-friendly error messages

### Phase 4: Performance & Monitoring (Week 5)
1. **Query Optimization**
   - Database index optimization
   - Query performance monitoring
   - N+1 query prevention

2. **Caching Strategy**
   - Tenant-scoped cache keys
   - Cache invalidation strategies
   - Performance monitoring

3. **Monitoring & Alerting**
   - Tenant isolation monitoring
   - Performance dashboards
   - Security event alerting

### Phase 5: Testing & Documentation (Week 6)
1. **Comprehensive Test Suite**
   - Property-based tests
   - Integration tests
   - Performance benchmarks
   - Security tests

2. **Documentation**
   - Developer guidelines
   - Security procedures
   - Operational runbooks

3. **Training & Rollout**
   - Team training sessions
   - Gradual feature rollout
   - Monitoring and feedback collection

## Success Metrics

### Correctness Metrics
- **Zero Cross-Tenant Data Leakage**: 100% pass rate on property tests
- **Authorization Accuracy**: 100% correct permission decisions
- **Audit Completeness**: 100% operation coverage in audit logs

### Performance Metrics
- **Query Performance**: <10% overhead from tenant scoping
- **Cache Hit Rate**: >90% for tenant-scoped data
- **Response Time**: <200ms for typical tenant operations

### Security Metrics
- **Failed Authorization Attempts**: <0.1% of total requests
- **Security Event Response**: <5 minutes to alert on anomalies
- **Audit Trail Integrity**: 100% log retention and accuracy

### Business Metrics
- **Operational Efficiency**: 50% reduction in admin intervention requests
- **User Satisfaction**: >90% satisfaction with self-service capabilities
- **Support Ticket Reduction**: 40% reduction in tenant-related support tickets

## Risk Mitigation

### Technical Risks
- **Performance Degradation**: Comprehensive benchmarking and optimization
- **Complex Debugging**: Enhanced logging and tenant-aware debugging tools
- **Data Migration Complexity**: Careful migration planning and rollback procedures

### Security Risks
- **Accidental Cross-Tenant Access**: Multiple layers of protection and monitoring
- **Privilege Escalation**: Strict role hierarchy enforcement and audit logging
- **Data Corruption**: Comprehensive backup and recovery procedures

### Business Risks
- **User Confusion**: Clear documentation and training programs
- **Compliance Issues**: Comprehensive audit trails and compliance reporting
- **Operational Disruption**: Gradual rollout with rollback capabilities

## Conclusion

This design provides a comprehensive solution for multi-tenant data management that balances security with operational efficiency. The permissive workflow approach addresses business needs while maintaining strict security boundaries through formal correctness properties and comprehensive testing strategies.

The implementation plan ensures a systematic rollout with proper risk mitigation, while the success metrics provide clear targets for measuring the effectiveness of the solution. The design's emphasis on property-based testing and formal verification ensures long-term maintainability and correctness.