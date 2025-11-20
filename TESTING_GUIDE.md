# Testing Guide

## Quick Start

### Setting Up Test Environment

The fastest way to get started with testing is to use the test setup command:

```bash
# Fresh database with test data
php artisan test:setup --fresh

# Re-seed test data (keeps existing schema)
php artisan test:setup
```

This command will:
1. Drop all tables and run migrations (with `--fresh` flag)
2. Seed all test data (users, buildings, properties, meters, readings, tariffs, invoices)
3. Display test user credentials in a table format
4. Show summary of created data

### Test User Credentials

All test users use the password: `password`

| Role    | Email                  | Password | Tenant ID | Description                    |
|---------|------------------------|----------|-----------|--------------------------------|
| Admin   | admin@test.com         | password | 1         | Full system access             |
| Manager | manager@test.com       | password | 1         | Tenant 1 property manager      |
| Manager | manager2@test.com      | password | 2         | Tenant 2 property manager      |
| Tenant  | tenant@test.com        | password | 1         | Tenant 1 renter (Property 1)   |
| Tenant  | tenant2@test.com       | password | 1         | Tenant 1 renter (Property 2)   |
| Tenant  | tenant3@test.com       | password | 2         | Tenant 2 renter                |

### Accessing Dashboards

After logging in, users are automatically redirected to their role-specific dashboard:

- **Admin**: http://localhost:8000/admin/dashboard
- **Manager**: http://localhost:8000/manager/dashboard
- **Tenant**: http://localhost:8000/tenant/dashboard

## Test Data Structure

### Tenant 1 (Property Management Company A)

**Buildings:**
- Building 1: Gedimino pr. 15, Vilnius (6 apartments)
- Building 2: Konstitucijos pr. 7, Vilnius (multiple apartments)
- Standalone House: Žvėryno g. 5, Vilnius

**Properties:** 7 total (6 apartments + 1 house)

**Meters per Property:**
- Electricity meter (supports day/night zones)
- Cold water meter
- Hot water meter
- Heating meter (apartments only, not standalone house)

**Historical Data:**
- 3+ months of meter readings for all meters
- Sample invoices in all states (draft, finalized, paid)

### Tenant 2 (Property Management Company B)

**Buildings:**
- Building 3: Pilies g. 22, Vilnius (3 apartments)

**Properties:** 3 apartments

**Meters:** Same structure as Tenant 1

**Historical Data:** Same structure as Tenant 1

### Providers and Tariffs

**Ignitis (Electricity):**
- Type: Time-of-use
- Day rate (07:00-23:00): €0.18/kWh
- Night rate (23:00-07:00): €0.10/kWh
- Weekend logic: Apply night rate

**Vilniaus Vandenys (Water):**
- Type: Flat rate
- Supply rate: €0.97/m³
- Sewage rate: €1.23/m³
- Fixed fee: €0.85/month

**Vilniaus Energija (Heating):**
- Type: Flat rate
- Rate: €0.065/kWh

## Test Scenarios by Role

### Admin Testing

#### Scenario 1: Login and Dashboard Access

1. Navigate to http://localhost:8000/login
2. Enter credentials:
   - Email: `admin@test.com`
   - Password: `password`
3. Click "Login"
4. **Expected**: Redirect to `/admin/dashboard`
5. **Expected**: Dashboard displays system-wide statistics

#### Scenario 2: Tariff Management

1. Login as admin (see Scenario 1)
2. Navigate to http://localhost:8000/admin/tariffs
3. **Expected**: List of all tariffs displays
4. Click "Create New Tariff"
5. Fill in tariff details with time-of-use zones
6. Create overlapping zones (e.g., 07:00-15:00 and 14:00-22:00)
7. **Expected**: Validation error "Time zones overlap at 14:00"
8. Fix zones to not overlap but leave gap (e.g., 07:00-15:00 and 16:00-23:00)
9. **Expected**: Validation error "Time zones do not cover all 24 hours"
10. Fix zones to cover all 24 hours without overlap
11. Submit form
12. **Expected**: Tariff created successfully

#### Scenario 3: User Management

1. Login as admin
2. Navigate to http://localhost:8000/admin/users
3. **Expected**: List of all users from all tenants displays
4. Click "Create New User"
5. Create a new manager for tenant 1
6. **Expected**: User created and appears in list

#### Scenario 4: Cross-Tenant Data Access

