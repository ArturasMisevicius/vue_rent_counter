# Design Document

## Overview

The Authentication Testing System is a comprehensive testing infrastructure for the Vilnius Utilities Billing System that enables developers to quickly create, populate, and test the application with realistic data across all user roles (Admin, Manager, Tenant). The system provides automated seeders, test commands, and documented test scenarios to verify authentication, authorization, multi-tenancy isolation, and complete business workflows.

Key objectives:
- **Rapid test environment setup**: Single command to reset and populate database with test data
- **Known test credentials**: Predefined users with simple passwords for manual testing
- **Complete test data**: Buildings, properties, meters, readings, tariffs, and invoices spanning multiple months
- **Role-based testing**: Verify each role can access only permitted resources
- **Multi-tenancy verification**: Ensure data isolation between different property management companies
- **Workflow validation**: Test complete flows from meter reading entry to invoice generation

The system builds upon the existing Laravel authentication and authorization infrastructure, adding comprehensive test data generation and documentation to enable thorough manual and automated testing.

## Architecture

### Component Overview

```
┌─────────────────────────────────────────────────┐
│         Test Command (Artisan)                   │
│  - php artisan test:setup                        │
│  - Orchestrates full test environment setup     │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│         Test Seeders Layer                       │
│  - TestUsersSeeder (known credentials)          │
│  - TestBuildingsSeeder (realistic addresses)    │
│  - TestPropertiesSeeder (apartments & houses)   │
│  - TestMetersSeeder (all utility types)         │
│  - TestMeterReadingsSeeder (3+ months history)  │
│  - TestTariffsSeeder (all providers)            │
│  - TestInvoicesSeeder (all states)              │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│         Existing Application Layer               │
│  - Authentication (LoginController)              │
│  - Authorization (Policies, Gates)               │
│  - Multi-tenancy (TenantScope)                   │
│  - Business Logic (Services)                     │
└─────────────────────────────────────────────────┘
                      ↓
┌─────────────────────────────────────────────────┐
│         Test Documentation                       │
│  - Test scenarios for each role                  │
│  - Expected results                              │
│  - API testing examples (curl commands)          │
│  - Troubleshooting guide                         │
└─────────────────────────────────────────────────┘
```

### Test Data Structure

The system creates a multi-tenant test environment with the following structure:

**Tenant 1 (Property Management Company A)**
- Admin user: admin@test.com
- Manager user: manager@test.com
- 2 buildings with 6 properties total
- 3 tenant users with active leases
- All meters (electricity, water, heating) for each property
- 3 months of meter readings
- Sample invoices (draft, finalized, paid)

**Tenant 2 (Property Management Company B)**
- Manager user: manager2@test.com
- 1 building with 3 properties
- 2 tenant users with active leases
- All meters for each property
- 3 months of meter readings
- Sample invoices

This structure enables testing of:
- Cross-tenant access prevention
- Role-based access control
- Complete billing workflows
- Multi-property tenant scenarios


## Components and Interfaces

### Test Command

**TestSetupCommand**: Artisan command to reset and populate test environment

```php
class TestSetupCommand extends Command
{
    protected $signature = 'test:setup {--fresh : Drop all tables and recreate}';
    protected $description = 'Set up test environment with sample data';
    
    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->call('migrate:fresh');
        }
        
        $this->call('db:seed', ['--class' => 'TestDatabaseSeeder']);
        
        $this->displayTestCredentials();
        
        return Command::SUCCESS;
    }
    
    private function displayTestCredentials(): void
    {
        $this->info('Test users created:');
        $this->table(
            ['Role', 'Email', 'Password', 'Tenant ID'],
            [
                ['Admin', 'admin@test.com', 'password', '1'],
                ['Manager', 'manager@test.com', 'password', '1'],
                ['Manager', 'manager2@test.com', 'password', '2'],
                ['Tenant', 'tenant@test.com', 'password', '1'],
                ['Tenant', 'tenant2@test.com', 'password', '1'],
                ['Tenant', 'tenant3@test.com', 'password', '2'],
            ]
        );
    }
}
```

### Test Seeders

