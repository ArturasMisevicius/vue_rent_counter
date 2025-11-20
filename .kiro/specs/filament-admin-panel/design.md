# Design Document

## Overview

This design document outlines the integration of Filament PHP framework into the Vilnius Utilities Billing System. Filament will provide a modern, feature-rich administration panel that replaces the existing Blade-based admin interface while maintaining compatibility with the current multi-tenancy architecture, role-based access control, and existing validation logic.

The integration will leverage Filament's resource-based architecture to create CRUD interfaces for all major domain entities (meter readings, properties, buildings, invoices, tariffs, users, and providers). The design ensures seamless integration with existing Laravel components including Eloquent models, form requests, policies, and the tenant scoping system.

## Architecture

### High-Level Architecture

The Filament integration follows a layered architecture that sits on top of the existing Laravel application:

```
┌─────────────────────────────────────────┐
│         Filament Admin Panel            │
│  (Resources, Pages, Widgets, Actions)   │
├─────────────────────────────────────────┤
│      Existing Laravel Application       │
│  (Models, Policies, Form Requests)      │
├─────────────────────────────────────────┤
│         Database Layer (SQLite)         │
└─────────────────────────────────────────┘
```

### Panel Configuration

Filament will be configured as a single admin panel accessible at `/admin` route. The panel will:
- Use the existing `web` authentication guard
- Authenticate against the `User` model
- Apply role-based navigation and resource visibility
- Integrate with the existing `TenantScope` for data isolation

### Resource Architecture

Each Filament resource will map to an existing Eloquent model and will:
- Define table columns for list views
- Define form schemas for create/edit operations
- Integrate with existing Form Request validation
- Apply existing authorization policies
- Respect tenant scope where applicable

## Components and Interfaces

### Core Filament Components

#### 1. Panel Provider (`AdminPanelProvider`)

The main panel configuration class that defines:
- Panel ID and path (`/admin`)
- Authentication configuration
- Navigation structure
- Theme and branding
- Middleware stack

```php
public function panel(Panel $panel): Panel
{
    return $panel
        ->id('admin')
        ->path('admin')
        ->login()
        ->authGuard('web')
        ->colors([...])
        ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
        ->middleware([...])
        ->authMiddleware([...]);
}
```

#### 2. Resources

Each resource extends `Filament\Resources\Resource` and defines:
- **Model**: The Eloquent model class
- **Table**: Column definitions for list view
- **Form**: Field definitions for create/edit
- **Pages**: Custom page classes (List, Create, Edit, View)
- **Navigation**: Icon, label, group, sort order

### Resource Implementations

#### MeterReadingResource

**Table Columns:**
- Property address (via relationship)
- Meter type (badge with color coding)
- Reading date (formatted)
- Reading value (numeric)
- Consumption (calculated, numeric)
- Created at (datetime)

**Form Fields:**
- Property (Select relationship with search)
- Meter (Select relationship, filtered by selected property)
- Reading date (DatePicker)
- Reading value (TextInput, numeric, min: 0)

**Special Behaviors:**
- Apply tenant scope automatically
- Integrate `StoreMeterReadingRequest` validation
- Integrate `UpdateMeterReadingRequest` validation
- Display consumption calculation in table
- Filter meters by property selection in form

#### PropertyResource

**Table Columns:**
- Address (searchable)
- Property type (badge)
- Building name (via relationship)
- Tenant name (via relationship)
- Area (numeric with unit)
- Created at (datetime)

**Form Fields:**
- Address (TextInput, required)
- Property type (Select from PropertyType enum)
- Building (Select relationship, nullable)
- Area (TextInput, numeric, suffix: "m²")
- Tenant (Select relationship, nullable)

**Special Behaviors:**
- Apply tenant scope automatically
- Integrate `StorePropertyRequest` validation
- Integrate `UpdatePropertyRequest` validation
- Auto-populate tenant_id from session

#### InvoiceResource

**Table Columns:**
- Invoice number (searchable)
- Property address (via relationship)
- Billing period (formatted date range)
- Total amount (money format)
- Status (badge with color coding)
- Created at (datetime)

