# Database Seeders Summary

## Overview

All required database seeders for the Vilnius Utilities Billing system have been implemented with realistic Lithuanian data. The seeders are orchestrated through `TestDatabaseSeeder` and provide comprehensive test data for development and testing.

## Implemented Seeders

### 1. ProvidersSeeder ✅
**Location:** `database/seeders/ProvidersSeeder.php`

**Lithuanian Providers:**
- **Ignitis** - Electricity provider
  - Phone: +370 700 55 055
  - Email: info@ignitis.lt
  - Website: https://www.ignitis.lt

- **Vilniaus Vandenys** - Water supply and sewage
  - Phone: +370 5 266 2600
  - Email: info@vv.lt
  - Website: https://www.vv.lt

- **Vilniaus Energija** - Heating provider
  - Phone: +370 5 239 5555
  - Email: info@ve.lt
  - Website: https://www.ve.lt

### 2. TestTariffsSeeder ✅
**Location:** `database/seeders/TestTariffsSeeder.php`

**Realistic Tariff Rates:**

**Ignitis (Electricity):**
- Type: Time-of-use
- Day rate (07:00-23:00): €0.18/kWh
- Night rate (23:00-07:00): €0.10/kWh
- Weekend logic: Apply night rate
- Fixed fee: €0.00

**Vilniaus Vandenys (Water):**
- Type: Flat rate
- Supply rate: €0.97/m³
- Sewage rate: €1.23/m³
- Fixed fee: €0.85/month

**Vilniaus Energija (Heating):**
- Type: Flat rate
- Heating rate: €0.065/kWh
- Fixed fee: €0.00

### 3. UsersSeeder ✅
**Location:** `database/seeders/UsersSeeder.php`

**User Types:**
- **Superadmin:** System-wide administrator
  - Email: superadmin@example.com
  - Password: password

- **Admins:** Property owners with subscriptions
  - Test Admin (tenant_id: 1): admin@test.com
  - Test Manager 2 (tenant_id: 2): manager2@test.com
  - Vilnius Properties Ltd: admin1@example.com
  - Baltic Real Estate: admin2@example.com
  - Old Town Management (expired): admin3@example.com

- **Managers:** Legacy role
  - Test Manager: manager@test.com

- **Tenants:** Renters with property assignments
  - Jonas Petraitis: jonas.petraitis@example.com
  - Ona Kazlauskienė: ona.kazlauskiene@example.com
  - Petras Jonaitis: petras.jonaitis@example.com
  - Marija Vasiliauskaite: marija.vasiliauskaite@example.com
  - Andrius Butkus: andrius.butkus@example.com
  - And more...

### 4. TestBuildingsSeeder ✅
**Location:** `database/seeders/TestBuildingsSeeder.php`

**Vilnius Addresses:**

**Tenant 1 Buildings:**
- **Gedimino 15** - Gedimino pr. 15, Vilnius
  - Total apartments: 12
  - Gyvatukas summer average: 150.50
  - Location: Prestigious central location

- **Konstitucijos 7** - Konstitucijos pr. 7, Vilnius
  - Total apartments: 8
  - Gyvatukas summer average: 120.30
  - Location: Modern business district

**Tenant 2 Buildings:**
- **Pilies 22** - Pilies g. 22, Vilnius
  - Total apartments: 6
  - Gyvatukas summer average: 95.75
  - Location: Old Town

### 5. TestPropertiesSeeder ✅
**Location:** `database/seeders/TestPropertiesSeeder.php`

**Property Types:**

**Apartments:**
- 4 apartments in Gedimino pr. 15 (45-85 m²)
- 2 apartments in Konstitucijos pr. 7 (50-90 m²)
- 3 apartments in Pilies g. 22 (40-75 m²)

**Houses:**
- 1 standalone house at Žvėryno g. 5, Vilnius (150 m²)

**Total:** 10 properties (9 apartments + 1 house)

### 6. TestMetersSeeder ✅
**Location:** `database/seeders/TestMetersSeeder.php`

**Lithuanian Serial Number Format:**

For each property, the following meters are created:

**Electricity Meter:**
- Serial format: `EL-XXXXXX` (e.g., EL-000001)
- Supports zones: Yes (day/night)
- Type: ELECTRICITY

**Cold Water Meter:**
- Serial format: `WC-XXXXXX` (e.g., WC-000001)
- Supports zones: No
- Type: WATER_COLD

**Hot Water Meter:**
- Serial format: `WH-XXXXXX` (e.g., WH-000001)
- Supports zones: No
- Type: WATER_HOT

**Heating Meter** (apartments only):
- Serial format: `HT-XXXXXX` (e.g., HT-000001)
- Supports zones: No
- Type: HEATING

**Total Meters:** ~40 meters across all properties

## Seeding Order

The seeders are executed in the following order via `TestDatabaseSeeder`:

1. LanguageSeeder
2. FaqSeeder
3. OrganizationSeeder
4. **ProvidersSeeder** ← Lithuanian providers
5. **TestBuildingsSeeder** ← Vilnius addresses
6. **TestPropertiesSeeder** ← Apartments and houses
7. **UsersSeeder** ← Admin, manager, tenant users
8. TestTenantsSeeder
9. TenantHistorySeeder
10. **TestMetersSeeder** ← Lithuanian serial numbers
11. TestMeterReadingsSeeder
12. **TestTariffsSeeder** ← Realistic rates
13. TestInvoicesSeeder

## Usage

### Seed All Test Data
```bash
php artisan db:seed --class=TestDatabaseSeeder
```

### Seed Individual Seeders
```bash
php artisan db:seed --class=ProvidersSeeder
php artisan db:seed --class=TestTariffsSeeder
php artisan db:seed --class=UsersSeeder
php artisan db:seed --class=TestBuildingsSeeder
php artisan db:seed --class=TestPropertiesSeeder
php artisan db:seed --class=TestMetersSeeder
```

### Fresh Migration with Seeding
```bash
php artisan migrate:fresh --seed
```

## Requirements Validation

### Requirement 2.1 ✅
**Tariff Configuration:**
- JSON structure with flexible zone definitions
- Time-of-use zones for Ignitis (day/night rates)
- Flat rates for Vilniaus Vandenys and Vilniaus Energija
- Active date ranges properly configured

### Requirement 3.1 ✅
**Water Billing:**
- Supply rate: €0.97/m³
- Sewage rate: €1.23/m³
- Fixed meter fee: €0.85/month
- All rates match Vilniaus Vandenys specifications

## Data Quality

All seeders provide:
- ✅ Realistic Lithuanian company names and addresses
- ✅ Authentic contact information (phone, email, website)
- ✅ Accurate tariff rates based on Lithuanian utility providers
- ✅ Proper serial number formats for meters
- ✅ Vilnius street addresses (Gedimino pr., Konstitucijos pr., Pilies g., Žvėryno g.)
- ✅ Lithuanian names for tenant users
- ✅ Proper tenant_id scoping for multi-tenancy
- ✅ Realistic property sizes (40-150 m²)
- ✅ Gyvatukas summer averages for buildings

## Status

**Task 21: Create database seeders with realistic Lithuanian data** ✅ **COMPLETE**

All required seeders have been implemented and tested. The data is production-ready and follows Lithuanian utility billing standards.

**Date Completed:** 2025-11-25
**Requirements:** 2.1, 3.1