#### TestUsersSeeder

Creates users with known credentials for each role:

```php
class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user (tenant_id = 1, but can access all data)
        User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'tenant_id' => 1,
        ]);
        
        // Manager for tenant 1
        User::create([
            'name' => 'Test Manager',
            'email' => 'manager@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
            'tenant_id' => 1,
        ]);
        
        // Manager for tenant 2
        User::create([
            'name' => 'Test Manager 2',
            'email' => 'manager2@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
            'tenant_id' => 2,
        ]);
        
        // Tenant users for tenant 1
        User::create([
            'name' => 'Test Tenant',
            'email' => 'tenant@test.com',
            'password' => Hash::make('password'),
            'role' => UserRole::TENANT,
            'tenant_id' => 1,
        ]);
        
        // Additional tenant users...
    }
}
```


#### TestBuildingsSeeder

Creates buildings with realistic Vilnius addresses:

```php
class TestBuildingsSeeder extends Seeder
{
    public function run(): void
    {
        // Tenant 1 buildings
        Building::create([
            'tenant_id' => 1,
            'address' => 'Gedimino pr. 15, Vilnius',
            'total_apartments' => 12,
            'gyvatukas_summer_average' => 150.50,
            'gyvatukas_last_calculated' => Carbon::create(2024, 10, 1),
        ]);
        
        Building::create([
            'tenant_id' => 1,
            'address' => 'Konstitucijos pr. 7, Vilnius',
            'total_apartments' => 8,
            'gyvatukas_summer_average' => 120.30,
            'gyvatukas_last_calculated' => Carbon::create(2024, 10, 1),
        ]);
        
        // Tenant 2 building
        Building::create([
            'tenant_id' => 2,
            'address' => 'Pilies g. 22, Vilnius',
            'total_apartments' => 6,
            'gyvatukas_summer_average' => 95.75,
            'gyvatukas_last_calculated' => Carbon::create(2024, 10, 1),
        ]);
    }
}
```

#### TestPropertiesSeeder

Creates properties (apartments and houses) for each building:

```php
class TestPropertiesSeeder extends Seeder
{
    public function run(): void
    {
        $building1 = Building::where('tenant_id', 1)->first();
        
        // Create apartments in building 1
        for ($i = 1; $i <= 6; $i++) {
            Property::create([
                'tenant_id' => 1,
                'address' => "{$building1->address}, Apt {$i}",
                'type' => PropertyType::APARTMENT,
                'area_sqm' => fake()->numberBetween(45, 85),
                'building_id' => $building1->id,
            ]);
        }
        
        // Create standalone house
        Property::create([
            'tenant_id' => 1,
            'address' => 'Žvėryno g. 5, Vilnius',
            'type' => PropertyType::HOUSE,
            'area_sqm' => 150,
            'building_id' => null,
        ]);
        
        // Similar for other buildings...
    }
}
```

#### TestMetersSeeder

Creates all meter types for each property:

```php
class TestMetersSeeder extends Seeder
{
    public function run(): void
    {
        $properties = Property::all();
        
        foreach ($properties as $property) {
            // Electricity meter (supports day/night zones)
            Meter::create([
                'tenant_id' => $property->tenant_id,
                'serial_number' => 'EL-' . str_pad($property->id, 6, '0', STR_PAD_LEFT),
                'type' => MeterType::ELECTRICITY,
                'property_id' => $property->id,
                'installation_date' => Carbon::now()->subYears(2),
                'supports_zones' => true,
            ]);
            
            // Cold water meter
            Meter::create([
                'tenant_id' => $property->tenant_id,
                'serial_number' => 'WC-' . str_pad($property->id, 6, '0', STR_PAD_LEFT),
                'type' => MeterType::WATER_COLD,
                'property_id' => $property->id,
                'installation_date' => Carbon::now()->subYears(2),
                'supports_zones' => false,
            ]);
            
            // Hot water meter
            Meter::create([
                'tenant_id' => $property->tenant_id,
                'serial_number' => 'WH-' . str_pad($property->id, 6, '0', STR_PAD_LEFT),
                'type' => MeterType::WATER_HOT,
                'property_id' => $property->id,
                'installation_date' => Carbon::now()->subYears(2),
                'supports_zones' => false,
            ]);
            
            // Heating meter (if apartment in building)
            if ($property->building_id) {
                Meter::create([
                    'tenant_id' => $property->tenant_id,
                    'serial_number' => 'HT-' . str_pad($property->id, 6, '0', STR_PAD_LEFT),
                    'type' => MeterType::HEATING,
                    'property_id' => $property->id,
                    'installation_date' => Carbon::now()->subYears(2),
                    'supports_zones' => false,
                ]);
            }
        }
    }
}
```