**Form Fields:**
- Property (Select relationship with search)
- Billing period start (DatePicker)
- Billing period end (DatePicker)
- Status (Select from InvoiceStatus enum, disabled after finalization)

**Special Behaviors:**
- Apply tenant scope automatically
- Display invoice items as relationship manager
- Integrate `StoreInvoiceRequest` validation
- Integrate `FinalizeInvoiceRequest` for finalization action
- Disable editing when status is finalized
- Custom action for invoice finalization
- Filter by status (draft, finalized, paid)

#### TariffResource

**Table Columns:**
- Provider name (via relationship)
- Service type (badge)
- Tariff type (badge)
- Effective from (date)
- Effective to (date, nullable)
- Is active (boolean icon)

**Form Fields:**
- Provider (Select relationship)
- Service type (Select from ServiceType enum)
- Tariff type (Select from TariffType enum)
- Effective from (DatePicker)
- Effective to (DatePicker, nullable)
- Tariff configuration (JSON editor or repeater for time-of-use rates)

**Special Behaviors:**
- Integrate `StoreTariffRequest` validation
- Custom form component for tariff_config JSON
- Display time-of-use rates in repeater format
- Validate time ranges using `TimeRangeValidator`

#### UserResource

**Table Columns:**
- Name (searchable)
- Email (searchable)
- Role (badge)
- Tenant name (via relationship, nullable)
- Created at (datetime)

**Form Fields:**
- Name (TextInput, required)
- Email (TextInput, email, required)
- Password (TextInput, password, required on create)
- Role (Select from UserRole enum)
- Tenant (Select relationship, required if role is manager or tenant)

**Special Behaviors:**
- Integrate `StoreUserRequest` validation
- Integrate `UpdateUserRequest` validation
- Conditional tenant field visibility based on role
- Hash password before saving
- Admin-only access

#### BuildingResource

**Table Columns:**
- Name (searchable)
- Address (searchable)
- Total area (numeric with unit)
- Property count (count relationship)
- Created at (datetime)

**Form Fields:**
- Name (TextInput, required)
- Address (TextInput, required)
- Total area (TextInput, numeric, suffix: "m²")

**Special Behaviors:**
- Apply tenant scope automatically
- Integrate `StoreBuildingRequest` validation
- Integrate `UpdateBuildingRequest` validation
- Display properties as relationship manager

#### ProviderResource

**Table Columns:**
- Name (searchable)
- Service types (badge list)
- Contact information (text)
- Tariff count (count relationship)

**Form Fields:**
- Name (TextInput, required)
- Service types (CheckboxList from ServiceType enum)
- Contact information (Textarea)

**Special Behaviors:**
- Display tariffs as relationship manager
- Admin-only access

## Data Models

The design leverages existing Eloquent models without modification:

- `MeterReading` - With TenantScope
- `Property` - With TenantScope
- `Invoice` - With TenantScope
- `InvoiceItem` - With TenantScope (via invoice)
- `Tariff` - No tenant scope (system-wide)
- `User` - With TenantScope (except admins)
- `Building` - With TenantScope
- `Provider` - No tenant scope (system-wide)
- `Meter` - With TenantScope

All models retain their existing relationships, scopes, and business logic.


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Tenant scope isolation for meter readings

*For any* manager user and their associated tenant, accessing the meter readings resource should only display meter readings that belong to properties within that tenant's scope.

**Validates: Requirements 2.1, 2.7**

### Property 2: Meter reading validation consistency

*For any* meter reading data submitted through the Filament form, the validation rules applied should be identical to those defined in `StoreMeterReadingRequest` and `UpdateMeterReadingRequest`.

**Validates: Requirements 2.4, 2.6**

### Property 3: Monotonicity enforcement

*For any* meter and any new reading value, if the new reading is less than the most recent reading for that meter, the system should reject the submission.

**Validates: Requirements 2.5**

### Property 4: Tenant scope isolation for properties

*For any* manager user and their associated tenant, accessing the properties resource should only display properties that belong to that tenant's scope.

**Validates: Requirements 3.1**

### Property 5: Property validation consistency

*For any* property data submitted through the Filament form, the validation rules applied should be identical to those defined in `StorePropertyRequest` and `UpdatePropertyRequest`.

