# Multi-Tenant Data Management Requirements

## Overview

This specification defines the requirements for implementing robust multi-tenant data management in the Vilnius utilities billing platform. The system must ensure complete data isolation between tenants while providing efficient access patterns and maintaining performance.

## Workflow Evolution: Strict to Permissive

### Background
The initial implementation used a "Strict" workflow that prioritized security over operational efficiency. Business feedback indicated this was blocking daily operations and creating unnecessary bottlenecks.

### Permissive Workflow Changes

#### 1. Tenant Self-Service for Meter Readings
**Before (Strict)**: Tenants could not edit/delete readings once submitted
**After (Permissive)**: Tenants can edit/delete their OWN readings IF status is 'pending'

**Business Impact**: 
- Reduces support tickets for reading corrections
- Enables tenant self-service for common mistakes
- Maintains data integrity through status validation

#### 2. Manager User Management Authority
**Before (Strict)**: Managers had read-only access to users
**After (Permissive)**: Managers have full CRUD rights over users within their tenant

**Business Impact**:
- Eliminates admin bottlenecks for user management
- Enables operational efficiency for property managers
- Maintains security through role hierarchy protection

#### 3. Manager Invoice Corrections
**Before (Strict)**: Managers could not delete generated invoices
**After (Permissive)**: Managers can delete draft invoices

**Business Impact**:
- Allows correction of billing errors without admin intervention
- Reduces billing disputes through proactive error correction
- Maintains financial integrity through draft-only limitation

### Security Safeguards Maintained
- All operations remain tenant-scoped
- Audit logging captures all permissive operations
- Role hierarchy protection prevents privilege escalation
- Status-based validation maintains data integrity
- Cross-tenant access prevention remains absolute

## Core Requirements

### 1. Data Isolation

#### 1.1 Tenant Scoping
- **Requirement**: All data access must be automatically scoped to the current tenant
- **Implementation**: Global scopes on all tenant-aware models
- **Verification**: Property tests ensure no cross-tenant data leakage

#### 1.2 Model-Level Protection
- **Requirement**: Every model with tenant data must implement `BelongsToTenant` trait
- **Models**: Organization, User, Property, Building, Meter, MeterReading, Invoice, Tariff, Provider
- **Enforcement**: Global `TenantScope` applied automatically

#### 1.3 Query-Level Security
- **Requirement**: All queries must include tenant filtering by default
- **Exception**: Superadmin operations with explicit `withoutGlobalScopes()`
- **Audit**: Log all cross-tenant access attempts

### 2. Tenant Context Management

#### 2.1 Context Initialization
- **Requirement**: Tenant context must be established early in request lifecycle
- **Implementation**: `TenantContext` service with session-based persistence
- **Fallback**: Non-superadmin users locked to their organization

#### 2.2 Context Switching
- **Requirement**: Superadmins can switch between tenant contexts
- **Security**: All switches must be logged for audit compliance
- **UI**: Clear indication of current tenant context in admin interface

#### 2.3 Context Validation
- **Requirement**: Validate tenant access permissions on every request
- **Implementation**: Middleware to ensure user can access current tenant
- **Error Handling**: Graceful fallback to user's default tenant

### 3. Performance Optimization

#### 3.1 Query Optimization
- **Requirement**: Prevent N+1 queries in multi-tenant scenarios
- **Implementation**: Eager loading with tenant-scoped relationships
- **Monitoring**: Track query performance per tenant

#### 3.2 Caching Strategy
- **Requirement**: Cache keys must include tenant identifier
- **Implementation**: Tenant-scoped cache keys for all cached data
- **Invalidation**: Automatic cache clearing on tenant data changes

#### 3.3 Database Indexing
- **Requirement**: All tenant-aware tables must have optimized indexes
- **Implementation**: Composite indexes starting with `tenant_id`
- **Monitoring**: Query performance monitoring per tenant

### 4. Security Requirements

#### 4.1 Authorization - Permissive Workflow
- **Requirement**: All operations must check tenant-level permissions with business-friendly access patterns
- **Implementation**: Policies that verify tenant ownership with permissive rules for operational efficiency
- **Enforcement**: No data access without proper tenant authorization, but allow reasonable self-service operations

##### 4.1.1 Meter Reading Permissions (Permissive)
- **Tenant Self-Service**: Tenants can edit/delete their OWN readings ONLY IF status is 'pending'
- **Business Justification**: Allows tenants to correct mistakes before manager approval
- **Security**: Limited to pending status and ownership validation
- **Audit**: All tenant modifications logged for compliance

