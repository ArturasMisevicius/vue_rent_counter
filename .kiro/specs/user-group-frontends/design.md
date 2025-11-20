# Design Document

## Overview

This design document outlines the implementation of comprehensive frontend interfaces for all user groups (Admin, Manager, Tenant) in the Vilnius Utilities Billing System. The design leverages Laravel's Blade templating engine with Alpine.js for reactive components, following the existing architectural patterns established in the codebase.

The implementation will create role-specific dashboards, navigation systems, and CRUD interfaces while enforcing authorization through Laravel policies. The design emphasizes consistency, reusability, and mobile responsiveness while maintaining the existing majestic monolith architecture.

## Architecture

### High-Level Structure

```
┌─────────────────────────────────────────────────────────┐
│                    Browser (Client)                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │  Alpine.js   │  │  Tailwind    │  │   Axios      │  │
│  │  (CDN)       │  │  CSS (CDN)   │  │              │  │
│  └──────────────┘  └──────────────┘  └──────────────┘  │
└─────────────────────────────────────────────────────────┘
                          ↕ HTTP
┌─────────────────────────────────────────────────────────┐
│              Laravel Application (Server)                │
│  ┌──────────────────────────────────────────────────┐  │
│  │              Routes (web.php)                     │  │
│  │  ┌────────────┐ ┌────────────┐ ┌────────────┐   │  │
│  │  │   Admin    │ │  Manager   │ │   Tenant   │   │  │
│  │  │   Routes   │ │   Routes   │ │   Routes   │   │  │
│  │  └────────────┘ └────────────┘ └────────────┘   │  │
│  └──────────────────────────────────────────────────┘  │
│                          ↕                               │
│  ┌──────────────────────────────────────────────────┐  │
│  │              Middleware Layer                     │  │
│  │  ┌────────────┐ ┌────────────┐ ┌────────────┐   │  │
│  │  │    Auth    │ │    Role    │ │   Tenant   │   │  │
│  │  │            │ │  Middleware│ │   Context  │   │  │
│  │  └────────────┘ └────────────┘ └────────────┘   │  │
│  └──────────────────────────────────────────────────┘  │
│                          ↕                               │
│  ┌──────────────────────────────────────────────────┐  │
│  │              Controllers                          │  │
│  │  ┌────────────┐ ┌────────────┐ ┌────────────┐   │  │
│  │  │   Admin    │ │  Manager   │ │   Tenant   │   │  │
│  │  │Controllers │ │Controllers │ │Controllers │   │  │
│  │  └────────────┘ └────────────┘ └────────────┘   │  │
│  └──────────────────────────────────────────────────┘  │
│                          ↕                               │
│  ┌──────────────────────────────────────────────────┐  │
│  │              Policies (Authorization)             │  │
│  │  ┌────────────┐ ┌────────────┐ ┌────────────┐   │  │
│  │  │  Invoice   │ │   Tariff   │ │   Meter    │   │  │
│  │  │   Policy   │ │   Policy   │ │  Reading   │   │  │
│  │  └────────────┘ └────────────┘ └────────────┘   │  │
│  └──────────────────────────────────────────────────┘  │
│                          ↕                               │
│  ┌──────────────────────────────────────────────────┐  │
│  │              Blade Views                          │  │
│  │  ┌────────────┐ ┌────────────┐ ┌────────────┐   │  │
│  │  │   Layouts  │ │ Components │ │Role-Specific│  │  │
│  │  │  (app.php) │ │  (Reusable)│ │    Views    │   │  │
│  │  └────────────┘ └────────────┘ └────────────┘   │  │
│  └──────────────────────────────────────────────────┘  │
│                          ↕                               │
│  ┌──────────────────────────────────────────────────┐  │
│  │         Models + TenantScope                      │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                          ↕
┌─────────────────────────────────────────────────────────┐
│                  SQLite Database                         │
└─────────────────────────────────────────────────────────┘
```

### Component Layers

1. **Routing Layer**: Role-based route groups with middleware protection
2. **Middleware Layer**: Authentication, role verification, tenant context
3. **Controller Layer**: Thin controllers delegating to services and models
4. **Policy Layer**: Authorization logic for resource access
5. **View Layer**: Blade templates with Alpine.js for interactivity
6. **Component Layer**: Reusable Blade components for consistency

## Components and Interfaces

### 1. Route Structure