**Validates: Requirements 3.4**

### Property 6: Automatic tenant assignment

*For any* property created by a manager user, the tenant_id should automatically be set to match the manager's associated tenant_id.

**Validates: Requirements 3.5**

### Property 7: Tenant scope isolation for invoices

*For any* manager user and their associated tenant, accessing the invoices resource should only display invoices that belong to properties within that tenant's scope.

**Validates: Requirements 4.1**

### Property 8: Invoice items visibility

*For any* invoice viewed in the Filament panel, all associated invoice items should be displayed with their snapshotted pricing details.

**Validates: Requirements 4.3**

### Property 9: Invoice finalization immutability

*For any* invoice with status "finalized", attempts to modify the invoice or its items should be rejected by the system.

**Validates: Requirements 4.5**

### Property 10: Invoice status filtering

*For any* status filter applied to the invoices resource, only invoices matching that specific status should be returned in the results.

**Validates: Requirements 4.6**

### Property 11: Tariff validation consistency

*For any* tariff data submitted through the Filament form, the validation rules applied should be identical to those defined in `StoreTariffRequest`.

**Validates: Requirements 5.5**

### Property 12: Tariff configuration JSON persistence

*For any* tariff saved through the Filament panel, the tariff_config column should contain valid JSON that can be decoded without errors.

**Validates: Requirements 5.6**

### Property 13: User validation consistency

*For any* user data submitted through the Filament form, the validation rules applied should be identical to those defined in `StoreUserRequest` and `UpdateUserRequest`.

**Validates: Requirements 6.4**

### Property 14: Conditional tenant requirement for non-admin users

*For any* user with role "manager" or "tenant", the tenant_id field must be present and non-null when the user is saved.

**Validates: Requirements 6.5**

### Property 15: Null tenant allowance for admin users

*For any* user with role "admin", the tenant_id field can be null when the user is saved.

**Validates: Requirements 6.6**

### Property 16: Tenant scope isolation for buildings

*For any* non-admin user and their associated tenant, accessing the buildings resource should only display buildings that belong to that tenant's scope.

**Validates: Requirements 7.1**

### Property 17: Building validation consistency

*For any* building data submitted through the Filament form, the validation rules applied should be identical to those defined in `StoreBuildingRequest` and `UpdateBuildingRequest`.

**Validates: Requirements 7.4**

### Property 18: Building-property relationship visibility

*For any* building viewed in the Filament panel, all associated properties should be displayed in the relationship manager.

**Validates: Requirements 7.5**

### Property 19: Provider-tariff relationship visibility

*For any* provider viewed in the Filament panel, all associated tariffs should be displayed in the relationship manager.

**Validates: Requirements 8.4**

### Property 20: Tenant role resource restriction

*For any* user with role "tenant" who logs into the Filament panel, they should only be able to access tenant-specific resources (their own invoices, meter readings, and property information).

**Validates: Requirements 9.1**

### Property 21: Manager role resource access with tenant scope

*For any* user with role "manager" who logs into the Filament panel, they should be able to access operational resources (properties, meters, meter readings, invoices) but only for entities within their tenant scope.

**Validates: Requirements 9.2**

### Property 22: Admin role full resource access

*For any* user with role "admin" who logs into the Filament panel, they should be able to access all resources including system configuration resources (users, providers, tariffs).

**Validates: Requirements 9.3**

### Property 23: Authorization denial for restricted resources

*For any* user attempting to access a resource or perform an action they don't have permission for, the system should deny access and return an appropriate error response.

**Validates: Requirements 9.4**

### Property 24: Policy integration

*For any* resource action (view, create, update, delete) performed in the Filament panel, the corresponding policy method from the existing policy classes should be invoked to determine authorization.

**Validates: Requirements 9.5**

## Error Handling

### Validation Errors

Filament provides built-in validation error handling that displays errors inline with form fields. The integration will leverage this by:

1. **Form Request Integration**: Each resource will use the `rules()` method from existing Form Request classes
2. **Custom Validation Messages**: Preserve existing custom validation messages from Form Requests
3. **Real-time Validation**: Utilize Filament's reactive validation for immediate feedback