#### TestMeterReadingsSeeder

Creates 3 months of historical meter readings:

```php
class TestMeterReadingsSeeder extends Seeder
{
    public function run(): void
    {
        $meters = Meter::all();
        $manager = User::where('role', UserRole::MANAGER)->first();
        
        foreach ($meters as $meter) {
            $currentValue = 1000; // Starting value
            
            // Create readings for last 3 months
            for ($month = 3; $month >= 0; $month--) {
                $readingDate = Carbon::now()->subMonths($month)->startOfMonth();
                
                if ($meter->supports_zones) {
                    // Day zone reading
                    MeterReading::create([
                        'tenant_id' => $meter->tenant_id,
                        'meter_id' => $meter->id,
                        'reading_date' => $readingDate,
                        'value' => $currentValue,
                        'zone' => 'day',
                        'entered_by' => $manager->id,
                    ]);
                    
                    // Night zone reading
                    MeterReading::create([
                        'tenant_id' => $meter->tenant_id,
                        'meter_id' => $meter->id,
                        'reading_date' => $readingDate,
                        'value' => $currentValue * 0.6, // Night usage typically lower
                        'zone' => 'night',
                        'entered_by' => $manager->id,
                    ]);
                    
                    $currentValue += fake()->numberBetween(100, 200);
                } else {
                    MeterReading::create([
                        'tenant_id' => $meter->tenant_id,
                        'meter_id' => $meter->id,
                        'reading_date' => $readingDate,
                        'value' => $currentValue,
                        'zone' => null,
                        'entered_by' => $manager->id,
                    ]);
                    
                    // Increment based on meter type
                    $increment = match($meter->type) {
                        MeterType::WATER_COLD => fake()->numberBetween(5, 15),
                        MeterType::WATER_HOT => fake()->numberBetween(3, 10),
                        MeterType::HEATING => fake()->numberBetween(50, 150),
                        default => fake()->numberBetween(10, 50),
                    };
                    
                    $currentValue += $increment;
                }
            }
        }
    }
}
```

#### TestTariffsSeeder

Creates tariffs for all three Lithuanian providers:

```php
class TestTariffsSeeder extends Seeder
{
    public function run(): void
    {
        // Ignitis - Electricity with time-of-use
        $ignitis = Provider::where('name', 'Ignitis')->first();
        
        Tariff::create([
            'provider_id' => $ignitis->id,
            'name' => 'Ignitis Standard Time-of-Use',
            'configuration' => [
                'type' => 'time_of_use',
                'currency' => 'EUR',
                'zones' => [
                    ['id' => 'day', 'start' => '07:00', 'end' => '23:00', 'rate' => 0.18],
                    ['id' => 'night', 'start' => '23:00', 'end' => '07:00', 'rate' => 0.10],
                ],
                'weekend_logic' => 'apply_night_rate',
                'fixed_fee' => 0.00,
            ],
            'active_from' => Carbon::now()->subYear(),
            'active_until' => null,
        ]);
        
        // Vilniaus Vandenys - Water with fixed rates
        $vv = Provider::where('name', 'Vilniaus Vandenys')->first();
        
        Tariff::create([
            'provider_id' => $vv->id,
            'name' => 'VV Standard Water Rates',
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'supply_rate' => 0.97,
                'sewage_rate' => 1.23,
                'fixed_fee' => 0.85,
            ],
            'active_from' => Carbon::now()->subYear(),
            'active_until' => null,
        ]);
        
        // Vilniaus Energija - Heating
        $ve = Provider::where('name', 'Vilniaus Energija')->first();
        
        Tariff::create([
            'provider_id' => $ve->id,
            'name' => 'VE Heating Standard',
            'configuration' => [
                'type' => 'flat',
                'currency' => 'EUR',
                'rate' => 0.065,
                'fixed_fee' => 0.00,
            ],
            'active_from' => Carbon::now()->subYear(),
            'active_until' => null,
        ]);
    }
}
```


