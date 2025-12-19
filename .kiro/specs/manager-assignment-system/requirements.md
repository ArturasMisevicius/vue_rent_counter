# Requirements Document

## Introduction

The Manager Assignment System enables hierarchical access control within the Tenanto rental management platform. This system allows Admins to create Managers and assign specific Buildings and Properties to them, ensuring Managers can only access resources they are explicitly assigned to manage. This addresses the current limitation where Managers have the same broad access as Admins within an organization.

## Glossary

- **Admin**: A user with role_id 2 who owns an organization and has full access to all buildings and properties within their tenant scope
- **Manager**: A user with role_id 3 who can access only buildings and properties explicitly assigned to them by an Admin
- **Assignment**: The relationship between a Manager and a Building or Property that grants the Manager access to that resource
- **Building Assignment**: A many-to-many relationship stored in the building_manager pivot table
- **Property Assignment**: A many-to-many relationship stored in the property_manager pivot table
- **Hierarchical Access**: Access control system where each role level can only see resources within their assigned scope
- **Tenant Scope**: The organizational boundary that ensures users only access data within their organization
- **Resource Cascade**: The principle that assigning a Manager to a Building automatically grants access to all Properties within that Building

## Requirements

### Requirement 1

**User Story:** As an Admin, I want to create Manager users within my organization, so that I can delegate building and property management responsibilities while maintaining control over access.

#### Acceptance Criteria

1. WHEN an Admin creates a new user with Manager role, THE system SHALL assign the Manager to the Admin's organization automatically
2. WHEN an Admin views the Users resource, THE system SHALL display all users within the Admin's tenant scope including newly created Managers
3. WHEN a Manager is created, THE system SHALL initialize them with no building or property assignments
4. WHEN an Admin creates a Manager, THE system SHALL enforce that the Manager belongs to the same tenant_id as the Admin
5. WHEN a Manager is created, THE system SHALL prevent the Manager from accessing any buildings or properties until explicitly assigned

### Requirement 2

**User Story:** As an Admin, I want to assign Buildings to Managers through multiple interfaces, so that I can efficiently control which buildings each Manager can access.

#### Acceptance Criteria

1. WHEN an Admin edits a Building, THE system SHALL display an "Assigned Managers" multi-select field populated with Managers from the same organization
2. WHEN an Admin selects Managers in the Building edit form, THE system SHALL save the assignments to the building_manager pivot table
3. WHEN an Admin selects multiple Buildings in the list view, THE system SHALL provide a "Assign to Manager" bulk action
4. WHEN an Admin uses the bulk assign action, THE system SHALL display a Manager selection dropdown filtered to the Admin's organization
5. WHEN building assignments are saved, THE system SHALL automatically grant the Manager access to all Properties within those Buildings

### Requirement 3

**User Story:** As an Admin, I want to assign individual Properties to Managers, so that I can provide granular access control beyond building-level assignments.

#### Acceptance Criteria

1. WHEN an Admin edits a Property, THE system SHALL display an "Assigned Managers" multi-select field populated with Managers from the same organization
2. WHEN an Admin selects Managers in the Property edit form, THE system SHALL save the assignments to the property_manager pivot table
3. WHEN an Admin selects multiple Properties in the list view, THE system SHALL provide a "Assign to Manager" bulk action
4. WHEN an Admin uses the property bulk assign action, THE system SHALL display a Manager selection dropdown filtered to the Admin's organization
5. WHEN property assignments are saved, THE system SHALL grant the Manager direct access to those Properties regardless of building assignments

### Requirement 4

**User Story:** As a Manager, I want to see only the buildings and properties assigned to me, so that I can focus on my specific management responsibilities without being overwhelmed by irrelevant data.

#### Acceptance Criteria

1. WHEN a Manager accesses the Buildings resource, THE system SHALL display only buildings assigned to them via the building_manager pivot table
2. WHEN a Manager accesses the Properties resource, THE system SHALL display properties from assigned buildings plus individually assigned properties
3. WHEN a Manager accesses the Meters resource, THE system SHALL display only meters from properties they have access to
4. WHEN a Manager accesses the Invoices resource, THE system SHALL display only invoices for properties they have access to
5. WHEN a Manager accesses the Tenants resource, THE system SHALL display only tenants from properties they have access to

### Requirement 5

**User Story:** As a system administrator, I want to ensure data integrity and security in the assignment system, so that tenant boundaries are respected and unauthorized access is prevented.

#### Acceptance Criteria

1. WHEN any assignment is created, THE system SHALL verify that both the Manager and the resource belong to the same tenant_id
2. WHEN a Manager is deleted, THE system SHALL remove all building and property assignments automatically
3. WHEN a Building or Property is deleted, THE system SHALL remove all associated manager assignments automatically
4. WHEN assignment queries are executed, THE system SHALL enforce tenant scope to prevent cross-tenant data access
5. WHEN Manager access is evaluated, THE system SHALL combine building-based and direct property assignments to determine the complete access scope