1. Login as admin
2. Navigate to http://localhost:8000/admin/properties
3. **Expected**: Properties from both tenant 1 and tenant 2 display
4. Filter by tenant 1
5. **Expected**: Only tenant 1 properties display
6. Filter by tenant 2
7. **Expected**: Only tenant 2 properties display

### Manager Testing

#### Scenario 5: Login and Dashboard Access

1. Navigate to http://localhost:8000/login
2. Enter credentials:
   - Email: `manager@test.com`
   - Password: `password`
3. Click "Login"
4. **Expected**: Redirect to `/manager/dashboard`
5. **Expected**: Dashboard displays tenant 1 statistics only

#### Scenario 6: Meter Reading Entry

1. Login as manager@test.com (tenant 1)
2. Navigate to http://localhost:8000/manager/meter-readings/create
3. Select a meter from dropdown
4. **Expected**: Previous reading displays (e.g., "Last reading: 1250.5 on 2024-10-01")
5. Enter new reading lower than previous (e.g., 1200.0)
6. Submit form
7. **Expected**: Validation error "Reading cannot be lower than previous reading (1250.5)"
8. Enter reading with future date
9. **Expected**: Validation error "Reading date cannot be in the future"
10. Enter valid reading higher than previous with current date
11. Submit form
12. **Expected**: Success message "Meter reading saved successfully"
13. **Expected**: Audit trail entry created

#### Scenario 7: Multi-Zone Meter Reading

1. Login as manager@test.com
2. Navigate to meter reading entry
3. Select an electricity meter (supports zones)
4. **Expected**: Form displays separate fields for "Day Zone" and "Night Zone"
5. Enter values for both zones
6. Submit form
7. **Expected**: Both readings saved with correct zone identifiers

#### Scenario 8: Invoice Generation

1. Login as manager@test.com
2. Navigate to http://localhost:8000/manager/invoices/create
3. Select a tenant from dropdown
4. Select billing period (current month)
5. Click "Generate Invoice"
6. **Expected**: Invoice preview displays with itemized breakdown
7. **Expected**: Items show: Electricity (day), Electricity (night), Water (cold), Water (hot), Heating
8. **Expected**: Each item shows: quantity, unit price, total
9. **Expected**: Tariff rates are snapshotted in invoice items
10. Review calculations
11. Click "Finalize Invoice"
12. **Expected**: Invoice status changes to "finalized"
13. **Expected**: finalized_at timestamp is set
14. Attempt to edit invoice
15. **Expected**: Edit button disabled or returns error "Cannot modify finalized invoice"

#### Scenario 9: Data Isolation Verification

1. Login as manager@test.com (tenant 1)
2. Navigate to http://localhost:8000/manager/properties
3. **Expected**: Only tenant 1 properties display
4. Note a property ID from tenant 2 (check database or use manager2@test.com)
5. Manually navigate to http://localhost:8000/manager/properties/{tenant2_property_id}
6. **Expected**: 404 error "Property not found"
7. Navigate to http://localhost:8000/manager/invoices
8. **Expected**: Only tenant 1 invoices display

#### Scenario 10: Authorization Verification

1. Login as manager@test.com
2. Attempt to navigate to http://localhost:8000/admin/tariffs
3. **Expected**: 403 error "You do not have permission to access this page"
4. Attempt to navigate to http://localhost:8000/admin/users
5. **Expected**: 403 error

### Tenant Testing

#### Scenario 11: Login and Dashboard Access

1. Navigate to http://localhost:8000/login
2. Enter credentials:
   - Email: `tenant@test.com`
   - Password: `password`
3. Click "Login"
4. **Expected**: Redirect to `/tenant/dashboard`
5. **Expected**: Dashboard displays personal consumption and invoice summary

#### Scenario 12: Invoice Viewing

1. Login as tenant@test.com
2. Navigate to http://localhost:8000/tenant/invoices
3. **Expected**: List of own invoices displays
4. **Expected**: Invoices show status (draft, finalized, paid)
5. Click on a finalized invoice
6. **Expected**: Detailed invoice view displays
7. **Expected**: Itemized breakdown shows:
   - Service type (Electricity, Water, Heating)
   - Consumption amount
   - Unit price (snapshotted rate)
   - Total cost
8. **Expected**: Invoice total matches sum of items

#### Scenario 13: Consumption History