#### TestInvoicesSeeder

Creates sample invoices in different states:

```php
class TestInvoicesSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = Tenant::all();
        
        foreach ($tenants as $tenant) {
            // Draft invoice for current month
            $draftInvoice = Invoice::create([
                'tenant_id' => $tenant->tenant_id,
                'billing_period_start' => Carbon::now()->startOfMonth(),
                'billing_period_end' => Carbon::now()->endOfMonth(),
                'total_amount' => 0, // Will be calculated
                'status' => InvoiceStatus::DRAFT,
                'finalized_at' => null,
            ]);
            
            // Add sample invoice items
            InvoiceItem::create([
                'invoice_id' => $draftInvoice->id,
                'description' => 'Electricity (Day Rate)',
                'quantity' => 150.5,
                'unit' => 'kWh',
                'unit_price' => 0.18,
                'total' => 27.09,
                'meter_reading_snapshot' => ['meter_id' => 1, 'reading' => 1150.5],
            ]);
            
            $draftInvoice->update(['total_amount' => $draftInvoice->items->sum('total')]);
            
            // Finalized invoice for last month
            $finalizedInvoice = Invoice::create([
                'tenant_id' => $tenant->tenant_id,
                'billing_period_start' => Carbon::now()->subMonth()->startOfMonth(),
                'billing_period_end' => Carbon::now()->subMonth()->endOfMonth(),
                'total_amount' => 85.50,
                'status' => InvoiceStatus::FINALIZED,
                'finalized_at' => Carbon::now()->subMonth()->endOfMonth(),
            ]);
            
            // Add invoice items...
            
            // Paid invoice for 2 months ago
            $paidInvoice = Invoice::create([
                'tenant_id' => $tenant->tenant_id,
                'billing_period_start' => Carbon::now()->subMonths(2)->startOfMonth(),
                'billing_period_end' => Carbon::now()->subMonths(2)->endOfMonth(),
                'total_amount' => 92.30,
                'status' => InvoiceStatus::PAID,
                'finalized_at' => Carbon::now()->subMonths(2)->endOfMonth(),
            ]);
            
            // Add invoice items...
        }
    }
}
```

### Test Database Seeder

Master seeder that orchestrates all test seeders:

```php
class TestDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // First seed providers (no tenant dependency)
            ProvidersSeeder::class,
            
            // Then test users
            TestUsersSeeder::class,
            
            // Then buildings and properties
            TestBuildingsSeeder::class,
            TestPropertiesSeeder::class,
            
            // Then tenants (renters)
            TestTenantsSeeder::class,
            
            // Then meters
            TestMetersSeeder::class,
            
            // Then meter readings
            TestMeterReadingsSeeder::class,
            
            // Then tariffs
            TestTariffsSeeder::class,
            
            // Finally invoices
            TestInvoicesSeeder::class,
        ]);
    }
}
```

## Data Models

The authentication testing system uses the existing data models from the main application:

- **User**: Extended with test users having known credentials
- **Building**: Test buildings with realistic Vilnius addresses
- **Property**: Mix of apartments and houses
- **Tenant**: Test tenants (renters) linked to properties
- **Meter**: All meter types for each property
- **MeterReading**: 3+ months of historical readings
- **Provider**: Ignitis, Vilniaus Vandenys, Vilniaus Energija
- **Tariff**: Realistic tariff configurations for each provider
- **Invoice**: Sample invoices in all states (draft, finalized, paid)
- **InvoiceItem**: Itemized charges with snapshotted prices

### Test Data Relationships