### Authorization Errors

When a user attempts to access a restricted resource or action:

1. **Policy Enforcement**: Filament will call existing policy methods (`viewAny`, `view`, `create`, `update`, `delete`)
2. **403 Responses**: Unauthorized attempts will result in 403 Forbidden responses
3. **User-Friendly Messages**: Display clear error messages explaining why access was denied
4. **Redirect Handling**: Redirect unauthorized users to appropriate pages

### Tenant Scope Violations

If tenant scope isolation is violated:

1. **Global Scope Enforcement**: The existing `TenantScope` will automatically filter queries
2. **404 Responses**: Attempts to access out-of-scope resources will result in 404 Not Found
3. **Logging**: Log potential security violations for audit purposes

### Data Integrity Errors

For database constraint violations:

1. **Foreign Key Violations**: Display user-friendly messages for relationship constraints
2. **Unique Constraint Violations**: Show clear messages for duplicate entries
3. **Monotonicity Violations**: Specific error messages for meter reading decreases

## Testing Strategy

### Unit Testing

Unit tests will verify individual Filament components:

1. **Resource Configuration Tests**
   - Verify table columns are correctly defined
   - Verify form fields are correctly defined
   - Verify navigation configuration

2. **Form Validation Tests**
   - Test that Filament forms apply existing Form Request rules
   - Test custom validation logic integration
   - Test conditional field visibility

3. **Policy Integration Tests**
   - Verify policy methods are called for resource actions
   - Test authorization logic for different user roles

### Property-Based Testing

Property-based tests will verify universal behaviors across all inputs using Pest PHP with 100+ iterations:

1. **Tenant Isolation Properties**
   - Generate random users with different tenants
   - Generate random resources (properties, meters, invoices)
   - Verify users only see resources within their tenant scope

2. **Validation Consistency Properties**
   - Generate random valid and invalid form data
   - Verify Filament validation matches Form Request validation
   - Test across all resources

3. **Authorization Properties**
   - Generate random users with different roles
   - Generate random resource access attempts
   - Verify authorization decisions match policy logic

4. **Data Integrity Properties**
   - Generate random meter readings
   - Verify monotonicity is enforced
   - Verify tenant_id is automatically applied

5. **Relationship Properties**
   - Generate random related entities
   - Verify relationships are correctly displayed
   - Verify cascade behaviors

### Integration Testing

Integration tests will verify end-to-end workflows:

1. **Authentication Flow**
   - Test login with different user roles
   - Verify session management
   - Test logout functionality

2. **CRUD Operations**
   - Test complete create-read-update-delete cycles for each resource
   - Verify data persistence
   - Verify relationship updates

3. **Multi-Tenancy Workflows**
   - Test data isolation across multiple tenants
   - Verify tenant switching (if applicable)
   - Test admin access to all tenants

4. **Invoice Workflow**
   - Test draft invoice creation
   - Test invoice item management
   - Test invoice finalization
   - Verify immutability after finalization

### Testing Configuration

- **Framework**: Pest PHP v2.36+
- **Minimum Iterations**: 100 per property-based test
- **Test Tagging**: Each property test tagged with `// Feature: filament-admin-panel, Property X: [description]`
- **Database**: Use SQLite in-memory database for tests
- **Factories**: Leverage existing model factories for test data generation

## Implementation Phases

### Phase 1: Foundation (Installation & Configuration)

1. Install Filament packages via Composer
2. Publish Filament assets and configuration
3. Configure admin panel provider
4. Set up authentication integration
5. Configure theme and branding

### Phase 2: Core Resources (Operational Data)

1. Implement MeterReadingResource
2. Implement PropertyResource
3. Implement MeterResource
4. Implement InvoiceResource with relationship manager for invoice items
5. Test tenant scope isolation for all resources

### Phase 3: Configuration Resources (System Data)

1. Implement TariffResource with JSON configuration handling
2. Implement ProviderResource
3. Implement BuildingResource
4. Test admin-only access controls

### Phase 4: User Management

1. Implement UserResource
2. Implement role-based field visibility
3. Implement conditional tenant requirement
4. Test user creation and role assignment