##### 4.1.2 User Management Permissions (Permissive)
- **Manager Authority**: Managers have FULL CRUD rights over users within their tenant scope
- **Business Justification**: Enables operational efficiency without admin bottlenecks
- **Security**: Cannot edit/delete Superadmins or Admins, only peers and tenants
- **Audit**: All manager user operations logged for compliance

##### 4.1.3 Invoice Management Permissions (Permissive)
- **Manager Corrections**: Managers can delete draft invoices (e.g., if generated by mistake)
- **Business Justification**: Allows correction of billing errors without admin intervention
- **Security**: Limited to draft invoices only, finalized invoices remain protected
- **Audit**: All invoice deletions logged for compliance

#### 4.2 Audit Logging
- **Requirement**: Log all tenant data access and modifications
- **Implementation**: Observers for tenant-aware models
- **Retention**: Audit logs retained per compliance requirements

#### 4.3 Data Encryption
- **Requirement**: Sensitive tenant data must be encrypted at rest
- **Implementation**: Laravel's built-in encryption for sensitive fields
- **Key Management**: Tenant-specific encryption keys where required

### 5. API Security

#### 5.1 API Authentication
- **Requirement**: API access must include tenant identification
- **Implementation**: Sanctum tokens with tenant scoping
- **Validation**: API requests validated against tenant permissions

#### 5.2 Rate Limiting
- **Requirement**: Rate limiting must be applied per tenant
- **Implementation**: Tenant-aware rate limiting middleware
- **Monitoring**: Track API usage per tenant

### 6. Data Migration and Seeding

#### 6.1 Migration Safety
- **Requirement**: Database migrations must preserve tenant data integrity
- **Implementation**: Tenant-aware migration scripts
- **Testing**: Verify migrations don't cause cross-tenant data issues

#### 6.2 Seeding Strategy
- **Requirement**: Test data must be properly tenant-scoped
- **Implementation**: Seeders that create isolated tenant datasets
- **Cleanup**: Ability to clean up tenant-specific test data

### 7. Monitoring and Alerting

#### 7.1 Data Isolation Monitoring
- **Requirement**: Monitor for potential data leakage between tenants
- **Implementation**: Automated tests and runtime checks
- **Alerting**: Immediate alerts for any cross-tenant data access

#### 7.2 Performance Monitoring
- **Requirement**: Track performance metrics per tenant
- **Implementation**: Tenant-specific performance dashboards
- **Optimization**: Identify and resolve tenant-specific performance issues

### 8. Backup and Recovery

#### 8.1 Tenant-Specific Backups
- **Requirement**: Ability to backup and restore individual tenant data
- **Implementation**: Tenant-scoped backup procedures
- **Testing**: Regular restore testing for tenant data integrity

#### 8.2 Disaster Recovery
- **Requirement**: Disaster recovery procedures must maintain tenant isolation
- **Implementation**: Tenant-aware recovery processes
- **Validation**: Verify tenant data integrity after recovery

## Implementation Guidelines

### Model Implementation
```php
<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use BelongsToTenant;
    
    protected $fillable = [
        'tenant_id',
        'name',
        'address',
        // other fields
    ];
    
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
    }
}
```

### Service Implementation
```php
<?php

namespace App\Services;

use App\Models\Property;
use App\Services\TenantContext;

class PropertyService
{
    public function getProperties(): Collection
    {
        // Automatically scoped to current tenant
        return Property::all();
    }
    
    public function createProperty(array $data): Property
    {
        // Tenant ID automatically added
        return Property::create($data);
    }
}
```

