# Design Document

## Overview

The Manager Assignment System implements hierarchical access control by introducing assignment relationships between Managers and Buildings/Properties. The system extends the existing role-based access control with resource-level assignments, ensuring Managers can only access explicitly assigned resources while maintaining tenant boundaries and data integrity.

## Architecture

### High-Level Architecture

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Presentation  │    │   Application   │    │     Domain      │
│                 │    │                 │    │                 │
│ • Filament      │───▶│ • Assignment    │───▶│ • User Model    │
│   Resources     │    │   Services      │    │ • Building      │
│ • Bulk Actions  │    │ • Access        │    │ • Property      │
│ • Form Fields   │    │   Control       │    │ • Pivot Tables  │
│                 │    │ • Validation    │    │                 │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │
                                ▼
                       ┌─────────────────┐
                       │ Infrastructure  │
                       │                 │
                       │ • Database      │
                       │ • Eloquent      │
                       │ • Policies      │
                       │ • Scopes        │
                       └─────────────────┘
```

### Component Interaction Flow

1. **Admin Assignment Flow**: Admin → Filament Resource → Assignment Service → Database
2. **Manager Access Flow**: Manager → Policy Check → Scope Filter → Filtered Results
3. **Data Cascade Flow**: Building Assignment → Automatic Property Access → Related Resources

## Components and Interfaces

### Core Services

#### ManagerAssignmentService
```php
interface ManagerAssignmentServiceInterface
{
    public function assignBuildingsToManager(User $manager, array $buildingIds): void;
    public function assignPropertiesToManager(User $manager, array $propertyIds): void;
    public function removeBuildingAssignments(User $manager, array $buildingIds): void;
    public function removePropertyAssignments(User $manager, array $propertyIds): void;
    public function getManagerBuildings(User $manager): Collection;
    public function getManagerProperties(User $manager): Collection;
    public function validateAssignments(User $manager, array $resourceIds, string $resourceType): bool;
}
```

#### ManagerAccessControlService
```php
interface ManagerAccessControlServiceInterface
{
    public function getAccessibleBuildings(User $manager): Builder;
    public function getAccessibleProperties(User $manager): Builder;
    public function getAccessibleMeters(User $manager): Builder;
    public function getAccessibleInvoices(User $manager): Builder;
    public function getAccessibleTenants(User $manager): Builder;
    public function canAccessResource(User $manager, Model $resource): bool;
}
```

### Filament Components

#### Assignment Form Fields
- **BuildingManagersField**: Multi-select field for Building → Manager assignments
- **PropertyManagersField**: Multi-select field for Property → Manager assignments
- **ManagerBuildingsField**: Multi-select field for Manager → Building assignments
- **ManagerPropertiesField**: Multi-select field for Manager → Property assignments

#### Bulk Actions
- **AssignBuildingsToManagerAction**: Bulk assign selected buildings to a manager
- **AssignPropertiesToManagerAction**: Bulk assign selected properties to a manager
- **RemoveManagerAssignmentsAction**: Remove manager assignments from selected resources

### Policies and Scopes

#### Enhanced Policies
- **BuildingPolicy**: Extended with manager assignment checks
- **PropertyPolicy**: Extended with manager assignment checks
- **UserPolicy**: Enhanced for manager creation and assignment permissions

#### New Scopes
- **ManagerBuildingScope**: Filters buildings based on manager assignments
- **ManagerPropertyScope**: Filters properties based on manager assignments
- **ManagerResourceScope**: Generic scope for manager-accessible resources

## Data Models

### Database Schema Changes

#### New Pivot Tables

```sql
-- Building-Manager assignments
CREATE TABLE building_manager (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    building_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (building_id) REFERENCES buildings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_building_manager (building_id, user_id),
    INDEX idx_building_manager_building (building_id),
    INDEX idx_building_manager_user (user_id),
    INDEX idx_building_manager_assigned_by (assigned_by)
);

-- Property-Manager assignments
CREATE TABLE property_manager (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_property_manager (property_id, user_id),
    INDEX idx_property_manager_property (property_id),
    INDEX idx_property_manager_user (user_id),
    INDEX idx_property_manager_assigned_by (assigned_by)
);
```

### Model Relationships

#### User Model Extensions
```php
// Manager's assigned buildings
public function assignedBuildings(): BelongsToMany
{
    return $this->belongsToMany(Building::class, 'building_manager')
                ->withPivot(['assigned_at', 'assigned_by'])
                ->withTimestamps();
}

// Manager's assigned properties
public function assignedProperties(): BelongsToMany
{
    return $this->belongsToMany(Property::class, 'property_manager')
                ->withPivot(['assigned_at', 'assigned_by'])
                ->withTimestamps();
}