### Phase 5: Authorization & Access Control

1. Integrate existing policy classes with Filament resources
2. Implement role-based navigation visibility
3. Implement resource-level authorization
4. Test authorization across all user roles

### Phase 6: Advanced Features

1. Implement custom actions (invoice finalization)
2. Implement bulk actions where appropriate
3. Implement filters and search functionality
4. Implement relationship managers for complex relationships

### Phase 7: Cleanup & Optimization

1. Remove obsolete Vue.js configuration
2. Clean up Vite configuration
3. Remove unnecessary npm scripts
4. Optimize Filament configuration
5. Update documentation

## Dependencies

### New Dependencies

- `filament/filament`: ^3.0 - Main Filament package
- `filament/forms`: ^3.0 - Form builder (included with filament/filament)
- `filament/tables`: ^3.0 - Table builder (included with filament/filament)
- `filament/notifications`: ^3.0 - Notification system (included with filament/filament)

### Existing Dependencies (Retained)

- `laravel/framework`: ^11.0
- `pestphp/pest`: ^2.36
- `pestphp/pest-plugin-laravel`: ^2.4
- Alpine.js (CDN) - Required for Filament and existing Blade components

### Dependencies to Remove

- Vue.js related packages (if any)
- Unnecessary Vite plugins for SPA builds
- Frontend build tools not required for Filament

## Configuration Files

### New Configuration Files

- `config/filament.php` - Main Filament configuration (if needed for customization)
- `app/Providers/Filament/AdminPanelProvider.php` - Panel provider

### Modified Configuration Files

- `composer.json` - Add Filament dependencies
- `package.json` - Remove unnecessary frontend dependencies
- `vite.config.js` - Simplify for Filament asset compilation

### Configuration Files to Remove

- Vue.js specific configuration files
- SPA-specific build configurations

## Security Considerations

### Authentication

- Use existing Laravel authentication system
- Leverage existing `User` model and authentication guards
- Maintain existing password hashing and security measures

### Authorization

- Integrate existing policy classes without modification
- Respect existing role-based access control
- Maintain tenant scope isolation through global scopes

### Data Protection

- Ensure tenant scope is applied to all queries automatically
- Prevent cross-tenant data access through URL manipulation
- Log authorization failures for security monitoring

### Input Validation

- Use existing Form Request validation rules
- Sanitize user input through Filament's built-in mechanisms
- Prevent SQL injection through Eloquent ORM

### Session Security

- Use existing session configuration
- Implement CSRF protection (built into Laravel/Filament)
- Configure appropriate session timeouts

## Performance Considerations

### Query Optimization

- Eager load relationships in resource table queries
- Use pagination for large datasets (Filament default)
- Index tenant_id columns for efficient filtering

### Caching

- Cache Filament navigation structure
- Cache policy results where appropriate
- Use Laravel's query result caching for expensive queries

### Asset Loading

- Minimize custom CSS/JS additions
- Leverage Filament's optimized asset pipeline
- Use CDN for Alpine.js (already in place)

### Database

- Maintain existing SQLite WAL mode configuration
- Ensure foreign key constraints remain enabled
- Monitor query performance with tenant scope

## Migration Strategy

### Gradual Rollout

1. **Phase 1**: Deploy Filament alongside existing Blade admin interface
2. **Phase 2**: Train users on new Filament interface
3. **Phase 3**: Gradually migrate users to Filament
4. **Phase 4**: Deprecate old Blade admin routes
5. **Phase 5**: Remove old Blade admin views and controllers

### Rollback Plan

- Maintain existing Blade admin interface during initial deployment
- Keep old routes active until Filament is fully validated
- Document rollback procedures in case of critical issues

### Data Migration

No data migration required - Filament works with existing database schema and models.

## Documentation Requirements

### Developer Documentation

- Installation and setup guide
- Resource creation guide
- Custom action implementation guide
- Testing guide for Filament components

### User Documentation

- Admin user guide for system configuration
- Manager user guide for operational tasks
- Tenant user guide for viewing personal data

### API Documentation

Not applicable - Filament is a UI framework, existing API endpoints remain unchanged.