```php
// routes/web.php

// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', UserController::class);
    Route::resource('providers', ProviderController::class);
    Route::resource('tariffs', TariffController::class);
    Route::resource('buildings', BuildingController::class);
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
});

// Manager Routes
Route::middleware(['auth', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('/dashboard', [ManagerDashboardController::class, 'index'])->name('dashboard');
    Route::resource('properties', PropertyController::class);
    Route::resource('buildings', BuildingController::class);
    Route::resource('meters', MeterController::class);
    Route::resource('meter-readings', MeterReadingController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::post('/invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])->name('invoices.finalize');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
});

// Tenant Routes
Route::middleware(['auth', 'role:tenant'])->prefix('tenant')->name('tenant.')->group(function () {
    Route::get('/dashboard', [TenantDashboardController::class, 'index'])->name('dashboard');
    Route::get('/property', [PropertyController::class, 'show'])->name('property.show');
    Route::get('/meters', [MeterController::class, 'index'])->name('meters.index');
    Route::get('/meters/{meter}', [MeterController::class, 'show'])->name('meters.show');
    Route::get('/meter-readings', [MeterReadingController::class, 'index'])->name('meter-readings.index');
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
});
```

### 2. Controller Structure

Controllers follow a thin controller pattern, delegating business logic to services and models:

```php
// Example: Admin User Controller
class UserController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);
        
        $users = User::with('tenant')
            ->paginate(20);
            
        return view('admin.users.index', compact('users'));
    }
    
    public function show(User $user)
    {
        $this->authorize('view', $user);
        
        return view('admin.users.show', compact('user'));
    }
    
    public function create()
    {
        $this->authorize('create', User::class);
        
        return view('admin.users.create');
    }
    
    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);
        
        $user = User::create($request->validated());
        
        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'User created successfully');
    }
    
    // ... update, destroy methods
}
```

### 3. Policy Integration

Policies are evaluated at multiple levels:

1. **Controller Level**: `$this->authorize()` calls
2. **View Level**: `@can` Blade directives
3. **Route Level**: Middleware for role-based access

```php
// In Blade views
@can('create', App\Models\User::class)
    <a href="{{ route('admin.users.create') }}" class="btn-primary">
        Create User
    </a>
@endcan

@can('update', $user)
    <a href="{{ route('admin.users.edit', $user) }}" class="btn-secondary">
        Edit
    </a>
@endcan
```

### 4. Blade Component Architecture

Reusable components for consistency:

```php
// x-card component
<div {{ $attributes->merge(['class' => 'bg-white shadow-md rounded-lg p-6']) }}>
    @if(isset($title))
        <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ $title }}</h3>
    @endif
    
    {{ $slot }}
</div>

// x-stat-card component
<div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                {{ $icon }}
            </div>
            <div class="ml-5 w-0 flex-1">
                <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                        {{ $label }}
                    </dt>
                    <dd class="text-3xl font-semibold text-gray-900">
                        {{ $value }}
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

// x-data-table component
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            {{ $header }}
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            {{ $slot }}
        </tbody>
    </table>
</div>

// x-status-badge component
@props(['status'])

@php
$classes = match($status) {
    'draft' => 'bg-yellow-100 text-yellow-800',
    'finalized' => 'bg-blue-100 text-blue-800',
    'paid' => 'bg-green-100 text-green-800',
    'active' => 'bg-green-100 text-green-800',
    'inactive' => 'bg-gray-100 text-gray-800',
    default => 'bg-gray-100 text-gray-800',
};
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $classes"]) }}>
    {{ $slot }}
</span>

// x-breadcrumbs component
<nav class="flex mb-4" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        {{ $slot }}
    </ol>
</nav>

// x-breadcrumb-item component
@props(['href' => null, 'active' => false])

<li {{ $attributes->merge(['class' => 'inline-flex items-center']) }}>
    @if($href && !$active)
        <a href="{{ $href }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-indigo-600">
            {{ $slot }}
        </a>
    @else
        <span class="text-sm font-medium text-gray-500">
            {{ $slot }}
        </span>
    @endif
</li>
```

## Data Models

The existing data models remain unchanged. The frontend interfaces will interact with:

- **User**: Authentication and role management
- **Property**: Rental units
- **Building**: Multi-unit buildings
- **Meter**: Utility meters
- **MeterReading**: Consumption records
- **Invoice**: Bills
- **InvoiceItem**: Line items
- **Tariff**: Pricing configurations
- **Provider**: Utility companies
- **Tenant**: Renters

All tenant-scoped models automatically apply the `TenantScope` global scope, ensuring data isolation.

## Correctness Properties


*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

Before defining the final properties, I've reviewed all testable criteria to eliminate redundancy:

- Properties 11.1, 11.2, 11.3, and 11.4 all relate to authorization enforcement and can be consolidated into comprehensive authorization properties
- Properties 5.1, 9.3, 10.1, and 11.5 all relate to multi-tenancy data isolation and can be consolidated
- Properties 2.3, 3.3, 5.3, and 6.2 all relate to validation and can be grouped by validation type
- Properties 3.4 and 7.4 both relate to immutability invariants

### Authorization and Access Control Properties

Property 1: Admin CRUD authorization
*For any* resource type and any admin user, the admin should be authorized to perform create, read, update, and delete operations on all resources within their tenant scope
**Validates: Requirements 1.5**

Property 2: Policy enforcement on resource access
*For any* user and any resource, when the user attempts to access the resource, the system should evaluate the appropriate policy and only grant access if the policy returns true
**Validates: Requirements 11.1**

Property 3: Unauthorized access returns 403
*For any* user and any resource, when a policy denies access, the HTTP response should have a 403 status code
**Validates: Requirements 11.2**

Property 4: Action button authorization
*For any* user and any action button in a view, the button should only be rendered if the user is authorized to perform that action according to the policy
**Validates: Requirements 11.3**

Property 5: Unauthorized action prevention
*For any* user attempting an unauthorized action, the system should prevent the action from executing and return an authorization error
**Validates: Requirements 11.4**

### Multi-Tenancy Data Isolation Properties

Property 6: Tenant scope filtering
*For any* tenant-scoped resource and any user, all queries should automatically filter results to only include resources belonging to the user's tenant
**Validates: Requirements 5.1, 9.3, 10.1, 11.5**

Property 7: Manager property isolation
*For any* manager user viewing the properties list, the returned properties should only include properties where the tenant_id matches the manager's tenant_id
**Validates: Requirements 5.1**

Property 8: Tenant invoice isolation
*For any* tenant user viewing invoices, the returned invoices should only include invoices where the tenant_renter_id matches the tenant's id
**Validates: Requirements 10.1**

Property 9: Tenant meter reading isolation
*For any* tenant user viewing meter readings, the returned readings should only include readings for meters associated with properties rented by that tenant
**Validates: Requirements 9.3**

### Validation Properties

Property 10: Email uniqueness validation
*For any* user creation or update request, if the email already exists in the database for a different user, the validation should fail with an appropriate error message
**Validates: Requirements 2.3**

Property 11: Tariff JSON schema validation
*For any* tariff creation or update request, the JSON configuration should be validated against the schema for the specified tariff type, and invalid configurations should be rejected
**Validates: Requirements 3.3**

Property 12: Property type validation
*For any* property creation request, the property_type field should be validated against the PropertyType enum, and invalid types should be rejected
**Validates: Requirements 5.3**

Property 13: Meter reading monotonicity validation
*For any* meter reading creation request, if the new reading value is less than the most recent reading value for that meter, the validation should fail
**Validates: Requirements 6.2**

### Immutability and Audit Properties

Property 14: Tariff version preservation
*For any* tariff update operation, the original tariff record should remain unchanged, and a new tariff record with a new effective date should be created
**Validates: Requirements 3.4**

Property 15: Invoice finalization immutability
*For any* invoice that has been finalized, all subsequent update attempts should be rejected, and the invoice data should remain unchanged
**Validates: Requirements 7.4**

Property 16: Meter reading correction audit trail
*For any* meter reading correction, an audit record should be created containing the original value, new value, correction reason, and timestamp
**Validates: Requirements 6.5**

### Calculation Properties

Property 17: Invoice charge calculation
*For any* invoice generation, the total amount should equal the sum of all line item totals, where each line item total equals quantity multiplied by unit_price
**Validates: Requirements 7.2**

### Navigation and UI Properties

Property 18: Breadcrumb presence
*For any* page in the application (excluding the dashboard), breadcrumbs should be rendered showing the navigation path from the dashboard to the current page
**Validates: Requirements 13.1**