```
Tenant 1 (Company A)
├── Admin User (admin@test.com)
├── Manager User (manager@test.com)
├── Building 1 (Gedimino pr. 15)
│   ├── Property 1 (Apt 1)
│   │   ├── Tenant (tenant@test.com)
│   │   ├── Electricity Meter → 4 readings
│   │   ├── Water Cold Meter → 4 readings
│   │   ├── Water Hot Meter → 4 readings
│   │   └── Heating Meter → 4 readings
│   ├── Property 2 (Apt 2)
│   │   └── ... (similar structure)
│   └── ... (6 properties total)
├── Building 2 (Konstitucijos pr. 7)
│   └── ... (similar structure)
└── Property 7 (Standalone house)
    └── ... (no heating meter)

Tenant 2 (Company B)
├── Manager User (manager2@test.com)
├── Building 3 (Pilies g. 22)
│   └── ... (3 properties)
└── Tenant Users (tenant3@test.com, etc.)
```


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property 1: Test user tenant assignment
*For any* test user created by the seeder, the user should have a non-null tenant_id value.
**Validates: Requirements 1.4**

### Property 2: Valid credentials authentication
*For any* user with valid credentials (correct email and password), authentication should succeed and establish a session.
**Validates: Requirements 2.1**

### Property 3: Invalid credentials rejection
*For any* authentication attempt with invalid credentials (wrong email or wrong password), authentication should fail and return an error message.
**Validates: Requirements 2.2**

### Property 4: Manager property isolation
*For any* manager user from tenant A, querying properties should return only properties where tenant_id equals A.
**Validates: Requirements 4.1**

### Property 5: Cross-tenant property access prevention
*For any* manager user from tenant A attempting to access a property from tenant B, the system should return a 404 error.
**Validates: Requirements 4.2**

### Property 6: Tenant invoice isolation
*For any* tenant user, querying invoices should return only invoices associated with that specific tenant (not just tenant_id).
**Validates: Requirements 4.3**

### Property 7: Cross-tenant invoice access prevention
*For any* tenant user attempting to access another tenant's invoice, the system should return a 404 error.
**Validates: Requirements 4.4**

### Property 8: Complete meter coverage
*For any* property created by the test seeder, the property should have meters for all applicable utility types (electricity, water_cold, water_hot, and heating if in a building).
**Validates: Requirements 5.2**

### Property 9: Meter reading storage completeness
*For any* valid meter reading entered by a manager, the stored reading should contain a non-null entered_by user ID and a created_at timestamp.
**Validates: Requirements 6.1**

### Property 10: Meter reading monotonicity enforcement
*For any* meter reading submission where the new value is less than the most recent reading for that meter, the submission should be rejected with a validation error.
**Validates: Requirements 6.2**

### Property 11: Meter reading temporal validation
*For any* meter reading submission where the reading_date is in the future, the submission should be rejected with a validation error.
**Validates: Requirements 6.3**

### Property 12: Multi-zone meter reading support
*For any* meter where supports_zones is true, the system should accept and store readings with different zone identifiers for the same reading_date.
**Validates: Requirements 6.4**

### Property 13: Meter reading audit trail creation
*For any* meter reading that is successfully stored, an audit trail entry should be created (or the reading itself should serve as the audit record).
**Validates: Requirements 6.5**

### Property 14: Invoice calculation from readings
*For any* invoice generated for a tenant, the invoice total should be calculated based on the meter readings for the billing period and the current tariff rates.
**Validates: Requirements 7.1**

### Property 15: Invoice itemization by utility type
*For any* generated invoice, the invoice should contain invoice items for each utility type where consumption occurred during the billing period.
**Validates: Requirements 7.2**

### Property 16: Tariff rate snapshotting
*For any* generated invoice, the invoice_items should contain snapshotted tariff rates that do not change even if the tariff table is modified.
**Validates: Requirements 7.3**

### Property 17: Invoice immutability after finalization
*For any* invoice where finalized_at is set to a non-null timestamp, attempts to modify the invoice or its items should be rejected.
**Validates: Requirements 7.4**

### Property 18: Finalized invoice tariff independence
*For any* finalized invoice, if the tariff rates in the tariff table are modified, the invoice total and item prices should remain unchanged.
**Validates: Requirements 7.5**