### Policy Implementation - Permissive Workflow
```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\MeterReading;
use App\Enums\ValidationStatus;

class MeterReadingPolicy
{
    public function update(User $user, MeterReading $meterReading): bool
    {
        // Superadmin and Admin/Manager can update any reading in their scope
        if ($user->isSuperadmin() || $user->isAdmin() || $user->role === UserRole::MANAGER) {
            return $this->belongsToUserTenant($user, $meterReading);
        }
        
        // Tenants can update their OWN readings ONLY IF status is 'pending' (Permissive)
        if ($user->role === UserRole::TENANT) {
            return $user->id === $meterReading->entered_by && 
                   $meterReading->validation_status === ValidationStatus::PENDING;
        }
        
        return false;
    }
    
    public function delete(User $user, MeterReading $meterReading): bool
    {
        // Superadmin and Admin can delete any reading in their scope
        if ($user->isSuperadmin() || $user->isAdmin()) {
            return $this->belongsToUserTenant($user, $meterReading);
        }
        
        // Tenants can delete their OWN readings ONLY IF status is 'pending' (Permissive)
        if ($user->role === UserRole::TENANT) {
            return $user->id === $meterReading->entered_by && 
                   $meterReading->validation_status === ValidationStatus::PENDING;
        }
        
        return false;
    }
}

class UserPolicy
{
    public function create(User $user): bool
    {
        // Managers can create users (Permissive)
        return $user->isSuperadmin() || $user->isAdmin() || $user->role === UserRole::MANAGER;
    }
    
    public function update(User $user, User $model): bool
    {
        // Self-update always allowed
        if ($user->id === $model->id) {
            return true;
        }
        
        // Managers can update users within tenant (except Superadmins/Admins)
        if ($user->role === UserRole::MANAGER && $this->isSameTenant($user, $model)) {
            return !in_array($model->role, [UserRole::SUPERADMIN, UserRole::ADMIN], true);
        }
        
        return $user->isSuperadmin() || $this->canManageTenantUser($user, $model);
    }
    
    public function delete(User $user, User $model): bool
    {
        // Cannot delete yourself
        if ($user->id === $model->id) {
            return false;
        }
        
        // Managers can delete users within tenant (except Superadmins/Admins)
        if ($user->role === UserRole::MANAGER && $this->isSameTenant($user, $model)) {
            return !in_array($model->role, [UserRole::SUPERADMIN, UserRole::ADMIN], true);
        }
        
        return $user->isSuperadmin() || $this->canManageTenantUser($user, $model);
    }
}

class InvoicePolicy
{
    public function delete(User $user, Invoice $invoice): bool
    {
        // Only draft invoices can be deleted
        if (!$invoice->isDraft()) {
            return false;
        }
        
        // Managers can delete draft invoices (Permissive)
        if ($user->role === UserRole::MANAGER) {
            return $invoice->tenant_id === $user->tenant_id;
        }
        
        return $user->isSuperadmin() || 
               ($user->isAdmin() && $invoice->tenant_id === $user->tenant_id);
    }
}
```

## Testing Requirements

### Property-Based Tests
- Verify no cross-tenant data access in any scenario
- Test tenant context switching for superadmins
- Validate cache isolation between tenants
- Ensure API endpoints respect tenant boundaries

### Performance Tests
- Measure query performance with tenant scoping
- Verify cache hit rates per tenant
- Test database performance under multi-tenant load

### Security Tests - Permissive Workflow
- Attempt unauthorized cross-tenant access
- Verify audit logging captures all tenant operations
- Test API security with invalid tenant tokens
- **Permissive Tests**: Verify tenants can edit/delete pending readings
- **Manager Tests**: Verify managers can perform full user CRUD within tenant
- **Invoice Tests**: Verify managers can delete draft invoices
- **Boundary Tests**: Verify managers cannot edit Superadmins/Admins
- **Status Tests**: Verify tenants cannot edit finalized readings

## Success Criteria

1. **Zero Cross-Tenant Data Leakage**: Property tests pass 100% of the time
2. **Performance Maintained**: No significant performance degradation with tenant scoping
3. **Security Compliance**: All audit requirements met for tenant data access
4. **Developer Experience**: Clear patterns for implementing tenant-aware features
5. **Operational Excellence**: Monitoring and alerting provide visibility into tenant operations

## Risks and Mitigations

### Risk: Accidental Cross-Tenant Access
- **Mitigation**: Global scopes and comprehensive testing
- **Detection**: Runtime monitoring and alerting

### Risk: Performance Degradation
- **Mitigation**: Proper indexing and query optimization
- **Monitoring**: Per-tenant performance metrics

### Risk: Complex Debugging
- **Mitigation**: Clear tenant context in logs and debugging tools
- **Tools**: Tenant-aware debugging interfaces

## Dependencies

- Laravel 12 multi-tenancy features
- Filament v4.3+ tenant-aware components
- Spatie Laravel Permission for tenant-scoped roles
- Custom TenantContext service
- Comprehensive test suite with property-based testing

## Timeline

- **Phase 1**: Core tenant scoping implementation (2 weeks)
- **Phase 2**: Security and audit features (1 week)
- **Phase 3**: Performance optimization (1 week)
- **Phase 4**: Monitoring and alerting (1 week)
- **Phase 5**: Documentation and training (1 week)

Total estimated timeline: 6 weeks