// Properties accessible through building assignments
public function buildingProperties(): HasManyThrough
{
    return $this->hasManyThrough(
        Property::class,
        Building::class,
        'id', // building.id
        'building_id', // property.building_id
        'id', // user.id
        'id' // building.id
    )->join('building_manager', 'buildings.id', '=', 'building_manager.building_id')
     ->where('building_manager.user_id', $this->id);
}
```

#### Building Model Extensions
```php
// Assigned managers
public function assignedManagers(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'building_manager')
                ->withPivot(['assigned_at', 'assigned_by'])
                ->withTimestamps();
}
```

#### Property Model Extensions
```php
// Assigned managers
public function assignedManagers(): BelongsToMany
{
    return $this->belongsToMany(User::class, 'property_manager')
                ->withPivot(['assigned_at', 'assigned_by'])
                ->withTimestamps();
}
```

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Analysis

Based on the requirements analysis, the following acceptance criteria are testable as properties:

**1.1 Manager Organization Assignment**
- Thoughts: This is a rule that should apply to all Manager creation operations. We can test that for any Admin and any Manager creation request, the resulting Manager belongs to the Admin's organization.
- Testable: yes - property

**1.4 Tenant Boundary Enforcement**
- Thoughts: This is a fundamental security property that must hold for all assignment operations. We can test that for any assignment operation, both the Manager and resource belong to the same tenant.
- Testable: yes - property

**2.2 Building Assignment Persistence**
- Thoughts: This is a data integrity property that should hold for all building assignment operations. We can test that assignments are correctly saved and retrievable.
- Testable: yes - property

**2.5 Building-to-Property Cascade**
- Thoughts: This is a business rule that should apply whenever a Manager is assigned to a Building. We can test that the Manager automatically gains access to all Properties in that Building.
- Testable: yes - property

**4.1 Manager Building Access Restriction**
- Thoughts: This is an access control property that should hold for all Manager queries. We can test that Managers only see buildings they are assigned to.
- Testable: yes - property

**4.2 Manager Property Access Combination**
- Thoughts: This is a complex access control property combining building-based and direct property assignments. We can test that the final property list is the union of both access types.
- Testable: yes - property

**5.1 Cross-Tenant Assignment Prevention**
- Thoughts: This is a security property that should prevent invalid assignments. We can test that assignment attempts across tenant boundaries are rejected.
- Testable: yes - property

**5.3 Cascade Deletion Integrity**
- Thoughts: This is a data integrity property ensuring referential integrity. We can test that when resources are deleted, related assignments are cleaned up.
- Testable: yes - property

### Property Definitions

**Property 1: Manager Organization Inheritance**
*For any* Admin and Manager creation operation, the created Manager should belong to the same organization (tenant_id) as the creating Admin
**Validates: Requirements 1.1, 1.4**

**Property 2: Assignment Tenant Boundary Enforcement**
*For any* assignment operation between a Manager and a resource (Building or Property), both entities should belong to the same tenant_id
**Validates: Requirements 5.1**

**Property 3: Building Assignment Persistence**
*For any* building assignment operation, the assignment should be correctly stored in the building_manager pivot table and be retrievable through the relationship
**Validates: Requirements 2.2**

**Property 4: Building-to-Property Access Cascade**
*For any* Manager assigned to a Building, the Manager should automatically have access to all Properties within that Building
**Validates: Requirements 2.5**

**Property 5: Manager Building Access Restriction**
*For any* Manager's building query, the results should contain only buildings explicitly assigned to that Manager through the building_manager pivot table
**Validates: Requirements 4.1**

**Property 6: Manager Property Access Combination**
*For any* Manager's property query, the results should be the union of properties from assigned buildings and directly assigned properties
**Validates: Requirements 4.2**

**Property 7: Assignment Deletion Cascade**
*For any* resource deletion (Manager, Building, or Property), all related assignments in pivot tables should be automatically removed
**Validates: Requirements 5.3**

**Property 8: Manager Resource Access Transitivity**
*For any* Manager with property access, the Manager should also have access to all related resources (Meters, Invoices, Tenants) from those properties
**Validates: Requirements 4.3, 4.4, 4.5**

## Error Handling

### Assignment Validation Errors
- **CrossTenantAssignmentException**: Thrown when attempting to assign resources across tenant boundaries
- **InvalidManagerRoleException**: Thrown when attempting to assign resources to non-Manager users
- **DuplicateAssignmentException**: Thrown when attempting to create duplicate assignments
- **OrphanedAssignmentException**: Thrown when assignments reference non-existent resources

### Access Control Errors
- **UnauthorizedResourceAccessException**: Thrown when Managers attempt to access unassigned resources
- **InsufficientAssignmentPrivilegesException**: Thrown when non-Admins attempt to create assignments

### Data Integrity Errors
- **AssignmentIntegrityException**: Thrown when assignment data becomes inconsistent
- **CascadeFailureException**: Thrown when cascade operations fail during resource deletion

## Testing Strategy

### Unit Testing
The system will use both unit tests and property-based tests to ensure comprehensive coverage:

**Unit Tests** will cover:
- Service method functionality with specific examples
- Form field validation and display
- Bulk action execution
- Policy authorization checks
- Model relationship definitions

**Property-Based Tests** will verify:
- Assignment operations maintain tenant boundaries across all inputs
- Access control rules hold for all Manager-resource combinations
- Data integrity is preserved during all CRUD operations
- Cascade rules work correctly for all resource hierarchies

### Property-Based Testing Framework
The system will use **Pest PHP** with **QuickCheck-style** property testing to run a minimum of 100 iterations per property. Each property-based test will be tagged with comments referencing the design document properties:

```php
/**
 * Feature: manager-assignment-system, Property 1: Manager Organization Inheritance
 * Validates: Requirements 1.1, 1.4
 */
it('ensures managers inherit admin organization', function () {
    // Property test implementation
});
```

### Integration Testing
- **Assignment Workflow Tests**: End-to-end testing of assignment creation through Filament interfaces
- **Access Control Integration Tests**: Verification that Manager access restrictions work across all resources
- **Cascade Behavior Tests**: Testing that building assignments properly cascade to properties
- **Multi-Tenant Isolation Tests**: Ensuring assignments don't leak across tenant boundaries

### Performance Testing
- **Query Performance Tests**: Ensuring Manager access queries perform efficiently with large datasets
- **Assignment Bulk Operation Tests**: Testing performance of bulk assignment operations
- **Cascade Operation Performance**: Measuring performance impact of cascade access rules