### Property 19: Time-of-use zone overlap validation
*For any* tariff configuration with type "time_of_use", if the time zones overlap, the tariff creation should be rejected with a validation error.
**Validates: Requirements 8.1**

### Property 20: Time-of-use zone coverage validation
*For any* tariff configuration with type "time_of_use", if the time zones do not cover all 24 hours, the tariff creation should be rejected with a validation error.
**Validates: Requirements 8.2**

### Property 21: Tariff temporal selection
*For any* provider and billing date, the system should select the tariff where active_from ≤ billing_date AND (active_until IS NULL OR active_until ≥ billing_date).
**Validates: Requirements 8.3**

### Property 22: Tariff precedence with overlaps
*For any* billing date where multiple tariffs are active, the system should select the tariff with the most recent active_from date.
**Validates: Requirements 8.4**

### Property 23: Weekend tariff rate application
*For any* tariff with weekend_logic defined and any consumption on Saturday or Sunday, the calculated cost should use the weekend rate specified in the configuration.
**Validates: Requirements 8.5**

### Property 24: Summer gyvatukas calculation formula
*For any* building and billing period in non-heating season (May-September), the calculated circulation energy should equal Q_total - (V_water × c × ΔT).
**Validates: Requirements 9.1**

### Property 25: Winter gyvatukas norm application
*For any* building and billing period in heating season (October-April), the calculated gyvatukas cost should use the building's stored gyvatukas_summer_average.
**Validates: Requirements 9.2**

### Property 26: Circulation cost distribution
*For any* building with N apartments and total circulation cost C, if distribution is equal, each apartment should be charged C/N; if distribution is by area, apartment i with area A_i should be charged C × (A_i / Σ A_j).
**Validates: Requirements 9.4**

### Property 27: Gyvatukas invoice itemization
*For any* invoice that includes gyvatukas charges, the invoice should contain a separate invoice item specifically for the circulation fee.
**Validates: Requirements 9.5**


## Error Handling

### Authentication Errors

**Invalid Credentials:**
```php
// LoginController
if (!Auth::attempt($credentials)) {
    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ])->onlyInput('email');
}
```

**Session Expiration:**
- Redirect to login page with message: "Your session has expired. Please log in again."
- Preserve intended URL for redirect after successful login

### Authorization Errors

**Insufficient Permissions:**
```php
// In Policy or Gate
if (!$user->can('manage-tariffs')) {
    abort(403, 'You do not have permission to manage tariffs.');
}
```

**Cross-Tenant Access Attempt:**
- Return 404 (not 403) to avoid information disclosure
- Log security event with user ID, attempted resource, and timestamp

### Multi-Tenancy Errors

**Missing Tenant Context:**
```php
// EnsureTenantContext middleware
if (!session()->has('tenant_id')) {
    return redirect()->route('login')
        ->withErrors(['error' => 'Tenant context is required.']);
}
```

**Invalid Tenant ID:**
- Validate tenant_id exists in database
- If invalid, log out user and redirect to login

### Test Data Errors

**Seeder Failures:**
- Wrap seeder operations in try-catch blocks
- Log detailed error messages with context
- Rollback transaction on failure
- Display user-friendly error message

**Missing Dependencies:**
```php
// In TestMeterReadingsSeeder
$manager = User::where('role', UserRole::MANAGER)->first();
if (!$manager) {
    throw new \RuntimeException(
        'No manager user found. Run TestUsersSeeder first.'
    );
}
```

### Validation Errors

**Meter Reading Validation:**
- Monotonicity violation: "Reading ({value}) cannot be lower than previous reading ({previous})"
- Future date: "Reading date cannot be in the future"
- Missing meter: "Meter not found or does not belong to your tenant"

**Tariff Configuration Validation:**
- Zone overlap: "Time zones overlap at {time}"
- Incomplete coverage: "Time zones do not cover all 24 hours (missing: {gaps})"
- Invalid JSON: "Tariff configuration must be valid JSON"

## Testing Strategy

The authentication testing system employs both automated tests and manual testing scenarios.

### Property-Based Testing