1. Login as tenant@test.com
2. Navigate to http://localhost:8000/tenant/meters
3. **Expected**: List of meters for tenant's property displays
4. Click on a meter
5. **Expected**: Historical readings display in table or chart
6. **Expected**: Readings show date, value, and consumption since last reading

#### Scenario 14: Data Isolation Verification

1. Login as tenant@test.com
2. Navigate to invoices
3. **Expected**: Only own invoices display (not tenant2@test.com's invoices)
4. Note an invoice ID belonging to tenant2@test.com (check database)
5. Manually navigate to http://localhost:8000/tenant/invoices/{tenant2_invoice_id}
6. **Expected**: 404 error "Invoice not found"

#### Scenario 15: Authorization Verification

1. Login as tenant@test.com
2. Attempt to navigate to http://localhost:8000/manager/meter-readings/create
3. **Expected**: 403 error "You do not have permission to access this page"
4. Attempt to navigate to http://localhost:8000/admin/tariffs
5. **Expected**: 403 error

## API Testing

### Authentication

#### Login Request

```bash
# Login and save session cookie
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=manager@test.com&password=password" \
  -c cookies.txt \
  -L

# Verify session established
curl -X GET http://localhost:8000/manager/dashboard \
  -b cookies.txt
```

**Expected Response:** HTML content of manager dashboard

#### Logout Request

```bash
# Logout
curl -X POST http://localhost:8000/logout \
  -b cookies.txt \
  -L

# Verify session cleared
curl -X GET http://localhost:8000/manager/dashboard \
  -b cookies.txt
```

**Expected Response:** Redirect to login page

### Meter Reading API

#### Get Last Reading

```bash
# Get last reading for meter ID 1
curl -X GET http://localhost:8000/api/meters/1/last-reading \
  -b cookies.txt \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "meter_id": 1,
  "serial_number": "EL-000001",
  "last_reading": {
    "id": 45,
    "value": 1250.5,
    "reading_date": "2024-10-01",
    "zone": "day"
  }
}
```

#### Submit New Reading

```bash
# Submit new meter reading
curl -X POST http://localhost:8000/api/meter-readings \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -b cookies.txt \
  -d '{
    "meter_id": 1,
    "reading_date": "2024-11-19",
    "value": 1350.5,
    "zone": "day"
  }'
```

**Expected Response:**
```json
{
  "id": 123,
  "meter_id": 1,
  "reading_date": "2024-11-19",
  "value": 1350.5,
  "zone": "day",
  "entered_by": 2,
  "created_at": "2024-11-19T10:30:00.000000Z"
}
```

#### Submit Invalid Reading (Monotonicity Violation)

```bash
# Submit reading lower than previous
curl -X POST http://localhost:8000/api/meter-readings \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -b cookies.txt \
  -d '{
    "meter_id": 1,
    "reading_date": "2024-11-19",
    "value": 1200.0,
    "zone": "day"
  }'
```

**Expected Response (422 Unprocessable Entity):**
```json
{
  "message": "The value field is invalid. (and 0 more errors)",
  "errors": {
    "value": ["Reading cannot be lower than previous reading (1250.5)"]
  }
}
```

### Property API

#### List Properties

```bash
# Get properties for authenticated manager
curl -X GET http://localhost:8000/api/properties \
  -b cookies.txt \
  -H "Accept: application/json"
```

**Expected Response:**
```json
[
  {
    "id": 1,
    "address": "Gedimino pr. 15, Vilnius, Apt 1",
    "type": "apartment",
    "area_sqm": 65.5,
    "building_id": 1,
    "building_address": "Gedimino pr. 15, Vilnius",
    "meter_count": 4
  },
  {
    "id": 2,
    "address": "Gedimino pr. 15, Vilnius, Apt 2",
    "type": "apartment",
    "area_sqm": 72.0,
    "building_id": 1,
    "building_address": "Gedimino pr. 15, Vilnius",
    "meter_count": 4
  }
]
```

#### Get Property Details

```bash
# Get specific property
curl -X GET http://localhost:8000/api/properties/1 \
  -b cookies.txt \
  -H "Accept: application/json"
```

**Expected Response:**
```json
{
  "id": 1,
  "address": "Gedimino pr. 15, Vilnius, Apt 1",
  "type": "apartment",
  "area_sqm": 65.5,
  "building_id": 1,
  "building": {
    "id": 1,
    "address": "Gedimino pr. 15, Vilnius",
    "total_apartments": 12
  },
  "meters": [
    {
      "id": 1,
      "serial_number": "EL-000001",
      "type": "electricity",
      "supports_zones": true,
      "installation_date": "2022-11-19",
      "last_reading": {
        "value": 1250.5,
        "date": "2024-10-01",
        "zone": "day"
      }
    },
    {
      "id": 2,
      "serial_number": "WC-000001",
      "type": "water_cold",
      "supports_zones": false,
      "installation_date": "2022-11-19",
      "last_reading": {
        "value": 45.2,
        "date": "2024-10-01",
        "zone": null
      }
    }
  ]
}
```

### Authorization Testing

#### Test Manager Cannot Access Admin Endpoints

```bash
# Login as manager
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=manager@test.com&password=password" \
  -c manager_cookies.txt \
  -L

# Attempt to access admin tariff page
curl -X GET http://localhost:8000/admin/tariffs \
  -b manager_cookies.txt \
  -w "\nHTTP Status: %{http_code}\n"
```

**Expected Response:** HTTP Status: 403

#### Test Tenant Cannot Access Manager Endpoints

```bash
# Login as tenant
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=tenant@test.com&password=password" \
  -c tenant_cookies.txt \
  -L

# Attempt to access meter reading entry
curl -X GET http://localhost:8000/manager/meter-readings/create \
  -b tenant_cookies.txt \
  -w "\nHTTP Status: %{http_code}\n"
```

**Expected Response:** HTTP Status: 403

### Multi-Tenancy Testing

#### Test Cross-Tenant Property Access

```bash
# Login as manager from tenant 1
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=manager@test.com&password=password" \
  -c manager1_cookies.txt \
  -L

# Get properties (should only see tenant 1)
curl -X GET http://localhost:8000/api/properties \
  -b manager1_cookies.txt \
  -H "Accept: application/json"

# Attempt to access tenant 2 property (ID 10 or higher)
curl -X GET http://localhost:8000/api/properties/10 \
  -b manager1_cookies.txt \
  -H "Accept: application/json" \
  -w "\nHTTP Status: %{http_code}\n"
```

**Expected Response:** HTTP Status: 404

#### Test Cross-Tenant Invoice Access

```bash
# Login as tenant from tenant 1
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=tenant@test.com&password=password" \
  -c tenant1_cookies.txt \
  -L

# Get own invoices
curl -X GET http://localhost:8000/tenant/invoices \
  -b tenant1_cookies.txt \
  -H "Accept: application/json"

# Attempt to access another tenant's invoice
curl -X GET http://localhost:8000/tenant/invoices/999 \
  -b tenant1_cookies.txt \
  -H "Accept: application/json" \
  -w "\nHTTP Status: %{http_code}\n"
```

**Expected Response:** HTTP Status: 404

## Common Issues and Troubleshooting

### Issue 1: Session Not Persisting

**Symptoms:**
- Login succeeds but subsequent requests return 401 or redirect to login
- Cookie not being saved or sent with requests

**Solutions:**
1. Verify session driver is configured correctly in `.env`:
   ```
   SESSION_DRIVER=file
   ```
2. Check that `storage/framework/sessions` directory is writable
3. Clear session files:
   ```bash
   php artisan session:clear
   ```
4. For curl requests, ensure `-c cookies.txt` (save) and `-b cookies.txt` (send) are used
5. Check that `SESSION_DOMAIN` and `SESSION_SECURE_COOKIE` are set correctly for your environment

### Issue 2: 403 Errors When Accessing Resources

**Symptoms:**
- User can login but gets 403 when accessing pages they should have access to

**Solutions:**
1. Verify user role is set correctly:
   ```bash
   php artisan tinker
   >>> User::where('email', 'manager@test.com')->first()->role
   ```
2. Check that policies are registered in `AuthServiceProvider`
3. Verify middleware is applied correctly in routes
4. Check that `RoleMiddleware` is functioning:
   ```bash
   # In routes/web.php, verify:
   Route::middleware(['auth', 'role:manager'])->group(function () {
       // Manager routes
   });
   ```
5. Clear route cache:
   ```bash
   php artisan route:clear
   php artisan config:clear
   ```

### Issue 3: 404 Errors for Cross-Tenant Access

**Symptoms:**
- Getting 404 when trying to access resources from another tenant (this is expected behavior)
- Getting 404 when trying to access own resources (this is a problem)

**Solutions:**
1. Verify `TenantScope` is applied to the model:
   ```php
   protected static function booted(): void
   {
       static::addGlobalScope(new TenantScope);
   }
   ```
2. Check that `tenant_id` is set in session:
   ```bash
   php artisan tinker
   >>> session()->get('tenant_id')
   ```
3. Verify `EnsureTenantContext` middleware is applied to routes
4. Check that the resource actually belongs to the user's tenant:
   ```bash
   php artisan tinker
   >>> Property::withoutGlobalScopes()->find(1)->tenant_id
   ```

### Issue 4: Validation Errors on Meter Readings

**Symptoms:**
- Valid readings are being rejected
- Monotonicity check failing incorrectly

**Solutions:**
1. Check that previous reading exists:
   ```bash
   php artisan tinker
   >>> MeterReading::where('meter_id', 1)->orderBy('reading_date', 'desc')->first()
   ```
2. Verify reading value is numeric and greater than previous
3. Check that `reading_date` is not in the future
4. For multi-zone meters, ensure zone is specified correctly
5. Verify `StoreMeterReadingRequest` validation rules are correct

### Issue 5: Invoice Generation Failures

**Symptoms:**
- Invoice generation returns error
- Invoice items not created
- Calculations incorrect

**Solutions:**
1. Verify meter readings exist for the billing period:
   ```bash
   php artisan tinker
   >>> MeterReading::whereBetween('reading_date', ['2024-11-01', '2024-11-30'])->count()
   ```
2. Check that tariffs are active for the billing period:
   ```bash
   php artisan tinker
   >>> Tariff::where('active_from', '<=', '2024-11-19')
            ->where(function($q) {
                $q->whereNull('active_until')
                  ->orWhere('active_until', '>=', '2024-11-19');
            })->get()
   ```
3. Verify `BillingService` is functioning correctly
4. Check logs for detailed error messages:
   ```bash
   tail -f storage/logs/laravel.log
   ```
5. Test tariff calculation manually:
   ```bash
   php artisan tinker
   >>> $resolver = app(TariffResolver::class);
   >>> $resolver->calculateCost($consumption, $tariff, $date);
   ```

### Issue 6: Test Data Not Created

**Symptoms:**
- `php artisan test:setup` completes but data is missing
- Seeder errors not displayed

**Solutions:**
1. Run with verbose output:
   ```bash
   php artisan test:setup --fresh -v
   ```
2. Check for foreign key constraint violations:
   ```bash
   php artisan tinker
   >>> DB::select('PRAGMA foreign_keys;')
   ```
3. Verify seeder order in `TestDatabaseSeeder`:
   - Providers must be seeded before tariffs
   - Users must be seeded before meter readings (for entered_by)
   - Buildings must be seeded before properties
   - Properties must be seeded before meters
4. Check database file permissions:
   ```bash
   ls -la database/database.sqlite
   ```
5. Verify SQLite is in WAL mode:
   ```bash
   php artisan tinker
   >>> DB::select('PRAGMA journal_mode;')
   ```

### Issue 7: Gyvatukas Calculation Errors

**Symptoms:**
- Gyvatukas not appearing in invoices
- Calculation values seem incorrect

**Solutions:**
1. Verify building has `gyvatukas_summer_average` set:
   ```bash
   php artisan tinker
   >>> Building::find(1)->gyvatukas_summer_average
   ```
2. Check that property is in a building (not standalone house):
   ```bash
   php artisan tinker
   >>> Property::find(1)->building_id
   ```
3. Verify season detection is correct (May-September = summer, October-April = winter)
4. Check that `GyvatukasCalculator` service is being called during invoice generation
5. Verify circulation fee appears as separate invoice item

## Test Data Reference

### Test User IDs

| Email                  | User ID | Role    | Tenant ID |
|------------------------|---------|---------|-----------|
| admin@test.com         | 1       | admin   | 1         |
| manager@test.com       | 2       | manager | 1         |
| manager2@test.com      | 3       | manager | 2         |
| tenant@test.com        | 4       | tenant  | 1         |
| tenant2@test.com       | 5       | tenant  | 1         |
| tenant3@test.com       | 6       | tenant  | 2         |

### Building IDs

| Building ID | Address                      | Tenant ID | Total Apartments |
|-------------|------------------------------|-----------|------------------|
| 1           | Gedimino pr. 15, Vilnius     | 1         | 12               |
| 2           | Konstitucijos pr. 7, Vilnius | 1         | 8                |
| 3           | Pilies g. 22, Vilnius        | 2         | 6                |

### Property IDs (Approximate)

| Property ID Range | Description                | Tenant ID |
|-------------------|----------------------------|-----------|
| 1-6               | Tenant 1 apartments (Bldg 1) | 1         |
| 7                 | Tenant 1 standalone house    | 1         |
| 8-10              | Tenant 2 apartments (Bldg 3) | 2         |

### Meter Serial Number Format

- Electricity: `EL-XXXXXX` (where XXXXXX is property ID padded to 6 digits)
- Cold Water: `WC-XXXXXX`
- Hot Water: `WH-XXXXXX`
- Heating: `HT-XXXXXX`

Example: Property ID 1 has meters:
- `EL-000001`
- `WC-000001`
- `WH-000001`
- `HT-000001`

### Provider IDs

| Provider ID | Name              | Service Type |
|-------------|-------------------|--------------|
| 1           | Ignitis           | Electricity  |
| 2           | Vilniaus Vandenys | Water        |
| 3           | Vilniaus Energija | Heating      |

### Tariff IDs (Approximate)

| Tariff ID | Provider          | Type         | Active From |
|-----------|-------------------|--------------|-------------|
| 1         | Ignitis           | time_of_use  | 1 year ago  |
| 2         | Vilniaus Vandenys | flat         | 1 year ago  |
| 3         | Vilniaus Energija | flat         | 1 year ago  |

### Invoice Status Values

- `draft`: Invoice is being prepared, can be modified
- `finalized`: Invoice is locked, cannot be modified
- `paid`: Invoice has been paid by tenant

## Running Automated Tests

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suite

```bash
# Feature tests only
php artisan test --testsuite=Feature

# Unit tests only
php artisan test --testsuite=Unit
```

### Run Specific Test File

```bash
php artisan test tests/Feature/AuthenticationTest.php
php artisan test tests/Feature/MultiTenancyTest.php
php artisan test tests/Feature/MeterReadingValidationTest.php
```

### Run Tests with Coverage

```bash
php artisan test --coverage
```

### Run Property-Based Tests

Property-based tests run 100+ iterations automatically. Look for tests with comments like:

```php
// Feature: authentication-testing, Property 1: Test user tenant assignment
```

These tests generate random inputs and verify properties hold across all inputs.

## Best Practices

### For Manual Testing

1. **Always start fresh**: Run `php artisan test:setup --fresh` before each testing session
2. **Test in order**: Follow scenarios in sequence (login → access → action → verify)
3. **Verify isolation**: Always test cross-tenant access prevention
4. **Check audit trails**: Verify that actions create appropriate audit records
5. **Test edge cases**: Try invalid inputs, boundary values, and error conditions

### For API Testing

1. **Save cookies**: Always use `-c cookies.txt` and `-b cookies.txt` for session persistence
2. **Check status codes**: Use `-w "\nHTTP Status: %{http_code}\n"` to verify responses
3. **Use JSON format**: Add `-H "Accept: application/json"` for API endpoints
4. **Test authorization**: Verify that unauthorized requests return 403
5. **Test isolation**: Verify that cross-tenant requests return 404

### For Automated Testing

1. **Run frequently**: Run tests after every code change
2. **Check property tests**: Ensure property-based tests run 100+ iterations
3. **Review failures**: Property test failures often reveal edge cases
4. **Maintain factories**: Keep factories up-to-date with model changes
5. **Document new tests**: Add comments explaining what each test verifies

## Additional Resources

- **Laravel Documentation**: https://laravel.com/docs/11.x
- **Pest PHP Documentation**: https://pestphp.com/docs
- **SQLite Documentation**: https://www.sqlite.org/docs.html
- **Project README**: See `README.md` for setup instructions
- **Spec Documents**: See `.kiro/specs/authentication-testing/` for requirements and design

## Support

If you encounter issues not covered in this guide:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Run tests with verbose output: `php artisan test -v`
3. Use tinker to inspect data: `php artisan tinker`
4. Review the spec documents for expected behavior
5. Check that all migrations have run: `php artisan migrate:status`