Property 19: Breadcrumb hierarchy
*For any* nested resource detail page, the breadcrumbs should reflect the hierarchical relationship (e.g., Properties → Property #123 → Meters → Meter #456)
**Validates: Requirements 13.4**

### Error Handling Properties

Property 20: Validation error messages
*For any* form submission that fails validation, the response should include specific error messages for each invalid field
**Validates: Requirements 12.4**

### Data Consistency Properties

Property 21: Table pagination consistency
*For any* data table with pagination, the sum of items across all pages should equal the total count, and no items should appear on multiple pages
**Validates: Requirements 14.2**

Property 22: Form validation error display
*For any* form with validation errors, each field with an error should display the error message adjacent to the field in a consistent format
**Validates: Requirements 14.3**

## Error Handling

### HTTP Error Responses

1. **401 Unauthorized**: User is not authenticated
   - Redirect to login page
   - Preserve intended destination for post-login redirect

2. **403 Forbidden**: User is authenticated but not authorized
   - Display error page with explanation
   - Provide link back to dashboard
   - Log unauthorized access attempt

3. **404 Not Found**: Resource does not exist
   - Display error page with helpful message
   - Provide search functionality or link to list view

4. **422 Unprocessable Entity**: Validation failed
   - Return to form with validation errors
   - Preserve user input
   - Highlight invalid fields

5. **500 Internal Server Error**: Unexpected error
   - Display generic error page
   - Log full error details
   - Provide support contact information

### Validation Error Handling

```php
// In Form Requests
public function messages()
{
    return [
        'email.unique' => 'This email address is already registered.',
        'value.gte' => 'Meter reading must be greater than or equal to the previous reading.',
        'tariff_config.json' => 'The tariff configuration must be valid JSON.',
    ];
}

// In Blade views
@error('email')
    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
@enderror
```

### Flash Message Handling

```php
// Success messages
return redirect()
    ->route('admin.users.index')
    ->with('success', 'User created successfully');

// Error messages
return redirect()
    ->back()
    ->with('error', 'Unable to delete user with associated data')
    ->withInput();
```

## Testing Strategy

### Unit Testing

Unit tests will verify individual components and methods:

1. **Policy Tests**: Verify authorization logic for each policy method
2. **Validation Tests**: Test form request validation rules
3. **Component Tests**: Test Blade component rendering with various props
4. **Helper Tests**: Test any utility functions used in views

Example unit test:
```php
test('admin can view any user', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $policy = new UserPolicy();
    
    expect($policy->viewAny($admin))->toBeTrue();
});

test('tenant cannot view other tenants invoices', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    $user = User::factory()->create(['role' => UserRole::TENANT]);
    $user->tenant()->associate($tenant1);
    
    $invoice = Invoice::factory()->create(['tenant_renter_id' => $tenant2->id]);
    $policy = new InvoicePolicy();
    
    expect($policy->view($user, $invoice))->toBeFalse();
});
```

### Property-Based Testing

Property-based tests will verify universal properties across randomized inputs using Pest PHP. Each test will run a minimum of 100 iterations.

**Configuration**:
- Use Pest's `repeat()` function for property-based testing
- Generate random test data using factories
- Test properties across various user roles and resource states

Example property-based test:
```php
// Feature: user-group-frontends, Property 6: Tenant scope filtering
test('tenant scoped resources are automatically filtered by tenant_id', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    // Create properties for both tenants
    Property::factory()->count(5)->create(['tenant_id' => $tenant1->id]);
    Property::factory()->count(5)->create(['tenant_id' => $tenant2->id]);
    
    // Act as user from tenant1
    $user = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant1->id
    ]);
    
    $this->actingAs($user);
    
    // Query properties
    $properties = Property::all();
    
    // Assert all properties belong to tenant1
    expect($properties)->each(fn ($property) => 
        $property->tenant_id->toBe($tenant1->id)
    );
})->repeat(100);

// Feature: user-group-frontends, Property 13: Meter reading monotonicity validation
test('meter readings must be monotonically increasing', function () {
    $meter = Meter::factory()->create();
    $previousReading = MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'value' => 1000,
        'reading_date' => now()->subDays(1),
    ]);
    
    $invalidValue = fake()->numberBetween(0, 999);
    
    $request = new StoreMeterReadingRequest();
    $request->merge([
        'meter_id' => $meter->id,
        'value' => $invalidValue,
        'reading_date' => now(),
    ]);
    
    $validator = Validator::make($request->all(), $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('value'))->toBeTrue();
})->repeat(100);

// Feature: user-group-frontends, Property 15: Invoice finalization immutability
test('finalized invoices cannot be modified', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::DRAFT]);
    $invoice->finalize();
    
    $originalTotal = $invoice->total_amount;
    
    // Attempt to modify
    $invoice->total_amount = $originalTotal + 100;
    $result = $invoice->save();
    
    // Refresh from database
    $invoice->refresh();
    
    expect($invoice->total_amount)->toBe($originalTotal);
})->repeat(100);
```

### Feature Testing

Feature tests will verify complete user workflows:

1. **Authentication Flow**: Login, logout, role-based redirects
2. **CRUD Operations**: Create, read, update, delete for each resource
3. **Navigation**: Verify correct pages load for each role
4. **Authorization**: Verify unauthorized access is blocked
5. **Multi-tenancy**: Verify data isolation between tenants

Example feature test:
```php
test('admin can access user management interface', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    
    $response = $this->actingAs($admin)->get(route('admin.users.index'));
    
    $response->assertOk();
    $response->assertViewIs('admin.users.index');
    $response->assertSee('Users');
});

test('tenant cannot access admin routes', function () {
    $tenant = User::factory()->create(['role' => UserRole::TENANT]);
    
    $response = $this->actingAs($tenant)->get(route('admin.users.index'));
    
    $response->assertForbidden();
});

test('manager sees only their tenant properties', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $manager = User::factory()->create([
        'role' => UserRole::MANAGER,
        'tenant_id' => $tenant1->id
    ]);
    
    $property1 = Property::factory()->create(['tenant_id' => $tenant1->id]);
    $property2 = Property::factory()->create(['tenant_id' => $tenant2->id]);
    
    $response = $this->actingAs($manager)->get(route('manager.properties.index'));
    
    $response->assertOk();
    $response->assertSee($property1->address);
    $response->assertDontSee($property2->address);
});
```

### Browser Testing

While not part of the automated test suite, manual browser testing should verify:

1. **Responsive Design**: Test on mobile, tablet, and desktop viewports
2. **Alpine.js Interactions**: Verify dropdowns, modals, and dynamic content
3. **Form Validation**: Test client-side validation feedback
4. **Navigation**: Verify breadcrumbs and menu highlighting
5. **Accessibility**: Test keyboard navigation and screen reader compatibility

## Implementation Phases

### Phase 1: Core Infrastructure
1. Create missing policies for all resources
2. Implement reusable Blade components
3. Update layout with role-based navigation
4. Create breadcrumb component system

### Phase 2: Admin Interface
1. Implement admin dashboard with statistics
2. Create user management CRUD interface
3. Implement provider management interface
4. Create settings and audit log views

### Phase 3: Manager Interface
1. Implement manager dashboard with pending tasks
2. Create property and building management interfaces
3. Implement meter and meter reading interfaces
4. Create invoice generation and management interface
5. Implement reports interface

### Phase 4: Tenant Interface
1. Implement tenant dashboard with consumption overview
2. Create property and meter viewing interfaces
3. Implement invoice viewing and history interface
4. Create profile management interface

### Phase 5: Testing and Refinement
1. Write unit tests for policies and validation
2. Write property-based tests for correctness properties
3. Write feature tests for complete workflows
4. Perform browser testing and accessibility audit
5. Optimize performance and refine UI/UX

## Dependencies

- **Laravel 11**: Framework foundation
- **Blade**: Templating engine
- **Alpine.js**: Client-side interactivity (loaded via CDN)
- **Tailwind CSS**: Styling (loaded via CDN)
- **Pest PHP**: Testing framework with property-based testing support
- **Existing Models**: User, Property, Building, Meter, MeterReading, Invoice, Tariff, Provider
- **Existing Policies**: InvoicePolicy, TariffPolicy, MeterReadingPolicy
- **Existing Middleware**: RoleMiddleware, EnsureTenantContext

## Performance Considerations

1. **Eager Loading**: Use `with()` to prevent N+1 queries
2. **Pagination**: Limit list views to 20 items per page
3. **Caching**: Cache dashboard statistics for 5 minutes
4. **Indexes**: Ensure tenant_id columns are indexed
5. **Asset Loading**: Use CDN for Alpine.js and Tailwind CSS

## Security Considerations

1. **CSRF Protection**: All forms include CSRF tokens
2. **Authorization**: All routes protected by policies
3. **Multi-tenancy**: Automatic tenant scope filtering
4. **Input Validation**: Form requests validate all user input
5. **SQL Injection**: Use Eloquent ORM and parameter binding
6. **XSS Protection**: Blade automatically escapes output

## Accessibility Considerations

1. **Semantic HTML**: Use proper heading hierarchy and landmarks
2. **ARIA Labels**: Add labels to interactive elements
3. **Keyboard Navigation**: Ensure all functionality is keyboard accessible
4. **Color Contrast**: Meet WCAG AA standards
5. **Screen Reader Support**: Test with screen readers
6. **Focus Indicators**: Visible focus states on all interactive elements