**Framework:** Pest PHP with pest-plugin-faker

**Configuration:** Each property-based test runs a minimum of 100 iterations.

**Tagging Convention:** Each property-based test includes a comment referencing the design document property:
```php
// Feature: authentication-testing, Property 1: Test user tenant assignment
test('all test users have valid tenant assignments', function () {
    // Run test seeder
    $this->artisan('db:seed', ['--class' => 'TestUsersSeeder']);
    
    // Property: all test users should have non-null tenant_id
    $users = User::whereIn('email', [
        'admin@test.com',
        'manager@test.com',
        'manager2@test.com',
        'tenant@test.com',
        'tenant2@test.com',
        'tenant3@test.com',
    ])->get();
    
    foreach ($users as $user) {
        expect($user->tenant_id)->not->toBeNull();
    }
});
```

### Unit Testing

Unit tests complement property tests by verifying specific examples and edge cases:

**Authentication Tests:**
- Test login with admin credentials redirects to /admin/dashboard
- Test login with manager credentials redirects to /manager/dashboard
- Test login with tenant credentials redirects to /tenant/dashboard
- Test login with invalid credentials returns error
- Test logout clears session and redirects to home

**Authorization Tests:**
- Test admin can access tariff management pages
- Test manager cannot access tariff management pages (403)
- Test tenant cannot access tariff management pages (403)
- Test manager can access meter reading entry pages
- Test tenant cannot access meter reading entry pages (403)

**Multi-Tenancy Tests:**
- Test manager from tenant 1 sees only tenant 1 properties
- Test manager from tenant 1 cannot access tenant 2 properties (404)
- Test tenant sees only their own invoices
- Test tenant cannot access another tenant's invoices (404)
- Test admin can see data from all tenants

**Test Data Generation Tests:**
- Test seeder creates all required test users
- Test seeder creates buildings with properties
- Test seeder creates all meter types for each property
- Test seeder creates 3+ months of meter readings
- Test seeder creates tariffs for all providers
- Test seeder creates invoices in all states

### Manual Testing Scenarios

**Scenario 1: Admin Login and Tariff Management**
1. Navigate to /login
2. Enter email: admin@test.com, password: password
3. Verify redirect to /admin/dashboard
4. Navigate to /admin/tariffs
5. Verify tariff list displays
6. Create new tariff with time-of-use zones
7. Verify validation for zone overlap
8. Verify validation for 24-hour coverage

**Scenario 2: Manager Login and Meter Reading Entry**
1. Navigate to /login
2. Enter email: manager@test.com, password: password
3. Verify redirect to /manager/dashboard
4. Navigate to /meter-readings/create
5. Select a meter from the dropdown
6. Verify previous reading displays
7. Enter new reading lower than previous
8. Verify validation error displays
9. Enter valid reading higher than previous
10. Submit and verify success message

**Scenario 3: Tenant Login and Invoice Viewing**
1. Navigate to /login
2. Enter email: tenant@test.com, password: password
3. Verify redirect to /tenant/dashboard
4. Navigate to /tenant/invoices
5. Verify only own invoices display
6. Click on an invoice
7. Verify itemized breakdown displays
8. Verify consumption and rates are shown

**Scenario 4: Cross-Tenant Access Prevention**
1. Login as manager@test.com (tenant 1)
2. Note a property ID from tenant 2 (from database)
3. Attempt to navigate to /properties/{tenant2_property_id}
4. Verify 404 error displays
5. Attempt to navigate to /meter-readings?property_id={tenant2_property_id}
6. Verify no data displays or 404 error

**Scenario 5: Invoice Generation Workflow**
1. Login as manager@test.com
2. Navigate to /invoices/create
3. Select a tenant
4. Select billing period (current month)
5. Click "Generate Invoice"
6. Verify invoice items display with calculations
7. Verify tariff rates are snapshotted
8. Click "Finalize Invoice"
9. Verify invoice status changes to "finalized"
10. Attempt to edit invoice
11. Verify modification is prevented


### API Testing with curl

**Test Authentication:**
```bash
# Login request
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"email":"manager@test.com","password":"password"}' \
  -c cookies.txt

# Verify session established
curl -X GET http://localhost:8000/manager/dashboard \
  -b cookies.txt
```

**Test Meter Reading API:**
```bash
# Get last reading for meter
curl -X GET http://localhost:8000/api/meters/1/last-reading \
  -b cookies.txt

# Submit new reading
curl -X POST http://localhost:8000/api/meter-readings \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "meter_id": 1,
    "reading_date": "2024-11-19",
    "value": 1250.5,
    "zone": "day"
  }'
```

**Test Authorization:**
```bash
# Login as tenant
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"email":"tenant@test.com","password":"password"}' \
  -c tenant_cookies.txt

# Attempt to access manager endpoint (should fail)
curl -X GET http://localhost:8000/meter-readings/create \
  -b tenant_cookies.txt \
  -w "\nHTTP Status: %{http_code}\n"
```

**Test Multi-Tenancy:**
```bash
# Login as manager from tenant 1
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"email":"manager@test.com","password":"password"}' \
  -c manager1_cookies.txt

# Get properties (should only see tenant 1)
curl -X GET http://localhost:8000/api/properties \
  -b manager1_cookies.txt

# Attempt to access tenant 2 property (should fail)
curl -X GET http://localhost:8000/api/properties/10 \
  -b manager1_cookies.txt \
  -w "\nHTTP Status: %{http_code}\n"
```

## Test Documentation Structure

The system includes comprehensive test documentation in a separate file:

**TESTING_GUIDE.md** structure:
```markdown
# Testing Guide

## Quick Start
- Running the test setup command
- Test user credentials table
- Accessing different dashboards

## Test Scenarios by Role

### Admin Testing
- Login and dashboard access
- Tariff management
- User management
- Cross-tenant data access

### Manager Testing
- Login and dashboard access
- Meter reading entry
- Invoice generation
- Property management
- Data isolation verification

### Tenant Testing
- Login and dashboard access
- Invoice viewing
- Consumption history
- Multi-property filtering

## API Testing
- Authentication endpoints
- Meter reading endpoints
- Invoice endpoints
- Authorization verification

## Common Issues and Troubleshooting
- Session not persisting
- 403 errors when accessing resources
- 404 errors for cross-tenant access
- Validation errors on meter readings
- Invoice generation failures

## Test Data Reference
- Test user credentials
- Building and property IDs
- Meter serial numbers
- Sample invoice IDs
```

## Implementation Notes

### Database Considerations

**Transaction Safety:**
- Wrap all seeder operations in database transactions
- Rollback on any failure to maintain consistency
- Use `DB::transaction()` in TestDatabaseSeeder

**Performance:**
- Use bulk inserts where possible for meter readings
- Disable foreign key checks during seeding if needed
- Re-enable foreign key checks after seeding

**Data Cleanup:**
- `php artisan test:setup --fresh` drops all tables and recreates
- Regular `php artisan test:setup` only reseeds data
- Consider adding `--clean` option to delete test data only

### Security Considerations

**Test Credentials:**
- Use simple passwords ("password") for test users only
- Never use these credentials in production
- Document that test users should be deleted before production deployment

**Cross-Tenant Access:**
- Always return 404 (not 403) for cross-tenant access attempts
- Log all cross-tenant access attempts for security monitoring
- Include user ID, attempted resource, and timestamp in logs

**Session Management:**
- Ensure sessions are properly invalidated on logout
- Test session timeout behavior
- Verify CSRF protection is active

### Extensibility

**Adding New Test Scenarios:**
1. Create new seeder class extending `Seeder`
2. Add to `TestDatabaseSeeder` call list
3. Update test documentation with new scenario
4. Add corresponding property-based tests

**Adding New Roles:**
1. Add new role to `UserRole` enum
2. Create test user in `TestUsersSeeder`
3. Add authorization tests for new role
4. Update test documentation

**Adding New Utility Types:**
1. Add new meter type to `MeterType` enum
2. Update `TestMetersSeeder` to create new meter type
3. Update `TestMeterReadingsSeeder` to generate readings
4. Add tariff configuration for new utility type
5. Update test scenarios

